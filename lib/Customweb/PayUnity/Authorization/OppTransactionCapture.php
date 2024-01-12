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

//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Payment/Authorization/DefaultTransactionCapture.php';


class Customweb_PayUnity_Authorization_OppTransactionCapture extends Customweb_Payment_Authorization_DefaultTransactionCapture
{
	/**
	 *
	 * @var array
	 */
	private $parameters = null;

	/**
	 * @param array $parameters
	 * @return Customweb_PayUnity_Authorization_OppTransactionCapture
	 */
	public function setParameters(array $parameters) {
		$this->parameters = $parameters;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}

	protected function getTransactionSpecificLables() {
		$labels = array();
		if ($this->parameters != null && isset($this->parameters['resultDetails.ConnectorTxID2'])) {
			$labels['connector_txid'] = array(
				'label' => Customweb_I18n_Translation::__('Connector Transaction ID'),
				'value' => $this->parameters['resultDetails.ConnectorTxID2']
			);
		}
		return $labels;
	}
}