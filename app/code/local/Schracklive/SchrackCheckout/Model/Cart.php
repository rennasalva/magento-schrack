<?php
class Schracklive_SchrackCheckout_Model_Cart extends Mage_Checkout_Model_Cart{

    public function addProduct($productInfo, $requestInfo=null) {
        $sku = $productInfo instanceof Mage_Catalog_Model_Product ? $productInfo->getSku() : $productInfo;
        $qty = $requestInfo instanceof Varien_Object ? $requestInfo->getQty() : $requestInfo;
        if ( is_array($qty) ) {
            if ( isset($qty['qty']) ) {
                $qty = $qty['qty'];
            } else {
                $qty = reset($qty);
            }
        }
        $this->logAddToCart('addProduct',$sku,$qty);
        return parent::addProduct($productInfo, $requestInfo);
    }

    public function addProductsByIds($productIds) {
        if ( ! empty($productIds) ) {
            $ids = implode(",",$productIds);
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $sql = "SELECT sku FROM catalog_product_entity WHERE entity_id IN (" . $ids . ")";
            $col = $readConnection->fetchCol($sql);
            $skus = implode(",",$col);
            $this->logAddToCart('addProductsByIds',$skus);
        }
        return parent::addProductsByIds($productIds);
    }

    private function logAddToCart ( $method, $skus, $qty = '' ) {
        $uri = $_SERVER['REQUEST_URI'];
        $port = $_SERVER['REMOTE_PORT'];
        $time = $_SERVER['REQUEST_TIME_FLOAT'];
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $customerId = is_object($customer) && $customer->getSchrackWwsCustomerId() ? $customer->getSchrackWwsCustomerId() : '(anonymous)';
        $user = is_object($customer) && $customer->getEmail() ? $customer->getEmail() : '(anonymous)';
        $skus = is_array($skus) ? implode (',',$skus) : $skus;
        $msg = implode(';',array($method,$skus,$qty,$customerId,$user,$uri,$port,$time));
        Mage::log($msg,null,'add_to_cart.log');
    }

}