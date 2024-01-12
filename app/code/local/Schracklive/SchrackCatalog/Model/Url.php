<?php

class Schracklive_SchrackCatalog_Model_Url extends Mage_Catalog_Model_Url {


	/**
	 * Get requestPath that was not used yet.
	 *
	 * Will try to get unique path by adding -1 -2 etc. between url_key and optional url_suffix
	 *
	 * @param int $storeId
	 * @param string $requestPath
	 * @param string $idPath
	 * @return string
	 */
	public function getUnusedPath($storeId, $requestPath, $idPath) {
	    if ( $idPath == 'category/1760471' || $idPath == 'product/11187/1758568' || $idPath == '1557966733.6868_1760471' ) {
	        echo '';
        }
		if (strpos($idPath, 'product') !== false) {
			$suffix = $this->getProductUrlSuffix($storeId);
		} else {
			$suffix = $this->getCategoryUrlSuffix($storeId);
		}
		if (empty($requestPath)) {
			$requestPath = '-';
		} elseif ($requestPath == $suffix) {
			$requestPath = '-'.$suffix;
		}

		/**
		 * Validate maximum length of request path - schrack4you: allow UTF-8 URLs
		 */
		if (mb_strlen($requestPath, 'utf-8') > self::MAX_REQUEST_PATH_LENGTH + self::ALLOWED_REQUEST_PATH_OVERFLOW) {
			$requestPath = mb_substr($requestPath, 0, self::MAX_REQUEST_PATH_LENGTH, 'utf-8');
		}

		if (isset($this->_rewrites[$idPath])) {
			$this->_rewrite = $this->_rewrites[$idPath];
			if ($this->_rewrites[$idPath]->getRequestPath() == $requestPath) {
				return $requestPath;
			}
		} else {
			$this->_rewrite = null;
		}

		$rewrite = $this->getResource()->getRewriteByRequestPath($requestPath, $storeId);
		if ($rewrite && $rewrite->getId()) {
			if ($rewrite->getIdPath() == $idPath) {
				$this->_rewrite = $rewrite;
				return $requestPath;
			}
			$idParts = explode('/', $idPath);
			if (isset($idParts[0]) && $idParts[0] == 'category' && isset($idParts[1]) && $rewrite->getCategoryId() == $idParts[1]) {
				$this->getResource()->deleteRewrite($requestPath, $storeId);
				return $this->getUnusedPath($storeId, $requestPath, $idPath);
			}
			// match request_url letterNumberSymbol(-12)(.html) pattern - schrack4you: allow UTF-8 URLs
			$match = array();
			if (!preg_match('#^([\p{L}\p{N}\p{S}-/]+?)(-([0-9]+))?('.preg_quote($suffix).')?$#ui', $requestPath, $match)) {
				return $this->getUnusedPath($storeId, '-', $idPath);
			}
			$requestPath = $match[1].(isset($match[3]) ? '-'.($match[3] + 1) : '-1').(isset($match[4]) ? $match[4] : '');
			return $this->getUnusedPath($storeId, $requestPath, $idPath);
		} else {
			return $requestPath;
		}
	}


