<?php

require_once('shell.php');

class Schracklive_Shell_OrderStatus extends Schracklive_Shell {

	public function run() {
		if ($this->getArg('order')) {
			$this->show($this->getArg('order'));
		} else {
			echo $this->usageHelp();
		}
	}

	public function usageHelp() {
		return <<<USAGE
Usage:  php -f orderStatus.php -- [options]

Gets the WWS state of an order.

  order <number>  WWWS order number
  help            This help

USAGE;
	}

	protected function show($wwsOrderNumber) {
		$arguments = array(
			'client' => Mage::helper('wws')->createSoapClient(),
			'wwsOrderNumber' => $wwsOrderNumber,
		);
		$statusRequest = Mage::getModel('wws/request_getorderstatus', $arguments);
		if (!$statusRequest->call()) {
			die("Cannot determine order status.\n");
		}
		$orderStatus = $statusRequest->getOrderStatus();
		print_r($orderStatus);
	}

}

$shell = new Schracklive_Shell_OrderStatus();
$shell->run();

