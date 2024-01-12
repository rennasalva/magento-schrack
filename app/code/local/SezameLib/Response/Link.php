<?php

namespace SezameLib\Response;

class Link extends Generic
{
	public function isDuplicate()
	{
		return $this->_response->getStatusCode() == 409;
	}

    protected function getId()
	{
		return $this->_data->id;
	}

    protected function getClientCode()
    {
        return $this->_data->clientcode;
    }

    public function getQrCodeData($username)
    {
        return json_encode(Array(
            'id'       => $this->getId(),
            'username' => $username,
            'client'   => $this->getClientCode()
        ));
    }

    /**
     * @param $username
     *
     * @return \Endroid\QrCode\QrCode
     */
    public function getQrCode($username)
    {
        $qrCodeData = $this->getQrCodeData($username);

        $qrCode = new \Endroid\QrCode\QrCode();
        $qrCode
            ->setText($qrCodeData)
            ->setSize(300)
            ->setPadding(10)
            ->setErrorCorrection('high')
            ->setForegroundColor(array('r' => 0, 'g' => 0, 'b' => 0, 'a' => 0))
            ->setBackgroundColor(array('r' => 255, 'g' => 255, 'b' => 255, 'a' => 0));

        return $qrCode;
    }

    public function isOk()
	{
		return is_object($this->_data);
	}
}
