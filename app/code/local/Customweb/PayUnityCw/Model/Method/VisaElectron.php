<?php
/**
  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2015 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *

 * @category	Customweb
 * @package		Customweb_PayUnityCw
 * @version		2.0.28
 */

class Customweb_PayUnityCw_Model_Method_VisaElectron extends Customweb_PayUnityCw_Model_Method
{
	protected $_code = 'payunitycw_visaelectron';
	protected $paymentMethodName = 'visaelectron';
	
	protected function getMultiSelectKeys(){
		$multiSelectKeys = array(
		);;
		return $multiSelectKeys;
	}
	
	protected function getFileKeys(){
		$fileKeys = array(
		);;
		return $fileKeys;
	}
	
	protected function getNotSupportedFeatures(){
		return array(
			0 => 'ServerAuthorization',
 		);
	}
}