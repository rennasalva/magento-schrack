<?php

class Schracklive_SchrackGoogleAnalytics_Block_Ga extends Mage_GoogleAnalytics_Block_Ga {

	/**
	 * Retrieve Google Domain Identifier
	 *
	 * @return string
	 */
	public function getDomain() {
		if (!$this->hasData('domain')) {
			$this->setData('domain', Mage::getStoreConfig('google/analytics/domain'));
		}

		return $this->getData('domain');
	}

	protected function _getPageTrackingCode($accountId) {
		return '';
	}

	/**
	 * Render regular page tracking javascript code
	 * The custom "page name" may be set from layout or somewhere else. It must start from slash.
	 *
	 * @link http://code.google.com/apis/analytics/docs/gaJS/gaJSApiBasicConfiguration.html#_gat.GA_Tracker_._trackPageview
	 * @link http://code.google.com/apis/analytics/docs/gaJS/gaJSApi_gaq.html
	 * @param string $location
	 * @return string
	 */
	protected function _getSchrackPageTrackingCode(&$location) {
		$ga = array();
		$request = Mage::app()->getFrontController()->getRequest();
		$requestPath = $request->getModuleName().'/'.$request->getControllerName().'/'.$request->getActionName();
		$session = Mage::getSingleton('customer/session');
		$sessionIsPostLogin = $session->getData('schracklive_post_login');

		// Add user specific tracking data
		$this->_addUserTrackingCode($ga, $session, $sessionIsPostLogin);
		// Track search data
		$gaSearchTrackingCode = $this->_getSearchTrackingCode($ga, $location);
		return implode("\n", $ga);
	}

	protected function _addUserTrackingCode(&$ga, &$session, $sessionIsPostLogin) {
		if ($session->getId() && is_object($session->getCustomer())) {
				$customer = $session->getCustomer();
				$account = $customer->getAccount();
				$session->setData('schracklive_post_login', false);
				if (is_object($account)) {
					$ga[] = "
						ga('set', 'dimension1', '".$account->getSalesArea()."'); // sales_area;
						ga('set', 'dimension2', '".$account->getRating()."'); // rating;
						ga('set', 'dimension3', '".$account->getEnterpriseSize()."'); // enterprise_size;
						ga('set', 'dimension4', '".$account->getAccountType()."'); // account_type;
						";
				}
			//}
		}
	}

	protected function _getSearchTrackingCode(&$ga, &$location) {
		$controllerName = Mage::app()->getRequest()->getControllerName();
		// No tracking for category controller, broken due to fluctuating AJAX
		if ($controllerName == 'category') {
			return;
		}
		$searchTerms = Mage::app()->getRequest()->getParam('q');
		$searchFacets = Mage::app()->getRequest()->getParam('fq');
		$searchCategory = Mage::app()->getRequest()->getParam('cat');
		// Only track no results on global un-faceted searches
		/*if ($searchTerms && !$searchFacets) {
			/** @var $solr Schracklive_SolrSearch_Model_Search */
			/*$solr = Mage::getSingleton('solrsearch/search')->initData();
			$solrProducts = $solr->getProductIds();
			if (!is_array($solrProducts) || count($solrProducts) == 0) {
				$location = "gaCanonical + '?q=".$searchTerms."&cat=No_Results'";
				return;
			}
		}*/

		$gaUrlParams = array();
		$gaUrlRequest = '';
		if ($searchTerms) {
			$gaUrlParams[] = 'q='.$searchTerms;
		}
		if ($searchCategory) {
			$gaUrlParams[] = 'cat='.$searchCategory;
		}
		if ($gaUrlParams) {
			$gaUrlRequest = '?'.implode('&', $gaUrlParams);
		}
		/*if ($searchFacets) {
			if (is_array($searchFacets)) {
				$searchFacetKeys = array_keys($searchFacets);
				$searchFacet = $searchFacets[$searchFacetKeys[count($searchFacetKeys) - 1]];
			} else {
				$searchFacet = $searchFacets;
			}

		}*/
		$location = "gaCanonical + '".$gaUrlRequest."'";
	}

