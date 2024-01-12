<?php
/**
 * orcamultimedia
 * http://www.orca-multimedia.de
 * 
 * @author		Thomas Wild
 * @package	Orcamultimedia_Sapoci
 * @copyright	Copyright (c) 2017 orcamultimedia Thomas Wild (http://www.orca-multimedia.de)
 * 
**/

class Orcamultimedia_Sapoci_Block_Link extends Mage_Checkout_Block_Onepage_Link {

	protected $index = 1;


	public function isSapociCheckout(){
		return Mage::helper('sapoci')->getIsPunchout();
    }


	public function getActionUrl(){
		$session = Mage::getSingleton('customer/session');
		
		return $session['sapoci']['HOOK_URL'];
    }


	public function getTarget() {
		$session = Mage::getSingleton('customer/session');
		if (isset($session['sapoci']['returntarget']) && !empty($session['sapoci']['returntarget'])) {
			return $session['sapoci']['returntarget'];
        }
		return false;
	}


	public function cartItems() {
		$session = Mage::getSingleton('checkout/session');
		$sapOciHelper = Mage::helper('sapoci');
		$cartItems = array();
		$this->index = 1;

        if(Mage::getStoreConfig('sapoci/configuration/logging')) {
            Mage::log(date('Y-m-d H:i:s') . ' -> Link.php', null, 'sapoci.log');
        }

		foreach ($session->getQuote()->getAllVisibleItems() as $item) {			// CART ITEM values

			$cartItems['NEW_ITEM-DESCRIPTION[' . $this->index . ']'] 	= substr($sapOciHelper->prepareString($item->getName()), 0, 40);
			$cartItems['NEW_ITEM-QUANTITY[' . $this->index . ']'] 		= substr(number_format($item->getQty(), 3, '.', ''), 0, 15);
            $priceFactor                                                = $item->getQty() / $item->getProduct()->getData('schrack_priceunit');
			$cartItems['NEW_ITEM-CURRENCY[' . $this->index . ']'] 		= substr($item->getStore()->getCurrentCurrencyCode(), 0, 5);
			//$cartItems['NEW_ITEM-PRICE[' . $this->index . ']'] 		= substr(number_format($item->getBaseCalculationPrice(), 3, '.', ''), 0, 15);
            $unformattedPrice                                           = str_replace(',', '.', Mage::helper('schrackcheckout')->formatPrice($item->getProduct(), number_format( ($item->getRowTotal() / $priceFactor), 2)));
			$cartItems['NEW_ITEM-VENDORMAT[' . $this->index . ']'] 		= substr($item->getSku(), 0, 40);
			$cartItems['NEW_ITEM-EXT_PRODUCT_ID[' . $this->index . ']'] = substr($item->getProductId(), 0, 40);

            if ($item->getSchrackOfferReference()) {
                $unformattedOfferPrice = $item->getSchrackOfferPricePerUnit();
                $surcharges_per_unit = 0;

                if ($item->getSchrackOfferSurcharge() > 0) {
                    $surcharges_per_unit = number_format($item->getSchrackOfferSurcharge(), 2);
                }

                $cartItems['NEW_ITEM-PRICE[' . $this->index . ']'] = number_format($unformattedOfferPrice, 2) + $surcharges_per_unit;
            } else {
                $cartItems['NEW_ITEM-PRICE[' . $this->index . ']'] = substr_replace($unformattedPrice, '.', -2, 0);
            }


			// Get more values from PRODUCT ITEM
			$product = Mage::getModel('catalog/product')->load($item->getProductId());

            $storeId = null;
            $wwsCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();
            if ($wwsCustomerId == "703495" || $wwsCustomerId == 703495) {
                $storeId = 2;
                if (strtoupper($item->getStore()->getCurrentCurrencyCode()) == 'RON') {
                    $cartItems['NEW_ITEM-CURRENCY[' . $this->index . ']'] = 'ROL';
                }
            }
            if ($wwsCustomerId == "821931" || $wwsCustomerId == 821931) {
                $storeId = 2;
            }
            $customSku = null;
            if ($wwsCustomerId == "180480" || $wwsCustomerId == 180480) {
                $storeId = 3;
                $customSku = $sapOciHelper->getCustomerIndividualArticleNumber($wwsCustomerId, $item->getSku());
                if (strlen($customSku) > 3) {
                    $cartItems['NEW_ITEM-DESCRIPTION[' . $this->index . ']'] = '';
                } else {
                    $cartItems['NEW_ITEM-DESCRIPTION[' . $this->index . ']'] = substr($sapOciHelper->prepareString($item->getName()), 0, 40);
                }
            }

            Mage::log('SAP_OCI : Log Entry Point #001 -> WWS-ID = ' . $wwsCustomerId, null, 'sapoci.log');
			if (Mage::getStoreConfig('sapoci/vendor/enabled', $storeId)) {
				$cartItems['NEW_ITEM-VENDOR[' . $this->index . ']'] = $sapOciHelper->getFieldValue('vendor', $product, 10);
            }
			if (Mage::getStoreConfig('sapoci/leadtime/enabled', $storeId)) {
				$cartItems['NEW_ITEM-LEADTIME[' . $this->index . ']'] = (int)$sapOciHelper->getFieldValue('leadtime', $product, 5);
            }
			if (Mage::getStoreConfig('sapoci/matgroup/enabled', $storeId)) {
				$cartItems['NEW_ITEM-MATGROUP[' . $this->index . ']'] = $sapOciHelper->getFieldValue('matgroup', $product, 10);
            }
			if (Mage::getStoreConfig('sapoci/manufactmat/enabled', $storeId)) {
				$cartItems['NEW_ITEM-MANUFACTMAT[' . $this->index . ']'] = $sapOciHelper->getFieldValue('manufactmat', $product, 40);
            }
			if (Mage::getStoreConfig('sapoci/cust_field1/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD1[' . $this->index . ']'] = $sapOciHelper->getFieldValue('cust_field1', $product, 10);
            }
			if (Mage::getStoreConfig('sapoci/cust_field2/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD2[' . $this->index . ']'] = $sapOciHelper->getFieldValue('cust_field2', $product, 10);
            }
			if (Mage::getStoreConfig('sapoci/cust_field3/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD3[' . $this->index . ']'] = $sapOciHelper->getFieldValue('cust_field3', $product, 10);
            }
			if (Mage::getStoreConfig('sapoci/cust_field4/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD4[' . $this->index . ']'] = $sapOciHelper->getFieldValue('cust_field4', $product, 20);
            }
			if (Mage::getStoreConfig('sapoci/cust_field5/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD5[' . $this->index . ']'] = $sapOciHelper->getFieldValue('cust_field5', $product, 50);
            }
			if (Mage::getStoreConfig('sapoci/ext_category_id/enabled', $storeId)) {
				$cartItems['NEW_ITEM-EXT_CATEGORY_ID[' . $this->index . ']'] = $sapOciHelper->getFieldValue('ext_category_id', $product, 60);
            }
			if (Mage::getStoreConfig('sapoci/unit/enabled', $storeId)) {
				$cartItems['NEW_ITEM-UNIT[' . $this->index . ']'] = $sapOciHelper->getFieldValue('unit', $product, 3);
            }
			if (Mage::getStoreConfig('sapoci/matnr/enabled', $storeId)) {
				$cartItems['NEW_ITEM-MATNR[' . $this->index . ']'] = $sapOciHelper->getFieldValue('matnr', $product, 40);
                if (strlen($customSku) > 3) {
                    $cartItems['NEW_ITEM-MATNR[' . $this->index . ']'] = $customSku;
                }
            }
			if (Mage::getStoreConfig('sapoci/contract/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CONTRACT[' . $this->index . ']'] = $sapOciHelper->getFieldValue('contract', $product, 10);
            }
			if (Mage::getStoreConfig('sapoci/contract_item/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CONTRACT_ITEM[' . $this->index . ']'] = $sapOciHelper->getFieldValue('contract_item', $product, 5);
            }

            if (Mage::getStoreConfig('sapoci/ext_quote_id/enabled', $storeId)) {
                if ($item->getSchrackOfferReference()) {
                    $cartItems['NEW_ITEM-EXT_QUOTE_ID[' . $this->index . ']'] = $item->getSchrackOfferReference();
                } else {
                    $cartItems['NEW_ITEM-EXT_QUOTE_ID[' . $this->index . ']'] = $sapOciHelper->getFieldValue('ext_quote_id', $product, 35);
                }
            }

			if (Mage::getStoreConfig('sapoci/ext_quote_item/enabled', $storeId)) {
				$cartItems['NEW_ITEM-EXT_QUOTE_ITEM[' . $this->index . ']'] = $sapOciHelper->getFieldValue('ext_quote_item', $product, 35);
            }
			if (Mage::getStoreConfig('sapoci/attachment/enabled', $storeId)) {
				$cartItems['NEW_ITEM-ATTACHMENT[' . $this->index . ']'] = $sapOciHelper->getFieldValue('attachment', $product, 255);
            }
			if (Mage::getStoreConfig('sapoci/attachment_title/enabled', $storeId)) {
				$cartItems['NEW_ITEM-ATTACHMENT_TITLE[' . $this->index . ']'] = $sapOciHelper->getFieldValue('attachment_title', $product, 255);
            }
			if (Mage::getStoreConfig('sapoci/attachment_purpose/enabled', $storeId)) {
				$cartItems['NEW_ITEM-ATTACHMENT_PURPOSE[' . $this->index . ']'] = $sapOciHelper->getFieldValue('attachment_purpose', $product, 1);
            }
			if (Mage::getStoreConfig('sapoci/ext_schema_type/enabled', $storeId)) {
				$cartItems['NEW_ITEM-EXT_SCHEMA_TYPE[' . $this->index . ']'] = $sapOciHelper->getFieldValue('ext_schema_type', $product, 10);
            }
			if (Mage::getStoreConfig('sapoci/ext_category/enabled', $storeId)) {
				$cartItems['NEW_ITEM-EXT_CATEGORY[' . $this->index . ']'] = $sapOciHelper->getFieldValue('ext_category', $product, 40);
            }
			if (Mage::getStoreConfig('sapoci/sld_sys_name/enabled', $storeId)) {
				$cartItems['NEW_ITEM-SLD_SYS_NAME[' . $this->index . ']'] = $sapOciHelper->getFieldValue('sld_sys_name', $product, 60);
            }
			if (Mage::getStoreConfig('sapoci/priceunit/enabled', $storeId)) {
				$cartItems['NEW_ITEM-PRICEUNIT[' . $this->index . ']'] = (int)$sapOciHelper->getFieldValue('priceunit', $product, 5);
            }
			if (Mage::getStoreConfig('sapoci/manufactcode/enabled', $storeId)) {
				$cartItems['NEW_ITEM-MANUFACTCODE[' . $this->index . ']'] = $sapOciHelper->getFieldValue('manufactcode', $product, 10);
            }
			if (Mage::getStoreConfig('sapoci/service/enabled', $storeId)) {
				$cartItems['NEW_ITEM-SERVICE[' . $this->index . ']'] = $sapOciHelper->getFieldValue('service', $product, 1);
            }
			if (Mage::getStoreConfig('sapoci/custom/enabled', $storeId)) {
				$cartItems['NEW_ITEM-' . strtoupper(Mage::getStoreConfig('sapoci/custom/name')) . '[' . $this->index . ']'] = $sapOciHelper->getFieldValue('custom', $product, 1);
            }
			if (Mage::getStoreConfig('sapoci/item_type/enabled', $storeId)) {
				$cartItems['NEW_ITEM-ITEM_TYPE[' . $this->index . ']'] = $sapOciHelper->getFieldValue('item_type', $product, 35);
            }
			if (Mage::getStoreConfig('sapoci/parent_id/enabled', $storeId)) {
				$cartItems['NEW_ITEM-PARENT_ID[' . $this->index . ']'] = $sapOciHelper->getFieldValue('parent_id', $product, 35);
            }

			// Product Options
            $cartItems['NEW_ITEM-LONGTEXT_'. $this->index .':132[]'] = $sapOciHelper->prepareString($product->getShortDescription());

            $helper = Mage::helper('catalog/product_configuration');
			$options = array();
            if($options = $helper->getOptions($item)) {
				foreach ($options as $option) {
					$cartItems['NEW_ITEM-LONGTEXT_' . $this->index . ':132[]'] .= ' // ' . $sapOciHelper->prepareString($option['label']) . ': ' . $sapOciHelper->prepareString($option['value']);
                }
            }

			if($item->getOptionByCode('bundle_option_ids')) {
				$options = array();
                $helper = Mage::helper('bundle/catalog_product_configuration');
                $options = $helper->getOptions($item);
				if (!empty($options)) {
					foreach ($options as $option) {
						$cartItems['NEW_ITEM-LONGTEXT_' . $this->index .':132[]'] .= ' // ' . $sapOciHelper->prepareString($option['label']) . ': ' . $sapOciHelper->prepareString(implode(', ', $option['value']));
                    }
                }
	        }

			// Next Item
			$this->index++;
		}

        if(Mage::getStoreConfig('sapoci/configuration/logging')) {
            Mage::log(date('Y-m-d H:i:s') . ' -> Link.php', null, 'sapoci.log');
            Mage::log($cartItems, null, 'sapoci.log');
        }

		return $cartItems;
	}				
}
