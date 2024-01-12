<?php

namespace SezameLib;

use Buzz\Browser;
use Buzz\Client\Curl;
use Buzz\Message\Response;


class Client
{
    protected $_http = null;
    protected $_endpoint;
    protected $_keyFile = null;
    protected $_certFile = null;

    public function __construct($cert = null, $key = null, $keypassword = null)
    {
        $this->_http = new Browser(new Curl());

        /** @var \Buzz\Client\Curl $client */
        $client = $this->_http->getClient();

        $client->setVerifyPeer(true);
        $client->setTimeout(10);

        if ($cert !== null && $key !== null) {
            if (!file_exists($cert) && is_string($cert)) {
                $this->_certFile = tempnam(sys_get_temp_dir(), 'szcert');
                file_put_contents($this->_certFile, $cert);
                $cert = $this->_certFile;
            }

            if (!file_exists($key) && is_string($key)) {
                $this->_keyFile = tempnam(sys_get_temp_dir(), 'szkey');
                file_put_contents($this->_keyFile, $key);
                $key = $this->_keyFile;
            }

            $client->setOption(CURLOPT_SSLCERT, $cert);
            $client->setOption(CURLOPT_SSLKEY, $key);
            if (strlen($keypassword))
                $client->setOption(CURLOPT_SSLKEYPASSWD, $keypassword);
        }

        if (isset($_SERVER['HTTP_USER_AGENT']))
            $client->setOption(CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);

        if (isset($_SERVER['REMOTE_ADDR']))
            $client->setOption(CURLOPT_HTTPHEADER, Array('X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR']));

        $this->setMode('prod');
    }

    public function __destruct()
    {
        if ($this->_keyFile !== null) unlink($this->_keyFile);
        if ($this->_certFile !== null) unlink($this->_certFile);
    }

    /**
     * @return \Buzz\Message\Response()
     */
    public function getResponse()
    {
        return $this->_http->getLastResponse();
    }

    public function setMode($mode)
    {
        if ($mode == 'dev')
            //$this->_endpoint = 'https://hqfrontend-finprin.dev.finprin.com/';
            $this->_endpoint = 'https://finprin-frontend.bretterklieber.com/';
        else
            $this->_endpoint = 'https://hqfrontend-finprin.finprin.com/';
    }

    public function register()
    {
        return new \SezameLib\Request\Register($this);
    }

    public function sign()
    {
        return new \SezameLib\Request\Sign($this);
    }

    public function cancel()
    {
        return new \SezameLib\Request\Cancel($this);
    }

    public function authorize()
    {
        return new \SezameLib\Request\Auth($this);
    }

    public function status()
    {
        return new \SezameLib\Request\Status($this);
    }

    public function link()
    {
        return new \SezameLib\Request\Link($this);
    }

    public function linkStatus()
    {
        return new \SezameLib\Request\LinkStatus($this);
    }


    public function post($endpoint, $postdata = Array())
    {
        try {
            return $this->_http->post($this->_endpoint . $endpoint, Array('Content-Type' => 'application/json'), json_encode($postdata));
        } catch (\Buzz\Exception\RequestException $e) {
            $ce = new Exception\Connection($e->getMessage(), $e->getCode());
            $ce->setRequest($e->getRequest());
            throw $ce;
        }
    }

    public function get($endpoint)
    {
        try {
            return $this->_http->get($this->_endpoint . $endpoint);
        } catch (\Buzz\Exception\RequestException $e) {
            $ce = new Exception\Connection($e->getMessage(), $e->getCode());
            $ce->setRequest($e->getRequest());
            throw $ce;
        }
    }

    public function checkResponse(Response $response, $notFoundisErr = false)
    {
        if ($response->isEmpty())
            throw new \SezameLib\Exception\Response('Got empty response', $response->getStatusCode());

        if ($response->isOk())
            return json_decode($response->getContent());

        if ($response->isNotFound() && !$notFoundisErr)
            return null;

        if (strlen($response->getContent())) {
            $errorInfo = json_decode($response->getContent());
            if ($errorInfo !== null) {
                $pe = new Exception\Parameter($errorInfo->message, $response->getStatusCode());
                $pe->setErrorInfo($errorInfo);
                throw $pe;
            }
        }

        throw new \SezameLib\Exception\Response($response->getReasonPhrase(), $response->getStatusCode());

    }

    /**
     * @param string $clientcode obtained from registration
     * @param string $email recovery e-mail
     * @param null|string $keyPassword private key password
     * @param array $dnParams
     *
     * @return \stdClass
     */
    public function makeCsr($clientcode, $email, $keyPassword = null, $dnParams = Array())
    {
        $dn = Array(
            "countryName"            => '-',
            "stateOrProvinceName"    => '-',
            "localityName"           => '-',
            "organizationName"       => '-',
            "organizationalUnitName" => '-',
            "commonName"             => $clientcode,
            "emailAddress"           => $email
        );

        foreach ($dnParams as $p => $v)
        {
            if (array_key_exists($p, $dn) && strlen($v))
                $dn[$p] = $v;
        }

        $privkey = openssl_pkey_new(Array(
                                        "digest_alg"       => "sha512",
                                        "private_key_bits" => 2048,
                                        "private_key_type" => OPENSSL_KEYTYPE_RSA
                                    ));

        $csr = openssl_csr_new($dn, $privkey);
        openssl_csr_export($csr, $csrout);
        openssl_pkey_export($privkey, $pkeyout, $keyPassword);

        $ret = new \stdClass();
        $ret->csr = $csrout;
        $ret->key = $pkeyout;
        return $ret;
    }
}
