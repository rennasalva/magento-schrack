<?php

class Schracklive_Wws_Model_Message_Pool {

	/** @var Mage_Core_Model_Message_Collection */
	protected $_messages;
	/** @var array */
	protected $_configuration;
	/** @var Schracklive_Wws_Helper_Mailer */
	protected $_mailService;

	public function __construct(array $arguments) {
		$this->_messages = $arguments['messages'];
		$this->_configuration = $arguments['configuration'];
	}

	public function getMessages() {
		return $this->_messages;
	}

	public function getConfiguation() {
		return $this->_configuration;
	}

	/**
	 * @param array $wwsCodes
	 * @return bool
	 */
	public function hasMessagesWithWwsCodes(array $wwsCodes) {
		foreach ($this->_messages->getItems() as $message) {
			/* @var $message Mage_Core_Model_Message_Abstract */
			if ($wwsCode = $this->_extractWwsCodeFromIdentifier($message->getIdentifier())) {
				if (in_array($wwsCode, $wwsCodes)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @param $identifier
	 * @return int
	 */
	protected function _extractWwsCodeFromIdentifier($identifier) {
		$matches = array();
		if (preg_match('/^WWS-(\d+)$/', $identifier, $matches)) {
			return (int)$matches[1];
		}
		return 0;
	}

	/**
	 * @return Mage_Core_Model_Message_Collection
	 */
	public function getTranslatedMessages() {
		$translatedMessages = Mage::getModel('core/message_collection');
		$filterStatistics = array(
			Mage_Core_Model_Message::ERROR => 0,
			Mage_Core_Model_Message::WARNING => 0,
			Mage_Core_Model_Message::NOTICE => 0,
			Mage_Core_Model_Message::SUCCESS => 0,
		);
		foreach ($this->_messages->getItems() as $message) {
			$translatedMessage = null;
			if ($wwsCode = $this->_extractWwsCodeFromIdentifier($message->getIdentifier())) {
				$translatedMessage = $this->_translateWwsMessage($filterStatistics, $message->getType(), $wwsCode);
				if (!$translatedMessage && Mage::getStoreConfig('schrackdev/development/debug_messages')) {
					$translatedMessage = $message;
				}
			} else {
				$translatedMessage = $message; // show non-WWS messages
			}
			if ($translatedMessage) {
				$translatedMessages->addMessage($translatedMessage);
			}
		}
		$this->_addDefaultMessages($translatedMessages, $filterStatistics);
		return $translatedMessages;
	}

	protected function _translateWwsMessage(&$filterStatistics, $type, $wwsCode) {
		$message = null;
		$text = $this->_configuration->getMessageTextForCode($wwsCode);
		if ($text) {
			$message = Mage::getSingleton('core/message')->$type($text);
			$message->setIdentifier('WWS-'.$wwsCode);
		} else {
			$filterStatistics[$type]++;
		}
		return $message;
	}

	protected function _addDefaultMessages(Mage_Core_Model_Message_Collection $translatedMessages, array $filterStatistics) {
		$message = null;
		if ($filterStatistics[Mage_Core_Model_Message::ERROR]) {
			$text = $this->_configuration->getMessageTextForCode(Schracklive_Wws_Model_Signal_Configuration::DEFAULT_ERROR_CODE);
			if (!$text) {
				$text = 'There was an error processing your order. Please contact us.';
			}
			$message = Mage::getSingleton('core/message')->error($text);
			$message->setIdentifier('WWS-'.Schracklive_Wws_Model_Signal_Configuration::DEFAULT_ERROR_CODE);
		} elseif ($filterStatistics[Mage_Core_Model_Message::WARNING]) {
			$text = $this->_configuration->getMessageTextForCode(Schracklive_Wws_Model_Signal_Configuration::DEFAULT_WARNING_CODE);
			if (!$text) {
				$text = 'There was a problem processing your order. Please try again.';
			}
			$message = Mage::getSingleton('core/message')->warning($text);
			$message->setIdentifier('WWS-'.Schracklive_Wws_Model_Signal_Configuration::DEFAULT_WARNING_CODE);
		}
		if ($message) {
			$translatedMessages->addMessage($message);
		}
	}

	public function sendMails(array $mailArguments) {
		if (Mage::getStoreConfig('schrackdev/customer/testEmails')) {
			$mailArguments['transport'] = Mage::getModel('schrackcore/email_transport_test');
			$mailArguments['transport']->setMessages($this->_messages);
		} elseif (Mage::getStoreConfig('schrackdev/customer/disableEmails')) {
			return;
		}

		foreach ($this->_configuration->getCodesWithMailAction() as $code) {
			if ($this->_messages->getMessageByIdentifier('WWS-'.$code)) {
				$mailArguments['subject'] = $this->_configuration->getMailSubjectForCode($code);
				$mailArguments['body'] = $this->_configuration->getMailBodyForCode($code);
				$mailer = $this->_getMailService();
				if ($mailer) {
					if ($mailer->send($mailArguments) && !Mage::getStoreConfig('schrackdev/development/debug_messages')) {
						$this->_messages->deleteMessageByIdentifier('WWS-'.$code);
					}
				}
			}
		}
	}

	/**
	 * @return bool
	 */
	public function signalsDeactivateQuote() {
		if ($this->hasMessagesWithWwsCodes($this->_configuration->getCodesDroppingRequest())) {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function signalsRetryWithEmptyOrderNumber() {
		if ($this->hasMessagesWithWwsCodes($this->_configuration->getCodesForcingRetryWithEmptyOrderNumber())) {
			return true;
		}
		return false;
	}

	protected function _getMailService() {
		if (!$this->_mailService) {
			$this->_mailService = Mage::helper('wws/mailer');
		}
		return $this->_mailService;
	}

	public function setMailServiceForTesting(Schracklive_Wws_Helper_Mailer $mailer) {
		$this->_mailService = $mailer;
	}

}
