<?php
/**
 *
 * @package	Orcamultimedia_Ids
 *
 **/

require_once 'Mage/Checkout/controllers/OnepageController.php';
class Orcamultimedia_Ids_OnepageController extends Mage_Checkout_OnepageController {
	
	public function indexAction(){
		$session = Mage::getSingleton('customer/session');
	
		if(isset($session['ids']['hookurl']) && !empty($session['ids']['hookurl']) && $this->getRequest()->getControllerName() == 'onepage')
			parent::_redirect('checkout/cart');
		else
			parent::indexAction();
	}

}
?>
