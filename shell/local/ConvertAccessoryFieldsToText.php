<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_ConvertAccessoryFieldsToText extends Mage_Shell_Abstract {

    private $readConnection = null;
    private $writeConnection = null;
    private $attrCode2IdMap = [];

    function __construct() {
        parent::__construct();
        $this->readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $this->writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
    }

	public function run() {
	    $this->getAttributeIDs();
        $this->changeEavAttribute();
        $this->moveData();
		echo "done\n";
    }

    private function getAttributeIDs () {
        $sql = "SELECT attribute_code, attribute_id FROM eav_attribute WHERE attribute_code IN ('schrack_accessories_necessary','schrack_accessories_optional','schrack_necessary_accessories','schrack_optional_accessories')";
        $dbRes = $this->readConnection->fetchAll($sql);
        foreach ( $dbRes as $row ) {
            $this->attrCode2IdMap[$row['attribute_code']] = $row['attribute_id'];
        }
    }

    private function changeEavAttribute () {
        $sql = "UPDATE eav_attribute SET backend_type = 'text' WHERE attribute_id IN (" . implode(",",$this->attrCode2IdMap) . ")";
        $this->writeConnection->query($sql);
    }

    private function moveData () {
        $sql = "SELECT DISTINCT entity_id FROM catalog_product_entity_varchar WHERE attribute_id IN (" . implode(",",$this->attrCode2IdMap) . ")";
        $dbRes = $this->readConnection->fetchCol($sql);
        echo count($dbRes) . " products to handle\n";
        $i = 0;
        foreach ( $dbRes as $entityId ) {
            $this->writeConnection->beginTransaction();
            try {
                $sql = "SELECT value_id, attribute_id, value FROM catalog_product_entity_varchar WHERE entity_id = ? AND attribute_id IN (" . implode(",",$this->attrCode2IdMap) . ")";
                $dbRes = $this->readConnection->fetchAll($sql,$entityId);
                foreach ( $dbRes as $row ) {
                    $oldValueId = $row['value_id'];
                    $attributeId = $row['attribute_id'];
                    $value = $row['value'];
                    $sql = " INSERT INTO catalog_product_entity_text (entity_type_id,attribute_id,store_id,entity_id,value)"
                         . " VALUES(4,?,0,?,?)";
                    $this->writeConnection->query($sql,[$attributeId,$entityId,$value]);
                    $sql = "DELETE FROM catalog_product_entity_varchar WHERE value_id = ?";
                    $this->writeConnection->query($sql,$oldValueId);
                }
                $this->writeConnection->commit();
                echo '.';
            } catch ( Exception $ex ) {
                $this->writeConnection->rollback();
                echo "X\n";
                throw $ex;
            }
            if ( ++$i % 100 == 0 ) {
                echo "\n";
            }
        }
    }

    public function usageHelp () {
        return <<<USAGE
Usage:  sudo php -f ConvertAccessoryFieldsToText.php



USAGE;
    }
}

$shell = new Schracklive_Shell_ConvertAccessoryFieldsToText();
$shell->run();
