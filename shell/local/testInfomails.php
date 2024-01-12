<?php

// This file is just to test mail sending from mail server for each separate country!!

require_once('shell.php');

class Schracklive_Shell_testInfomails extends Schracklive_Shell {

    public function run() {
        $mageTranslation = Mage::getModel('core/translate')
            ->setLocale(Mage::getStoreConfig('general/locale/code', Mage::getStoreConfig('schrack/shop/store')))
            ->init('frontend', true);

        $mailSubject = $mageTranslation->translate(array('New Full Prospect Registration Online Shop')) . ' [TEST]';
        $schrackNewsletter = 'Newsletter: ' . $mageTranslation->translate(array('Yes')) . '<br>';

        $mailText  = $mageTranslation->translate(array('New Full Prospect Registration Online Shop Headline')) . '<br><br>';
        $mailText .= '<b>' . $mageTranslation->translate(array('Personal Data')) . '</b>:<br>';
        $mailText .= $schrackNewsletter;
        $mailText .= $mageTranslation->translate(array('Gender')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('First Name')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('Last Name')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('Email')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('Phone')) . ': ' . 'TEST' . '<br><br>';
        $mailText .= '<b>' . $mageTranslation->translate(array('Company Information')) . '</b>:<br>';
        $mailText .= $mageTranslation->translate(array('Companyname')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('Companyname')) . ' #2: ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('Contact')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('VAT Identification Number')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('Phone')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('Fax')) . ': ' . 'TEST' . '<br><br>';
        $mailText .= '<b>' .  $mageTranslation->translate(array('billing address')) . '</b>:<br>';
        $mailText .= $mageTranslation->translate(array('street')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('Zip')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('City')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('Country')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('Website')) . ': ' . 'TEST' . '<br>';
        $mailText .= $mageTranslation->translate(array('Webshop Country')) . ': ' . strtoupper(Mage::getStoreConfig('schrack/general/country')) . '<br>';
        $mailText .= $mageTranslation->translate(array('Date')) . ': ' . date('Y-m-d H:i:s') . '<br>';

        $mail = new Zend_Mail('utf-8');
        $mail->setFrom(Mage::getStoreConfig('trans_email/ident_sales/email'), Mage::getStoreConfig('general/store_information/name'))
             ->setSubject($mailSubject)
             ->setBodyHtml($mailText);

        // Send mail schrack support employee(s):
        $checkoutEmailDestinationProspects = Mage::getStoreConfig('schrack/new_self_registration/checkoutEmailDestinationProspects');
        if ($checkoutEmailDestinationProspects) {
            if (stristr($checkoutEmailDestinationProspects, ';')) {
                // Send mail to multiple recipients, if seperated by semicolon:
                $emailRecipients = explode(';', preg_replace('/\s+/', '', $checkoutEmailDestinationProspects));
                foreach ($emailRecipients as $index => $emailRecipient) {
                    $mail->addTo($emailRecipient);
                }
            } else {
                $mail->addTo($checkoutEmailDestinationProspects);
            }

            Mage::log($mailSubject . ' -> ' . $mailText, null, 'prospect_mail_send.log');
            Mage::log('Mail Receiver -> ' . $checkoutEmailDestinationProspects, null, 'prospect_mail_send.log');
            $mail->send();
        }
    }
}

$shell = new Schracklive_Shell_testInfomails();
$shell->run();
