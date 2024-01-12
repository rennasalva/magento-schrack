<?php

abstract class Schracklive_Wws_Model_Action_Wwsrequest extends Schracklive_Wws_Model_Action_Abstract {

	const CODE_CHARGES = 'CHARGES';

	/* @var $_quote Schracklive_SchrackSales_Model_Quote */

	protected $_quote;
	protected $_items = array(); // Mage_Sales_Model_Quote_Item
	/* @var $_customer Schracklive_SchrackCustomer_Model_Customer */
	protected $_customer;
	/* @var $_loggedInCustomer Schracklive_SchrackCustomer_Model_Customer */
	protected $_loggedInCustomer;
	protected $_ip;

	public function __construct(array $arguments) {
		$checkedArguments = $this->_checkArguments($arguments, array(
			'quote' => 'Schracklive_SchrackSales_Model_Quote',
			'loggedInCustomer' => array('Schracklive_SchrackCustomer_Model_Customer', null),
			'ip' => array(null, ''),
				)
		);

		$customer = $checkedArguments['quote']->getCustomer();
		if ($customer->isSystemContact()) {
			if (!$this->_isEmployeeForCustomer($checkedArguments['loggedInCustomer'])) {
				// Workaround for PayUnity Problem:
				if (isset($arguments['paymentSource']) && $arguments['paymentSource'] == 'payunity-payment') {
					// Do nothing, or implement something meaningful!
				} else {
					throw new InvalidArgumentException('Argument "quote" has an invalid customer (no employee).');
				}
			}
		} else {
			// Respect the new self registration process (Durchlaeufer / Interessent):
			$isNotRegisteredCustomer = false;
			// guest = non-registering-user
			if (in_array($checkedArguments['quote']->getSchrackCustomertype(), array('oldFullProspect', 'oldLightProspect', 'newProspect', 'guest'))) {
				$isNotRegisteredCustomer = true;
			}

			if (!$customer->getSchrackWwsCustomerId() && $isNotRegisteredCustomer == false) {
				return 'guest-order';
			}
			if ($customer->getSchrackWwsCustomerId() == 'PROS' && $isNotRegisteredCustomer == false) {
				return 'full-prospect-order';
			}
			if ($customer->getSchrackWwsCustomerId() == 'PROSLI' && $isNotRegisteredCustomer == false) {
				return 'light-prospect-order';
			}
		}

		$this->_quote = $checkedArguments['quote'];
		$this->_items = $this->_quote->getAllItems();
		$this->_customer = $customer;
		$this->_loggedInCustomer = $checkedArguments['loggedInCustomer'];
		$this->_ip = $checkedArguments['ip'];

		parent::__construct($arguments);
	}

	protected function _isEmployeeForCustomer($loggedInCustomer) {
		return (is_object($loggedInCustomer) && $loggedInCustomer->isEmployee());
	}

	protected function _buildArguments($memo = array()) {
		if ($this->_ip) {
			//array_push($memo, $this->_ip); // Removed from Memo Field, because it is useless
		}
		$this->_requestArguments += array(
			'memo' => $memo,
		);
	}

	protected function _checkOrderResponse() {
		$order = $this->_request->getOrder();
        $schrackWwsOrderId = $order['wwsNumber'];
        $hasCashDisount = 0;

        if ($schrackWwsOrderId) {
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');

            $query  = "SELECT has_discount FROM wws_insert_update_order_response";
            $query .= " WHERE wws_order_id LIKE '" . $schrackWwsOrderId . "'";
            $query .= " ORDER BY response_datetime DESC";
            $query .= " LIMIT 1";
            $hasCashDisount = $readConnection->fetchOne($query);
        }

		// use floor() to allow for a rounding errors
		if ($this->_isOutsideRoundingErrorRange($order['amountGross'], $order['amountNet'] + $order['amountVat']) && $hasCashDisount == 0) {
			throw $this->_requestException("Gross amount mismatch: got {$order['amountGross']}, expected ".($order['amountNet'] + $order['amountVat']));
		}
		// foreign customers may have no VAT at all - @todo check if customer is foreign (how?)
		/* deactivate - it seems that some items (charges) have no VAT at all
		  if ((float)$order['amountVat'] != 0.0) {
		  if (floor($order['amountVat']) != floor($order['amountNet'] * Mage::getStoreConfig('schrack/sales/vat') / 100)) {
		  throw $this->_requestException("VAT mismatch: got {$order['amountVat']}, expected ".($order['amountNet'] * Mage::getStoreConfig('schrack/sales/vat') / 100).'.');
		  }
		  }
		 * 
		 */

		$this->_parseMemoIntoMessages($this->_request->getMemo());
	}

	protected function _isOutsideRoundingErrorRange($value1, $value2) {
		return abs(floor($value1) - floor($value2)) > 1;
	}

    public function execute() {
        if ( isset($this->_loggedInCustomer) && is_object($this->_loggedInCustomer) && ($this->_loggedInCustomer->getSchrackWwsCustomerId() != $this->_customer->getSchrackWwsCustomerId()) ) {
            Mage::log('Schracklive_Wws_Model_Action_Wwsrequest: Customer ' . $this->_loggedInCustomer->getSchrackWwsCustomerId() . ' (' . $this->_loggedInCustomer->getEmail()
                . ') will execute ' . $this->_requestName . ' for customer ' . $this->_customer->getSchrackWwsCustomerId() . ' (' . $this->_customer->getEmail() . ')', null, 'act_as_user.log');
        }
        return parent::execute();
    }

}
