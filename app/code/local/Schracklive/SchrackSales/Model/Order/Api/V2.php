<?php

class Schracklive_SchrackSales_Model_Order_Api_V2 extends Mage_Sales_Model_Order_Api_V2 {
    
    const LA_1 = 1;
    const LA_2 = 2;
    const LA_3 = 3;
    const LA_4 = 4;
    const LA_5 = 5;
    const LA_6 = 6;
    const LA_7 = 7;
    const LA_8 = 8;
    const LA_9 = 9;
    const DEL  = 99;
    
    static $string2intLA = array(
      'LA1' => self::LA_1,
      'LA2' => self::LA_2,
      'LA3' => self::LA_3,
      'LA4' => self::LA_4,
      'LA5' => self::LA_5,
      'LA6' => self::LA_6,
      'LA7' => self::LA_7,
      'LA8' => self::LA_8,
      'LA9' => self::LA_9,
      'DEL' => self::DEL,
    );

    private $_storeId;
    private $_convertor;
    private $_xrow;
    private $_country;
    private $_shippingMethod;
    private $_paymentMethod;
    private $_sku2productMap = array();
    private $_originTimestamp;
    
    function __construct() {
       parent::__construct();
       $this->_storeId = Mage::app()->getStore('default')->getId();
       $this->_convertor = Mage::getModel('sales/convert_order');
       $this->_xrow = 0;
       $this->_country = strtoupper(Mage::getStoreConfig('schrack/general/country'));
       $this->_shippingMethod = Mage::getStoreConfig('schrack/general/auto_shipping_method');
       $this->_paymentMethod = Mage::helper('schrackpayment/method')->getDefaultPaymentMethod();
   }

    public function schrackGetOrderStatus ( $getOrderStatusData ) {
        $handler = new Schracklive_SchrackSales_Model_Order_Api_OrderStatusHandler();
        return $handler->handleGetOrderStatus($getOrderStatusData);
    }

    public function schrackSetOrderTransmitted ( $setOrderTransmitted ) {
        $handler = new Schracklive_SchrackSales_Model_Order_Api_OrderStatusHandler();
        return $handler->handleSetOrderTransmitted($setOrderTransmitted);
    }

    public function schrackGetOrderStatusViaJson ( $getOrderStatusData ) {
        $handler = new Schracklive_SchrackSales_Model_Order_Api_OrderStatusHandler();
        return $handler->handleGetOrderStatusViaJson($getOrderStatusData);
    }

    public function schrackSetOrderTransmittedViaCsv ( $setOrderTransmitted ) {
        $handler = new Schracklive_SchrackSales_Model_Order_Api_OrderStatusHandler();
        return $handler->handleSetOrderTransmittedViaCsv($setOrderTransmitted);
    }

    public function schrackInsertUpdate ( $ctry_code, $sender_id, $orderData, $itemData ) {
        Schracklive_Wws_Model_Action_Cache::suppressWwsCalls(true);
        $headers = getallheaders();
        $this->_originTimestamp = $headers['Origin_Timestamp'];

        $res['exit_code'] = 1;
        $res['exit_msg'] = "";
        $res['data_result'] = array();
        if ( isset($headers['override_result']) ) {
            $res = $this->_overrideResult($headers,$res);
            return $res;
        }
        register_shutdown_function( "fatal_handler" );

        try { 
            $groupedItemData = array();
            if ( is_array($itemData) ) {
                foreach ( $itemData as $ndx => $record ) {
                    if ( $record instanceof SoapVar )
                        $record = $record->enc_value;
                    $orderNum = $record->OrderNumber;
                    if ( !$orderNum )
                        $this->_fault(198, 'No order number given.');
                    $record->Sku = strtoupper($record->Sku);
                    if ( $record->IsDirectMaterial ) {
                        $record->Sku = sprintf('%s#%03d', $record->Sku, intval($record->Position));
                    }
                    $record->key = Schracklive_SchrackSales_Model_Order_Api_DocumentHandler::mkSoapItemKey($record);
                    if ( isset($groupedItemData[$orderNum][$record->key]) ) {
                        if ( !is_array($groupedItemData[$orderNum][$record->key]) ) {
                            $groupedItemData[$orderNum][$record->key] = array($groupedItemData[$orderNum][$record->key]);
                        }
                        $groupedItemData[$orderNum][$record->key][] = $record;
                    } else {
                        $groupedItemData[$orderNum][$record->key] = $record;
                    }
                }
            }

            $orderNum = -1;
            if ( is_array($orderData) ) {
                foreach ( $orderData as $ndx => $record ) {
                    if ( $record instanceof SoapVar )
                        $record = $record->enc_value;
                    $orderNum = $record->OrderNumber;
                    if ( !$orderNum )
                        $this->_fault(198, 'No order number given.');
                    $lineRes = $this->_handleOrder($record, $groupedItemData[$orderNum]);
                    $res['data_result'][] = $lineRes;
                }
            }
        } catch ( Zend_Db_Statement_Exception $zendDbEx ) {
            $this->_dieToForceLaterRetry($zendDbEx);
        } catch ( PDOException $pdoEx ) {
            $this->_dieToForceLaterRetry($pdoEx);
        } catch ( Mage_Api_Exception $apiEx ) {
			Mage::logException($apiEx);
            $res['exit_code'] = $apiEx->getMessage();
            $res['exit_msg']  = $apiEx->getCustomMessage();
        } catch ( Exception $ex ) {
			Mage::logException($ex);
            $res['exit_code'] = $ex->getCode();
            $res['exit_msg']  = $ex->getMessage();
        }
        return $res;
    }

