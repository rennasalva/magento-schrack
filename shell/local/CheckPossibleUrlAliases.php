<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_CheckPossibleUrlAliases extends Mage_Shell_Abstract {

	var $deletedOrderIDs = array();
	var $counts = array();

	public function run() {
        $file = $this->getArg('file');
        if ( ! $file || ! file_exists($file)  ) {
            die("Usage: php CheckPossibleUrlAliases --file <csv file name with old structure>" . PHP_EOL . PHP_EOL);
        }

		$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $countryCode = strtolower(Mage::getStoreConfig('schrack/general/country'));
        echo $countryCode . PHP_EOL;

        $sql = "select id, link from promotion_book";
		$results = $readConnection->fetchAll($sql);

        if ( ($handle = fopen($file, "r")) !== false ) {
            $first = true;
            $yes = $no = 0;
            while ( ($data = fgetcsv($handle, 1000, ",")) !== false ) {
                if ( $first ) {
                    $first = false;
                } else {
                    $oldID = $data[11];
                    $oldName = $data[12];
                    if ( substr($oldID,-4) == '-999' ) {
                        continue;
                    }
                    $sql = " SELECT count(*)"
                         . " FROM catalog_category_entity_varchar"
                         . " WHERE attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id')"
                         . " AND value LIKE '%/$oldID'";
                    $cnt = $readConnection->fetchOne($sql);
                    if ( $cnt == 0 ) {
                        echo "no match for $oldID/$oldName" . PHP_EOL;
                        ++$no;
                    } else {
                        echo "Match(es) for $oldID/$oldName" . PHP_EOL;
                        ++$yes;
                        $sql = " SELECT cat.*, attrID.value AS id, attrName.value AS name FROM catalog_category_entity AS cat"
                             . " JOIN catalog_category_entity_varchar attrID     ON (cat.entity_id = attrID.entity_id      AND attrID.attribute_id     IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id'))"
                             . " JOIN catalog_category_entity_varchar attrName   ON (cat.entity_id = attrName.entity_id    AND attrName.attribute_id   IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'name'))"
                             . " WHERE attrID.value LIKE '%/$oldID'"
                             . " ORDER BY attrID.value;";
                        $res = $readConnection->fetchAll($sql);
                        foreach ( $res as $row ) {
                            $newID = $row['id'];
                            $newName = $row['name'];
                            echo "    $newID/$newName" . PHP_EOL;
                        }
                    }
                }
            }
        }
        fclose($handle);

		echo "done. Can map $yes, cannot map $no categories." . PHP_EOL;
	}

}

$shell = new Schracklive_Shell_CheckPossibleUrlAliases();
$shell->run();