	protected function _getCheckoutTrackingCode() {
		if(!$this->getLayout()->getBlock('checkout.onepage')) {
			return '';
		}
		$cart = Mage::getModel('checkout/cart')->getQuote();
		foreach ($cart->getAllItems() as $item) {
			$mainCategoryId = $item->getProduct()->getSchrackMainCategoryEntityId();
			if ($mainCategoryId) {
				$categoryName = Mage::getModel('catalog/category')->load($mainCategoryId)->getName();
			} else {
				$categoryName = null;
			}
			$result[] = sprintf("ga('ec:addProduct', {
										'id': '%s',
										'name': '%s',
										'category': '%s',
										'price': '%s',
										'quantity': '%s'
									});",
			                    $this->jsQuoteEscape($item->getSku()),
			                    $this->jsQuoteEscape($item->getName()),
			                    $this->jsQuoteEscape($categoryName),
			                    $this->jsQuoteEscape($item->getBaseRowTotal()),
			                    $this->jsQuoteEscape($item->getQty())
			);
		}
		$session = Mage::getSingleton('customer/session');
		if ($session->getId() && is_object($session->getCustomer())) {
			$step = 2;
		} else {
			$step = 1;
		}
		$result[] = "var gaCheckoutStep = (gaCheckoutStepOverride ? gaCheckoutStepOverride : ".$step.");
			ga('ec:setAction','checkout', {
				'step': gaCheckoutStep
			});";
		return implode("\n", $result);
	}

	protected function _getOrdersTrackingCode() {
		$orderIds = $this->getOrderIds();
		if (empty($orderIds) || !is_array($orderIds)) {
			return;
		}
		$collection = Mage::getResourceModel('sales/order_collection')
		                  ->addFieldToFilter('entity_id', array('in' => $orderIds));
		$result = array();
		foreach ($collection as $order) {
			foreach ($order->getAllVisibleItems() as $item) {
				$mainCategoryId = Mage::getModel('catalog/product')->load($item->getProductId())->getSchrackMainCategoryEntityId();
				if ($mainCategoryId) {
					$categoryName = Mage::getModel('catalog/category')->load($mainCategoryId)->getName();
				} else {
					$categoryName = null;
				}
				$result[] = sprintf("ga('ec:addProduct', {
										'id': '%s',
										'name': '%s',
										'category': '%s',
										'price': '%s',
										'quantity': '%s'
									});",
				                    $this->jsQuoteEscape($item->getSku()),
				                    $this->jsQuoteEscape($item->getName()),
				                    $this->jsQuoteEscape($categoryName),
				                    $item->getBasePrice(),
				                    $item->getQtyOrdered()
				);
			}
			$orderNumber = $order->getSchrackWwsOrderNumber();
			if (!$orderNumber) {
				$orderNumber = $order->getIncrementId();
			}
			$result[] = sprintf("ga('ec:setAction', 'purchase', {
									'id': '%s',
									'affiliation': '%s',
									'revenue': '%s',
									'tax': '%s',
									'shipping': '%s'
								});",
			                    $orderNumber,
			                    $this->jsQuoteEscape(Mage::app()->getStore()->getFrontendName()),
			                    $order->getBaseGrandTotal(),
			                    $order->getBaseTaxAmount(),
			                    $order->getBaseShippingAmount()
			);
		}
		//$result[] = "ga('set', 'dimension8', '".$order->getPayment()->getMethodInstance()->getTitle()."');"; // payment_method
		return implode("\n", $result);
	}

	/**
	 * Prepare and return block's html output
	 *
	 * @return string
	 */
	protected function _toHtml() {
		if (!Mage::helper('googleanalytics')->isGoogleAnalyticsAvailable()) {
			return '';
		}
		$accountId = Mage::getStoreConfig(Mage_GoogleAnalytics_Helper_Data::XML_PATH_ACCOUNT);
		$location = 'gaCanonical';

		return '
			<!-- BEGIN GOOGLE UNIVERSAL ANALYTICS CODE -->
			<script type="text/javascript">
			//<![CDATA[
					function trackPage(gaCheckoutStepOverride) {
						if (typeof(ga) != "undefined") {
							'.$this->_getSchrackPageTrackingCode($location).'
							'.$this->_getCheckoutTrackingCode().'
							'.$this->_getOrdersTrackingCode().'
							var d1 = jQuery.Deferred();
							var d2 = jQuery.Deferred();
							jQuery(".product-detail").each(function() {
								ga("ec:addProduct", {
									"id": jQuery(this).attr("data-sku"),
									"name": jQuery(this).attr("data-name"),
									"category": jQuery(this).attr("data-category"),
								});
								ga("ec:setAction", "detail");
							}).promise().done(function () {
								d1.resolve();
							});
							jQuery(".product-list").each(function() {
								var listName = jQuery(this).attr("data-name");
								if (listName) {
									jQuery(this).find(".product-item").each(function () {
										ga("ec:addImpression", {
											"id": jQuery(this).attr("data-sku"),
											"name": jQuery(this).attr("data-name"),
											"category": jQuery(this).attr("data-category"),
											"list": listName,
											"position": jQuery(this).attr("data-position")
										});
									});
								}
							}).promise().done(function () {
								d2.resolve();
							});
							jQuery.when(d1, d2).done(function() {
								ga("send", "pageview", gaLocation);
							});
						}
					}

					var gaCanonical;
					var gaLocation;
					jQuery(document).ready(function() {
						gaCanonical = getCanonicalUrl();
						gaLocation = '.$location.';
						if ((typeof(isCookiesAllow) === "function" && isCookiesAllow()) || typeof(isCookiesAllow) === "undefined") {
							(function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){
							(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
							m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
							})(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\'ga\');
							ga("create", "'.$this->jsQuoteEscape($accountId).'", {'.Mage::helper('googleanalytics')->generateUserId().'}, {"legacyCookieDomain": "'.$this->jsQuoteEscape(trim($this->getDomain())).'", "allowLinker": true});
							ga("require", "ec");
							ga("require", "displayfeatures");
							ga("require", "linkid", "linkid.js");
							trackPage();
						}
			        });
			//]]>
			</script>
			<!-- END GOOGLE UNIVERSAL ANALYTICS CODE -->';
	}
}