    private function _handleOrder ( $soapOrder, $soapItems ) {
        $res = new stdClass();
        $res->xrow = ++$this->_xrow;
        $res->xstatus = 998;
        $res->xerror = '(undefined error)';

        $msgKey = "wwsord=" . $soapOrder->OrderNumber;

        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->beginTransaction();
        
        $isNew = false;
        $hasInvalidPositionNumbers = false;
        
        try { 
            if ( isset($soapOrder->Shop) && strtoupper($soapOrder->Shop) !== $this->_country ) {
                $this->_fault(101,"Wrong shop for country '$soapOrder->Shop'");
            }
            if ( $this->_originTimestamp ) {
                $isLast = Mage::helper("schrack/mq")->isLatestUpdate($msgKey,$this->_originTimestamp);
                if ( ! $isLast ) {
                    $res->xstatus = 1;
                    $res->xerror  = 'Message was outdated.';
                    $this->_fault(10,'Message was outdated.'); // ### Workaround to have a filter for those
                    return $res;
                }
            }
            $intLA = $this->_string2intLA($soapOrder->WWSStatus);
            if ( $intLA === self::DEL ) {
                $magentoOrder = $this->_getExistingOrder($soapOrder->OrderNumber);
                if ( isset($magentoOrder) ) {
                    $this->_deleteOrder($magentoOrder);
                }
                // else we ignore that deletion
            } else if ( $intLA === self::LA_2 && ! $this->_checkLA2($soapOrder,$soapItems) ) {
                // just ignore such messages
            }
            else {
                if ( count($soapItems) < 1 ) {
                    $this->_fault(502,'Empty order not supported');
                }
                $magentoOrder = $this->_getExistingOrder($soapOrder->OriginalOrderNumber);
                if ( ! isset($magentoOrder) ) {
                    if ( $soapOrder->OriginalOrderNumber !== $soapOrder->OrderNumber ) {
                        $this->_fault(503,'Followup-Order without known original order');
                    }
                }
                if ( ! isset($magentoOrder) ) {
                    $magentoOrder = $this->_insertNewOrder($soapOrder,$soapItems);
                    $isNew = true;
                }
                else {
                    $hasInvalidPositionNumbers = $this->hasInvalidPositionNumbers($magentoOrder);
                    if ( $hasInvalidPositionNumbers ) { // wtf don't know how they can go lost...
                        $hasInvalidPositionNumbers = ! $this->tryFixPositionNumbers($magentoOrder,$soapItems);
                        if ( $hasInvalidPositionNumbers ) {
                            $soapItems = $this->reindexSoapItems($soapItems);
                        }
                    }
                    if ( $this->_hasDifferentPositions($soapOrder,$soapItems,$magentoOrder) ) {
                        if ( $soapOrder->OriginalOrderNumber == $soapOrder->OrderNumber && !$this->_hasDocuments($magentoOrder) ) {
                            $this->_deleteOrder($magentoOrder);
                            $magentoOrder = $this->_insertNewOrder($soapOrder, $soapItems);
                            $isNew = true;
                        } else {
                            $magentoOrder->setSchrackIsComplete();
                        }
                    }
                }
                $isFollowupOder = ($soapOrder->OriginalOrderNumber !== $soapOrder->OrderNumber);
                switch ( $intLA ) {
                    case self::LA_1 : // Angebot
                        $this->_handleBasicAndLa1($soapOrder, $soapItems, $magentoOrder,$isNew,$isFollowupOder);
                        break;
                    case self::LA_2 : // Auftrag
                        $this->_handleLa2($soapOrder, $soapItems, $magentoOrder,$isNew,$isFollowupOder);
                        break;
                    case self::LA_3 : // Kommissionsschein
                        $this->_handleLa3($soapOrder, $soapItems, $magentoOrder,$isNew,$isFollowupOder);
                        break;
                    case self::LA_4 : // Lieferschein
                        $this->_handleLa4($soapOrder, $soapItems, $magentoOrder,false,$isNew,$isFollowupOder);
                        break;
                    case self::LA_5 : // Rechnung
                        $this->_handleLa5($soapOrder, $soapItems, $magentoOrder,$isNew,$isFollowupOder);
                        break;
                    case self::LA_8 : // Gutschrift
                        $this->_handleLa8($soapOrder, $soapItems, $magentoOrder,$isNew,$isFollowupOder);
                        break;
                    
                    // the unsupported ones:
                    case self::LA_6 : // Komm retour (should not happen)
                        $this->_fault(501,'Unsupported status ' . $soapOrder->WWSStatus);
                        break;
                    case self::LA_7 : // Retourschein (should not happen)
                        $this->_fault(501,'Unsupported status ' . $soapOrder->WWSStatus);
                        break;
                    case self::LA_9 : // ???
                        $this->_fault(501,'Unsupported status ' . $soapOrder->WWSStatus);
                        break;
                    
                    default : // SNH
                        $this->_fault(500,'Invalid status ' . $soapOrder->WWSStatus);
                        break;
                }
                $magentoOrder->setSchrackWwsStatus($soapOrder->WWSStatus);
                $magentoOrder->setSchrackWwsCustomerId($soapOrder->CustomerNumber);
                $magentoOrder->setSchrackIsCurrentDownloaded(0);
                $magentoOrder->save();
            }
            if ( $this->_originTimestamp ) {
                Mage::helper("schrack/mq")->saveLatestUpdate($msgKey,$this->_originTimestamp);
            }
            $write->commit();
            $res->xstatus = 1;
            $res->xerror = '';
        } catch ( PDOException $pdoEx ) {
            $write->rollback();
            throw $pdoEx;
        } catch ( Mage_Api_Exception $apiEx ) {
            $write->rollback();
            throw $apiEx;
        } catch ( Exception $ex ) {
            $write->rollback();
			Mage::logException($ex);
            $res->xstatus = 997;
            $res->xerror  = 'Unexpected Error: ' . $ex->getMessage() . " (OrderNumber was $soapOrder->OrderNumber, row number was $res->xrow)";
            $this->_fault($res->xstatus,$res->xerror);
        }
        if ( $isNew ) {
            // check if really created:
            $magentoOrder = $this->_getExistingOrder($soapOrder->OriginalOrderNumber);
            if ( ! isset($magentoOrder) ) {
                $this->_fault(666,"Order {$soapOrder->OriginalOrderNumber} was not created!");
            }
        }
        return $res;
    }

    private function _insertNewOrder ( $soapOrder, $soapItems ) {
        $customer = $this->_determineCustomer($soapOrder);
        
        $soapItemKeysToDelete = array();
        $isComplete = 1;
        $isOrderable = 1;

        $quote = Mage::getModel('sales/quote')->setStoreId($this->_storeId);
        $quote->assignCustomer($customer);

        $cnt = 0;
        $alreadyDone = array();
        foreach ( $soapItems as $key => $item ) {
            $product = $this->_getProductBySku($item->Sku);
            if ( ! $product ) {
                $product = $this->_createDummyProduct($item);
                $isOrderable = 0;
            }
            $product->addCustomOption('unique_position', '' . ++$cnt);
            $qty = intval($item->Qty);
            if ( $this->_string2intLA($soapOrder->WWSStatus) >= self::LA_3 ) {
                $backorderQty = intval($item->BackorderQty);
                $qty += $backorderQty;
            }
            if ( $qty == 0 ) {
                $qty = 1;
            }
            $buyInfo = array(
                        'qty' => $qty,
            );
            $quote->addProduct($product, new Varien_Object($buyInfo));
            $quote->save();
            if ( isset($alreadyDone[$item->Sku]) ) {
                $quoteItem = end($quote->getAllItems());
                $quoteItem->addOption(array(
                    "code" => "random",
                    "value" => serialize(array(time(), $cnt))
                ));
                $quoteItem->save();
            } else {
                $alreadyDone[$item->Sku] = true;
            }
        }
        
        foreach ( $soapItemKeysToDelete as $key ) {
            unset($soapItems[$key]);
        }
        
        $shippingAddressData = $this->_getShippingAddressData($soapOrder);
        if ( ! $shippingAddressData ) {
            $this->_fault(520,"No or invalid shipping address found");
        }
        $shippingAddress = $quote->getShippingAddress()->addData($shippingAddressData);
        $billingAaddressData = $this->_getBillingAddressData($soapOrder);
        if ( ! $billingAaddressData ) {
            // use shipping addr as invoice adr.
            $billingAaddressData = $this->_getShippingAddressData($soapOrder);
        }
        $billingAddress = $quote->getBillingAddress()->addData($billingAaddressData);
        
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
        $shippingAddress->setShippingMethod($this->_shippingMethod); 
        $shippingAddress->setPaymentMethod($this->_paymentMethod); 
        $quote->getPayment()->importData(array('method' => $this->_paymentMethod));
        $quote->setIsActive(false);
        $quote->collectTotals()->save();

        $service = Mage::getModel('sales/service_quote', $quote);
        $service->submitAll();
        $createdOrder = $service->getOrder();
        if ( ! $createdOrder ) {
            $this->_fault(530,'Internal error: order could not be created');
        }
        
        $createdOrder->setSchrackWwsOrderNumber($soapOrder->OriginalOrderNumber);
        $createdOrder->setSchrackWwsCustomerId($soapOrder->CustomerNumber);
        $createdOrder->setSchrackWwsStatus($soapOrder->WWSStatus);
        $createdOrder->setSchrackShipmentMode($soapOrder->ShipmentMode);
        $createdOrder->setSchrackIsComplete($isComplete);
        $createdOrder->setSchrackIsOrderable($isOrderable);
        if ( $soapOrder->OrderDate ) {
            $createdOrder->setSchrackWwsCreationDate($soapOrder->OrderDate);
        }
        
        $itemsCollection = $createdOrder->getItemsCollection();
        $itemsCnt = $itemsCollection->count();
        if ( $itemsCnt != count($soapItems) ) {
            $createdSKUs = null;
            foreach ( $itemsCollection as $magentoItem ) {
                if ( ! $createdSKUs ) $createdSKUs = ''; else $createdSKUs.=',';
                $createdSKUs .= $magentoItem->getSku();
            }
            $requestSKUs = null; 
            foreach ( $soapItems as $soapItem ) {
                if ( ! $requestSKUs ) $requestSKUs = ''; else $requestSKUs.=',';
                $requestSKUs .= $soapItem->Sku;
            }

            $this->_fault(531,'Internal error: created item count mismatch. Created: ' . $createdSKUs . '; should be ' . $requestSKUs); 
        }
        $magentoItemsArray = array();
        foreach ( $itemsCollection as $magentoItem ) {
            $magentoItemsArray[] = $magentoItem;
        }
        $i = 0;
        foreach ( $soapItems as $key => $soapItem ) {
            $magentoItem = $magentoItemsArray[$i];
            if ( ! $magentoItem ) { // magento didn't create one or more items...
                $isComplete = 0;
                break;
            }
            if ( $soapItem->Sku !== $magentoItem->getSku() ) {
                $this->_fault(532,'Internal error: sku mismatch ' . $soapItem->Sku.'/'.$magentoItem->getSku());
            }
            $magentoItem->setSchrackPosition($soapItem->Position);
            if ( $soapItems->Qty == 0 ) {
                $magentoItem->setQty(0);
            }
            $magentoItem->save();
            ++$i;
        }
        
        return $createdOrder;
    }
    
