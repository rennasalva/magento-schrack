<?php

class Schracklive_SchrackSales_Model_SolrSalesContainerEntity extends Schracklive_SchrackSales_Model_SolrSalesEntity {
    private static $order2solrMap = array();
    private static $solr2orderMap = array(
        'OrderNumber'               => array('schrack_wws_order_number','order_id'),
        'CreditmemoNumber'          => array('entity_id','schrack_wws_creditmemo_number','wws_document_number','doc_id','credit_memo_id'),
        'ShipmentNumber'            => array('entity_id','schrack_wws_shipment_number','wws_document_number','doc_id','shipment_id'),
        'InvoiceNumber'             => array('entity_id','schrack_wws_invoice_number','wws_document_number','doc_id','invoice_id'),
        'IsCollectiveInvoice'       => array('schrack_is_collective_doc'),
        'ColloNumbers'              => array('schrack_wws_parcels'),
        'OrderStatus'               => array('schrack_wws_status'),
        'CustomerNumber'            => array('schrack_wws_customer_id'),
        'CustomerOrderInfo'         => array('schrack_wws_reference'),
        'CustomerProjectInfo'       => array('schrack_customer_project_info'),
        'CustomerDeliveryInfo'      => array('schrack_customer_delivery_info'),
        'Currency'                  => array('currency'),
        // DocumentUrl ???
        // TrackingUrl ???
        // Discount ???
        'Date'                      => array('created_at','updated_at','schrack_wws_document_date','document_date_time'),
        'Amounts_Net'               => array('base_subtotal','subtotal'),
        'Amounts_Total'             => array('base_grand_total','grand_total','base_subtotal_incl_tax','subtotal_incl_tax'),
        'Amounts_Vat'               => array('base_tax_amount','tax_amount'),
        'DocumentType'              => array('document_type')
    );
    private $items = array();

    protected function createAdditionalAttributes ( $solrJsonData ) {
        $id = $solrJsonData->id;
        $p = strpos($id,'_');
        $q = strpos($id,'.');
        if ( $p !== false && $q !== false ) {
            // 0123456789012345678901234567890
            //      p         q                     5,15
            // Order_390015759.Invoice_390009866
            $orderID = substr($id,$p + 1,$q - ($p + 1));
            $this->setData('schrack_wws_order_number',$orderID);
            $this->setData('order_id',$orderID);
        }
    }

    protected function getStaticValueMap () {
        return self::$order2solrMap;
    }

    protected function getSolrAttributeMap () {
        return self::$solr2orderMap;
    }

    public function getItemsCollection () {
        return $this->items;
    }

    public function getAllItems () {
        return $this->items;
    }

    public function setAllItems ( array $items = array() ) {
        $this->items = $items;
    }

    public function addItem ( $item ) {
        $this->items[] = $item;
    }

    public function setItem ( $ndx, $item ) {
        $items[$ndx] = $item;
    }

    public function getDocumentNumber () {
        $type = $this->_data['DocumentType'];
        return $this->_data[$type . 'Number'];
    }
}
