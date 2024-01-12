<?php
/**
  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
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
 */

//require_once 'Customweb/PayUnity/AbstractParameterBuilder.php';


class Customweb_PayUnity_Update_ParameterBuilder extends Customweb_PayUnity_AbstractParameterBuilder
{
	/**
	 * @return array
	 */
	/**
	 * @return array
	 */
	public function buildStatusParameters(){
		return $this->getAuthenticationParameters();
	}
	
	
	
	/**
	 * @return array
	 */
	public function buildStatusParametersMerchantId(){
		return array_merge($this->getAuthenticationParameters(),
				array(
					'merchantTransactionId' => $this->getPaymentMethod()->getMerchantTransactionId($this->getTransaction())
				));
	}
}