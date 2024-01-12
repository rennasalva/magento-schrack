<?php

/**
 * Created by IntelliJ IDEA.
 * User: d.laslov
 * Date: 11.11.2015
 * Time: 17:39
 */
class Schracklive_SchrackSales_Model_Order_Api_OrderStatusHandler {

    const MAX_ORDER_COUNT    = 100;

    private $result = array();
    private $requestData = null;
    private $orders = null;
    private $orderPositionReferences = array();

    public function handleSetOrderTransmitted ( $requestData ) {
        if ( ! isset($requestData) ) {
            throw new Exception("Missing request data!");
        }
        if ( ! isset($requestData->CustomerNumber) || strlen($requestData->CustomerNumber) < 6 ) {
            throw new Exception("Wrong CustomerNumber!");
        }
        $this->requestData = $requestData;
        $this->getOrders();
        $this->markOrders();

        return array('TransmittedOrders' => $this->requestData->TransmittedOrders);
    }

    public function handleSetOrderTransmittedViaCsv ( $requestData ) {
        if ( ! isset($requestData) ) {
            throw new Exception("Missing request data!");
        }
        if ( ! isset($requestData->CustomerNumber) || strlen($requestData->CustomerNumber) < 6 ) {
            throw new Exception("Wrong CustomerNumber!");
        }
        $this->requestData = $requestData;
        $this->requestData->TransmittedOrders = new stdClass();
        $this->requestData->TransmittedOrders->OrderNumber = explode(",",$this->requestData->TransmittedOrdersCsv);
        $this->getOrders();
        $this->markOrders();

        return array('TransmittedOrdersCsv' => $this->requestData->TransmittedOrdersCsv);
    }

    public function handleGetOrderStatusViaJson ( $requestData ) {
        $res = $this->handleGetOrderStatus($requestData);
        $res2 = array('OrdersStatus' => $res);
        // DLA 20161005: json_encode_alt - workaround for bloody json_encode which returns sometimes invalid stuff
        $res = json_encode_alt($res2);
        $res = base64_encode($res);
        return $res;
    }

    public function handleGetOrderStatus ( $requestData ) {
        if ( ! isset($requestData) ) {
            throw new Exception("Missing request data!");
        }
        if ( ! isset($requestData->CustomerNumber) || strlen($requestData->CustomerNumber) < 6 ) {
            throw new Exception("Wrong CustomerNumber!");
        }
        $this->requestData = $requestData;
        $this->markOrdersInitiallyIfNeccessary();
        $this->getOrders();

        foreach ( $this->orders as $order ) {
            $this->handleOrder($order);
        }
        if ( isset($requestData->SetOrderTransmitted) && $requestData->SetOrderTransmitted != false ) {
            $this->markOrders();
        }
        return $this->result;
    }

    private function getOrders () {
        $orderModel = Mage::getModel('sales/order');
        $this->orders = $orderModel->getCollection();
        $this->orders->addFieldToFilter('schrack_wws_customer_id',$this->escape($this->requestData->CustomerNumber));
        if ( isset($this->requestData->OrderNumber) && $this->requestData->OrderNumber > 0  ) {
            $this->orders->addFieldToFilter('schrack_wws_order_number',$this->escape($this->requestData->OrderNumber));
        } else if ( isset($this->requestData->TransmittedOrders) ) {
            if ( ! is_array($this->requestData->TransmittedOrders->OrderNumber) ) {
                $this->requestData->TransmittedOrders->OrderNumber = array($this->requestData->TransmittedOrders->OrderNumber);
            }
            $inAr = array();
            foreach ( $this->requestData->TransmittedOrders->OrderNumber as $num ) {
                $inAr[] = $this->escape($num);
            }
            $this->orders->addFieldToFilter('schrack_wws_order_number',array('in' => $inAr));
        } else {
            $this->orders->addFieldToFilter('schrack_is_current_downloaded',0);
            // $this->orders->addFieldToFilter('status',array('neq' => 'pending')); DLA 20170407: removed wrong filtering of imaginated ;-) shop-omly orders
            $this->orders->setOrder('updated_at', 'desc');
            $this->orders->getSelect()->limit(self::MAX_ORDER_COUNT);
        }
    }

