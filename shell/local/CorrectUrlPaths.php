<?php

require_once 'shell.php';
 
class Schracklive_Shell_CorrectUrlPaths extends Schracklive_Shell {
    
    public function run() {
        $this->showCounts('before: ');
        $this->deleteRewriteRecords();
        $this->doCategories();
        $this->showCounts('after: ');
        echo 'done.'.PHP_EOL;
    }
    
    private function deleteRewriteRecords () {
        echo 'Deleting rewrites: '.$modelName.PHP_EOL;
        $collection = Mage::getModel('core/url_rewrite')->getCollection();
        $collection->getSelect();
        foreach ( $collection as $record ) {
            $record->delete();
        }
    }
    
    private function doCategories () {
        $this->doit('catalog/category');
        // $this->doit('catalog/product');
    }

    private function doit ( $modelName ) {
        echo 'Working on: '.$modelName.PHP_EOL;
        $collection = Mage::getModel($modelName)->getCollection();
        $collection->addAttributeToSelect('is_active');
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('url_key');
        $collection->addAttributeToSelect('url_path');
        $collection->addFieldToFilter('is_active',array("in"=>array('0', '1')));

        $collection->addOrder('url_path');
        $collection->getSelect();
        foreach ( $collection as $record ) {
            $url = $record->getUrlPath();
            $isActive = $record->getIsActive();
            echo $isActive;
            if ( $isActive != 0 ) {
                if ( ($p = strpos($url,'-1/')) || ($p = strpos($url,'-1.html')) ) {
                    $url = $this->cutMinus1($url,$p);
                    $record->setUrlPath($url);
                    $record->save();
                }
            }
            else {
                $record->delete();
            }
        }
    }

    private function cutMinus1 ( $url, $p ) {
        $url = substr_replace($url,'',$p,2);
        return $url;
    }
 
    private function showCounts ( $prefix ) {
        $cntActive = $this->countCats('1');
        $cntInActive = $this->countCats('0');
        echo $prefix.$cntActive.' active and '.$cntInActive.' inactive categories found'.PHP_EOL;
    }
    
    private function countCats ( $activeFlag ) {
        echo 'Working on: '.$modelName.PHP_EOL;
        $collection = Mage::getModel('catalog/category')->getCollection();
        $collection->addAttributeToSelect('is_active');
        $collection->addFieldToFilter('is_active', array('eq'=>$activeFlag));
        // $collection->getSelect();
        // echo $collection->getSelect()->__toString().PHP_EOL;

        $res = $collection->count();
        return $res;
    }
}

$shell = new Schracklive_Shell_CorrectUrlPaths();
$shell->run();

