<?php

class Schracklive_SchrackCore_Model_Email_Transport_Test extends Zend_Mail_Transport_Abstract {

	/** @var Mage_Core_Model_Message_Collection */
	protected $_messages = null;

	protected function _sendMail() {
		if ($this->_messages) {
			$this->_messages->addMessage(Mage::getModel('core/message')->notice('<div class="schracklive-show-mails">MAIL HEADERS:<br/>'.nl2br(htmlspecialchars($this->header)).'<br/>MAIL BODY: '.mb_strlen($this->body, 'utf-8').' characters</div>'));
		}
	}

	public function setMessages(Mage_Core_Model_Message_Collection $messages) {
		$this->_messages = $messages;
	}

}