    private function _deleteOrder ( Mage_Sales_Model_Order $magentoOrder ) {
        $orderId = $magentoOrder->getEntityId();
        $indexModel = Mage::getModel('schracksales/order_index');
        $indexCollection = $indexModel->getCollection();
        $indexCollection->addFieldToFilter('order_id',$orderId);
        $indexCollection->getSelect();
        $positionModel = Mage::getModel('schracksales/order_index_position');
        foreach ( $indexCollection as $index ) {
            $indexId = $index->getEntityId();
            $positionCollection = $positionModel->getCollection();
            $positionCollection->addFieldToFilter('parent_id',$indexId);
            $positionCollection->getSelect();
            foreach ( $positionCollection as $position ) {
                $position->delete();
            }
            $index->delete();
        }
        $magentoOrder->delete();
    }

    private function _handleBasicAndLa1( $soapOrder, $soapItems, Mage_Sales_Model_Order $magentoOrder, $isNew, $isFollowupOder ) {
        $magentoOrder->setStatus('schrack_offered');
        
        // handle order data
        $magentoOrder->setSchrackWwsReference($soapOrder->Reference);
        $magentoOrder->setSchrackCustomerProjectInfo($soapOrder->CustomerProjectInfo);
        $magentoOrder->setSchrackCustomerDeliveryInfo($soapOrder->CustomerDeliveryInfo);
        $magentoOrder->setSchrackWwsOfferNumber($soapOrder->QuoteNumber);
        if ( isset($soapOrder->OrderDate) && ! $isFollowupOder ) {
            $magentoOrder->setSchrackWwsCreationDate($soapOrder->OrderDate);
        }
        if ( isset($soapOrder->OperatorMail) ) {
            $magentoOrder->setSchrackWwsOperatorMail($soapOrder->OperatorMail);
        }
        $magentoOrder->setData('schrack_sp_reference_1',$soapOrder->SolutionProviderReference1);
        $magentoOrder->setData('schrack_sp_reference_2',$soapOrder->SolutionProviderReference2);
        $magentoOrder->setData('schrack_sp_reference_3',$soapOrder->SolutionProviderReference3);
        $magentoOrder->setData('schrack_sp_reference_4',$soapOrder->SolutionProviderReference4);
        $magentoOrder->setData('schrack_sp_reference_5',$soapOrder->SolutionProviderReference5);

        $magentoOrder->setSchrackPaymentTerms($soapOrder->PaymentTerms);
        $magentoOrder->setSchrackShipmentMode($soapOrder->ShipmentMode);
        
        $magentoOrder->setBaseGrandTotal($soapOrder->AmountTot);
        $magentoOrder->setGrandTotal($soapOrder->AmountTot);
        $magentoOrder->setBaseSubtotalInclTax($soapOrder->AmountTot);
        $magentoOrder->setSubtotalInclTax($soapOrder->AmountTot);
        
        $magentoOrder->setBaseSubtotal($soapOrder->AmountNet);
        $magentoOrder->setSubtotal($soapOrder->AmountNet);
        
        $magentoOrder->setBaseTaxAmount($soapOrder->AmountVat);
        $magentoOrder->setTaxAmount($soapOrder->AmountVat);
        
        $magentoOrder->setBaseTotalPaid($soapOrder->AmountVat);
        $magentoOrder->setTotalPaid($soapOrder->AmountVat);

        $magentoOrder->setBaseTotalPaid($soapOrder->AmountPaid);
        $magentoOrder->setTotalPaid($soapOrder->AmountPaid);
        
        $magentoOrder->setBaseTotalDue($soapOrder->AmountOpen);
        $magentoOrder->setTotalDue($soapOrder->AmountOpen);
        
        $magentoOrder->setBaseShippingAmount($soapOrder->ShippingAmount);
        $magentoOrder->setShippingAmount($soapOrder->ShippingAmount);
        $magentoOrder->setBaseShippingInclTax($soapOrder->ShippingAmount);
        $magentoOrder->setShippingInclTax($soapOrder->ShippingAmount);
        
        $magentoOrder->setWeight($soapOrder->WeightTot);

        $magentoOrder->setSchrackWwsOfferValidThru($soapOrder->OfferValidThru);
        $magentoOrder->setSchrackWwsOfferFlagValid($soapOrder->OfferFlagValid);
        $magentoOrder->setSchrackWwsWebSendNo($soapOrder->WebSendNr);
        
        // update Addresses
        $billingAddressData = $this->_getBillingAddressData($soapOrder);
        $shippingAddressData = $this->_getShippingAddressData($soapOrder);
        $addressCollection = $magentoOrder->getAddressesCollection();
        foreach ( $addressCollection as $address ) {
            $type = $address->getAddressType();
            switch ( $type ) {
                case 'billing' : 
                    if ( $billingAddressData ) {
                        $address->addData($billingAddressData);
                        $address->save();
                    }
                    break;
                case 'shipping' :
                    if ( $shippingAddressData ) {
                        $address->addData($shippingAddressData);
                        $address->save();
                    }
                    break;
            }
        }
        
        // update index
        $indexParentId = $this->_insertUpdateOrderIndex($magentoOrder->getSchrackWwsCustomerId(), 
                                                        $magentoOrder->getSchrackWwsOrderNumber(), 
                                                        $magentoOrder->getEntityId(),
                                                        $magentoOrder->getSchrackWwsCreationDate(),
                                                        null,null,null,false,false,false);
        
        // handle item data
        $this->_updateItemCollection($this->_string2intLA($soapOrder->WWSStatus),$magentoOrder,$soapItems,$indexParentId,$isNew,$isFollowupOder);
        
        $magentoOrder->setSchrackWwsOfferDate($soapOrder->QuoteDate);
        if ( $soapOrder->QuoteNumber ) {
            $indexParentId = $this->_insertUpdateOrderIndex($magentoOrder->getSchrackWwsCustomerId(), 
                                                            $soapOrder->QuoteNumber, 
                                                            $magentoOrder->getEntityId(),
                                                            $soapOrder->QuoteDate,
                                                            null,null,null,true,false,false);
        }
        
    }

