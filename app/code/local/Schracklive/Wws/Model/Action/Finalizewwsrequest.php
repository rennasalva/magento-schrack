<?php

class Schracklive_Wws_Model_Action_Finalizewwsrequest extends Schracklive_Wws_Model_Action_Wwsrequest {

	protected $_requestName = 'shiporder';

	/**
	 * @param array $arguments
	 * @throws InvalidArgumentException
	 */
	public function __construct(array $arguments) {
		parent::__construct($arguments);

		$checkedArguments = $this->_checkArguments($arguments, array(
			'flagOrder' => array('int', 1),
            'eMailAddress' => array('string',null),
            'paymentInfo' => array('string', null),
            'memo' => array( 'string', null ),
		));

		if (!$this->_quote->getSchrackWwsOrderNumber()) {
			throw new InvalidArgumentException('Argument "quote" has no order number.');
		}
        $this->_eMailAddress = $checkedArguments['eMailAddress'];
		$this->_flagOrder = $checkedArguments['flagOrder'];
        $this->_paymentInfo = $checkedArguments['paymentInfo'];
        $this->_memo = $checkedArguments['memo'];
	}

	protected function _buildArguments($memo = array()) {
		$paymentInfo = array();
		$mailTo = $this->_eMailAddress;
		$mailCc = '';

        if( $this->_paymentInfo ) {            
            $paymentInfo[] = $this->_paymentInfo;
        }
        else {
            $payment = $this->_quote->getPayment();
            if ($payment->hasAdditionalInformation()) {
                foreach ($payment->getAdditionalInformation() as $key => $value) {
                    $paymentInfo[] = strtoupper($key).'='.$value;
                }
            }
        }

		// Get quote and get customer type: if NEW PROSPECT or GUEST, then override the code block:
		if ( !in_array($this->_quote->getSchrackCustomertype(), array('newProspect', 'guest')) ) {
			if ($this->_customer->isSystemContact()) {
				if ( ! $mailTo )
					$mailTo = $this->_customer->getAccount()->getEmail();
				if ($this->_customer->getAdvisor()) {
					$mailCc = $this->_customer->getAdvisor()->getEmail();
				} else {
					Mage::log('System contact #'.$this->_customer->getId().' has no advisor.', Zend_Log::ERR);
				}

				if (is_object($this->_loggedInCustomer)) {
					if ($mailTo) {
						$mailCc .= ( $mailCc ? ';' : '').$this->_loggedInCustomer->getEmail();
					} else {
						$mailTo = $this->_loggedInCustomer->getEmail();
						array_push( $memo, 'WARNUNG=Kunde bekommt keine AB per Mail.') ;
					}
				}
			} else {
				if ( ! $mailTo )
					$mailTo = $this->_customer->getEmail();
				if ($this->_customer->getAdvisor()) {
					$mailCc = $this->_customer->getAdvisor()->getEmail();
				} else {
					Mage::log('Customer #'.$this->_customer->getId().' has no advisor.', Zend_Log::ERR);
					if ($this->_customer->getAccount()->getAdvisor()) {
						$mailCc = $this->_customer->getAccount()->getAdvisor()->getEmail();
					} else {
						Mage::log('Account #'.$this->_customer->getAccount()->getId().' has no advisor.', Zend_Log::ERR);
					}
				}
			}
		}
        
        if( $this->_memo ) {
            array_push( $memo, $this->_memo );
        }
        
		$this->_requestArguments += array(
			'wwsOrderNumber' => $this->_quote->getSchrackWwsOrderNumber(),
			'flagOrder' => $this->_flagOrder,
			'wwsCustomerId' => $this->_quote->getSchrackWwsCustomerId(),
			'paymentInfo' => join("\n", $paymentInfo),
			'emailTo' => $mailTo,
			'emailCc' => $mailCc,
		);

		parent::_buildArguments($memo);
	}

	protected function _buildResponse() {
		$this->_checkOrderResponse();
		$this->_checkItemsResponse();

		$this->_quote->setSchrackWwsShipMemo($this->_request->getMemo());

		foreach ($this->_request->getItems() as $idx => $returnedItem) {
			$codes = $this->_parseMemoIntoMessages($returnedItem['memo']);
			if (isset($codes[self::CODE_CHARGES])) {
				continue;
			}
			$this->_items[$idx]->setSchrackWwsShipMemo($returnedItem['memo']);
		}

		$this->_response = $this->_messages;
	}

	protected function _checkOrderResponse() {
		parent::_checkOrderResponse();

		if ($this->_quote->getSchrackWwsOrderNumber() != $this->_request->getOrderNumber()) {
			throw $this->_requestException("order number mismatch: got {$this->_request->getOrderNumber()}, expected {$this->_quote->getSchrackWwsOrderNumber()}");
		}
	}

	protected function _checkItemsResponse() {
		$returnedItems = $this->_request->getItems();

		if (count($this->_items) > count($returnedItems)) {
			//throw $this->_requestException('Number of items mismatch: got '.count($returnedItems).', expected '.count($this->_items).' or more');
		}
		foreach ($returnedItems as $idx => $returnedItem) {
			$codes = $this->_parseMemoIntoMessages($returnedItem['memo']);
			if (isset($codes[self::CODE_CHARGES])) {
				continue;
			}

			if (!isset($this->_items[$idx])) {  
				throw $this->_requestException('WWS sent an unexpected item: '. $returnedItem['sku']);
			}

			$requestedItem = $this->_items[$idx];
			if ($requestedItem->getSku() != $returnedItem['sku']) {
				throw $this->_requestException("ItemID/SKU mismatch: got {$returnedItem['sku']}, expected {$requestedItem->getSku()}");
			}
			if ($requestedItem->getQty() != $returnedItem['qty'] 
                    && floatval($returnedItem['qty']) + floatval($returnedItem['backorderQty']) != $requestedItem->getQty()) {
				throw $this->_requestException("Quantity mismatch: got qty {$returnedItem['qty']}, backorderQty {$returnedItem['backorderQty']}; expected {$requestedItem->getQty()}");
			}
            /* DLA, 20140428: removed because recently corrected WWS response will cause (wrong) exception
			if ($returnedItem['qty'] < $returnedItem['backorderQty']) {
				throw $this->_requestException("Backorder error: backorder qty {$returnedItem['backorderQty']} higher than order qty {$returnedItem['getQty']}");
			}
             */
		}
	}

}
