<?php

class Schracklive_Wws_Model_Request_Shiporder extends Schracklive_Wws_Model_Request_Abstract {

	protected $_soapMethod = 'ship_order';
	/* arguments */
	protected $_wwsOrderNumber;
	protected $_flagOrder;
	protected $_wwsCustomerId;
	protected $_paymentInfo = '';
	protected $_emailTo = '';
	protected $_emailCc = '';
	protected $_requestMemo = '';
	/* return values */
	protected $_responseOrder = array();
	protected $_responseItems = array();

	public function __construct(array $arguments) {
		$this->_checkArgument($arguments, 'wwsOrderNumber');
		$this->_checkArgument($arguments, 'wwsCustomerId');
		$this->_checkArgument($arguments, 'flagOrder');

		$this->_wwsOrderNumber = $arguments['wwsOrderNumber'];
		$this->_flagOrder = $arguments['flagOrder'];
		$this->_wwsCustomerId = $arguments['wwsCustomerId'];
		$this->_paymentInfo = isset($arguments['paymentInfo']) ? $arguments['paymentInfo'] : '';
		$this->_emailTo = isset($arguments['emailTo']) ? $arguments['emailTo'] : '';
		$this->_emailCc = isset($arguments['emailCc']) ? $arguments['emailCc'] : '';
		$this->_requestMemo = isset($arguments['memo']) ? join(';', $arguments['memo']) : '';

		parent::__construct($arguments);
	}