    private function _handleLa2($soapOrder, $soapItems, Mage_Sales_Model_Order $magentoOrder, $isNew, $isFollowupOder) {
        $this->_handleBasicAndLa1($soapOrder,$soapItems,$magentoOrder,$isNew,$isFollowupOder);
        $magentoOrder->setStatus('pending');
        $this->_insertUpdateOrderIndex($magentoOrder->getSchrackWwsCustomerId(), 
                                       $magentoOrder->getSchrackWwsOrderNumber(), 
                                       $magentoOrder->getEntityId(),
                                       $magentoOrder->getSchrackWwsCreationDate(),
                                       null,null,null,false,true,false);
    }
    
    private function _handleLa3($soapOrder, $soapItems, Mage_Sales_Model_Order $magentoOrder, $isNew, $isFollowupOder) {
        $this->_handleLa2($soapOrder,$soapItems,$magentoOrder,$isNew,$isFollowupOder);
        $magentoOrder->setStatus('processing');
        $this->_insertUpdateOrderIndex($magentoOrder->getSchrackWwsCustomerId(), 
                                       $magentoOrder->getSchrackWwsOrderNumber(), 
                                       $magentoOrder->getEntityId(),
                                       $magentoOrder->getSchrackWwsCreationDate(),
                                       null,null,null,false,false,true);
    }
    
    private function _handleLa4($soapOrder, $soapItems, Mage_Sales_Model_Order $magentoOrder, $skipIfNoShipmentNo, $isNew, $isFollowupOder ) {
        if ( ! $isFollowupOder ) {
            $this->_handleLa3($soapOrder,$soapItems,$magentoOrder,$isNew,false);
        }
        else {
            $this->_updateItemCollection($this->_string2intLA($soapOrder->WWSStatus),$magentoOrder,$soapItems,null,$isNew,true);
        }
        if ( ! $soapOrder->ShipmentNumber || strlen($soapOrder->ShipmentNumber) < 1 ) {
            if ( $skipIfNoShipmentNo ) {
                return; 
            }
            else {
                $this->_fault(511,"Missing ShipmentNumber");
            }
        }
        $magentoOrder->setStatus('processing');
        $handler = new Schracklive_SchrackSales_Model_Order_Api_ShipmentHandler($soapOrder,$soapItems,$magentoOrder,$this->_convertor);
        $shipment = $handler->find();
        if ( ! $shipment ) {
            $shipment = $handler->create();
            if ( $shipment ) {
                $this->_insertUpdateOrderIndex($magentoOrder->getSchrackWwsCustomerId(),
                                               $shipment->getSchrackWwsShipmentNumber(),
                                               $magentoOrder->getEntityId(),
                                               $soapOrder->ShipmentDate,
                                               $shipment->getEntityId(), null, null, false, false, false, $soapOrder->OrderNumber);
            }
        }
        else {
            $handler->update();
        }
    }
    
    private function _handleLa5($soapOrder, $soapItems, Mage_Sales_Model_Order $magentoOrder, $isNew, $isFollowupOder) {
        if ( ! $soapOrder->InvoiceNumber || strlen($soapOrder->InvoiceNumber) < 1 ) {
            $this->_fault(512,"Missing InvoiceNumber");
        }
        $this->_handleLa4($soapOrder,$soapItems,$magentoOrder,true,$isNew,$isFollowupOder);
        $magentoOrder->setStatus('complete');
        $handler = new Schracklive_SchrackSales_Model_Order_Api_InvoiceHandler($soapOrder,$soapItems,$magentoOrder,$this->_convertor);
        $invoice = $handler->find();
        if ( ! $invoice ) {
            $invoice = $handler->create();
            if ( $invoice ) {
                $this->_insertUpdateOrderIndex($magentoOrder->getSchrackWwsCustomerId(),
                                               $invoice->getSchrackWwsInvoiceNumber(),
                                               $magentoOrder->getEntityId(),
                                               $soapOrder->InvoiceDate,
                                               null, $invoice->getEntityId(), null, false, false, false, $soapOrder->OrderNumber);
            }
        }
        else {
            $handler->update();
        }
    }
    
    private function _handleLa8($soapOrder, $soapItems, Mage_Sales_Model_Order $magentoOrder, $isNew,$isFollowupOder) {
        if ( ! $soapOrder->InvoiceNumber || strlen($soapOrder->InvoiceNumber) < 1 ) {
            $this->_fault(512,"Missing InvoiceNumber");
        }
        /*
        if ( $soapOrder->IsCollectiveInvoice ) {
            $this->_fault(513,"Collective Invoices are currently not supported!");
        }
         */
        $this->_handleBasicAndLa1($soapOrder,$soapItems,$magentoOrder, $isNew,$isFollowupOder);
        $magentoOrder->setStatus('complete');
        $handler = new Schracklive_SchrackSales_Model_Order_Api_CreditMemoHandler($soapOrder,$soapItems,$magentoOrder,$this->_convertor);
        $creditMemo = $handler->find();
        if ( ! $creditMemo ) {
            $creditMemo = $handler->create();
            if ( $creditMemo ) {
                $this->_insertUpdateOrderIndex($magentoOrder->getSchrackWwsCustomerId(),
                                               $creditMemo->getSchrackWwsCreditmemoNumber(),
                                               $magentoOrder->getEntityId(),
                                               $soapOrder->InvoiceDate,
                                               null, null, $creditMemo->getEntityId(), false, false, false, $soapOrder->OrderNumber);
            } else {
                $this->_fault(701,"Could not create creditmemo $soapOrder->InvoiceNumber");
            }
        }
        else {
            $handler->update();
        }
    }