    private function handleOrder ( Schracklive_SchrackSales_Model_Order $order ) {
        $isOffer = intval(substr($order->getSchrackWwsStatus(),2)) < 2;
        $orderRes = array();
        $orderRes['OrderNumber']            = $order->getSchrackWwsOrderNumber();
        $orderRes['OrderDate']              = date('Y-m-d',strtotime($order->getSchrackWwsCreationDate()));
        $orderRes['OrderStatus']            = $isOffer ? 'offer' : 'order';
        if ( $isOffer ) {
            $orderRes['OfferNumber']        = $order->getSchrackWwsOfferNumber();
            $orderRes['OfferValidUntil']    = $order->getSchrackWwsOfferValidThru() ? date('Y-m-d', strtotime($order->getSchrackWwsOfferValidThru())) : null;
            $orderRes['OfferDate']          = $order->getSchrackWwsOfferDate() ? date('Y-m-d',strtotime($order->getSchrackWwsOfferDate())) : null;
        }
        $orderRes['CustomerOrderInfo']      = $order->getSchrackWwsReference();
        $orderRes['CustomerProjectInfo']    = $order->getSchrackCustomerProjectInfo();
        $orderRes['CustomerDeliveryInfo']   = $order->getSchrackCustomerDeliveryInfo();
        $orderRes['Amounts']                = $this->mkAmounts($order->getSubtotal(),$order->getTaxAmount(),$order->getGrandTotal());
        $orderRes['References']             = $this->mkReferences($order);
        $orderRes['Memo']                   = ''; // ### not transferred yet
        $orderRes['WarnMessage']            = ''; // ### not transferred yet

        $itemsRes = array();
        foreach ( $order->getItemsCollection() as $item ) {
            $itemRes = array();
            $itemRes['ItemID']              = $item->getSku();
            $itemRes['Name']                = $item->getName();
            $itemRes['Quantity']            = $item->getQtyOrdered();
            $itemRes['BackorderQuantity']   = $item->getQtyBackordered();
            $itemRes['DrumType']            = $item->getSchrackDrumNumber();
            $itemRes['Serialnumber']        = ''; // ### not transferred yet
            $itemRes['SurchargeDesc']       = $item->getSchrackSurchargeDesc();
            $itemRes['Amounts']             = $this->mkAmounts($item->getRowTotal(),$item->getTaxAmount(),$item->getRowTotalInclTax(),$item->getSchrackRowTotalSurcharge());
            $itemRes['References']          = $this->mkReferences($item);
            $itemRes['Memo']                = ''; // ### not transferred yet
            $itemRes['WarnMessage']         = ''; // ### not transferred yet
            $itemsRes[] = $itemRes;
            $this->orderPositionReferences[$item->getId()] = $itemRes['References'];
        }
        $orderRes['OrderStatusPositions'] = $itemsRes;
        
        $orderRes['Shipments']   = $this->handleShipments($order,$orderRes['References']);
        $orderRes['Invoices']    = $this->handleInvoices($order,$orderRes['References']);
        $orderRes['Creditmemos'] = $this->handleCreditmemos($order,$orderRes['References']);

        $this->result[] = $orderRes;
    }

