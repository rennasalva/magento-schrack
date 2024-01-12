<?php

require_once('app/code/core/Mage/Paypal/controllers/IpnController.php');

class Schracklive_SchrackPaypal_IpnController extends Mage_Paypal_IpnController {

	/**
	 * When a customer cancel payment from paypal.
	 */
	public function indexAction() {
        Mage::log('ipnIndexAction - ' . ($this->getRequest()->isPost() ? 'post' : 'get') . ' - serverarray: ' . print_r($_SERVER, true) . ' - params: ' . print_r($this->getRequest()->getParams(), true), null, '/payment/paypal.log');
		
        return parent::indexAction();
	}
}
?>
