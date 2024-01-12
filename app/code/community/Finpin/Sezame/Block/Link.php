<?php

class Finpin_Sezame_Block_Link extends Mage_Core_Block_Template
{
    /** @var \Endroid\QrCode\QrCode */
    protected $_qrcode;

    protected $_message = null;

    public function setQrCode(\Endroid\QrCode\QrCode $qrcode)
    {
        $this->_qrcode = $qrcode;
    }

    public function getQrCode()
    {
        return $this->_qrcode;
    }

    public function setMessage($message)
    {
        $this->_message = $message;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function getLinkUrl()
    {
        return Mage::getUrl('sezame/link/link');
    }

}