	/**
	 * Get unique product request path
	 *
	 * @param   Varien_Object $product
	 * @param   Varien_Object $category
	 * @return  string
	 */
	public function getProductRequestPath($product, $category) {
		if ($product->getUrlKey() == '') {
			$urlKey = $this->getProductModel()->formatUrlKey($product->getName());
		} else {
			$urlKey = $this->getProductModel()->formatUrlKey($product->getUrlKey());
		}
		$storeId = $category->getStoreId();
		$suffix = $this->getProductUrlSuffix($storeId);
		$idPath = $this->generatePath('id', $product, $category);



	    if ( $idPath == 'product/11187/1758568' ) {
	        echo '';
        }



		/**
		 * Prepare product base request path
		 */
		if ($category->getLevel() > 1) {
			// To ensure, that category has path either from attribute or generated now
			$this->_addCategoryUrlPath($category);
			$categoryUrl = Mage::helper('catalog/category')->getCategoryUrlPath($category->getUrlPath(), false, $storeId);
			$requestPath = $categoryUrl.'/'.$urlKey;
		} else {
			$requestPath = $urlKey;
		}

		/**
		 * Validate maximum length of request path - schrack4you: allow UTF-8 URLs
		 */
		if (mb_strlen($requestPath, 'utf-8') > self::MAX_REQUEST_PATH_LENGTH + self::ALLOWED_REQUEST_PATH_OVERFLOW) {
			$requestPath = mb_substr($requestPath, 0, self::MAX_REQUEST_PATH_LENGTH, 'utf-8');
			if ( mb_substr($requestPath, -1,1, 'utf-8') == '/' ) {
    			$requestPath = mb_substr($requestPath, 0, self::MAX_REQUEST_PATH_LENGTH - 1, 'utf-8');
	        }
		}

		$this->_rewrite = null;
		/**
		 * Check $requestPath should be unique
		 */
		if (isset($this->_rewrites[$idPath])) {
			$this->_rewrite = $this->_rewrites[$idPath];
			$existingRequestPath = $this->_rewrites[$idPath]->getRequestPath();
			$existingRequestPath = str_replace($suffix, '', $existingRequestPath);

			if ($existingRequestPath == $requestPath) {
				return $requestPath.$suffix;
			}
			/**
			 * Check if existing request past can be used
			 */
			if ($product->getUrlKey() == '' && !empty($requestPath)
					&& strpos($existingRequestPath, $requestPath) !== false
			) {
				$existingRequestPath = str_replace($requestPath, '', $existingRequestPath);
				if (preg_match('#^-([0-9]+)$#i', $existingRequestPath)) {
					return $this->_rewrites[$idPath]->getRequestPath();
				}
			}
			/**
			 * check if current generated request path is one of the old paths
			 */
			$fullPath = $requestPath.$suffix;
			$finalOldTargetPath = $this->getResource()->findFinalTargetPath($fullPath, $storeId);
			if ($finalOldTargetPath && $finalOldTargetPath == $idPath) {
				$this->getResource()->deleteRewrite($fullPath, $storeId);
				return $fullPath;
			}
		}
		/**
		 * Check 2 variants: $requestPath and $requestPath . '-' . $productId
		 */
		$validatedPath = $this->getResource()->checkRequestPaths(
				array($requestPath.$suffix, $requestPath.'-'.$product->getId().$suffix), $storeId
		);

		if ($validatedPath) {
			return $validatedPath;
		}
		/**
		 * Use unique path generator
		 */
		return $this->getUnusedPath($storeId, $requestPath.$suffix, $idPath);
	}

	/**
	 * Refresh all product rewrites for designated store
	 *
	 * @param int $storeId
	 * @return Mage_Catalog_Model_Url
	 * @see Mage_Catalog_Model_Url::refreshProductRewrites
	 */
	public function refreshProductRewrites($storeId) {
		$this->_categories      = array();
		$storeRootCategoryId    = $this->getStores($storeId)->getRootCategoryId();
		$storeRootCategoryPath  = $this->getStores($storeId)->getRootCategoryPath();
		$this->_categories[$storeRootCategoryId] = $this->getResource()->getCategory($storeRootCategoryId, $storeId);

		$lastEntityId = 0;
		$process = true;

		while ($process == true) {
			$products = $this->getResource()->getProductsByStore($storeId, $lastEntityId);
			if (!$products) {
				$process = false;
				break;
			}

			$this->_rewrites = $this->getResource()->prepareRewrites($storeId, false, array_keys($products));

			$loadCategories = array();
			foreach ($products as $product) {
				foreach ($product->getCategoryIds() as $categoryId) {
					if (!isset($this->_categories[$categoryId])) {
						$loadCategories[$categoryId] = $categoryId;
					}
				}
			}

			if ($loadCategories) {
				foreach ($this->getResource()->getCategories($loadCategories, $storeId) as $category) {
					$this->_categories[$category->getId()] = $category;
				}
			}

			foreach ($products as $product) {
				// P2N Start: Skip URL creation of deactivated products
				if ($product->getStatus() != Mage_Catalog_Model_Product_Status::STATUS_ENABLED) {
					continue;
				}
				// P2N End: Skip URL creation of deactivated products
				$this->_refreshProductRewrite($product, $this->_categories[$storeRootCategoryId]);
				foreach ($product->getCategoryIds() as $categoryId) {
					if ($categoryId != $storeRootCategoryId && isset($this->_categories[$categoryId])) {
						if (strpos($this->_categories[$categoryId]['path'], $storeRootCategoryPath.'/') !== 0) {
							continue;
						}
						$this->_refreshProductRewrite($product, $this->_categories[$categoryId]);
					}
				}
			}

			unset($products);
			$this->_rewrites = array();
		}

		$this->_categories = array();
		return $this;
	}

}
