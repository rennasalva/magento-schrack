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

//require_once 'Customweb/PayUnity/BackendOperation/Adapter/AbstractAdapter.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Payment/BackendOperation/Adapter/Service/IRefund.php';


/**
 * @Bean
 */
class Customweb_PayUnity_BackendOperation_Adapter_RefundAdapter extends Customweb_PayUnity_BackendOperation_Adapter_AbstractAdapter implements Customweb_Payment_BackendOperation_Adapter_Service_IRefund
{
	public function refund(Customweb_Payment_Authorization_ITransaction $transaction)
	{
		

		$this->partialRefund($transaction, $transaction->getNonRefundedLineItems(), true);
	}

	public function partialRefund(Customweb_Payment_Authorization_ITransaction $transaction, $items, $close)
	{
		

		$transaction->refundDry($items, $close);
		$response = $this->sendBackofficeRequest($transaction, $this->getParameterBuilder($transaction)->buildRefundParameters($items));
		if ($response->result->code != '000.000.000'
			&& $response->result->code != '000.600.000'
			&& strpos($response->result->code, '000.100.1') !== 0) {
			if (!empty($response->result->description)) {
				throw new Exception(Customweb_I18n_Translation::__($response->result->description));
			}
			else {
				throw new Exception(Customweb_I18n_Translation::__("The refund failed due to an unknown reason."));
			}
		}
		$transaction->refund($items, $close, Customweb_I18n_Translation::__($response->result->description));
	}

	
}