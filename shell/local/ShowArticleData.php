<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_ShowArticleData extends Mage_Shell_Abstract {


    function __construct () {
        parent::__construct();
    }

	public function run() {

        $sku = $this->getArg('sku');
        if ( ! $sku ) {
            die($this->usageHelp());
        }

        $product = Mage::getModel('catalog/product')->loadBySku($sku);
        if ( ! $product->getId() ) {
            echo "No product found for sku '$sku'!\n";
        } else {
            $attributes = $product->getAttributes();
            foreach ( $product->getData() as $name => $value ) {
                if ( isset($attributes[$name]) ) {
                    $label = $attributes[$name]->getFrontend()->getLabel();
                    $value = $attributes[$name]->getFrontend()->getValue($product);
                }
                echo "$name = $label : $value\n";
            }
        }

		echo PHP_EOL . 'done.' . PHP_EOL;
	}

    public function usageHelp ()
    {
        return <<<USAGE

       php ShowArticleData.php --sku <sku>



USAGE;
    }
}

$shell = new Schracklive_Shell_ShowArticleData();
$shell->run();