    private function _updateItemCollection ( $status, $magentoOrder, $requestItems, $indexParentId, $isNew, $isFollowupOrder ) {
        $descriptionChanged = false;
        $magentoItems = $this->_getKeyToItemMap($magentoOrder->getItemsCollection());
        
        foreach ( $requestItems as $soapItem ) {
            $product = $this->_getProductBySku($soapItem->Sku);
            $key = $soapItem->key;
            $magentoItem = $magentoItems[$key];
            if ( ! isset($magentoItem) ) {
                // create new Item: not yet, needs a quote_item_id
                // $existingItem = Mage::getModel('sales/order_item');
                //$existingItem->setOrderId($magentoOrder->getEntityId());
                // ...
                $magentoOrder->setSchrackIsComplete(0);
            }
            else { // item exists: update
                $magentoItem->setSchrackPosition($soapItem->Position);
//                $existingItem->setItemId($soapItem->???);
//                $existingItem->setOrderId($requestItems->???);
//                $existingItem->setParentItemId($soapItem->???);
//                $existingItem->setQuoteItemId($soapItem->???);
//                $existingItem->setStoreId($soapItem->???);
//                $existingItem->setCreatedAt($soapItem->???);
//                $existingItem->setUpdatedAt($soapItem->???);
//                $existingItem->setProductId($soapItem->???);
//                $existingItem->setProductType($soapItem->???);
//                $existingItem->setProductOptions($soapItem->???);
                $magentoItem->setWeight($soapItem->ProductWeight);
//                $existingItem->setIsVirtual($soapItem->???);
//                $existingItem->setSku($soapItem->???);
                if ( $magentoItem->getName() !== $soapItem->Description ) {
                    $descriptionChanged = true;
                    $magentoItem->setName($soapItem->Description);
                }
//                $existingItem->setAppliedRuleIds($soapItem->???);
//                $existingItem->setAdditionalData($soapItem->???);
//                $existingItem->setFreeShipping($soapItem->???);
                $magentoItem->setIsQtyDecimal(true);
//                $existingItem->setNoDiscount($soapItem->???);
                
                $magentoQty         = $soapItem->Qty;
                $magentoBackordered = $magentoItem->getQtyBackordered() == null || $soapItem->BackorderQty < $magentoItem->getQtyBackordered()
                                    ? $soapItem->BackorderQty
                                    : $magentoItem->getQtyBackordered(); // smaller value is always the correct one - in all cases below
                $amountVat          = $soapItem->AmountVat;
                $amountSurcharge    = $soapItem->AmountSurcharge;
                $amountNet          = $soapItem->AmountNet;
                $amountTot          = $soapItem->AmountTot;
                if ( is_object($product) ) {
                    $priceunit = $product->getSchrackPriceunit();
                } else {
                    $priceunit = 1;
                }

                if ( ! $isFollowupOrder ) {
                    if ( $status <= self::LA_2 || $soapItem->BackorderQty == 0 ) { // update all amounts
                        // all fine as set above
                    } else {
                        $storedStatus = $this->_string2intLA($magentoOrder->getSchrackWwsStatus());
                        $qtyCalculated = $status <= self::LA_2
                                       ? $soapItem->Qty
                                       : $soapItem->Qty + $soapItem->BackorderQty;
                        if (    $status > self::LA_2 && $storedStatus >= self::LA_2
                             && $magentoItem->getQtyOrdered() == $qtyCalculated
                             && ($magentoItem->getRowTotal() > 0 || $soapItem->Price == 0.0) ) { // item is already correct stored, keep as it is
                            $magentoQty      = $magentoItem->getQtyOrdered();
                            $amountVat       = $magentoItem->getTaxAmount();
                            $amountSurcharge = $magentoItem->getSchrackSurcharge();
                            $amountNet       = $magentoItem->getRowTotal();
                            $amountTot       = $magentoItem->getRowTotalInclTax();
                        } else { // calculate something:
                            if ( $soapItem->Qty > 0 ) { // calculate real qty and increase amounts
                                $magentoQty      = $soapItem->Qty + $soapItem->BackorderQty;
                                $amountVat       = ($amountVat / $soapItem->Qty) * $magentoQty;
                                $amountSurcharge = ($amountSurcharge / $soapItem->Qty) * $magentoQty;
                                $amountNet       = ($amountNet / $soapItem->Qty) * $magentoQty;
                                $amountTot       = ($amountTot / $soapItem->Qty) * $magentoQty;
                            } else { // if full backorder, we have no amounts - calculate them from price, get qty from backorder qry
                                $magentoQty      = $soapItem->BackorderQty;
                                $amountSurcharge = ($soapItem->PriceSurcharge * $magentoQty) / $priceunit;
                                $amountNet       = (($soapItem->Price * $magentoQty) / $priceunit) + $amountSurcharge;
                                $amountVat       = ($amountNet / 100.0) * $soapItem->VatPercent;
                                $amountTot       = $amountNet + $amountVat;
                            }
                        }
                    }
                } else { // is followup order
                    if ( $soapItem->Qty == $magentoItem->getQtyOrdered() ) { // first (La2) followup of position full backorder - update all amounts
                        // all fine as set above
                    } else if ( $soapItem->Qty > 0 && $soapItem->Qty + $soapItem->BackorderQty == $magentoItem->getQtyOrdered() ) { // first followup status > LA2, some qty here but more folloup/backorder is pending
                            $magentoQty      = $soapItem->Qty + $soapItem->BackorderQty;
                            $amountVat       = ($amountVat / $soapItem->Qty) * $magentoQty;
                            $amountSurcharge = ($amountSurcharge / $soapItem->Qty) * $magentoQty;
                            $amountNet       = ($amountNet / $soapItem->Qty) * $magentoQty;
                            $amountTot       = ($amountTot / $soapItem->Qty) * $magentoQty;
                    } else { // leave qty and all amounts as they are, just as always update backorder qty
                        $magentoQty         = $magentoItem->getQtyOrdered();
                        $amountVat          = $magentoItem->getTaxAmount();
                        $amountSurcharge    = $magentoItem->getSchrackSurcharge();
                        $amountNet          = $magentoItem->getRowTotal();
                        $amountTot          = $magentoItem->getRowTotalInclTax();
                    }
                }

                $magentoItem->setQtyOrdered($magentoQty);
                $magentoItem->setQtyBackordered($magentoBackordered);
                
                if (    $status == self::LA_5
                     || ($status > self::LA_5 && $magentoItem->getQtyInvoiced() <= 0) ) {
                    $magentoItem->setQtyInvoiced($soapItem->Qty);
                }
                if ( $status == self::LA_8 ) {
                    $magentoItem->setQtyRefunded($soapItem->Qty);
                }
//                $existingItem->setQtyCanceled($soapItem->???);
//                $existingItem->setBaseCost($soapItem->???);
                $magentoItem->setPrice($soapItem->Price);
                $magentoItem->setBasePrice($soapItem->Price);
                $magentoItem->setOriginalPrice($soapItem->Price);
                $magentoItem->setBaseOriginalPrice($soapItem->Price);
                $magentoItem->setTaxPercent($soapItem->VatPercent);
                if ( $status < self::LA_5 || ! $magentoItem->getTaxAmount() ) {
                    $magentoItem->setTaxAmount($amountVat);
                    $magentoItem->setBaseTaxAmount($amountVat);
                }
                if (    $status == self::LA_5 
                     || ($status > self::LA_5 && ! $magentoItem->gettTaxInvoiced()) ) {
                    $magentoItem->setTaxInvoiced($amountVat);
                    $magentoItem->setBaseTaxInvoiced($amountVat);
                }
                if ( $status == self::LA_8 ) {
                    $magentoItem->setTaxRefunded($amountVat);
                }
//                $existingItem->setDiscountPercent($soapItem->???);
//                $existingItem->setDiscountAmount($soapItem->???);
//                $existingItem->setBaseDiscountAmount($soapItem->???);
//                $existingItem->setDiscountInvoiced($soapItem->???);
//                $existingItem->setBaseDiscountInvoiced($soapItem->???);
                
                $magentoItem->setRowTotal($amountNet);
                $magentoItem->setBaseRowTotal($amountNet);
                if (    $status == self::LA_5 
                     || ($status > self::LA_5 && ! $magentoItem->getRowInvoiced()) ) {
                    $magentoItem->setRowInvoiced($amountNet);
                    $magentoItem->setBaseRowInvoiced($amountNet);
                }
//                $existingItem->setRowWeight($soapItem->???);
//                $existingItem->setGiftMessageId($soapItem->???);
//                $existingItem->setGiftMessageAvailable($soapItem->???);
//                $existingItem->setBaseTaxBeforeDiscount($soapItem->???);
//                $existingItem->setTaxBeforeDiscount($soapItem->???);
//                $existingItem->setExtOrderItemId($soapItem->???);
//                $existingItem->setLockedDoInvoice($soapItem->???);
//                $existingItem->setLockedDoShip($soapItem->???);
//                $existingItem->setPriceInclTax($soapItem->???);
//                $existingItem->setBasePriceInclTax($soapItem->???);
                if ( $status < self::LA_8 || ! $magentoItem->getRowTotalInclTax() ) {
                    $magentoItem->setRowTotalInclTax($amountTot);
                    $magentoItem->setBaseRowTotalInclTax($amountTot);
                }
                if ( $status == self::LA_8 ) {
                    $magentoItem->setAmountRefunded($amountTot);
                    $magentoItem->setBaseAmountRefunded($amountTot);
                }
//                $existingItem->setWeeeTaxApplied($soapItem->???);
//                $existingItem->setWeeeTaxAppliedAmount($soapItem->???);
//                $existingItem->setWeeeTaxAppliedRowAmount($soapItem->???);
//                $existingItem->setBaseWeeeTaxAppliedAmount($soapItem->???);
//                $existingItem->setBaseWeeeTaxAppliedRowAmount($soapItem->???);
//                $existingItem->setWeeeTaxDisposition($soapItem->???);
//                $existingItem->setWeeeTaxRowDisposition($soapItem->???);
//                $existingItem->setBaseWeeeTaxDisposition($soapItem->???);
//                $existingItem->setBaseWeeeTaxRowDisposition($soapItem->???);
                $magentoItem->setSchrackRowTotalExclSurcharge($amountNet - $amountSurcharge);
                $magentoItem->setSchrackSurcharge($soapItem->PriceSurcharge);
                $magentoItem->setSchrackSurchargeDesc($soapItem->SurchargeDesc);
                $magentoItem->setSchrackBackorderQty($soapItem->BackorderQty);
//                $existingItem->setSchrackWwsPlaceMemo($soapItem->???);
//                $existingItem->setSchrackWwsShipMemo($soapItem->???);
                $magentoItem->setSchrackRowTotalSurcharge($amountSurcharge);
//                $magentoItem->setSchrackPosition($soapItem->Position);
//                $existingItem->setSchrackBasicPrice($soapItem->???);
//                $existingItem->setHiddenTaxAmount($soapItem->???);
//                $existingItem->setBaseHiddenTaxAmount($soapItem->???);
//                $existingItem->setHiddenTaxInvoiced($soapItem->???);
//                $existingItem->setBaseHiddenTaxInvoiced($soapItem->???);
//                $existingItem->setHiddenTaxRefunded($soapItem->???);
//                $existingItem->setBaseHiddenTaxRefunded($soapItem->???);
//                $existingItem->setIsNominal($soapItem->???);
//                $existingItem->setTaxCanceled($soapItem->???);
//                $existingItem->setHiddenTaxCanceled($soapItem->???);
                $magentoItem->setSchrackDrumNumber($soapItem->DrumNumber);
                if ( $soapItem->DrumShortDesc ) {
                    $magentoItem->setDescription($soapItem->DrumShortDesc);
                }
                if ( isset($soapItem->SolutionProviderReference1) ) {
                    $magentoItem->setData('schrack_sp_reference_1',$soapItem->SolutionProviderReference1);
                }
                if ( isset($soapItem->SolutionProviderReference2) ) {
                    $magentoItem->setData('schrack_sp_reference_2',$soapItem->SolutionProviderReference2);
                }
                if ( isset($soapItem->SolutionProviderReference3) ) {
                    $magentoItem->setData('schrack_sp_reference_3',$soapItem->SolutionProviderReference3);
                }
                if ( isset($soapItem->SolutionProviderReference4) ) {
                    $magentoItem->setData('schrack_sp_reference_4',$soapItem->SolutionProviderReference4);
                }
                if ( isset($soapItem->SolutionProviderReference5) ) {
                    $magentoItem->setData('schrack_sp_reference_5',$soapItem->SolutionProviderReference4);
                }

                $magentoItem->save();
                $magentoItems[$key] = null;

                // index:
                // if ( $descriptionChanged || $isNew ) { // do it always to ensure positions at least after update
                    if ( ! $indexParentId ) {
                        $indexParent = $this->_findOrderIndex($magentoOrder->getSchrackWwsCustomerId(), 
                                                              $magentoOrder->getSchrackWwsOrderNumber(), 
                                                              $magentoOrder->getEntityId(),
                                                              $magentoOrder->getCreatedAt(),
                                                              null,null,null,false,false,false);
                        if ( ! $indexParent ) {
                            $msg = sprintf("Internal Error: index parent not found for CustomerId '%s', OrderNo '%s', entityId '%d'",
                                           $magentoOrder->getSchrackWwsCustomerId(),$magentoOrder->getSchrackWwsOrderNumber(),$magentoOrder->getEntityId());
                            $this->_fault(601,$msg);
                        }
                        $indexParentId = $indexParent->getEntityId();
                    }
                    $this->_insertUpdateOrderIndexPosition($indexParentId,$soapItem);
                // }
            }
        }
    }
    
