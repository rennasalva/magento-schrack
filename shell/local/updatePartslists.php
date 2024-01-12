<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_UpdatePartslists extends Mage_Shell_Abstract {

	public function run() {
        $partslists = Mage::getModel('schrackwishlist/partslist')->getCollection();
        $okcount = 0;
        $problems = array();

        foreach ($partslists as $partslist) {
            try {
                $items = $partslist->getItemCollection();    
                foreach ($items as $item) {
                    $qty = $item->getQty();
                    $item->setQty($qty);
                    $item->save();
                    echo ".";
                    $okcount++;
                }
            } catch (Exception $e) {
                $problems[] = $e->getMessage();
            }

        }
        $pcount = count($problems);
        
        echo "   > problems: " . $pcount."\n";
        print_r($problems);
        $x = 0;
        
        
        die;        
	}

}

$shell = new Schracklive_Shell_UpdatePartslists();
$shell->run();
