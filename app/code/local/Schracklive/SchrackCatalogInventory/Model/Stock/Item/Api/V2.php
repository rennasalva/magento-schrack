<?php

class Schracklive_SchrackCatalogInventory_Model_Stock_Item_Api_V2 extends Mage_CatalogInventory_Model_Stock_Item_Api_V2 {

    const ANALYZE_AND_LOG_DATA = true;

    public function schrackUpdate ( $ctry_code, $sender_id, $data ) {
        if ( self::ANALYZE_AND_LOG_DATA ) {
            $analyzeData = array();
            $analyzeData['request_id'] = Mage::registry('soap_request_id');
            $analyzeData['timestamp'] = date("Y.m.d_H:i:s",$analyzeData['request_id']);
            $analyzeData['all'] = 0;
            $analyzeData['changed'] = 0;
            $analyzeData['unchanged'] = 0;
            $analyzeData['no qty'] = 0;
            $analyzeData['wrong sku'] = 0;
            $analyzeData['wrong stock'] = 0;
            $analyzeData['other errors'] = 0;
            $analyzeData['outdated'] = 0;
        }
        $productModel = Mage::getModel('catalog/product');
        $stockModel = Mage::getModel('cataloginventory/stock');
        // Origin_Timestamp
        $originTimestamp = false;
        if ( function_exists('getallheaders') ) {
            $originTimestamp = getallheaders()['Origin_Timestamp'];
        }

        $hasChanges = false;
        $res = array();
        $res['exit_code'] = 1;
        $res['exit_msg'] = "";
        $res['data_result'] = array();
        $xrow = -1;

        try { 
            if ( is_array($data) ) {
                foreach ( $data as $ndx => $record ) {
                    if ( self::ANALYZE_AND_LOG_DATA ) {
                        $analyzeData['all']++;
                    }
                    if ( ! isset($record->xrow) )
                        $this->_fault(198, 'No xrow given.');
                    if ( $record->xrow <= $xrow )
                        $this->_fault(199, 'Illegal xrow ' . $record->xrow . ' got.');
                    $xrow = $record->xrow;
                    $lineRes = new stdClass();
                    $lineRes->xrow = $xrow;
                    $lineRes->xstatus = 999;
                    $lineRes->xerror = "(unknown)";
                    $res['data_result'][] = $lineRes;

                    $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
                    try {
                        if ( ! isset($record->StockNumber) )
                            $this->_fault(110, 'No stock_number given.');
                        $stockId = $stockModel->getIdByNumberAndLocation($record->StockNumber,$record->StockLocation);
                        if ( ! isset($stockId) || $stockId == false )
                            $this->_fault(111, 'Stock ' . $record->StockNumber . ' and location ' . $record->StockLocation . ' not found.');
                        if ( ! isset($record->Sku) )
                            $this->_fault(120, 'No sku given.');
                        $productId = $productModel->getIdBySku($record->Sku);
                        if ( ! isset($productId) || $productId == false )
                            $this->_fault(121, 'Sku ' . $record->Sku . ' not found.');

                        if ( $originTimestamp ) {
                            $msgKey = "invitem=$record->StockNumber-$record->StockLocation-$record->Sku";
                            $isLast = Mage::helper("schrack/mq")->isLatestUpdate($msgKey,$originTimestamp);
                            if ( ! $isLast ) {
                                $this->_fault(190,'Outdated');
                            }
                        }

                        $stockItemModel = Mage::getModel('cataloginventory/stock_item');
                        $itemExists = $stockItemModel->loadByStockIdAndProductId($stockId,$productId);

                        if ( ! $itemExists ) {
                            $stockItemModel->setTypeId('simple');
                            $stockItemModel->setStockId($stockId);
                            $stockItemModel->setProductId($productId);
                            $stockItemModel->setIsInStock(1);
                        }

                        if ( isset($record->Qty) ) {
                            if ( self::ANALYZE_AND_LOG_DATA ) {
                                if ( intval($stockItemModel->getQty()) != intval($record->Qty) ) {
                                    $analyzeData['changed']++;
                                } else {
                                    $analyzeData['unchanged']++;
                                }
                            }
                            $stockItemModel->setQty((float) ($record->Qty));
                        } else if ( self::ANALYZE_AND_LOG_DATA ) {
                            $analyzeData['no qty']++;
                        }
                        if ( isset($record->StockLocation) ) {
                            $stockItemModel->setStockLocation($record->StockLocation);
                        }
                        if ( isset($record->PickupSalesUnit) ) {
                            $stockItemModel->setPickupSalesUnit($record->PickupSalesUnit);
                        }
                        if ( isset($record->PickupState) ) {
                            $stockItemModel->setPickupIconState($record->PickupState);
                        }
                        if ( isset($record->DeliverySalesUnit) ) {
                            $stockItemModel->setDeliverySalesUnit($record->DeliverySalesUnit);
                        }
                        if ( isset($record->DeliveryState) ) {
                            $stockItemModel->setDeliveryIconState($record->DeliveryState);
                        }
                        if ( isset($record->IsValid) ) {
                            $stockItemModel->setIsValid($record->IsValid);
                        }
                        if ( isset($record->IsOnRequest) ) {
                            $stockItemModel->setIsOnRequest($record->IsOnRequest);
                        }
                        $writeConnection->beginTransaction();
                        try {
                            $stockItemModel->save();
                            if ( $originTimestamp ) {
                                Mage::helper("schrack/mq")->saveLatestUpdate($msgKey,$originTimestamp);
                            }
                            $writeConnection->commit();
                        } catch ( Exception $ex ) {
                            $writeConnection->rollback();
                            $mysqlErr = 'SQLSTATE[HY000]: General error: 1205 Lock wait timeout';
                            if ( strncmp($ex->getMessage(),$mysqlErr,strlen($mysqlErr))  === 0 ) {
                                // on lock wait timeout we force MQ to resend that request later on
                                http_response_code(503);
                                die;
                            }
                            Mage::logException($ex);
                            throw $ex;
                        }
                        $lineRes->xstatus = 1;
                        $lineRes->xerror = "";
                    } catch ( Mage_Api_Exception $apiEx ) {
                        Mage::log($apiEx->getCustomMessage().' in '.$apiEx->getFile().' on line '.$apiEx->getLine(), Zend_Log::WARN);
                        $lineRes->xstatus = $apiEx->getMessage();
                        $lineRes->xerror = $apiEx->getCustomMessage();
                        if ( self::ANALYZE_AND_LOG_DATA ) {
                            switch ( intval($apiEx->getMessage()) ) {
                                case 110 :
                                case 111 :
                                    $analyzeData['wrong stock']++;
                                    break;
                                case 120 :
                                case 121 :
                                    $analyzeData['wrong sku']++;
                                    break;
                                case 190 :
                                    $analyzeData['outdated']++;
                                    break;
                                default :
                                    $analyzeData['other errors']++;
                            };
                        }
                    }
                }
                if ( self::ANALYZE_AND_LOG_DATA ) {
                    $msg = sprintf("arrived = %s (id = %d) all = %d changed = %d unchanged = %d no_qty = %d wrong_sku = %d wrong_stock = %d other errors = %d outdated = %d",
                                    $analyzeData['timestamp'],
                                    $analyzeData['request_id'],
                                    $analyzeData['all'],
                                    $analyzeData['changed'],
                                    $analyzeData['unchanged'],
                                    $analyzeData['no qty'],
                                    $analyzeData['wrong sku'],
                                    $analyzeData['wrong stock'],
                                    $analyzeData['other errors'],
                                    $analyzeData['outdated']);
                    Mage::log($msg,null,'stock_items.log');
                }
            }
        } catch ( Mage_Api_Exception $apiEx ) {
			Mage::log($apiEx->getCustomMessage().' in '.$apiEx->getFile().' on line '.$apiEx->getLine(), Zend_Log::WARN);
            $res['exit_code'] = $apiEx->getMessage();
            $res['exit_msg'] = $apiEx->getCustomMessage();
        } catch ( Exception $ex ) {
			Mage::logException($ex);
            $res['exit_code'] = 999;
            $res['exit_msg'] = $ex->getMessage();
        }

        return $res;
    }
    
