<?php

class Finpin_Sezame_Model_Link extends Finpin_Sezame_Model_Abstract
{
    /**
     * @param $username
     *
     * @return \Endroid\QrCode\QrCode
     */
    public function qrCode($username)
    {
        $sezameClient = new \SezameLib\Client($this->getConfigParam('credentials/certificate'), $this->getConfigParam('credentials/privatekey'));
        $linkRequest = $sezameClient->link()->setUsername($username);
        $response = $linkRequest->send();

        $qrCode = $response->getQrCode($username);
        $qrCode->setPath(__DIR__ . '/../assets/data');
        $qrCode->setImagePath(__DIR__ . '/../assets/image');
        $qrCode->setLabelFontPath(__DIR__ . '/../assets/font/opensans.ttf');

        return $qrCode;
    }

    public function status($username)
    {
        $sezameClient = new \SezameLib\Client($this->getConfigParam('credentials/certificate'), $this->getConfigParam('credentials/privatekey'));
        $response = $sezameClient->linkStatus()->setUsername($username)->send();
        return $response->isLinked();
    }
}