<?php
require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_ListCategory extends Mage_Shell_Abstract {

    private $rootCategories = null;
    private $printCategories = true;
    private $maxDeep = 100;
    private $printProducts = true;

    function __construct() {
        parent::__construct();
        $val = null;
        $this->rootCategories = array(Mage::getModel('catalog/category'));
        if ( ($val = $this->getArg('entity_id')) ) {
            $this->rootCategories[0]->load($val);
        } else if ( ($val = $this->getArg('sts_id')) ) {
            $this->rootCategories = Mage::getModel('catalog/category')->getCollection();
            $this->rootCategories->addAttributeToSelect('*');
            $this->rootCategories->addAttributeToFilter('schrack_group_id',array('like' => $val));
            $this->rootCategories->addAttributeToSort('schrack_group_id', 'asc');
            $this->rootCategories->load();
        } else if ( $this->getArg('help') ) {
            die($this->showUsage());
        } else {
            $this->rootCategories[0]->load(2);
        }
        if ( count($this->rootCategories) === 0 ) {
            die('ERROR: no such category');
        }
    }

    public function run () {
        echo '<ListCategory>' . PHP_EOL;
        foreach ( $this->rootCategories as $cat ) {
            $this->doCategory($cat);
        }
        echo '</ListCategory>' . PHP_EOL;
    }

    private function doCategory ( Schracklive_SchrackCatalog_Model_Category $cat, $deep = 1 ) {
        $subCats = $deep < $this->maxDeep && $this->printCategories ? explode(',',$cat->getChildren()) : array();
        if ( count($subCats) == 1 && $subCats[0] == '' ) {
            $subCats = array();
        }
        // $prods = $cat->getProductCollectionWithoutSolr();
        $prods = $this->printProducts ? Mage::getResourceModel('catalog/product_collection')->addCategoryFilter($cat)->addAttributeToSelect('*') : array();
        $showChildren = count($subCats) + count($prods) > 0;
        if ( $this->printCategories ) {
            $this->printRecStart('CATEGORY',$cat->getData(),$deep,! $showChildren);
        }

        /* @var $prod Schracklive_SchrackCatalog_Model_Product */
        foreach ( $prods as $prod ) {
            $data = $prod->getData();
            foreach ( $data as $k => $v ) {
                $attr = $prod->getResource()->getAttribute($k);
                if ( $attr && $attr->usesSource() ) {
                    $data[$k] = $prod->getAttributeText($k);
                }
            }
            $this->printRecStart('PRODUCT',$data,$deep + 1);
        }

        if ( $deep < $this->maxDeep ) {
            foreach ( $subCats as $subcatId ) {
                $subcat = Mage::getModel('catalog/category')->load($subcatId);
                $this->doCategory($subcat,$deep + 1);
            }
        }

        if ( $showChildren && $this->printCategories ) {
            $this->printRecStop('CATEGORY',$deep);
        }
    }

    private function printRecStart ( $typeName, array &$dataArray, $deep, $close=true ) {
        for ( $i = 0; $i < $deep; $i++ ) {
            echo '    ';
        }
        echo '<' . $typeName;
        foreach ( $dataArray as $k => $v ) {
            $v = str_replace(array('"',"'",'<','>','&'),array('&quot;','&apos;','&lt;','&gt;','&amp;'),$v);
            echo ' ' . $k . '="' . $v . '"';
        }
        if ( $close ) {
            echo '/';
        }
        echo '>' . PHP_EOL;
    }

    private function printRecStop ( $typeName, $deep ) {
        for ( $i = 0; $i < $deep; $i++ ) {
            echo '    ';
        }
        echo '</' . $typeName . '>' . PHP_EOL;
    }

    private function toXmlAttrVal ( $txt ) {
        return str_replace(array('"',"'",'<','>','&'),array('&quot;','&apos;','&lt;','&gt;','&amp;'),$txt);
    }

    protected function showUsage () {
        echo 'Usage: php ListCategory.php [--entity_id <category_entity_id>] [--sts_id <sts_id>]'.PHP_EOL;
    }

}

(new Schracklive_Shell_ListCategory())->run();
