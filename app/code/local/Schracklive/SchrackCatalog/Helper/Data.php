<?php

class Schracklive_SchrackCatalog_Helper_Data extends Mage_Catalog_Helper_Data {

	/**
	 * Small hack to base implementation for discontinued products categories
	 * getParentCategories doesn't load EAV attributes which are used to detect the category type
	 * As such, replace the category info in $categories array with fully loaded current category
	 *
	 * @return string
	 * @see Mage_Catalog_Helper_Data::getBreadcrumbPath
	 */
	public function getBreadcrumbPath () {
		if ( ! $this->_categoryPath ) {
			$path = array();
            $category = $this->getCategory();
            if ( ! $category ) {
                // if no category, use default/main category
                $catId = $this->getProduct()->getSchrackMainCategoryEntityId();
                if ( $catId ) {
                    $category = Mage::getModel('catalog/category')->load($catId);
                }
            }
			if ( $category ) {
				$pathInStore = $category->getPathInStore();
				$pathIds = array_reverse(explode(',', $pathInStore));

				$categories = $category->getParentCategories();
				$categories[$category->getId()] = $category;

				// add category path breadcrumb
				foreach ( $pathIds as $categoryId ) {
					if ( isset($categories[$categoryId]) && $categories[$categoryId]->getName() ) {
						$path['category'.$categoryId] = array(
							'label' => $categories[$categoryId]->getName(),
							'link' => $this->_isCategoryLink($categoryId) ? $categories[$categoryId]->getUrl() : ''
						);
					}
				}
			}

			if ( $this->getProduct() ) {
				$path['product'] = array('label'=>$this->getProduct()->getName());
			}

			$this->_categoryPath = $path;
		}
		return $this->_categoryPath;
	}

	/**
	 * Format URL key for category or product
	 *
	 * @param string $str
	 * @return string
	 */
	public function formatUtf8UrlKey($str) {
		/**
		 * @see http://www.php.net/manual/en/regexp.reference.unicode.php
		 * @see http://www.regular-expressions.info/unicode.html
		 */
		// remove "other" characters, marks and opening/closing brackets/quotes
		$str = preg_replace('/[\p{C}\p{M}\p{Ps}\p{Pe}\p{Pi}\p{Pf}]/u', '', $str);
		// replace punctuations, separators and symbols with a hyphen
		$str = preg_replace('/[\p{P}\p{Z}\p{S}]+/u', '-', $str);
		$urlKey = mb_strtolower(trim($str, '-'), 'utf-8');

		return $urlKey;
	}


    /**
     * Return current category object
     *
     * @return Mage_Catalog_Model_Category|null
     */
    public function getCategory()
    {
        $cc = Mage::getSingleton('catalog/layer')->getCurrentCategory();
        if (isset($cc) && $cc->getId()) {
            return Mage::registry('current_category');
        }
    }

    /**
     * Return Array
     *
     * @return Array
     */
    public function getGeneralFilter($data, $highlightFacets)
    {
        $generalFilter = array();
        if (is_array($data) && $data !== null && array_key_exists('last_purchased', $data)) {
            $lastPurchased = 'active';
        } else {
            $lastPurchased = 'available';
        }

        if (is_array($data) && $data !== null && array_key_exists('last_viewed', $data)) {
            $lastViewed = 'active';
        } else {
            $lastViewed = 'available';
        }

        if (is_array($data) && $data !== null && array_key_exists('promotions', $data)) {
            $promotions = 'active';
        } else {
            $promotions = 'available';
        }

        if (is_array($data) && $data !== null && array_key_exists('high_availability', $data)) {
            $highAvailability = 'active';
        } else {
            $highAvailability = 'available';
        }

        if (is_array($data) && $data !== null && array_key_exists('sale', $data)) {
            $sale = 'active';
        } else {
            $sale = 'available';
        }

        $generalFilter['general_filters'] = array(
            'label' => $this->__('Customized Filters'),
            'options' => array(
                'last_purchased' => array(
                    'label' => $this->__('Last Purchased'),
                    'count' => '',
                    'filter_type' => 'last_purchased',
                    'type' => $lastPurchased
                ),
                'last_viewed' => array(
                    'label' => $this->__('Last Viewed'),
                    'count' => '',
                    'filter_type' => 'last_viewed',
                    'type' => $lastViewed
                ),
                'promotions' => array(
                    'label' => $this->__('Promotions'),
                    'count' => '',
                    'filter_type' => 'promotions',
                    'type' => $promotions
                ),
                'high_availability' => array(
                    'label' => $this->__('Top Availability'),
                    'count' => '',
                    'filter_type' => 'high_availability',
                    'type' => $highAvailability
                ),
                'sale' => array(
                    'label' => $this->__('Products on Sale'),
                    'count' => '',
                    'filter_type' => 'sale',
                    'type' => $sale
                )
            )
        );

        if (!$highlightFacets['high_availability']) {
            unset($generalFilter['general_filters']['options']['high_availability']);
        }
        if (!$highlightFacets['on_sale']) {
            unset($generalFilter['general_filters']['options']['sale']);
        }

        return $generalFilter;
    }


}

