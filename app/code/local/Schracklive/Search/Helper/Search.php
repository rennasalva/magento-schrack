<?php


require_once('app/code/local/Schracklive/Schrack/controllers/AjaxDispatcherController.php');

class Schracklive_Search_Helper_Search extends Mage_Core_Helper_Abstract {

    const TOP_FILTER_COUNT = 1000;

    public function getSearchModel($request) {
        $ajaxController = new Schracklive_Schrack_AjaxDispatcherController(Mage::app()->getRequest(), Mage::app()->getResponse());
        $ajaxController->init();
        $searchModel = $ajaxController->getSearchModel($request);
        return $searchModel;
    }

    public function logSearchArticleSelection ( $query, $product ) {
        $sku = '';
        if ( is_object($product)  ) {
            if ( ! $product->getId() ) {
                return;
            }
            $sku = $product->getSku();
        } else if ( is_string($product) ) {
            if ( strlen($product) < 8 ) {
                return;
            }
            $sku = $product;
        } else {
            return;
        }
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $sql = "INSERT INTO schrack_selected_search_result_articles (query,selected_sku) VALUES(?,?)";
        $writeConnection->query($sql,[$query,$sku]);
    }
}