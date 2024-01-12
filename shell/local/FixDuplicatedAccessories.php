<?php
require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_FixDuplicatedAccessories extends Mage_Shell_Abstract {
    function __construct() {
        parent::__construct();
    }

    public function run () {
        $readConnection  = Mage::getSingleton('core/resource')->getConnection('core_read');
        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

        $sql = "SELECT * FROM catalog_product_entity_varchar WHERE attribute_id in (select attribute_id from eav_attribute where attribute_code = 'schrack_necessary_accessories');";
        $rows = $readConnection->fetchAll($sql);

        foreach ( $rows as $row ) {
            $val = $this->removeDuplicates($row['value']);
            if ( $val != $row['value'] ) {
                echo $row['value'] . ' => ' . $val . PHP_EOL;
                $valueId = $row['value_id'];
                $sql = "UPDATE catalog_product_entity_varchar SET value = '$val' WHERE value_id = $valueId;";
                $writeConnection->query($sql);
            }
        }

        echo 'done.' . PHP_EOL;
    }

    private function removeDuplicates ( $val ) {
        $p = strpos($val,',');
        if ( $p == false ) {
            $p = strpos($val,',');
            if ( $p == false ) {
                return $val;
            } else {
                $delimiter = ';';
            }
        } else {
            $delimiter = ',';
        }
        $inAr = explode($delimiter,$val);
        $outAr = array();
        foreach ( $inAr as $x ) {
            $outAr[strtoupper($x)] = true;
        }
        $outVal = implode($delimiter,array_keys($outAr));
        return $outVal;
    }
}

(new Schracklive_Shell_FixDuplicatedAccessories())->run();