    private function handleShipments ( Schracklive_SchrackSales_Model_Order $order, array $orderReferences ) {
        $res = array();
        foreach ( $order->getShipmentsCollection() as $shipment ) {
            $shipmentRes = array();
            $shipmentRes['ShipmentNumber'] = $shipment->getSchrackWwsShipmentNumber();
            $shipmentRes['ShipmentDate'] = date('Y-m-d',strtotime($shipment->getSchrackWwsDocumentDate()));
            $shipmentRes['DocumentUrl'] = Mage::getModel('core/url')->getUrl('customer/account/documentsDownload',
                                                                             array('id'         => $order->getId(),
                                                                                   'type'       => 'shipment',
                                                                                   'documentId' => $shipment->getId()));
            $colloNos = $shipment->getSchrackWwsParcels();
            if ( $colloNos && strlen($colloNos) > 0 ) {
                $shipmentRes['ColloNumbers'] = $colloNos;
            }
            $url = Mage::helper('schracksales/order')->getTrackandtraceUrl($shipment);
            if ( $url ) {
                $shipmentRes['TrackingUrl'] = $url;
            }
            $shipmentRes['References'] = $orderReferences;

            // ShipmentPositions
            $itemsRes = array();
            foreach ( $shipment->getItemsCollection() as $item ) {
                $itemRes = array();
                $itemRes['ItemID'] = $item->getSku();
                $itemRes['Quantity'] = $item->getQty();
                $itemRes['References'] = $this->orderPositionReferences[$item->getOrderItemId()];
                $itemsRes[] = $itemRes;
            }
            $shipmentRes['ShipmentPositions'] = $itemsRes;

            $res[] = $shipmentRes;
        }
        return $res;
    }

    private function handleInvoices ( Schracklive_SchrackSales_Model_Order $order, array $orderReferences ) {
        $res = array();
        /** @var Mage_Sales_Order_Invoice $invoice */
        foreach ( $order->getInvoiceCollection() as $invoice ) {
            $invoiceRes = array();
            $invoiceRes['InvoiceNumber']   = $invoice->getSchrackWwsInvoiceNumber();
            $invoiceRes['InvoiceDate']     = date('Y-m-d',strtotime($invoice->getSchrackWwsDocumentDate()));
            $invoiceRes['InvoiceCurrency'] = null; // ### not stored yet
            $invoiceRes['InvoiceDueDate']  = null; // ### not transferred yet
            $invoiceRes['PaymentTerms']    = $order->getSchrackPaymentTerms(); // ### always empty yet, possible wrong structure (simple string), not stored on invoice
            $invoiceRes['Amounts']         = null; // ### not stored yet
            $invoiceRes['DocumentUrl'] = Mage::getModel('core/url')->getUrl('customer/account/documentsDownload',
                                                                             array('id'         => $order->getId(),
                                                                                   'type'       => 'invoice',
                                                                                   'documentId' => $invoice->getId()));
            $invoiceRes['References'] = $orderReferences;
            $itemsRes = array();
            foreach ( $invoice->getItemsCollection() as $item ) {
                $itemRes = array();
                $itemRes['ItemID'] = $item->getSku();
                $itemRes['Quantity'] = $item->getQty();
                $itemRes['Amounts'] = $this->mkAmounts($item->getRowTotal(),$item->getTaxAmount(),$item->getRowTotalInclTax());
                $itemRes['References'] = $this->orderPositionReferences[$item->getOrderItemId()];
                $itemsRes[] = $itemRes;
            }
            $invoiceRes['InvoicePositions'] = $itemsRes;

            $res[] = $invoiceRes;
        }
        return $res;
    }

    private function handleCreditmemos ( Schracklive_SchrackSales_Model_Order $order, array $orderReferences ) {
        $res = array();
        /** @var Mage_Sales_Order_Creditmemo $creditmemo */
        foreach ( $order->getCreditmemosCollection() as $creditmemo ) {
            $creditmemoNumber = $creditmemo->getSchrackWwsCreditmemoNumber();
            if ( !isset($creditmemoNumber) or intval($creditmemoNumber) < 1 ) {
                continue;
            }
            $creditmemoRes = array();
            $creditmemoRes['CreditmemoNumber']   = $creditmemoNumber;
            $creditmemoRes['CreditmemoDate']     = date('Y-m-d',strtotime($creditmemo->getSchrackWwsDocumentDate()));
            $creditmemoRes['CreditmemoCurrency'] = null; // ### not stored yet
            $creditmemoRes['Amounts']            = null; // ### not stored yet
            $creditmemoRes['DocumentUrl'] = Mage::getModel('core/url')->getUrl('customer/account/documentsDownload',
                                                                             array('id'         => $order->getId(),
                                                                                   'type'       => 'creditmemo',
                                                                                   'documentId' => $creditmemo->getId()));
            $itemsRes = array();
            $creditmemoRes['References'] = $orderReferences;
            foreach ( $creditmemo->getItemsCollection() as $item ) {
                $itemRes = array();
                $itemRes['ItemID'] = $item->getSku();
                $itemRes['Quantity'] = $item->getQty();
                $itemRes['Amounts'] = $this->mkAmounts($item->getRowTotal(),$item->getTaxAmount(),$item->getRowTotalInclTax());
                $itemRes['References'] = $this->orderPositionReferences[$item->getOrderItemId()];
                $itemsRes[] = $itemRes;
            }
            $creditmemoRes['CreditmemoPositions'] = $itemsRes;

            $res[] = $creditmemoRes;
        }
        return $res;
    }