    public function schrackListStocks () {
        $res = array();
        $res['exit_code'] = 1;
        $res['exit_msg'] = "";
        $res['data_result'] = array();
          
        $stockCollection = Mage::getModel('cataloginventory/stock')->getCollection();
        foreach ( $stockCollection as $stock ) {
            if ( ! $stock->getIsPickup() ) {
                continue;
            }
            $lineRes = new stdClass();
            $res['data_result'][] = $lineRes;
            $lineRes->number = $stock->getStockNumber();
            $lineRes->name = $stock->getStockName();  
            $xmlstr = $stock->getXmlAddress();
            if ( $xmlstr !== null && strlen(trim($xmlstr)) > 0 ) {
                $xml = simplexml_load_string($xmlstr,'SimpleXMLElement', LIBXML_NOCDATA);
                $lineRes->street = $xml->street;
                $lineRes->zip    = $xml->zip;   
                $lineRes->city   = $xml->city;  
                $lineRes->phone  = $xml->phone; 
                $lineRes->fax    = $xml->fax;   
                $lineRes->email  = $xml->email; 
                $lineRes->gmap   = $xml->gmap;   
            }
        }
                
        return $res; 
    }

    public function schrackLockStocks ( $ctry_code, $sender_id, $data ) {
        $res = array();
        $res['exit_code'] = 1;
        $res['exit_msg'] = "";

        try {
            $helper = Mage::helper('schrackcataloginventory/stock');
            foreach ( $data as $row ) {
                if ( $row->StockNumber < 1 || $row->StockNumber >= 999 ) {
                    Mage::log("Ignoring invalid stock number {$row->StockNumber} in catalogInventoryStockItemSchrackLockStocks request.");
                    continue;
                }
                $stock = $helper->getStockByNumber($row->StockNumber);
                if ( ! $stock ) {
                    $res['exit_code'] = 101;
                    $res['exit_msg'] = "Invalid stock number {$row->StockNumber} got.";
                    return $res;
                }
                if ( $row->locked ) {
                    if ( isset($row->lockedUntil) && $row->lockedUntil ) {
                        $stock->setLockedUntil($row->lockedUntil . ' 23:59:59');
                    } else {
                        $stock->setLockedUntil('2038-01-19 03:14:07'); // max timestamp in mysql
                    }
                } else {
                    $stock->setLockedUntil(null);
                }
                $stock->save();
            }
        } catch ( Exception $ex ) {
			Mage::logException($ex);
            $res['exit_code'] = 999;
            $res['exit_msg'] = $ex->getMessage();
        }

        return $res;
    }
    
}

?>
