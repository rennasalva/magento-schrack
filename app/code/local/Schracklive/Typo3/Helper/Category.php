<?php

/**
 * WARNING: This helper is stateful, which is not very beautiful for a helper, but widespread in magento.
 * We apoligize for the inconvenience.
 */

class Schracklive_Typo3_Helper_Category
{
    private $cacheTime = Schracklive_Typo3_Block_Abstract::CACHE_LIFETIME;
    private $loaded = false;
    private $html = '';
    private $schrackGroupId = null;

    public function getHtml()
    {
        $this->fetchAndParseOrigHtml();
        return $this->html;
    }

    public function setSchrackGroupId($schrackGroupId)
    {
        $this->schrackGroupId = $schrackGroupId;
    }

    public function getCacheTime()
    {
        return $this->cacheTime;
    }

    private function fetchAndParseOrigHtml()
    {
        // Try to fetch data from cache
        if (Mage::app()->useCache('typo3')) {
            $cachedData = Mage::app()->loadCache($this->getCacheKey());
            if ($cachedData) {
                $cachedData = unserialize($cachedData);
                if (is_array($cachedData)) {
                    $this->html = $cachedData['html'];
                }
                $this->loaded = true;
                return;
            }
        }
        // Fetch data from server
        if (!$this->loaded && $this->schrackGroupId) {
            // Only process categories with a depth of less than 4, as that is the sync limit to the CMS
            $depth = count(explode('/', $this->schrackGroupId));
            if ($depth < 4) {
                $uri =  // http://www.schrack.at/?contentEID=content_delivery_category_content_endpoint&category_id=87-01-01%2387-01-01%2F87-02-23
                    Mage::getStoreConfig('schrack/typo3/typo3url') .
                    '?contentEID=content_delivery_category_content_endpoint' .
                    '&category_id=' . urlencode($this->schrackGroupId);
                try {
                    /** @var $typo3helper Schracklive_Typo3_Helper_Data */
                    $typo3helper = Mage::helper('typo3');
                    $response = $typo3helper->getResponse($uri);
                    $responseStatus = $response->getStatus();
                } catch (Exception $e) {
                    $response = null;
                    $responseStatus = 500;
                }
                if (is_object($response) && $responseStatus === 200) {
                    $html = json_decode($response->getBody(), true);
                    $this->html = implode("\n", $html);
                } elseif ($responseStatus !== 404) {
                    // 404 likely means that the category is not synced to typo3, so only change the cache time for other errors
                    $this->cacheTime = 60;
                }
            }
            $this->loaded = true;
            // Store data in cache
            if (Mage::app()->useCache('typo3')) {
                $cachedData = array(
                    'html' => $this->html,
                );
                Mage::app()->saveCache(serialize($cachedData), $this->getCacheKey(), array('TYPO3'), $this->cacheTime);
            }
        }
    }

    protected function getCacheKey()
    {
        $key = 'schrack_typo3_category_' . $this->schrackGroupId;
        return $key;
    }
}
