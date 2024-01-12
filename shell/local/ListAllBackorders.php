<?php

require_once 'shell.php';

class Schracklive_Shell_ListAllBackorders extends Schracklive_Shell {

	public function run() {
	    $countryCode = strtoupper(Mage::getStoreConfig('schrack/general/country'));
	    $detailFileName = "/tmp/backorder_details_$countryCode.csv";
	    $orderNoFileName = "/tmp/backorder_orders_$countryCode.txt";
	    echo "ListAllBackorders country = $countryCode, output files = $detailFileName, $orderNoFileName\n";
	    $fpDetails = fopen($detailFileName,"w");
	    $helper = Mage::helper('schracksales/order');
	    $positions = $helper->getBackorderPositions(false,false,null,null,null,null,null,true);
	    $orderNums = array();
	    fputs($fpDetails,"country;customer;order_number;creation_date;position;sku;backorder_qty\n");
	    foreach ( $positions as $position ) {
	        echo '.';
	        $csv = array($countryCode,
                         $position->getCustomerNumber(),
                         $position->getSchrackWwsOrderNumber(),
                         $position->getSchrackWwsCreationDate(),
                         $position->getData('PositionNumber'),
                         $position->getSku(),
                         $position->getSchrackBackorderQty());
	        $outputLine = implode(';',$csv);
            fputs($fpDetails,$outputLine . PHP_EOL);
            $orderNums[$position->getSchrackWwsOrderNumber()] = true;
        }
        fclose($fpDetails);
	    file_put_contents($orderNoFileName,implode(PHP_EOL,array_keys($orderNums)));
    	echo "\ndone.\n";
	}
}

$shell = new Schracklive_Shell_ListAllBackorders();
$shell->run();
