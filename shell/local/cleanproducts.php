
<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

//require_once '../../app/Mage.php';
class Schracklife_Shell_CleanProducts extends Mage_Shell_Abstract {

	public function usageHelp() {
		return <<<USAGE
Usage:  php -f cleanproducts.php -- [options]

  --mode todelete|active|inactive	status of matching articles
  --file <file>           input file
  help                          This help

USAGE;
	}

	public function run() {
		if ($file = $this->getArg('file')) {
			echo "opening " . $file . "\n";
			$fp = fopen($file, "r");
			$row = 0;
			$product_ids = array();
			$c=0;
			while (($data = fgetcsv($fp, 1000, " ")) !== FALSE) {  
				$product_ids[$data[0]] = $data[0];  
				$c++;
			} 
			echo "$c lines read with " . count($product_ids) . " product-ids from $file\n";
			if ($mode = $this->getArg('mode')){
				switch ($mode){
					case 'todelete':	//delete matching products
						foreach ($product_ids as $sku){
							$product=null;
							$product_id = Mage::getModel('catalog/product')->getIdBySku($sku);
							if ($product_id) $product=Mage::getModel('catalog/product')->load($product_id);
							if (is_object($product)){
								echo '.';
								$product->delete();
							}else{
								echo $sku.' not found'."\n";
							}
						}
						break;
					case 'active':		//deactivate nonmatching products
						$collection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('status')->load();
						foreach ($collection as $_prod) {
							if (isset($product_ids[$_prod->getSku()])){
								if ($_prod->getStatus()==2){
									echo '+';
									$_prod->setStatus(Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
									$_prod->save();

								}

							}else{
								if ($_prod->getStatus()!=2){
									echo '-';
									$_prod->setStatus(Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
									$_prod->save();
								}
							}
						}
						break;
					case 'inactive':  //deactivate matching products
						break;
					default:
						break;
				}
			}
		} else {
			echo $this->usageHelp();
		}
	}

}

$shell = new Schracklife_Shell_CleanProducts();
$shell->run();
?>
