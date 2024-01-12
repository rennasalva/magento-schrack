<?php

class MageDeveloper_TYPO3connect_ProductController extends Mage_Core_Controller_Front_Action {

	public function getProductsInfoAction() {
		/** @var string $referrer */
		$referrer = $this->getRequest()->get('referrer');
		/* @var Schracklive_SchrackCatalog_Helper_Info $infoHelper */
		$infoHelper = Mage::helper('schrackcatalog/info');
		/** @var array $skus */
		$skus = $this->getRequest()->get('skus');
		/** @var bool $skipStaticInfo */
		$skipStaticInfo = (bool)$this->getRequest()->get('skipStaticInfo');
		/** @var Schracklive_SchrackCatalog_Model_Resource_Eav_Mysql4_Product_Collection $products */
		$products = Mage::getResourceModel('catalog/product_collection')
			->addAttributeToSelect(array('sku','description','url'))
			->addAttributeToFilter(
				'sku', array('in' => $skus)
			)
			->load();
		if (!$products->count()) {
			return false;
		}
		/** @var Schracklive_SchrackCustomer_Model_Customer $customer */
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		/** @var Schracklive_SchrackCatalog_Helper_Product $productHelper */
		$productHelper = Mage::helper('schrackcatalog/product');
		/** @var Schracklive_SchrackCatalog_Helper_Price $priceHelper */
		$priceHelper = Mage::helper('schrackcatalog/price');
		$geoipHelper = Mage::helper('geoip/data');
		$maySeePrices = $geoipHelper->maySeePrices();
		/** @var string $currency */
		$currency = Mage::app()->getStore()->getCurrentCurrency()->getCode();
		/** @var Schracklive_Wws_Helper_SchrackCatalog_Info $infoHelper */
		$infoHelper->preloadProductsInfo($products, $customer);
		$productInfos = array();
		$position = 0;
		foreach ($products as $product) {
			$prices = array_reverse(Mage::helper('schrackcatalog/info')->getGraduatedPricesForCustomer($product, $customer));
			$unformattedPrice = '0.00';
			$price = null;
			$currency = null;
			if (count($prices) > 0) {
				try {
					$unformattedPrice = $prices[0]['price'];
					$price = Mage::helper('core')->formatPrice($unformattedPrice);
					$currency = $priceHelper->getCurrencyForCustomer($product, $customer);
				} catch (Schracklive_SchrackCatalog_Helper_Info_Exception $e) {
					if ($e->getCode() != Schracklive_SchrackCatalog_Helper_Info_Exception::PRICE_UNAVAILABLE) {
						throw $e;
					}
				}
			}
			else {
				try {
					$unformattedPrice = $priceHelper->getUnformattedBasicPriceForCustomer($product, $customer);
					$price = $priceHelper->getFormattedBasicPriceForCustomer($product, $customer);
					$currency = $priceHelper->getCurrencyForCustomer($product, $customer);
				} catch (Schracklive_SchrackCatalog_Helper_Info_Exception $e) {
					if ($e->getCode() != Schracklive_SchrackCatalog_Helper_Info_Exception::PRICE_UNAVAILABLE) {
						throw $e;
					}
				}
			}

			if ($maySeePrices) {
				if ($price === null) {
					$unformattedPrice = '0.00';
					$price = $this->__('not available');
				} else {
					$price = $currency.' '.$price;
				}
			} else {
				$unformattedPrice = '0.00';
				$price = $this->__('on request');
			}
			$regularPrice = $productHelper->getRegularPrice($product,$customer);
			if ($regularPrice > 0) {
				$regularPrice = Mage::helper('core')->formatPrice($regularPrice);
			}

			$cartParams = array(
				'product'=>$product->getId(),
				Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core/url')->getEncodedUrl($referrer),
				Mage_Core_Model_Url::FORM_KEY => Mage::getSingleton('core/session')->getFormKey()
			);

			if ($skipStaticInfo) {
				$productInfos[] = array(
					'sku' => $product->getSku(),
					'cartUrl' => Mage::getUrl('checkout/cart/add', $cartParams),
					'price' => $price,
					'unformattedPrice' => is_numeric($unformattedPrice) ? $unformattedPrice : "0.00",
					'regularPrice' => $regularPrice
				);
			} else {
				$productInfos[] = array(
					'position' => ++$position,
					'sku' => $product->getSku(),
					'description' => $product->getDescription(),
					'productUrl' => $product->getProductUrl(),
					'category' => $product->getPreferredCategory()->getName(),
					'cartUrl' => Mage::getUrl('checkout/cart/add', $cartParams),
					'thumbnail' => (string)Mage::helper('catalog/image')->init($product, 'small_image')->constrainOnly(true)->resize(66, 66),
					'image' => (string)Mage::helper('catalog/image')->init($product, 'image'),
					'price' => $price,
					'unformattedPrice' => is_numeric($unformattedPrice) ? $unformattedPrice : "0.00",
					'regularPrice' => $regularPrice
				);
			}
		}
		$this->getResponse()
			->setHttpResponseCode(200)
			->setHeader('Pragma', 'Private', true)
			->setHeader('Cache-Control', 'Private, max-age=3600', true)
			->setHeader('Content-type', 'application/json', true)
			->setHeader('Last-Modified', date('r', time()), true)
			->setHeader('Expires', date('r', time() + 3600), true)
			->clearBody();
		$this->getResponse()->sendHeaders();
		echo json_encode($productInfos);
		exit();
	}

    public function getPriceAction() {
        $sku = $this->getRequest()->get('id');
        if (!isset($sku))
            $sku = $this->getRequest()->get('sku');
        $product = Mage::getModel('catalog/product')->loadBySku($sku);
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $prices = array_reverse(Mage::helper('schrackcatalog/info')->getGraduatedPricesForCustomer($product, $customer));
        $priceHelper = Mage::helper('schrackcatalog/price');
        if (count($prices) > 0) {
            $price = Mage::helper('core')->formatPrice($prices[0]['price']);
        }
        else {
            $price = $priceHelper->getFormattedBasicPriceForCustomer($product, $customer);
        }
        die($price);
    }
    
    public function getCurrencyAction() {
        $sku = $this->getRequest()->get('id');
        if (!isset($sku))
            $sku = $this->getRequest()->get('sku');
        $product = Mage::getModel('catalog/product')->loadBySku($sku);
        $customer = Mage::getSingleton('customer/session')->getCustomer();
        $priceHelper = Mage::helper('schrackcatalog/price');
        $currency = $priceHelper->getCurrencyForCustomer($product, $customer);
        die($currency);
    }
}