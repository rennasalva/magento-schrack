<?php

class Schracklive_SchrackCatalogInventory_Model_Mysql4_Stock extends Mage_CatalogInventory_Model_Mysql4_Stock {

    public function getIdByNumber ( $number ) {
        $tabName = $this->getTable('cataloginventory/stock');
        return $this->_getReadAdapter()->fetchOne('select stock_id from '.$tabName.' where stock_number=?',$number);
    }

    public function getIdByNumberAndLocation ( $number, $location ) {
        $tabName = $this->getTable('cataloginventory/stock');
        return $this->_getReadAdapter()->fetchOne('select stock_id from '.$tabName.' where stock_number=? and stock_location=?',array($number,$location));
    }
   
}


?>
