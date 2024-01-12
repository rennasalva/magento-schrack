<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

//require_once '../../app/Mage.php';
class Schracklife_Shell_Sandbox extends Mage_Shell_Abstract {

	public function usageHelp() {
		return <<<USAGE
Usage:  php -f cleanproducts.php -- [options]

  --code <name>           attribute code
  help                          This help

USAGE;
	}

	public function run() {
		if ($attribute_code = $this->getArg('code')) {
			if ($attribute_code == 'all') {
				$conn = Mage::getSingleton('core/resource')->getConnection('core_write');
				$attributes = $conn->fetchAll("select * from eav_attribute where entity_type_id=4 and backend_type <> 'static' and (attribute_code like 'schrack%' or attribute_code in ('meta_title','meta_description','description','short_description'))");
				foreach ($attributes as $attribute) {
					echo "migrating attr ".$attribute['attribute_code']."\n";
					switch ($attribute['backend_type']) {
						case 'varchar':
							$conn->query("ALTER TABLE catalog_product_entity ADD " . $attribute['attribute_code'] . " VARCHAR(255) NOT NULL DEFAULT ''");
							$conn->query("UPDATE catalog_product_entity SET " . $attribute['attribute_code'] . "=(select value from catalog_product_entity_varchar where store_id=0 and entity_id=catalog_product_entity.entity_id and attribute_id=" . $attribute['attribute_id'] . ")");
							$conn->query("DELETE from catalog_product_entity_varchar WHERE attribute_id=" . $attribute['attribute_id']);
							$conn->query("UPDATE eav_attribute set backend_type='static' where attribute_id=" . $attribute['attribute_id']);
							break;
						case 'text':
							$conn->query("ALTER TABLE catalog_product_entity ADD " . $attribute['attribute_code'] . " text ");
							$conn->query("UPDATE catalog_product_entity SET " . $attribute['attribute_code'] . "=(select value from catalog_product_entity_text where store_id=0 and entity_id=catalog_product_entity.entity_id and attribute_id=" . $attribute['attribute_id'] . ")");
							$conn->query("DELETE from catalog_product_entity_text WHERE attribute_id=" . $attribute['attribute_id']);
							$conn->query("UPDATE eav_attribute set backend_type='static' where attribute_id=" . $attribute['attribute_id']);
							break;
						case 'int':

							$conn->query("ALTER TABLE catalog_product_entity ADD " . $attribute['attribute_code'] . " int(11) DEFAULT NULL");
							$conn->query("UPDATE catalog_product_entity SET " . $attribute['attribute_code'] . "=(select value from catalog_product_entity_int where store_id=0 and entity_id=catalog_product_entity.entity_id and attribute_id=" . $attribute['attribute_id'] . ")");
							$conn->query("DELETE from catalog_product_entity_int WHERE attribute_id=" . $attribute['attribute_id']);
							$conn->query("UPDATE eav_attribute set backend_type='static' where attribute_id=" . $attribute['attribute_id']);
							break;
						case 'decimal':
							$conn->query("ALTER TABLE catalog_product_entity ADD " . $attribute['attribute_code'] . " decimal(12,4) DEFAULT NULL");
							$conn->query("UPDATE catalog_product_entity SET " . $attribute['attribute_code'] . "=(select value from catalog_product_entity_decimal where store_id=0 and entity_id=catalog_product_entity.entity_id and attribute_id=" . $attribute['attribute_id'] . ")");
							$conn->query("DELETE from catalog_product_entity_decimal WHERE attribute_id=" . $attribute['attribute_id']);
							$conn->query("UPDATE eav_attribute set backend_type='static' where attribute_id=" . $attribute['attribute_id']);
							break;
						case 'datetime':
							$conn->query("ALTER TABLE catalog_product_entity ADD " . $attribute['attribute_code'] . " datetime DEFAULT NULL");
							$conn->query("UPDATE catalog_product_entity SET " . $attribute['attribute_code'] . "=(select value from catalog_product_entity_datetime where store_id=0 and entity_id=catalog_product_entity.entity_id and attribute_id=" . $attribute['attribute_id'] . ")");
							$conn->query("DELETE from catalog_product_entity_datetime WHERE attribute_id=" . $attribute['attribute_id']);
							$conn->query("UPDATE eav_attribute set backend_type='static' where attribute_id=" . $attribute['attribute_id']);
							break;
						default:
							echo "no handler for ".$attribute['attribute_code']."\n";
							break;
					}


				}
			} else {
				$entityType = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
				$attribute = Mage::getModel('catalog/entity_attribute');
				$attribute->loadByCode($entityType, $attribute_code);
				if ($attribute->getId()) {
					$attribute->setBackendType(Mage_Eav_Model_Entity_Attribute_Abstract::TYPE_STATIC);
					$attribute->save();
				}
			}
//			$product=Mage::getModel('catalog/product');
//			$productId = $product->getIdBySku('IL900166--');
//			$product->load($productId);
//			echo "old ean: ".$product->getSchrackEan().'\n';
//			$product->setSchrackEan("999991");
//			$product->save();
//			echo "new ean: ".$product->getSchrackEan().'\n';
		} else {
			echo $this->usageHelp();
		}
	}

}

$shell = new Schracklife_Shell_Sandbox();
$shell->run();
?>
