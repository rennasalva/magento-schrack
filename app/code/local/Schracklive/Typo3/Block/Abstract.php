<?php

abstract class Schracklive_Typo3_Block_Abstract extends Mage_Core_Block_Abstract {
	const CACHE_GROUP = 'typo3';
	const CACHE_TAG = 'TYPO3';
	const CACHE_LIFETIME = 3600;

	protected function _construct() {
		parent::_construct();

		try {
			// all other cache handling is done automatically for this block
			$this->addData(array(
				'identifier' => $this->getIdentifier(),
				'cache_lifetime' => Schracklive_Typo3_Block_Abstract::CACHE_LIFETIME,
				'cache_tags' => array(self::CACHE_TAG),
				'cache_key' => $this->getIdentifier()
			));
		} catch (Exception $e) {
			//Mage::logException($e);
		}
	}

	abstract protected function getIdentifier();

	/*
	 * overwritten method of the parent class
	 */

	protected function _toHtml() {
		$html = [];

        // http://www.schrack.at/?eID=user_schrack_magentoconnect_redirect&country=at&tid=87-01-04%2387-01-04%2F37
        $uri =  Mage::getStoreConfig('schrack/typo3/typo3url') .
            '?contentEID=content_delivery_category_content_endpoint' .
            '&category_id=' . urlencode($this->getIdentifier());
		try {
			/** @var $typo3helper Schracklive_Typo3_Helper_Data */
			$typo3helper = Mage::helper('typo3');
			$response = $typo3helper->getResponse($uri);
			$responseStatus = $response->getStatus();
		} catch (Exception $e) {
			$response = null;
			$responseStatus = 500;
		}
		if (is_object($response) && $responseStatus == 200) {
			$responseBody = json_decode($response->getBody(), true);
            $html = implode("\n", $html);
		} else {
			$this->setData('cache_lifetime', 60); // Lower cache time of block cache
		}

		return $html;
	}

	protected function _afterToHtml($html) {
		$html = parent::_afterToHtml($html);
		return Mage::helper('typo3/sessionUrl')->replacePlaceholderUrls($html);
	}
}