    private function _getExistingOrder ( $orderNumber ) {
        $orderModel = Mage::getModel('sales/order');
        $orderCollection = $orderModel->getCollection();
        $orderCollection->addFieldToFilter('schrack_wws_order_number',$orderNumber);
        $orderCollection->getSelect();
        if ( $orderCollection->getSize() > 0 ) {
            return $orderCollection->getFirstItem();
        }
        return null;
    }

    private function _determineCustomer ( $soapOrder ) {
        // select * from customer_entity where schrack_wws_customer_id = '777777' and schrack_wws_contact_number = 16;
        $customerModel = Mage::getModel('customer/customer');
        $customerCollection = $customerModel->getCollection();
        $customerCollection->addFieldToFilter('schrack_wws_customer_id',$soapOrder->CustomerNumber);
        $customerCollection->addFieldToFilter('schrack_wws_contact_number',$soapOrder->ContactNumber);
        $customerCollection->getSelect();
        if ( $customerCollection->getSize() > 0 ) {
            return $customerCollection->getFirstItem();
        }

        // o.k. so far we did not get a customer. we try now to find the first admin user for that wws customer id:
        $adminRoleId = Mage::helper('schrack/acl')->getAdminRoleId();
        
        $customerCollection = $customerModel->getCollection();
        $customerCollection->addAttributeToSelect('schrack_acl_role_id');
        $customerCollection->addFieldToFilter('schrack_wws_customer_id',$soapOrder->CustomerNumber);
        $customerCollection->addAttributeToSort('entity_id', 'ASC');
        $customerCollection->getSelect();
        foreach ( $customerCollection as $customer ) {
            $account = $customer->getAccount();
            if ( $account ) {
                $systemContact = $account->getSystemContact();
                if ( $systemContact ) {
                    return $systemContact;
                }
            }
            if ( $customer->getIsActive() && $customer->getSchrackAclRoleId() == $adminRoleId ) {
                return $customer;
            }
        }
        
        // at ths point we cannot continue, so we create an error result for this order:
        $this->_fault(201,'no appropriate customer for customer# '.$soapOrder->CustomerNumber.' and contact# '.$soapOrder->ContactNumber.' found.');
    }

    private function _getShippingAddressData ( $soapOrder ) {
        $addressData = $this->_getAddressData($soapOrder,'Delivery');
        return $addressData;
    }

    private function _getBillingAddressData ( $soapOrder ) {
        $addressData = $this->_getAddressData($soapOrder,'Invoice');
        return $addressData;
    }
    
    private function _getAddressData ( $soapOrder, $fieldPrefix ) {
        $addressData = array(
                'firstname'  => $this->_getAddressDataField($soapOrder,$fieldPrefix,'AddrName1'),
                'middlename' => $this->_getAddressDataField($soapOrder,$fieldPrefix,'AddrName2'),
                'lastname'   => $this->_getAddressDataField($soapOrder,$fieldPrefix,'AddrName3'),
                'street'     => $this->_getAddressDataField($soapOrder,$fieldPrefix,'AddrStreet'),
                'city'       => $this->_getAddressDataField($soapOrder,$fieldPrefix,'AddrCity'),
                'postcode'   => $this->_getAddressDataField($soapOrder,$fieldPrefix,'AddrZIP'),
                // 'telephone'  => '', 
                'country_id' => $this->_getAddressDataField($soapOrder,$fieldPrefix,'AddrCountry'),
                // 'region_id'  => 12, // id from directory_country_region table
        );
        if (    ($addressData['firstname']  == null || strlen($addressData['firstname'])  < 1)
             && ($addressData['middlename'] == null || strlen($addressData['middlename']) < 1)
             && ($addressData['lastname']   == null || strlen($addressData['lastname'])   < 1) ) {
            return null;
        }
        return $addressData;
    }

