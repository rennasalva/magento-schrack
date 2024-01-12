<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_fixProductAttributeOptions extends Mage_Shell_Abstract {

	var $_readConnection = null;
	var $_writeConnection = null;
    var $_storeId = null;
    var $_productEntityTypeID = null;

    function __construct() {
        parent::__construct();
	    $this->_readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
	    $this->_writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $this->_storeId = Mage::app()->getStore('default')->getStoreId();
        $sql = "SELECT entity_type_id FROM eav_entity_type WHERE entity_type_code = 'catalog_product'";
        $this->_productEntityTypeID = $this->_readConnection->fetchOne($sql);
    }

	public function run() {
        $countryCode = strtolower(Mage::getStoreConfig('schrack/general/country'));
        echo $countryCode . PHP_EOL;

        $data = array();

        $sql = " SELECT o.attribute_id, ov.value, ov.option_id, ov.store_id FROM eav_attribute_option o"
             . " LEFT JOIN eav_attribute_option_value ov ON o.option_id = ov.option_id"
             . " WHERE o.attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = ?)"
             . " ORDER BY o.attribute_id, ov.option_id, ov.store_id";

        $rows = $this->_readConnection->fetchAll($sql,array($this->_productEntityTypeID));
        foreach ( $rows as $row ) {
            $attributeID = $row['attribute_id'];
            $value = $row['value'];
            $optionID = $row['option_id'];
            if ( $optionID == null ) {
                continue; // no option value exists
            }
            $storeID = $row['store_id'];
            if ( ! isset($data[$attributeID]) ) $data[$attributeID] = array();
            if ( ! isset($data[$attributeID][$value]) ) $data[$attributeID][$value] = array();
            if ( ! isset($data[$attributeID][$value][$optionID]) ) $data[$attributeID][$value][$optionID] = array();
            $data[$attributeID][$value][$optionID][intval($storeID)] = true;
        }

        foreach ( $data as $attributeID => $values ) {
            foreach ( $values as $value => $options ) {
                $this->_writeConnection->beginTransaction();
                try {
                    $optionID = $this->ensureFirstOption($value,$options);
                    $this->removeOtherOptions($attributeID,$optionID,$options);
                    $this->_writeConnection->commit();
                } catch ( Exception $ex ) {
                    $this->_writeConnection->rollback();
                    throw $ex;
                }
                echo PHP_EOL;
            }
        }

		echo 'done.' . PHP_EOL;
	}

	private function ensureFirstOption ( $value, $options ) {
        foreach ( $options as $optionID => $storeIDs ) {
            if ( ! isset($storeIDs[0]) ) {
                $this->addOption($optionID,0,$value);
            }
            if ( ! isset($storeIDs[1]) ) {
                $this->addOption($optionID,1,$value);
            }
            return $optionID;
        }
        return null; // SNH
    }

    private function addOption ( $optionID, $storeID, $value ){
        $sql = "INSERT INTO eav_attribute_option_value (option_id, store_id, value) VALUES(?,?,?)";
        $this->_writeConnection->query($sql,array($optionID,$storeID,$value));
        echo $storeID;
    }

    private function removeOtherOptions ( $attributeID, $optionIDtoUse, $options ) {
        unset($options[$optionIDtoUse]);
        if ( count($options) < 1 ) {
            return;
        }
        $this->removeOtherOptionsFrom($attributeID,$optionIDtoUse,$options,'catalog_product_entity_varchar');
        $this->removeOtherOptionsFrom($attributeID,$optionIDtoUse,$options,'catalog_product_entity_text');
        $otherOptionIDs = array_keys($options);
        $otherOptionIDsImpl = implode(',',$otherOptionIDs);
        $sql = "DELETE FROM eav_attribute_option_value WHERE option_id IN ($otherOptionIDsImpl)";
        $this->_writeConnection->query($sql);
        echo 'V';
        $sql = " DELETE o FROM eav_attribute_option o"
             . " LEFT JOIN eav_attribute_option_value ov ON o.option_id = ov.option_id"
             . " WHERE attribute_id = ? AND ov.option_id IS NULL";
        $this->_writeConnection->query($sql,$attributeID);
        echo 'o';
    }

    private function removeOtherOptionsFrom ( $attributeID, $optionIDtoUse, $options, $table ) {
        $sql = "SELECT value_id, value FROM $table WHERE entity_type_id = ? AND attribute_id = ?";
        $rows = $this->_readConnection->fetchAll($sql,array($this->_productEntityTypeID,$attributeID));
        foreach ( $rows as $row ) {
            $valueID = $row['value_id'];
            $values = explode(',',$row['value']);
            $changed = false;
            foreach ( $values as $ndx => $value ) {
                if ( isset($options[$value]) ) {
                    $values[$ndx] = $optionIDtoUse;
                    $changed = true;
                }
            }
            if ( $changed ) {
                $valuesImpl = implode(',',$values);
                $sql = "UPDATE $table SET value = ? WHERE value_id = ?";
                $this->_writeConnection->query($sql,array($valuesImpl,$valueID));
                echo 'A';
            }
        }
    }
}

$shell = new Schracklive_Shell_fixProductAttributeOptions();
$shell->run();
