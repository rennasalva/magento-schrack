<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_RemoveAccessoriesFromCategories extends Mage_Shell_Abstract {

	var $readConnection, $writeConnection;

	public function run() {
		$this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $necessaryAccessories = $this->getAccessories('schrack_accessories_necessary');
        echo "necessaryAccessories : " . count($necessaryAccessories) . PHP_EOL;
        $optionalAccessories = $this->getAccessories('schrack_accessories_optional');
        echo "optionalAccessories : " . count($optionalAccessories) . PHP_EOL;
        $allAccessories = array_merge($necessaryAccessories,$optionalAccessories);
        echo "allAccessories : " . count($allAccessories) . PHP_EOL;
        $inString = implode(',',$allAccessories);
        $sql = "delete from catalog_category_product where product_id in ($inString);";
        $this->writeConnection->query($sql);
        
		echo 'done.' . PHP_EOL;
	}

    private function getAccessories ( $attributeCode ) {
		$sql = "select value from catalog_product_entity_varchar where attribute_id = (select attribute_id from eav_attribute where attribute_code = '$attributeCode') and value > ''";
		$dbRes = $this->readConnection->fetchCol($sql);
		$res = array();
		foreach ( $dbRes as $col ) {
            $skus = explode(';',$col);
            foreach ( $skus as $sku ) {
                $sql = "select entity_id from catalog_product_entity where sku = '$sku';";
                $entityId = $this->readConnection->fetchOne($sql);
                if ( $entityId ) {
                    $res[$sku] = $entityId;
                }
            }
		}
        return $res;
    }
}

$shell = new Schracklive_Shell_RemoveAccessoriesFromCategories();
$shell->run();