    private function _getAddressDataField ( $soapOrder, $fieldPrefix, $field ) {
        $fullField = $fieldPrefix . $field;
        $val = $soapOrder->$fullField;
        if ( $field == 'AddrStreet' && ($val == null || $val < ' ') ) {
            $val = 'NN';
        }
        return $val;
    }

    private function _insertUpdateOrderIndex ( $wwsCustomerId, $wwsDocumentNumber, $orderId, $dateTime,
                                               $shipmentId = null, $invoiceId = null, $creditMemoId = null,
                                               $isOffer = true, $isOrderConfirmation = false, $isProcessing = false,
                                               $wwsFollowupOrderNumber = null ) {
//        if ( is_string($dateTime) ) {
//            $dateTime = new DateTime($dateTime);
//        }
        $item = $this->_findOrderIndex($wwsCustomerId, $wwsDocumentNumber, $orderId, $dateTime,
                                       $shipmentId, $invoiceId, $creditMemoId, 
                                       $isOffer, $isOrderConfirmation, $isProcessing);
        if ( $item ) { // update
            $changed = false;
            if ( $item->getDocumentDateTime() != $dateTime ) {
                $item->setDocumentDateTime($dateTime);
                $changed = true;
            }
            if ( $item->getWwsDocumentNumber() != $wwsDocumentNumber ) {
                $item->setWwsDocumentNumber($wwsDocumentNumber);
                $changed = true;
            }
            if ( $changed ) {
                $item->save();
            }
        }
        else { // insert
            $item = Mage::getModel('schracksales/order_index');
            $item->setWwsCustomerId($wwsCustomerId);
            $item->setWwsDocumentNumber($wwsDocumentNumber);
            $item->setOrderId($orderId);
            $item->setShipmentId($shipmentId);
            $item->setInvoiceId($invoiceId);
            $item->setCreditMemoId($creditMemoId);
            $item->setWwsFollowupOrderNumber($wwsFollowupOrderNumber);
            if ( $isOffer ) {
                $item->setIsOffer(1);
            }
            if ( $isOrderConfirmation ) {
                $item->setIsOrderConfirmation(1);
            }
            if ( $isProcessing ) {
                $item->setIsProcessing(1);
            }
            $item->setDocumentDateTime($dateTime);
            $item = $item->save();
        }
        return $item->getEntityId();
    }

    private function _findOrderIndex ( $wwsCustomerId, $wwsDocumentNumber, $orderId, $dateTime,
                                       $shipmentId = null, $invoiceId = null, $creditMemoId = null, 
                                       $isOffer = false, $isOrderConfirmation = false, $isProcessing = false ) {
        $indexModel = Mage::getModel('schracksales/order_index');
        $indexCollection = $indexModel->getCollection();
        $indexCollection->addFieldToFilter('wws_customer_id',$wwsCustomerId);
        $indexCollection->addFieldToFilter('order_id',$orderId);
        $indexCollection->addFieldToFilter('shipment_id',$shipmentId ? $shipmentId : array('null' => true));
        $indexCollection->addFieldToFilter('invoice_id',$invoiceId ? $invoiceId : array('null' => true));
        $indexCollection->addFieldToFilter('credit_memo_id',$creditMemoId ? $creditMemoId : array('null' => true));
        $indexCollection->addFieldToFilter('is_offer',$isOffer ? 1 : 0);
        $indexCollection->addFieldToFilter('is_order_confirmation',$isOrderConfirmation ? 1 : 0);
        $indexCollection->addFieldToFilter('is_processing',$isProcessing ? 1 : 0 );
        $indexCollection->getSelect();
        $cnt = $indexCollection->count();
        if ( $cnt > 0 ) {
            if ( $cnt > 1 ) {
                $sql = $indexCollection->getSelectSql(true);
                Mage::log('Too many index entries ('.$cnt.') for query "'.$sql.'"',Zend_Log::ERR);
            }
            $item = $indexCollection->getFirstItem();
            return $item;
        }
        else {
            return null;
        }
        
    }
    
    private function _insertUpdateOrderIndexPosition ( $parentId, $soapItem ) {
        $positionCollection = $this->_findOrderIndexPosition($parentId,$soapItem->Position,$soapItem->Sku);
        $cnt = $positionCollection->count();
        if ( $cnt > 0 ) { // update
            if ( $cnt > 1 ) {
                $sql = $positionCollection->getSelectSql(true);
                Mage::log('Too many index position entries ('.$cnt.') for query "'.$sql.'"',Zend_Log::ERR);
            }
            $item = $positionCollection->getFirstItem();
            if ( $item->getDescription() != $soapItem->Description ) {
                $item->setDescription($soapItem->Description);
                $item->save();
            }
        }
        else { // insert
            $positionModel = Mage::getModel('schracksales/order_index_position');
            $positionModel->setParentId($parentId);
            $positionModel->setPosition($soapItem->Position);
            $positionModel->setSku($soapItem->Sku);
            $positionModel->setDescription($soapItem->Description);
            $positionModel->save();
        }
    }

    private function _findOrderIndexPosition ( $parentId, $position, $sku ) {
        $positionModel = Mage::getModel('schracksales/order_index_position');
        $positionCollection = $positionModel->getCollection();
        $positionCollection->addFieldToFilter('parent_id',$parentId);
        $positionCollection->addFieldToFilter('position',$position);
        $positionCollection->addFieldToFilter('sku',$sku);
        $positionCollection->getSelect();
        return $positionCollection;
    }

    private function _getKeyToItemMap ( $itemCollection ) {
        return Schracklive_SchrackSales_Model_Order_Api_DocumentHandler::mkKeyToItemMap($itemCollection);
    }

    private function _hasDifferentPositions ( $soapOrder, $soapItems, $magentoOrder ) {
        $magentoKeys = array();
        
        foreach ( $magentoOrder->getItemsCollection() as $magentoItem ) {
            $key = Schracklive_SchrackSales_Model_Order_Api_DocumentHandler::mkMagentoItemKey($magentoItem);
            $magentoKeys[] = $key;
        }

        $soapKeys = array();
        foreach ( $soapItems as $soapItem ) {
            $soapKeys[] = $soapItem->key;
        }

        if ( count($magentoKeys) != count($soapKeys) ) {
            return true;
        }
        $diff = array_diff($magentoKeys,$soapKeys);
        $diffCnt = count($diff);
        return $diffCnt > 0;
    }

    private function _hasDocuments ( Schracklive_SchrackSales_Model_Order $magentoOrder ) {
        return $magentoOrder->hasInvoices() || $magentoOrder->hasShipments() || $magentoOrder->hasCreditmemos();
    }    
    
    public static function now () {
        // strtotime('now')
        return date('Y-m-d\TH:i:s');
    }
    
    private function hasInvalidPositionNumbers ( Schracklive_SchrackSales_Model_Order $magentoOrder ) {
        foreach ( $magentoOrder->getItemsCollection() as $magentoItem ) {
            $itemPos = $magentoItem->getSchrackPosition();
            if ( ! is_numeric($itemPos) ) {
                return true;
            }
        }
        return false;
    }

    private function reindexSoapItems ( $soapItems ) {
        $res = array();
        foreach ( $soapItems as $item ) {
            if ( isset($res[$item->Sku]) ) {
                $res[$item->Sku]->Qty             += $item->Qty;
                $res[$item->Sku]->BackorderQty    += $item->BackorderQty;
                $res[$item->Sku]->AmountVat       += $item->AmountVat;
                $res[$item->Sku]->AmountTot       += $item->AmountTot;
                $res[$item->Sku]->ProductWeight   += $item->ProductWeight;
                $res[$item->Sku]->BackorderQty    += $item->BackorderQty;
                // $res[$item->Sku]->PriceSurcharge  += $item->PriceSurcharge;
                $res[$item->Sku]->AmountSurcharge += $item->AmountSurcharge;
                $res[$item->Sku]->AmountNet       += $item->AmountNet;
            } else {
                $item->key = $item->Sku;
                $res[$item->key] = $item;
            }
        }
        return $res;
    }

