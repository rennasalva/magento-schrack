<?php

/**
 *  * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 */

//require_once 'Customweb/Core/Url.php';
//require_once 'Customweb/Core/Http/Client/Factory.php';
//require_once 'Customweb/Core/Http/Request.php';
//require_once 'Customweb/Core/Http/Response.php';
//require_once 'Customweb/Core/Logger/Factory.php';

final class Customweb_PayUnity_Request {
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_DELETE = 'DELETE';

	private $logger;
	
	/**
	 *
	 * @var string
	 */
	private $url;
	
	private $configuration;
	
	/**
	 *
	 * @var string
	 */
	private $method = self::METHOD_POST;
	
	/**
	 *
	 * @var array
	 */
	private $data = null;

	/**
	 *
	 * @param string $url
	 */
	public function __construct($url, Customweb_PayUnity_Configuration $configuration){
		$this->url = $url;
		$this->configuration = $configuration;
		$this->logger = Customweb_Core_Logger_Factory::getLogger(get_class($this));
	}

	/**
	 *
	 * @return string
	 */
	public function getUrl(){
		return $this->url;
	}

	/**
	 *
	 * @return string
	 */
	public function getMethod(){
		return $this->method;
	}

	/**
	 *
	 * @param string $method
	 * @return Customweb_PayUnity_Request
	 */
	public function setMethod($method){
		$this->method = strtoupper($method);
		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function getData(){
		return $this->data;
	}

	/**
	 *
	 * @param array $data
	 * @return Customweb_PayUnity_Request
	 */
	public function setData(array $data){
		$this->data = $data;
		return $this;
	}

	/**
	 *
	 * @return array
	 * @throws Exception
	 */
	public function send($debugMsg = false){
		$url = new Customweb_Core_Url($this->url);
		
		$request = new Customweb_Core_Http_Request();
		if($this->configuration->isAccessTokenConfigured()){
			$request->appendHeader('Authorization: Bearer ' . $this->configuration->getAccessToken());
		}		
		
		if ($this->method == self::METHOD_GET || $this->method == self::METHOD_DELETE) {
			$url->appendQueryParameters($this->data);
		}
		elseif ($this->method == self::METHOD_POST) {
			$request->setContentType('application/x-www-form-urlencoded');
			$request->setBody($this->data);
		}
		$request->setMethod($this->method);
		$request->setUrl($url);
		if($debugMsg){
			$this->logger->logDebug($debugMsg." Request:\n".$request->toSendableString(false));
		}		
		$client = Customweb_Core_Http_Client_Factory::createClient();
		$response = new Customweb_Core_Http_Response($client->send($request));
		if($debugMsg){
			$this->logger->logDebug($debugMsg." Response:\n".$response->toString());
		}
		$headers = array_change_key_case($response->getParsedHeaders(), CASE_LOWER);
		if (isset($headers['content-type'])) {
			if (strpos(end($headers['content-type']), 'application/json') !== false) {
				return json_decode(trim($response->getBody()));
			}
		}
		return $response->getParsedBody();
	}
	
}