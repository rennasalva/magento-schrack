<?php
/**
 * orcamultimedia
 * http://www.orca-multimedia.de
 * 
 * @author		Thomas Wild
 * @package	Orcamultimedia_Sapoci
 * @copyright	Copyright (c) 2012 orcamultimedia Thomas Wild (http://www.orca-multimedia.de)
 * 
**/

require_once 'Mage/Checkout/controllers/OnepageController.php';
class Orcamultimedia_Sapoci_OnepageController extends Mage_Checkout_OnepageController {
	
	public function indexAction(){
		$session = Mage::getSingleton('customer/session');
	
		if(isset($session['sapoci']['HOOK_URL']) && !empty($session['sapoci']['HOOK_URL']) && $this->getRequest()->getControllerName() == 'onepage')
			parent::_redirect('checkout/cart');
		else
			parent::indexAction();
	}
}
?>