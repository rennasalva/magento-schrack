<?php

class Finpin_Sezame_Model_Auth extends Finpin_Sezame_Model_Abstract
{
    /** @var  \SezameLib\Client */
    protected $_client;

    /** @var Finpin_Sezame_Helper_Data */
    protected $_helper;
    protected function _construct()
    {
        /** @var Finpin_Sezame_Helper_Data $helper */
        $this->_helper = Mage::helper('sezame');
        $this->_client = new \SezameLib\Client($this->getConfigParam('credentials/certificate'), $this->getConfigParam('credentials/privatekey'));
    }

    /**
     * @param $username
     *
     * @return \SezameLib\Request\Auth
     */
    public function login($username)
    {
        $client = $this->_client->authorize()->setUsername($username);

        $msg = $this->getConfigParam('settings/authmsg');
        if (strlen($msg)) {
            $client->setMessage($this->_helper->__($msg));
        }

        return $client;
    }

    /**
     * @param $id
     *
     * @return \SezameLib\Request\Status
     */
    public function status($id)
    {
        return $this->_client->status()->setAuthId($id);
    }

    /**
     * @param $username
     *
     * @return \SezameLib\Request\Auth
     */
    public function fraud($username)
    {
        $client = $this->_client->authorize()->setUsername($username)->setType('fraud')->setTimeout(1440);

        $msg = $this->getConfigParam('settings/fraudmsg');
        if (strlen($msg)) {
            $client->setMessage($this->_helper->__($msg));
        }

        return $client;
    }

}