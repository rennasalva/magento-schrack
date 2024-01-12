<?php

class Schracklive_SchrackSales_Model_SolrSalesOrder extends Schracklive_SchrackSales_Model_SolrSalesContainerEntity {

    private static $order2solrMap = array(
        'schrack_is_complete'               => 1,
        'schrack_is_orderable'              => 1,
        'schrack_shipment_mode'             => null,              // ?
    );

    private static $solr2orderMap = array(
        'wws_document_number'         => array('wws_document_number'),
        'OrderNumber'                 => array('entity_id','schrack_wws_order_number','order_id'),
        'ShipmentNumber'              => array('ShipmentNumber'),
        'InvoiceNumber'               => array('InvoiceNumber'),
        'CreditmemoNumber'            => array('CreditmemoNumber'),
        'Date'                        => array('created_at','updated_at','schrack_wws_creation_date','document_date_time'),
        'OfferNumber'                 => array('schrack_wws_offer_number'),
        'OfferDate'                   => array('schrack_wws_offer_date'),
        'OfferValidUntil'             => array('schrack_wws_offer_valid_thru'),
        'OfferFlagValid'              => array('schrack_wws_offer_flag_valid'),
        'WebSendNr'                   => array('schrack_wws_web_send_no'),
        'OrderStatus'                 => array('schrack_wws_status'),
        'CustomerOrderInfo'           => array('schrack_wws_reference'),
        'CustomerProjectInfo'         => array('schrack_customer_project_info'),
        'CustomerDeliveryInfo'        => array('schrack_customer_delivery_info'),
        'CustomerNumber'              => array('schrack_wws_customer_id'),
        'PaymentTerms'                => array('schrack_payment_terms'),
        'Currency'                    => array('currency'),
        'Amounts_Net'                 => array('base_subtotal','subtotal'),
        'Amounts_Total'               => array('base_grand_total','grand_total','base_subtotal_incl_tax','subtotal_incl_tax'),
        'Amounts_Vat'                 => array('base_tax_amount','tax_amount')
    );

    private $shipments = false, $invoices = false, $creditmemos = false, $shippingAddress = false, $billingAddress = false;

    public static function mapOrderFieldNameToSolr ( $orderFieldName ) {
        foreach ( self::$solr2orderMap as $solrField => $orderFieldNameArray ){
            if ( in_array($orderFieldName,$orderFieldNameArray) ) {
                return $solrField;
            }
        }
        return false;
    }

    protected function createAdditionalAttributes ( $solrJsonData ) {
        if ( $this->getSchrackWwsStatus() == 'La1' && $this->getData('OfferNotValidReason') == 'offer already ordered' ) {
            $this->setSchrackWwsStatus('La2');
            $this->setData('OrderStatus','La2');
        }
    }

    protected function getStaticValueMap () {
        return self::$order2solrMap;
    }

    protected function getSolrAttributeMap () {
        return self::$solr2orderMap;
    }

    public function isOffer () {
        return strcasecmp($this->_data['schrack_wws_status'],'La1') == 0 && isset($this->_data['schrack_wws_offer_number']);
    }

    public function isOfferOutdated () {
        $dt = $this->_data['schrack_wws_offer_valid_thru'];
        if ( ! $dt ) {
            return false;
        }
        $dt = new DateTime($dt);
        $dt->setTime(23,59,00);
        $now = new DateTime();
        return $now >= $dt;
    }

    public function isOfferAndCanBeOrdered () {
        if ( ! $this->isOffer() ) {
            return 0;
        }
        $data = $this->_data;
        $simpleChecks = $data['schrack_wws_offer_number'] > ''
                     && intval($data['schrack_wws_offer_flag_valid']) == 1
                     && $data['schrack_wws_web_send_no'] != null
                     && Mage::getStoreConfig('payment/schrackpo/active'); // only when pay per bill is enabled

        if ( ! $simpleChecks ) {
            return 0;
        }
        return $this->isOfferOutdated() ? -1 : 1;
    }

    public function getCreditmemosCollection () {
        if ( $this->creditmemos === false ) {
            $this->loadRelatedDocs();
        }
        return $this->creditmemos;
    }

    public function getInvoiceCollection () {
        if ( $this->invoices === false ) {
            $this->loadRelatedDocs();
        }
        return $this->invoices;
    }

    public function getShipmentsCollection () {
        if ( $this->shipments === false ) {
            $this->loadRelatedDocs();
        }
        return $this->shipments;
    }

    private function loadRelatedDocs () {
        $helper = Mage::helper('schracksales/order');
        $docs = $helper->getRelatedDocumentsForOrder($this->getSchrackWwsOrderNumber());
        $this->shipments    = $docs['Shipment'];
        $this->invoices     = $docs['Invoice'];
        $this->creditmemos  = $docs['Creditmemo'];
    }

    public function getShippingAddress () {
        if ( ! $this->shippingAddress ) {
            $this->shippingAddress = $this->createAddress('Delivery');
        }
        if ( ! $this->shippingAddress->getFirstname() ) {
            return null;
        }
        return $this->shippingAddress;
    }

    public function getBillingAddress () {
        if ( ! $this->billingAddress ) {
            $this->billingAddress = $this->createAddress('Invoice');
        }
        if ( ! $this->billingAddress->getFirstname() ) {
            return null;
        }
        return $this->billingAddress;
    }
    
    private function createAddress ( $fieldPrefix ) {
        $res = new Schracklive_SchrackSales_Model_SolrSalesEntity();
        if ( $this->getData($fieldPrefix . 'AddrName1') ) {
            $res->setFirstname($this->getData($fieldPrefix . 'AddrName1'));
            $res->setMiddlename($this->getData($fieldPrefix . 'AddrName2'));
            $res->setLastname($this->getData($fieldPrefix . 'AddrName3'));
            $res->setStreet($this->getData($fieldPrefix . 'AddrStreet'));
            $res->setCity($this->getData($fieldPrefix . 'AddrCity'));
            $res->setPostcode($this->getData($fieldPrefix . 'AddrZIP'));
            $res->setCountryId($this->getData($fieldPrefix . 'AddrCountry'));
        }
        return $res;
    }
}
