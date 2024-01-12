<?php

require_once 'shell.php';

class Schracklive_Shell_ImportProductStockInfos extends Schracklive_Shell {

	var $_importFile;
    var $_productModel;
    var $_stockModel;
    var $_recsTotal;
    var $_recsSaved;
    
	public function __construct() {
		parent::__construct();
		if ($this->getArg('file')) {
			$this->_importFile = $this->getArg('file');
        }
        else {
            $this->usage();
        }
        $this->_productModel = Mage::getModel('catalog/product');
        $this->_stockModel = Mage::getModel('cataloginventory/stock');
        $this->_recsTotal = 0;
        $this->_recsSaved = 0;
    }
    
    private function usage () {
        die('Usage: ImportProductStockInfos --file <CVS-File>'.PHP_EOL);
    }
    
    public function isNumBool ( $x ) {
        return is_numeric($x) && ($x == 0 || $x == 1);
    }
    
    protected function _fault($code, $customMessage)
    {
        throw new Exception($customMessage,$code);
    }
    
    private function addRecord ( $stockNumber, $stockLocation, $sku, $qty, $pickupSalesUnit, $deliverySalesUnit, $pickupState, $deliveryState, $isValid, $isHidden ) {
        try {
            if ( isset($qty) )
                $qty = str_replace(',','.',$qty);
            if ( !is_numeric($stockNumber) )       $this->_fault(88101,"invalid input field stockNumber: $stockNumber");
            if ( !isset($stockLocation) )          $this->_fault(88102,"invalid input field stockLocation: $stockLocation");
            if ( !isset($sku) )                    $this->_fault(88103,"invalid input field sku: $sku");
            if ( !is_numeric($qty) )               $this->_fault(88104,"invalid input field qty: $qty");
            if ( !is_numeric($pickupSalesUnit) )   $this->_fault(88105,"invalid input field pickupSalesUnit: $pickupSalesUnit");
            if ( !is_numeric($deliverySalesUnit) ) $this->_fault(88106,"invalid input field deliverySalesUnit: $deliverySalesUnit");
            if ( !is_numeric($pickupState) )       $this->_fault(88107,"invalid input field pickupState: $pickupState");
            if ( !is_numeric($deliveryState) )     $this->_fault(88108,"invalid input field deliveryState: $deliveryState");
            if ( !$this->isNumBool($isValid) )     $this->_fault(88109,"invalid input field isValid: $isValid");
            if ( !$this->isNumBool($isHidden) )    $this->_fault(88110,"invalid input field isHidden: $isHidden");
            
            $stockId = $this->_stockModel->getIdByNumber($stockNumber);
            if ( ! isset($stockId) || $stockId == false )
                $this->_fault(88111, 'Stock ' . $stockNumber . ' not found.');
            if ( ! isset($sku) )
                $this->_fault(88120, 'No sku given.');
            $productId = $this->_productModel->getIdBySku($sku);
            if ( ! isset($productId) || $productId == false )
                $this->_fault(88121, 'Sku ' . $sku . ' not found.');

            $stockItemModel = Mage::getModel('cataloginventory/stock_item');
            $itemExists = $stockItemModel->loadByStockIdAndProductId($stockId,$productId);

            if ( ! $itemExists ) {
                $stockItemModel->setTypeId('simple');
                $stockItemModel->setStockId($stockId);
                $stockItemModel->setProductId($productId);
                $stockItemModel->setIsInStock(1);
            }
            
            $stockItemModel->setQty((float) ($qty));
            $stockItemModel->setStockLocation($stockLocation);
            $stockItemModel->setPickupSalesUnit($pickupSalesUnit);
            $stockItemModel->setPickupIconState($pickupState);
            $stockItemModel->setDeliverySalesUnit($deliverySalesUnit);
            $stockItemModel->setDeliveryIconState($deliveryState);
            $stockItemModel->setIsValid($isValid);
            $stockItemModel->setIsOnRequest($isOnRequest);
            $stockItemModel->save();
            $this->_recsSaved++;
        }
        catch ( Exception $ex ) {
            if ( $ex->getCode() > 88000 && $ex->getCode() < 89000 ) {
                echo "WARNING: ".$ex->getCode()." - '".$ex->getMessage()."' in row ".$this->_recsTotal.PHP_EOL;
            }
            else {
                throw $ex;
            }
        }
    }            
    
    public function run() {
        $ts = time();
        if (($handle = fopen($this->_importFile, "r")) !== FALSE) {
            try {
                while (($data = fgetcsv($handle, 1024, ";")) !== FALSE) {
                    $this->_recsTotal++;
                    $num = count($data);
                    if ( $num != 10 ) {
                        echo "WARNING: invalid field count $num in line ".$this->_recsTotal.PHP_EOL;
                    }
                    else {
                        $this->addRecord($data[0],$data[1],$data[2],$data[3],$data[4],$data[5],$data[6],$data[7],$data[8],$data[9]);
                    }
                    //if ( $this->_recsTotal > 1000 )
                    //    break;
                }
            } catch ( Exception $ex ) {
                echo "FATAL ERROR: ";
                var_dump($ex);
            }
            fclose($handle);
        }
        $ts = time() - $ts;
        $tsstr = date('H:i:s',$ts);
        echo "done in $tsstr.".PHP_EOL;
        echo $this->_recsTotal . " records read, " . $this->_recsSaved . " records imported.".PHP_EOL;
    }
}

$shell = new Schracklive_Shell_ImportProductStockInfos();
$shell->run();

?>