    private function tryFixPositionNumbers ( $magentoOrder, $soapItems ) {
        $ok = true;
        $occurenceCountArray = array();
        foreach ( $magentoOrder->getItemsCollection() as $magentoItem ) {
            $pos = $magentoItem->getSchrackPosition();
            $sku = $magentoItem->getSku();
            if ( isset($occurenceCountArray[$sku]) ) {
                $occurenceCountArray[$sku]++;
            } else {
                $occurenceCountArray[$sku] = 1;
            }
            if ( intval($pos) > 0 ) {
                continue;
            }
            $cnt = 0;
            $fixed = false;
            foreach ( $soapItems as $soapItem ) {
                if ( $soapItem->Sku == $sku ) {
                    if ( ++$cnt == $occurenceCountArray[$sku] ) {
                        $magentoItem->setSchrackPosition($soapItem->Position);
                        $magentoItem->save();
                        $fixed = true;
                        break;
                    }
                }
            }
            if ( ! $fixed ) {
                $ok = false;
            }
        }
        return $ok;
    }

    private function _createDummyProduct ( $soapItem ) {
        $sku = $soapItem->Sku;
        $name = $soapItem->Description;
        if ( ! $name ) {
            $name = $sku;
        }
        $product = Mage::getModel('catalog/product');
        $product->setSku($sku);
        $attributeID = Mage::helper('schrack')->getSchrackAttributeSetID();
        $product->setAttributeSetId($attributeID);
        $product->setTypeId('simple');
        $product->setName($name);
        // $product->setCategoryIds(array(42)); # some cat id's, 
        $product->setWebsiteIDs(array(1)); # Website id, 1 is default 
        // $product->setDescription('artificial dummy product');
        // $product->setShortDescription('artificial dummy product');
        $product->setPrice(39.99); # Set some price
        $product->setWeight(4.0000);
        $product->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE);
        $product->setStatus(1);
        $product->setTaxClassId(0); # default tax class 
        $product->setStockData(array(
            'is_in_stock' => 1,
            'qty' => 99999
        ));
        $product->setSchrackStsStatusglobal('tot');
        $product->setSchrackStsStatuslocal('tot');
        $product->setCreatedAt($this->now());
        $product->save();
        $this->_sku2productMap[$sku] = $product;
        return $product;
    }
    
    private function _getProductBySku ( $sku ) {
        if ( ! isset($this->_sku2productMap[$sku]) ) {
            $productModel = Mage::getModel('catalog/product');
            $product = $productModel->loadBySku($sku);
            if ( $product ) {
                $this->_sku2productMap[$sku] = $product;
            } else {
                return false;
            }
        }
        return $this->_sku2productMap[$sku];
    }

    private function _string2intLA ( $strLA ) {
        if ( $strLA == null ) {
            return -1;
        }
        $strLA = strtoupper($strLA);
        if ( isset(self::$string2intLA[$strLA]) ) {
            return self::$string2intLA[$strLA];
        } else {
            return -1;
        }
    }

    private function _checkLA2 ( $soapOrder, $soapItems ) {
        foreach ( $soapItems as $soapItem ) {
            if ( floatval($soapItem->Qty) === 0.0 ) {
                return false;
            }
        }
        return true;
    }

    protected function _fault($code, $customMessage=null) {
        throw new Exception($customMessage,$code);
    }

    private function _overrideResult ( $headers, $res ) {
        $tokens = explode(',',$headers['override_result']);
        $keys2vals = array('type' => 'response', 'http_status' => 200, 'error_num' => 1, 'error_msg' => '');
        foreach ( $tokens as $token ) {
            $kv = explode('=',$token);
            $keys2vals[trim($kv[0])] = trim($kv[1]);
        }

        switch ( $keys2vals['type'] ) {
            case 'response' :
                if ( $keys2vals['http_status'] <> 200 ) {
                    $this->_dieToForceLaterRetry(new Exception($keys2vals['error_msg'], $keys2vals['error_num']),intval($keys2vals['http_status']));
                } else {
                    $res['exit_code'] = $keys2vals['error_num'];
                    $res['exit_msg'] = $keys2vals['error_msg'];
                }
                return $res;
            case 'fault'  :
                if ( $keys2vals['http_status'] <> 200 ) {
                    http_response_code(intval($keys2vals['http_status']));
                    @ob_clean();
                    echo <<<EOL
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
<SOAP-ENV:Body>
<SOAP-ENV:Fault>
<faultcode>{$keys2vals['error_num']}</faultcode>
<faultstring>{$keys2vals['error_msg']}</faultstring>
</SOAP-ENV:Fault>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
EOL;
                    die();
                } else {
                    throw new Exception($keys2vals['error_msg'],$keys2vals['error_num']);
                }
                break;
            case 'text' :
                @ob_clean();
                echo $keys2vals['error_num'] . ': ' . $keys2vals['error_msg'];
                if ( $keys2vals['http_status'] > 200 ) {
                    http_response_code(intval($keys2vals['http_status']));
                }
                die();
                break;
            case 'blank' :
                @ob_clean();
                if ( $keys2vals['http_status'] > 200 ) {
                    http_response_code(intval($keys2vals['http_status']));
                }
                die();
                break;
        }

        return $res;
    }

    private function _dieToForceLaterRetry ( Exception $ex, $httpStatusCode = 500 ) {
        Mage::logException($ex);
        http_response_code($httpStatusCode);
        @ob_clean();
        echo <<<EOL
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="urn:Magento" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">
<SOAP-ENV:Body>
    <ns1:salesOrderSchrackInsertUpdateResponse>
        <exit_code xsi:type="xsd:int">{$ex->getCode()}</exit_code>
        <exit_msg xsi:type="xsd:string">{$ex->getMessage()}</exit_msg>
        <data_result SOAP-ENC:arrayType="ns1:tt_schrack_result[0]" xsi:type="ns1:ArrayOf_tt_schrack_result"/>
    </ns1:salesOrderSchrackInsertUpdateResponse>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
EOL;
        die();
    }
}

function fatal_handler() {
  $errfile = "(unknown file)";
  $errstr  = "(shutdown)";
  $errno   = E_CORE_ERROR;
  $errline = 0;

  $error = error_get_last();

  if( $error !== NULL) {
    $errno   = $error["type"];
    $errfile = $error["file"];
    $errline = $error["line"];
    $errstr  = $error["message"];
    $msg = "Fatal: {$errno} {$errstr} in file {$errfile}, line {$errline}";

    @ob_clean();
    header("HTTP/1.1 500 Internal Server Error", true, 500);
    echo <<<EOL
<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope SOAP-ENV:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="urn:Magento" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:SOAP-ENC="http://schemas.xmlsoap.org/soap/encoding/">
<SOAP-ENV:Body>
    <ns1:salesOrderSchrackInsertUpdateResponse>
        <exit_code xsi:type="xsd:int">1000</exit_code>
        <exit_msg xsi:type="xsd:string">$msg</exit_msg>
        <data_result SOAP-ENC:arrayType="ns1:tt_schrack_result[0]" xsi:type="ns1:ArrayOf_tt_schrack_result"/>
    </ns1:salesOrderSchrackInsertUpdateResponse>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
EOL;
    Mage::log($msg,Zend_Log::CRIT);
  }
}        

if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
           $headers = [];
       foreach ($_SERVER as $name => $value)
       {
           if (substr($name, 0, 5) == 'HTTP_')
           {
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
           }
       }
       return $headers;
    }
}
?>
