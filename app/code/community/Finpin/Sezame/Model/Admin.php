<?php

class Finpin_Sezame_Model_Admin extends Finpin_Sezame_Model_Abstract
{
    public function register()
    {
        /** @var Mage_Core_Model_Config */
        $config = Mage::getModel('core/config');

        $email = $this->getConfigParam('settings/email');
        if (!strlen($email)) {
            Mage::getSingleton('core/session')->addError('Recovery E-Mail is missing');
            return false;
        }

        $client = new \SezameLib\Client();

        $registerRequest = $client->register()->setEmail($email)->setName(Mage::getStoreConfig('general/store_information/name'));
        try {
            $registerResponse = $registerRequest->send();

            $config->saveConfig('sezame/credentials/clientcode', $registerResponse->getClientCode());
            $config->saveConfig('sezame/credentials/sharedsecret', $registerResponse->getSharedSecret());
        } catch (\Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }
    }

    public function sign()
    {
        /** @var Mage_Core_Model_Config */
        $config = Mage::getModel('core/config');

        $sharedsecret = $this->getConfigParam('credentials/sharedsecret');
        $clientcode = $this->getConfigParam('credentials/clientcode');
        if (!strlen($clientcode) || !strlen($sharedsecret)) {
            Mage::getSingleton('core/session')->addError('Please register and authorize first!');
            return false;
        }

        $email = $this->getConfigParam('settings/email');

        $client = new \SezameLib\Client();
        $csrKey = $client->makeCsr($clientcode, $email, null,
                                   Array(
                                       'countryName'            => Mage::getStoreConfig('general/store_information/merchant_country'),
                                       'organizationName'       => Mage::getStoreConfig('general/store_information/name'),
                                       'organizationalUnitName' => '-'
                                   ));

        $signRequest = $client->sign()->setCSR($csrKey->csr)->setSharedSecret($sharedsecret);

        try {
            $signResponse = $signRequest->send();

            $config->saveConfig('sezame/credentials/certificate', $signResponse->getCertificate());
            $config->saveConfig('sezame/credentials/privatekey', $csrKey->key);
            $config->saveConfig('sezame/credentials/csr', $csrKey->csr);
        } catch (\Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }

        Mage::getSingleton('core/session')->addSuccess('Your shop has been successfully registered!');
    }

    public function cancel()
    {
        /** @var Mage_Core_Model_Config */
        $config = Mage::getModel('core/config');

        $certificate = $this->getConfigParam('credentials/certificate');
        if (!strlen($certificate)) {
            Mage::getSingleton('core/session')->addError('Please register and authorize first!');
            return false;
        }

        $client = new \SezameLib\Client($this->getConfigParam('credentials/certificate'), $this->getConfigParam('credentials/privatekey'));

        try {
            $client->cancel()->send();

            $config->saveConfig('sezame/credentials/certificate', '');
            $config->saveConfig('sezame/credentials/csr', '');
            $config->saveConfig('sezame/credentials/privatekey', '');
            $config->saveConfig('sezame/credentials/sharedsecret', '');
            $config->saveConfig('sezame/credentials/clientcode', '');
            $config->saveConfig('sezame/settings/enabled', false);
        } catch (\Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());
        }

        Mage::getSingleton('core/session')->addSuccess('Sezame successfully cancelled!');
    }

    public function makeCsr()
    {
        /** @var Mage_Core_Model_Config */
        $config = Mage::getModel('core/config');

        $clientcode = $this->getConfigParam('credentials/clientcode');
        if (!strlen($clientcode)) {
            Mage::getSingleton('core/session')->addError('Client Code is missing');
            return false;
        }

        $client = new \SezameLib\Client();
        $csrKey = $client->makeCsr($clientcode, Mage::getStoreConfig('trans_email/ident_general/email'), null,
                                   Array(
                                       'countryName'            => Mage::getStoreConfig('general/store_information/merchant_country'),
                                       'organizationName'       => Mage::getStoreConfig('general/store_information/name'),
                                       'organizationalUnitName' => '-'
                                   ));

        $config->saveConfig('sezame/credentials/csr', $csrKey->csr);
        $config->saveConfig('sezame/credentials/privatekey', $csrKey->key);
    }
}