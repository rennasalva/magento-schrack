<?php

require_once('shell.php');

class Schracklive_Shell_ProductStatus extends Schracklive_Shell {

	public function run() {
		$customer = Mage::getModel('customer/customer');
		if ($this->getArg('customer')) {
			
		} elseif($this->getArg('email')) {
		} else {
			
		}
		if ($this->getArg('sku')) {
			switch ( $this->getArg('mode')) {
				case 'cached':
					$this->_showAvailabilityActionCached($this->getArg('sku'));
				break;
				case 'raw':
					$this->_showAvailabilityRawRequest($this->getArg('sku'));
				break;
				default:	
					$this->_showAvailabilityAction($this->getArg('sku'));
			}
		} else {
			echo $this->usageHelp();
		}
	}

	protected function _showAvailabilityActionCached($sku) {
		$skus = array($sku);
		$warehouseIds = Schracklive_Wws_Model_Action_Fetchavailability::ALL_WAREHOUSES;
		print_r(Mage::getModel('wws/action_cache', array(
			array(
				'products'=> $skus,
				'warehouses'=> Schracklive_Wws_Model_Action_Fetchavailability::ALL_WAREHOUSES,
		 	),
			Mage::getModel('wws/action_fetchavailability', array()),
			Mage::helper('wws/cache_availabilityinfo')
		))->execute());
	}

	protected function _showAvailabilityAction($sku) {
		print_r(Mage::getModel('wws/action_fetchavailability', array(
			'products'=> array($sku),
			'warehouses'=> Schracklive_Wws_Model_Action_Fetchavailability::ALL_WAREHOUSES,
		))->execute());
	}

	protected function _showAvailabilityRawRequest($sku) {
		$request = Mage::getModel('wws/request_getitemavail', array(
			'client'=>Mage::helper('wws')->createSoapClient(),
			'wwsCustomerId'=>(string)Mage::helper('wws')->getAnonymousWwsCustomerId(),
			'warehouseId'=>Schracklive_Wws_Model_Request_Getitemavail::ALL_WAREHOUSES,
			'products'=> array($sku)));
		$request->call();
		print_r($request->getAvailabilityInfos());
	}

	public function usageHelp() {
		return <<<USAGE
Usage:  php -f {$argv[0]} -- [options]

Gets the WWS state of a product.

  --sku <sku>         Show vailability for SKU
  --mode [cached|raw] Show current, cached or "raw" data
  help                This help

USAGE;
	}

}

$shell = new Schracklive_Shell_ProductStatus();
$shell->run();

