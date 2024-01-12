<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_AddMissingOrderIndexPositions extends Mage_Shell_Abstract {

	var $deletedOrderIDs = array();
	var $counts = array();

	public function run() {
		$readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
		$writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
        $countryCode = strtolower(Mage::getStoreConfig('schrack/general/country'));
        echo $countryCode . PHP_EOL;

        $sql = "select entity_id from sales_flat_order";
		$results = $readConnection->fetchCol($sql);
        $cnt = 0;
        try {
            foreach ( $results as $orderEntityId ) {
                if ( $cnt == 4240 ) {
                    echo '';
                }
                $sql = "select entity_id from sales_flat_order_schrack_index where order_id = $orderEntityId AND shipment_id IS NULL AND invoice_id IS NULL AND credit_memo_id IS NULL AND is_offer = 0 AND is_order_confirmation = 0 AND is_processing = 0";
                $indexEntityId = $readConnection->fetchOne($sql);
                if ( ! $indexEntityId ) {
                    echo "No index found for order $orderEntityId" . PHP_EOL;
                } else {
                    $sql = "select count(*) from sales_flat_order_item where order_id = $orderEntityId";
                    $countOrderItems = $readConnection->fetchOne($sql);
                    $sql = "select count(*) from sales_flat_order_schrack_index_position where parent_id = $indexEntityId";
                    $countIndexItems = $readConnection->fetchOne($sql);
                    if ( $countOrderItems != $countIndexItems ) {
                        echo "Difference: order_id = $orderEntityId, index_id = $indexEntityId...";
                        $writeConnection->beginTransaction();
                        try {
                            $sql = "delete from sales_flat_order_schrack_index_position where parent_id = $indexEntityId";
                            $writeConnection->query($sql);
                            $sql = "select * from sales_flat_order_item where order_id = $orderEntityId";
                            $orderItems = $readConnection->fetchAll($sql);
                            $posCnt = 1;
                            foreach ( $orderItems as $orderItem ) {
                                $position = $orderItem['schrack_position'];
                                if ( ! $position ) {
                                    $position = $posCnt;
                                }
                                $sql = " insert into sales_flat_order_schrack_index_position (parent_id,position,position_level,position_level_num,sku,description)"
                                     . " values(?,?,null,null,?,?)";
                                $writeConnection->query($sql,array($indexEntityId,$position,$orderItem['sku'],$orderItem['name']));
                                $posCnt++;
                            }
                            $writeConnection->commit();
                        } catch ( Exception $ex ) {
                            $writeConnection->rollBack();
                            throw $ex;
                        }
                        echo "corrected" . PHP_EOL;
                    }
                }
                if ( ++$cnt == 1000000 ) {
                    break;
                }
            }
        } catch ( Exception $ex ) {
            echo $cnt . PHP_EOL;
            throw $ex;
        }

		echo 'done.' . PHP_EOL;
	}

}

$shell = new Schracklive_Shell_AddMissingOrderIndexPositions();
$shell->run();
