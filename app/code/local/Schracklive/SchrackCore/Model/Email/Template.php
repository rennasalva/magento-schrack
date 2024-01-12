<?php

class Schracklive_SchrackCore_Model_Email_Template extends Mage_Core_Model_Email_Template {

	/** @var Zend_Mail_Transport_Abstract */
	protected $_transport = null;

	public function setTransport(Zend_Mail_Transport_Abstract $transport) {
		$this->_transport = $transport;
	}

	/**
	 * Send mail to recipient
	 *
	 * @param   array|string       $email        E-mail(s)
	 * @param   array|string|null  $name         receiver name(s)
	 * @param   array         $variables   template variables
	 * @param   string|array  $cc		   E-mail Cc: (Schrack Live)
	 * @param   string|array  $bcc		   E-mail Bcc: (Schrack Live)
	 * @return  boolean
	 * */
	public function send($email, $name = null, array $variables = array(), $cc=null, $bcc=null, array $attachments = array()) {
		if (!$this->isValidForSend()) {
			Mage::logException(new Exception('This letter cannot be sent.')); // translation is intentionally omitted
			return false;
		}

		/// --------
		// $emails = array_values((array)$email);

		$emails_unprocessed = array_values((array)$email);
		foreach ($emails_unprocessed as $index => $emailReceiver) {
            // DEVELOPER-EMAIL:
            // Catch Emails and send to alternate recipients:
            if (preg_match('/testuser[0-9]{0,3}_.{2}@schrack.com$/', $emailReceiver)) {
				$emailReceiver = Mage::getStoreConfig('schrackdev/customer/mappingDevelopmentMails');
			}
			$emails[$index] = $emailReceiver;
		}
		/// --------

		$names = is_array($name) ? $name : (array)$name;
		$names = array_values($names);
		foreach ($emails as $key => $email) {
			if (!isset($names[$key])) {
				$names[$key] = substr($email, 0, strpos($email, '@'));
		}
		}

		$variables['email'] = reset($emails);
		$variables['name'] = reset($names);

		ini_set('SMTP', Mage::getStoreConfig('system/smtp/host'));
		ini_set('smtp_port', Mage::getStoreConfig('system/smtp/port'));

		$mail = $this->getMail();

		$setReturnPath = Mage::getStoreConfig(self::XML_PATH_SENDING_SET_RETURN_PATH);
		switch ($setReturnPath) {
			case 1:
				$returnPathEmail = $this->getSenderEmail();
				break;
			case 2:
				$returnPathEmail = Mage::getStoreConfig(self::XML_PATH_SENDING_RETURN_PATH_EMAIL);
				break;
			default:
				$returnPathEmail = null;
				break;
		}

		if ($returnPathEmail !== null) {
			$mailTransport = new Zend_Mail_Transport_Sendmail("-f".$returnPathEmail);
			Zend_Mail::setDefaultTransport($mailTransport);
		}

		foreach ($emails as $key => $email) {
			$mail->addTo($email, '=?utf-8?B?'.base64_encode($names[$key]).'?=');
			}

		/* Schrack Live START */
		if (is_array($cc)) {
			foreach ($cc as $emailCc) {
				$mail->addCc($emailCc);
			}
		} elseif ($cc) {
			$mail->addCc($cc);
		}

		if (is_array($bcc)) {
			foreach ($bcc as $emailBcc) {
				$mail->addBcc($emailBcc);
			}
		} elseif ($bcc) {
			$mail->addBcc($bcc);
		}
		/* Schrack Live END */

		$this->setUseAbsoluteLinks(true);
		$text = $this->getProcessedTemplate($variables, true);

		if ($this->isPlain()) {
			$mail->setBodyText($text);
		} else {
			$mail->setBodyHTML($text);
		}

		$mail->setSubject('=?utf-8?B?'.base64_encode($this->getProcessedTemplateSubject($variables)).'?=');
		$mail->setFrom($this->getSenderEmail(), $this->getSenderName());

		/* Schrack LIVE - mail transport */
		$transport = $this->_transport;
		if (!$transport) {
			$transportClass = Mage::getStoreConfig('system/smtp/zend_transport');
			if (class_exists($transportClass)) {
				$transport = new $transportClass();
			}
		}

        if (count($attachments)) {
            foreach ($attachments as $attachment) {
                $mail->addAttachment($attachment);
            }
        }
		try {
			$mail->send($transport);
			$this->_mail = null;
		} catch (Exception $e) {
			$this->_mail = null;
			Mage::logException($e);
			return false;
		}

		return true;
	}

	/**
	 * Send transactional email to recipient
	 *
	 * @param   int           $templateId
	 * @param   string|array  $sender sender information, can be declared as part of config path
	 * @param   string        $email  recipient email
	 * @param   string        $name   recipient name
	 * @param   array         $vars   varianles which can be used in template
	 * @param   int|null      $storeId
	 * @param null            $cc
	 * @param   string|array  $bcc    E-mail Bcc: (Schrack Live)
	 * @throws Mage_Core_Exception
	 * @return  Mage_Core_Model_Email_Template
	 */
	public function sendTransactional($templateId, $sender, $email, $name, $vars=array(), $storeId=null, $cc=null, $bcc=null) {
		$this->setSentSuccess(false);
		if (($storeId === null) && $this->getDesignConfig()->getStore()) {
			$storeId = $this->getDesignConfig()->getStore();
		}

		if (is_numeric($templateId)) {
			$this->load($templateId);
		} else {
			$localeCode = Mage::getStoreConfig('general/locale/code', $storeId);
			$this->loadDefault($templateId, $localeCode);
		}

		if (!$this->getId()) {
			throw Mage::exception('Mage_Core', Mage::helper('core')->__('Invalid transactional email code: '.$templateId));
		}

		if (!is_array($sender)) {
			$this->setSenderName(Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId));
			$this->setSenderEmail(Mage::getStoreConfig('trans_email/ident_'.$sender.'/email', $storeId));
		} else {
			$this->setSenderName($sender['name']);
			$this->setSenderEmail($sender['email']);
		}

		if (!isset($vars['store'])) {
			$vars['store'] = Mage::app()->getStore($storeId);
		}

		/* Schrack Live: cc/bcc */
		$this->setSentSuccess($this->send($email, $name, $vars, $cc, $bcc));
		return $this;
	}

}
