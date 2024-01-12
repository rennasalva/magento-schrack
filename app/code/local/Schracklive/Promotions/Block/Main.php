<?php

class Schracklive_Promotions_Block_Main extends Mage_Page_Block_Html {

    private $promoHelper;
    private $coreHelper;

    public function __construct () {
        parent::__construct();
        /** @var Schracklive_Promotions_Helper_Data promoHelper */
        $this->promoHelper = Mage::helper('promotions');
        /** @var Mage_Core_Helper_Data coreHelper */
        $this->coreHelper = Mage::helper('core');
    }

    protected function getDefaultPromotionPictureUrl () {
        $res = Mage::getStoreConfig('schrack/promotions/default_image_url');
        if ( $res ) {
            return $res;
        } else {
            // return "https://image.schrack.com/foto/f_promotions_default.png";
            //$res = Mage::getBaseUrl('skin') . '/frontend/schrack/default/schrackdesign/Public/Images/empty_blue_promotion.jpg';
            $res = Mage::getBaseUrl('skin') . '/frontend/schrack/default/schrackdesign/Public/Images/empty_blue_promotion.png';
            return $res;
        }
    }

    protected function getPromotions () {
        return $this->promoHelper->getAllPromotionsWithPDFs();
    }

}