	protected function _buildArguments() {
        if ( is_bool($this->_flagOrder) ) {
            $flagOrder = $this->_flagOrder ? 1 : 0;
        } else {
            $flagOrder = intval($this->_flagOrder);
        }

        // Last stop, if customer is blocked from order (for test reasons on LIVE system):
        if (Mage::getStoreConfig('customer/online_customers/order_stop_email') && Mage::getStoreConfig('customer/online_customers/order_stop_email') == $this->_emailTo) {

            $flagOrder = 0;
            $country = strtoupper(Mage::getStoreConfig('schrack/general/country'));
            $mailText = 'COUNTRY = ' . $country . ' >>>  WWS-Order-Number = ' . $this->_wwsOrderNumber . ' not have been Shipped';
            if (Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails')) {
                $mail = new Zend_Mail('utf-8');
                try {
                    $mail->setFrom(Mage::getStoreConfig('web/secure/base_url'))
                        ->setSubject('Please check Order ' . $this->_wwsOrderNumber)
                        ->setBodyHtml($mailText)
                        ->addTo(Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails'))
                        ->send();
                } catch (Exception $ex) {
                    Mage::log($mailText . ' E-Mail: ' . $this->_emailTo, null, 'order_not_shipped.log');
                    Mage::logException($ex);
                }
            } else {
                Mage::log($mailText . ' E-Mail: ' . $this->_emailTo, null, 'order_not_shipped.log');
            }
        }

        if ($this->_emailTo == '' || $this->_emailTo == null) {
            if ($this->_wwsOrderNumber) {
                $resource        = Mage::getSingleton('core/resource');
                $readConnection  = $resource->getConnection('core_read');
                $query = "SELECT customer_email FROM sales_flat_quote WHERE schrack_wws_order_number LIKE '" . $this->_wwsOrderNumber . "'";
                $quoteEmail = $readConnection->fetchOne($query);
                if ($quoteEmail) {
                    $this->_emailTo = $quoteEmail;
                }
                if ($this->_emailCc == '' || $this->_emailCc == null) {
                    $advisorEmail = '';
                    if (Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor()) {
                        $query = "SELECT email FROM customer_entity WHERE schrack_user_principal_name LIKE '" . Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor() . "'";
                        if (Mage::helper('schrackcustomer')->getPersonalizedDefaultAdvisor() != '') {
                            $advisorEmail = $readConnection->fetchOne($query);
                        }
                        $this->_emailCc = $advisorEmail;
                    }
                }
            }
        }

		$this->_soapArguments = array(
			'tt_ship' => array(
				array(
					'xrow' => 1,
					'OrderNumber' => $this->_wwsOrderNumber,
					'FlagOrder' => $flagOrder,
					'CustomerNumber' => $this->_wwsCustomerId,
					'PaymentInfo' => $this->_paymentInfo,
					'MailTo' => $this->_emailTo,
					'MailCC' => $this->_emailCc,
					'Memo' => $this->_requestMemo,
				),
			),
		);
	}

	protected function _isResponseValid() {
		if (!parent::_isResponseValid()) {
			return false;
		}

		if (!$this->_isStatusOfOneRowValid('tt_wwsorder')) {
			return false;
		}

		$this->_checkReturnedFieldsOfOneRow('tt_wwsorder',
				array(
			'OrderNumber',
			'AmountNet', 'AmountVat', 'AmountTot',
			'Memo', 'xstatus',
				)
		);

		$this->_checkReturnedFieldsOfAllRows(
				'tt_wwspos', array(
			'Position',
			'ItemID', 'Qty', 'BackorderQty',
			'Memo',
				)
		);

		return true;
	}

	protected function _processResponse() {
		$this->_responseOrder = array(
			'wwsNumber' => $this->_soapResponse['tt_wwsorder'][0]->OrderNumber,
			'amountNet' => $this->_soapResponse['tt_wwsorder'][0]->AmountNet,
			'amountVat' => $this->_soapResponse['tt_wwsorder'][0]->AmountVat,
			'amountGross' => $this->_soapResponse['tt_wwsorder'][0]->AmountTot,
			'memo' => $this->_soapResponse['tt_wwsorder'][0]->Memo,
		);
		foreach ($this->_soapResponse['tt_wwspos'] as $idx => $wwsPos) {
			$this->_responseItems[] = array(
				'number' => $wwsPos->Position,
				'sku' => $wwsPos->ItemID,
				'qty' => $wwsPos->Qty,
				'backorderQty' => $wwsPos->BackorderQty,
				'memo' => $wwsPos->Memo,
			);
		}

		return true;
	}


	/**
	 * @return array
	 */
	public function getOrder() {
		return $this->_responseOrder;
	}

	/**
	 * @return string
	 */
	public function getOrderNumber() {
		return (string)$this->_responseOrder['wwsNumber'];
	}

	/**
	 * @return string
	 */
	public function getMemo() {
		return $this->_responseOrder['memo'];
	}

	/**
	 * @return array
	 */
	public function getItems() {
		return $this->_responseItems;
	}

	public function call() {
        $res = false;

        // Find out, if the latest request from shop has a successful response from WWS :
        $query  = "SELECT response_fetched_successfully FROM wws_insert_update_order_request";
        $query .= " WHERE wws_order_id LIKE '" . $this->_wwsOrderNumber . "' ORDER BY request_datetime DESC LIMIT 1";

        $resource = Mage::getSingleton('core/resource');
        $readConnection  = $resource->getConnection('core_read');

        $queryResult = $readConnection->query($query);

        if ($queryResult->rowCount() > 0) {
            foreach ($queryResult as $recordset) {
                if ($recordset['response_fetched_successfully'] == 0) {

                    // Send warning e-Mail to responsible Developer:
                    $mail = new Zend_Mail('utf-8');
                    $country = strtoupper(Mage::getStoreConfig('schrack/general/country'));
                    $mailText = 'Network Error (Data Inconsistence) COUNTRY = ' . $country . ' WWS-Order: '  . $this->_wwsOrderNumber;
                    try {
                        $mail->setFrom(Mage::getStoreConfig('web/secure/base_url'))
                             ->setSubject('Ship Order Failed ' . $this->_wwsOrderNumber)
                             ->setBodyHtml($mailText)
                             ->addTo( Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails') )
                             ->send();
                    } catch (Exception $ex) {
                        Mage::log($mailText . ' E-Mail transfer failed: ' . Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails'), null, 'ship_order_failed.log');
                        Mage::logException($ex);
                    }

                    // Show Error on checkout review page to customer:
                    throw Mage::exception('Schracklive_Wws', 'Network Error (Data Inconsistence)', self::EXCEPTION_ERROR);
                    return $res;
                }
            }
        }

        // Check, if ship_order send already in case of already logged in log-table
        // DLA 20201002: Do that check only for regular checkout orders (FlagOrder = 1)!
        if ( $this->_wwsOrderNumber ) {
            $currentFlagOrder = intval($this->_flagOrder);

            $query = "SELECT * FROM wws_ship_order_request WHERE wws_order_id LIKE '" . $this->_wwsOrderNumber . "'";
            $queryResult = $readConnection->query($query);

            // FOUND: already fired ship_order
            if ($queryResult->rowCount() > 0) {
                foreach ($queryResult as $recordset) {
                    $previousFlagOrder = intval($recordset['flag_order']);
                    if (($currentFlagOrder == 2 || $currentFlagOrder == 3) && $previousFlagOrder == 0) {
                        // The only 2 allowed cases: Do nothing!:
                        // flag_order = 2 -> Customer activates offer
                        // flag_order = 3 -> Employee is able to activate offer WITHOUT printing PDF in WWS (send via E-Mail to customer)
                    } else {
                        // All other cases are not allowed:
                        $logMsg  = 'WWS-Order-ID = ' . $this->_wwsOrderNumber;
                        $logMsg .= ' Current Flag-Order = ' . $currentFlagOrder;
                        $logMsg .= ' Previous Flag-Order (DB) = ' . $previousFlagOrder;
                        Mage::log($logMsg, null, 'already_ship_order_fired.log');
                        return $res;
                    }
                }

            }
        }

		if ( Mage::getSingleton('customer/session')->getCustomer()->getEmail() === 'pitt@twaroch.at' ) {
			throw Mage::exception('Schracklive_Wws', 'Customer pitt@twaroch.at cannot call ship_order.', self::EXCEPTION_SOAP_FAILURE);
		}
        else if ( $this->_wwsCustomerId === '666666' ) {
            throw Mage::exception('Schracklive_Wws', 'Customer 666666 cannot call ship_order.', self::EXCEPTION_SOAP_FAILURE);
        }
        else {
            $res = parent::call();
        }
        return $res;
    }
    
}
