<?php

class Schracklive_Wws_Helper_Mailer {

    /**
     * @param array $mailArguments
     * @param array $attachments array of Zend_Mime_Part
     * @return mixed
     */
    public function send(array $mailArguments, array $attachments = array()) {
		$storeId = Mage::getDesign()->getStore();
		$emailTemplate = Mage::getModel('core/email_template')
						 ->setDesignConfig(array(
							'area' => 'frontend',
							'store' => $storeId,
						));
		$sender = Mage::getStoreConfig('schrack/wws/limit_email_identity');

		$templateText = $mailArguments['body'];
		$searchString = 'skin/frontend/schrack/default/images/schrack_email_header.gif';

		if (stristr($templateText, $searchString)) {
			// Add protocol to schrack_email_header.gif, because of Outlook loading problems:
			$templateText = str_replace('//', 'http://', $templateText);
			Mage::log($templateText, null, 'wws_emailer.log');
		}

		$emailTemplate
				->setSenderName(Mage::getStoreConfig('trans_email/ident_'.$sender.'/name', $storeId))
				->setSenderEmail(Mage::getStoreConfig('trans_email/ident_'.$sender.'/email', $storeId))
				->setTemplateType(Mage_Core_Model_Email_Template::TYPE_HTML)
				->setTemplateSubject($mailArguments['subject'])
				->setTemplateText($templateText);

		if (isset($mailArguments['transport'])) {
			$emailTemplate->setTransport($mailArguments['transport']);
		}

		return $emailTemplate->send($mailArguments['to'], null, $mailArguments['templateVars'], $mailArguments['cc'], $mailArguments['bcc'], $attachments);
	}

}
