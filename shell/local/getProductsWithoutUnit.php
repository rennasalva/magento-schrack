
<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

//require_once '../../app/Mage.php';
class Schracklife_Shell_Getproductswithoutunit extends Mage_Shell_Abstract {

	public function usageHelp() {
		return <<<USAGE
Usage:  php -f getProductsWithoutUnit.php -- [options]

  --needle string           Sku (partial)
  --unit string				Unit to set
  help                          This help

USAGE;
	}

	public function run() {
		if (($needle = $this->getArg('needle')) && ( $unit = $this->getArg('unit'))) {
			$product_ids = array();
			$collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('schrack_qtyunit')->addAttributeToSelect('schrack_me')->load();
			foreach ($collection as $_prod) {
				if ((!$_prod->getData('schrack_qtyunit')) && (!$_prod->getData('schrack_me'))) {
					if (stripos($_prod->getSku(), $needle) === 0) {
						echo $_prod->getSku() . " has no unit \n";
						$_prod->setSchrackMe($unit)->save();
						echo $_prod->getSku() . " set to ".$unit." \n";
					}
				}
			}
		}else{
			echo $this->usageHelp();
		}
	}

}

$shell = new Schracklife_Shell_Getproductswithoutunit();
$shell->run();
?>
