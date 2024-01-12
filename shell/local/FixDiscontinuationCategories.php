<?php
require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_FixDiscontinuationCategories extends Mage_Shell_Abstract {
    function __construct() {
        parent::__construct();
    }

    public function run () {
        $categories = Mage::getModel('catalog/category')->getCollection();
        $categories->addAttributeToSelect('*');
        $categories->addAttributeToFilter('schrack_group_id', array('like' => '%-999'));
        $categories->load();
        foreach ( $categories as $cat ) {
            // echo $cat->getSchrackGroupId() . PHP_EOL;
            $products = Mage::getResourceModel('catalog/product_collection')->addCategoryFilter($cat);
            foreach ( $products as $prod ) {
                // echo '    ' . $prod->getSku() . ' statuslocal = ' . $prod->getSchrackStsStatuslocal() . ' forsale = ' . $prod->getSchrackStsForsale() . PHP_EOL;
                if ( $prod->getSchrackStsStatuslocal() !== 'istausl' || $prod->getSchrackStsForsale() !== '1' ) {
                    echo 'removing relation ' . $cat->getSchrackGroupId() . ' <--> ' . $prod->getSku() . PHP_EOL;
                    $this->deleteRelation($cat->getEntityId(),$prod->getEntityId());
                }
            }
        }
        echo 'done.' . PHP_EOL;
    }

    private function deleteRelation ( $categoryId, $productId ) {
        $tableName = Mage::getSingleton('core/resource')->getTableName('catalog_category_product');
        $query = "DELETE FROM `$tableName` WHERE category_id = '$categoryId' AND product_id = '$productId'";
        Mage::getSingleton('core/resource')->getConnection('core_write')->query($query);
    }
}

(new Schracklive_Shell_FixDiscontinuationCategories())->run();