<?php

class Schracklive_SchrackCustomer_Helper_Promotionbook extends Mage_Customer_Helper_Data {

    public function getPromotionbookLinksAndImages ( $customer = null ) {
        $session = Mage::getSingleton ('customer/session');
        $res2 = array();

        if ( ! $customer && ! $session->isLoggedIn() ) {
            return array();
        }
        if ( ! $customer && $session->isLoggedIn() ) {
            $customer = $session->getCustomer();
        }
        if ( ! $customer->isAllowed('price','view') ) {
            return array();
        }
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $customerId = $customer->getSchrackWwsCustomerId();
        $contactNo = $customer->getSchrackWwsContactNumber();
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        if ( $customerId && $contactNo ) {
            $sql = "SELECT link AS pdf_link, image_link FROM promotion_book main JOIN promotion_book_image img ON main.mailinglist_id = img.mailinglist_id WHERE customer_id = '$customerId' AND contact_number = $contactNo;";
            $res = $readConnection->fetchAll($sql);
            foreach ( $res as $row ) {
                $size = getimagesize($row['image_link']);
                if ( $size[0] > 1 ) {
                    $res2[] = $row;
                }
            }
            $sql = "SELECT link AS pdf_link, image_link FROM promotion_book main JOIN promotion_book_image img ON main.mailinglist_id = img.mailinglist_id WHERE customer_id = '$customerId' AND contact_number IS NULL;";
            $res = $readConnection->fetchAll($sql);
            foreach ( $res as $row ) {
                $size = getimagesize($row['image_link']);
                if ( $size[0] > 1 ) {
                    $res2[] = $row;
                }
            }
        }
        return $res2;
    }

    public function fileName2link ( $fileName ) {
        $pathBeforeCountry = $this->getPathPart('schrack/promotion_books/pathBeforeCountry',false,'https://image.schrack.com/customerflyer/');
        $pathAfterCountry  = $this->getPathPart('schrack/promotion_books/pathAfterCountry',true,'/wo_checklist/');
        // should become something like 'https://image.schrack.com/customerflyer/AT/wo_checklist/Flyer_666666_hbcb7re8whgf78erw9bnchf74892cbh748.pdf';
        return $pathBeforeCountry . strtoupper(Mage::helper('schrack')->getCountryTld()) . $pathAfterCountry . $fileName;
    }

    private function getPathPart ( $configPath, $pathDelimiterLeft, $defaultValue ) {
        $res = Mage::getStoreConfig($configPath);
        if ( ! $res )
            $res = $defaultValue;
        if ( substr($res,-1) != '/' )
            $res .= '/';
        if ( $pathDelimiterLeft && $res[0] != '/' )
            $res = '/' . $res;
        $res = str_replace('\\','/',$res);
        return $res;
    }

}
