
<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklife_Shell_Getopenquotestatus extends Mage_Shell_Abstract {

	public function run() {	
		$collection = Mage::getModel('sales/quote')
			->getCollection()
			->addFieldToFilter('is_active', array('eq' => '1'))
			->addFieldToFilter('schrack_wws_order_number', array('gt' => ''))
			->load();
		$i = 1;
		foreach ($collection as $quote) {
			$arguments = array(
				'client' => Mage::helper('wws')->createSoapClient(),
				'wwsOrderNumber' => $quote->getSchrackWwsOrderNumber(),
			);
			$statusRequest = Mage::getModel('wws/request_getorderstatus', $arguments);
			if (!$statusRequest->call()) {
				throw Mage::exception('Schracklive_Wws', "Cannot determine order status.");
			}
			$orderStatus = $statusRequest->getOrderStatus();
			echo $quote->getSchrackWwsOrderNumber().":".($orderStatus->isFinalized?' finalized':' open').($orderStatus->isOrder?' order':' quote')."\n";
			$i++;
		}
	}

}

$shell = new Schracklife_Shell_Getopenquotestatus();
$shell->run();

?>