    private function mkAmounts ( $net, $vat, $total, $surcharge = null ) {
        $res = array();
        if ( $surcharge ) {
            $res['Surcharge'] = $surcharge;
        }
        $res['Net'] = $net;
        $res['Vat'] = $vat;
        $res['Total'] = $total;
        return $res;
    }

    private function mkReferences ( $object ) {
        $res = array();
        for ( $i = 1; $i < 6; ++$i ) {
            $val = $object->getData('schrack_sp_reference_' . $i);
            if ( isset($val) ) {
                $res[] = '' . $val;
            }
        }
        return $res;
    }

    private function markOrders () {
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        $write->beginTransaction();
        try {
            foreach ( $this->orders as $order ) {
               $order->setSchrackIsCurrentDownloaded(1);
               $order->save();
           }
            $write->commit();
        } catch ( Exception $ex ) {
            $write->rollback();
            Mage::logException($ex);
            throw $ex;
        }
    }

    private function markOrdersInitiallyIfNeccessary () {
        if ( isset($this->requestData->OrderNumber) && $this->requestData->OrderNumber > 0  ) {
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $sql = "SELECT count(entity_id) FROM sales_flat_order WHERE schrack_wws_customer_id = ? AND schrack_is_current_downloaded = 1";
            $cnt = $read->fetchOne($sql,$this->requestData->CustomerNumber);
            if ( $cnt == 0 ) {
                $sql = "UPDATE sales_flat_order SET schrack_is_current_downloaded = 1 WHERE schrack_wws_customer_id = ?";
                $write = Mage::getSingleton('core/resource')->getConnection('core_write');
                $write->query($sql,$this->requestData->CustomerNumber);
            }
        }
    }

    private function escape ( $str ) {
        $res = Mage::getSingleton('core/resource')->getConnection('default_write')->quote($str);
        if ( is_string($res) ) {
            if ( $res[0] == "'" ) {
                $res = substr($res,1);
            }
            $l = strlen($res);
            if ( $l > 0 ) {
                if ( $res[$l - 1] == "'" ) {
                    $res = substr($res,0,$l - 1);
                }
            }
        }
        return $res;
    }
}

/*
 * DLA 20161005: json_encode_alt - workaround for bloody json_encode which returns sometimes invalid stuff
 * found at https://gist.github.com/jorgeatorres/1239453
 */
  function json_encode_alt($a=false)
  {
    if (is_null($a)) return 'null';
    if ($a === false) return 'false';
    if ($a === true) return 'true';
    if (is_scalar($a))
    {
      if (is_float($a))
      {
        // Always use "." for floats.
        return floatval(str_replace(",", ".", strval($a)));
      }
      if (is_string($a))
      {
        static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
        return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
      }
      else
        return $a;
    }
    $isList = true;
    for ($i = 0, reset($a); $i < count($a); $i++, next($a))
    {
      if (key($a) !== $i)
      {
        $isList = false;
        break;
      }
    }
    $result = array();
    if ($isList)
    {
      foreach ($a as $v) $result[] = json_encode_alt($v);
      return '[' . join(',', $result) . ']';
    }
    else
    {
      foreach ($a as $k => $v) $result[] = json_encode_alt($k).':'.json_encode_alt($v);
      return '{' . join(',', $result) . '}';
    }
  }