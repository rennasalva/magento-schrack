<?php

class Schracklive_Typo3_Block_Page_Html_Header extends Schracklive_SchrackPage_Block_Html_Header
{

    public function getUrl($route = '', $params = array())
    {
        $url = '';
        if ($route == '') {
            $url = Mage::getStoreConfig('schrack/typo3/typo3url');
        } else {
            $url = parent::getUrl($route, $params);
        }

        return $url;
    }

    public function getCacheKey()
    {
        $key = 'schrack_typo3_menu_main';
        if (Mage::app()->getFrontController()->getRequest()->isSecure()) {
            $key = 'schrack_typo3_menu_main_https';
        }

        return $key;
    }
}
