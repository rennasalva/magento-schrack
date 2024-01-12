<?php

class Schracklive_SchrackSales_Model_Order extends Mage_Sales_Model_Order {
    
    public function getTextSearchedOrderItemsCollection ( $text ) {
        $itemCollection = Mage::getResourceModel('sales/order_item_collection')->setOrderFilter($this);
        return $this->getTextSearchedItemsCollection($itemCollection,$text);
    }
    
    public function getTextSearchedItemsCollection ( $itemCollection, $text ) {
        if ( $text && strlen($text) > 0 ) {
            $textEsc = Mage::getSingleton('core/resource')->getConnection('default_write')->quote($text);
            $textEsc = substr($textEsc,1,strlen($textEsc)-2);
            $textEsc = '%'.$textEsc.'%';

            // TODO: has possibly to be changed when upgrading to Magento >= 1.6:
            $itemCollection->addFieldToFilter(array('main_table.sku','main_table.name'),
                                              array(
                                                    array('like' => $textEsc),
                                                    array('like' => $textEsc)
                                                   )
                                             );
        }
        return $itemCollection;
    }
    
    public function save () {
        $status = $this->getSchrackWwsStatus();
        if ( ! isset($status) || strlen($status) < 1 ) {
            $this->setSchrackWwsStatus("La1");
        }
        $wwsDate = $this->getSchrackWwsCreationDate();
        if ( ! isset($wwsDate) ) {
            $wwsDate = Schracklive_SchrackSales_Model_Order_Api_V2::now();
            $this->setSchrackWwsCreationDate($wwsDate);
        }
        
        return parent::save();
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
                     && $data['schrack_wws_web_send_no'] != null;

        if ( ! $simpleChecks ) {
            return 0;
        }
        return $this->isOfferOutdated() ? -1 : 1;
    }





    public function getPayment()
    {
        //if (Mage::registry('payunitycw_external_checkout_login') === true) {
        //$customerQuote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore()->getId())
          //                                            ->loadByCustomer(Mage::getSingleton('customer/session')->getCustomerId());
        //$customerQuote = Mage::getSingleton('checkout/session')->getQuote();
        //}
        //var_dump(Mage::helper('schrackpayment/method')->getMethodInstance()); die();




        foreach ($this->getPaymentsCollection() as $payment) {
            if (!$payment->isDeleted()) {
                return $payment;
            }
        }

        return false;
    }
}

