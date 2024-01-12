<?php
/**
 *
 * @package	Orcamultimedia_Ids
 * 
**/

class Orcamultimedia_Ids_Block_Link extends Mage_Checkout_Block_Onepage_Link {

	protected $index = 1;


	public function isIdsCheckout(){
		return Mage::helper('ids')->isIdsSession();
    }


	public function getActionUrl(){
		$session = Mage::getSingleton('customer/session');
		
		return $session['ids']['hookurl'];
    }


	public function getTarget() {
		$session = Mage::getSingleton('customer/session');
		if (isset($session['ids']['returntarget']) && !empty($session['ids']['returntarget'])) {
			return $session['ids']['returntarget'];
        }
		return false;
	}


	public function cartItems() {
		$session = Mage::getSingleton('checkout/session');
		$idsHelper = Mage::helper('ids');
		$cartItems = array();
		$this->index = 1;

        if(Mage::getStoreConfig('ids/configuration/logging')) {
            Mage::log(date('Y-m-d H:i:s') . ' -> Link.php', null, 'ids.log');
        }

		foreach ($session->getQuote()->getAllVisibleItems() as $item) {			// CART ITEM values

			$cartItems['NEW_ITEM-DESCRIPTION[' . $this->index . ']'] 	= substr($idsHelper->prepareString($item->getName()), 0, 40);
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
                $customSku = $idsHelper->getCustomerIndividualArticleNumber($wwsCustomerId, $item->getSku());
                if (strlen($customSku) > 3) {
                    $cartItems['NEW_ITEM-DESCRIPTION[' . $this->index . ']'] = '';
                } else {
                    $cartItems['NEW_ITEM-DESCRIPTION[' . $this->index . ']'] = substr($idsHelper->prepareString($item->getName()), 0, 40);
                }
            }

            Mage::log('IDS : Log Entry Point #001 -> WWS-ID = ' . $wwsCustomerId, null, 'ids.log');
			if (Mage::getStoreConfig('ids/vendor/enabled', $storeId)) {
				$cartItems['NEW_ITEM-VENDOR[' . $this->index . ']'] = $idsHelper->getFieldValue('vendor', $product, 10);
            }
			if (Mage::getStoreConfig('ids/leadtime/enabled', $storeId)) {
				$cartItems['NEW_ITEM-LEADTIME[' . $this->index . ']'] = (int)$idsHelper->getFieldValue('leadtime', $product, 5);
            }
			if (Mage::getStoreConfig('ids/matgroup/enabled', $storeId)) {
				$cartItems['NEW_ITEM-MATGROUP[' . $this->index . ']'] = $idsHelper->getFieldValue('matgroup', $product, 10);
            }
			if (Mage::getStoreConfig('ids/manufactmat/enabled', $storeId)) {
				$cartItems['NEW_ITEM-MANUFACTMAT[' . $this->index . ']'] = $idsHelper->getFieldValue('manufactmat', $product, 40);
            }
			if (Mage::getStoreConfig('ids/cust_field1/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD1[' . $this->index . ']'] = $idsHelper->getFieldValue('cust_field1', $product, 10);
            }
			if (Mage::getStoreConfig('ids/cust_field2/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD2[' . $this->index . ']'] = $idsHelper->getFieldValue('cust_field2', $product, 10);
            }
			if (Mage::getStoreConfig('ids/cust_field3/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD3[' . $this->index . ']'] = $idsHelper->getFieldValue('cust_field3', $product, 10);
            }
			if (Mage::getStoreConfig('ids/cust_field4/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD4[' . $this->index . ']'] = $idsHelper->getFieldValue('cust_field4', $product, 20);
            }
			if (Mage::getStoreConfig('ids/cust_field5/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD5[' . $this->index . ']'] = $idsHelper->getFieldValue('cust_field5', $product, 50);
            }
			if (Mage::getStoreConfig('ids/ext_category_id/enabled', $storeId)) {
				$cartItems['NEW_ITEM-EXT_CATEGORY_ID[' . $this->index . ']'] = $idsHelper->getFieldValue('ext_category_id', $product, 60);
            }
			if (Mage::getStoreConfig('ids/unit/enabled', $storeId)) {
				$cartItems['NEW_ITEM-UNIT[' . $this->index . ']'] = $idsHelper->getFieldValue('unit', $product, 3);
            }
			if (Mage::getStoreConfig('ids/matnr/enabled', $storeId)) {
				$cartItems['NEW_ITEM-MATNR[' . $this->index . ']'] = $idsHelper->getFieldValue('matnr', $product, 40);
                if (strlen($customSku) > 3) {
                    $cartItems['NEW_ITEM-MATNR[' . $this->index . ']'] = $customSku;
                }
            }
			if (Mage::getStoreConfig('ids/contract/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CONTRACT[' . $this->index . ']'] = $idsHelper->getFieldValue('contract', $product, 10);
            }
			if (Mage::getStoreConfig('ids/contract_item/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CONTRACT_ITEM[' . $this->index . ']'] = $idsHelper->getFieldValue('contract_item', $product, 5);
            }

            if (Mage::getStoreConfig('ids/ext_quote_id/enabled', $storeId)) {
                if ($item->getSchrackOfferReference()) {
                    $cartItems['NEW_ITEM-EXT_QUOTE_ID[' . $this->index . ']'] = $item->getSchrackOfferReference();
                } else {
                    $cartItems['NEW_ITEM-EXT_QUOTE_ID[' . $this->index . ']'] = $idsHelper->getFieldValue('ext_quote_id', $product, 35);
                }
            }

			if (Mage::getStoreConfig('ids/ext_quote_item/enabled', $storeId)) {
				$cartItems['NEW_ITEM-EXT_QUOTE_ITEM[' . $this->index . ']'] = $idsHelper->getFieldValue('ext_quote_item', $product, 35);
            }
			if (Mage::getStoreConfig('ids/attachment/enabled', $storeId)) {
				$cartItems['NEW_ITEM-ATTACHMENT[' . $this->index . ']'] = $idsHelper->getFieldValue('attachment', $product, 255);
            }
			if (Mage::getStoreConfig('ids/attachment_title/enabled', $idsHelper)) {
				$cartItems['NEW_ITEM-ATTACHMENT_TITLE[' . $this->index . ']'] = $idsHelper->getFieldValue('attachment_title', $product, 255);
            }
			if (Mage::getStoreConfig('ids/attachment_purpose/enabled', $storeId)) {
				$cartItems['NEW_ITEM-ATTACHMENT_PURPOSE[' . $this->index . ']'] = $idsHelper->getFieldValue('attachment_purpose', $product, 1);
            }
			if (Mage::getStoreConfig('ids/ext_schema_type/enabled', $storeId)) {
				$cartItems['NEW_ITEM-EXT_SCHEMA_TYPE[' . $this->index . ']'] = $idsHelper->getFieldValue('ext_schema_type', $product, 10);
            }
			if (Mage::getStoreConfig('ids/ext_category/enabled', $storeId)) {
				$cartItems['NEW_ITEM-EXT_CATEGORY[' . $this->index . ']'] = $idsHelper->getFieldValue('ext_category', $product, 40);
            }
			if (Mage::getStoreConfig('ids/sld_sys_name/enabled', $storeId)) {
				$cartItems['NEW_ITEM-SLD_SYS_NAME[' . $this->index . ']'] = $idsHelper->getFieldValue('sld_sys_name', $product, 60);
            }
			if (Mage::getStoreConfig('ids/priceunit/enabled', $storeId)) {
				$cartItems['NEW_ITEM-PRICEUNIT[' . $this->index . ']'] = (int)$idsHelper->getFieldValue('priceunit', $product, 5);
            }
			if (Mage::getStoreConfig('ids/manufactcode/enabled', $storeId)) {
				$cartItems['NEW_ITEM-MANUFACTCODE[' . $this->index . ']'] = $idsHelper->getFieldValue('manufactcode', $product, 10);
            }
			if (Mage::getStoreConfig('ids/service/enabled', $storeId)) {
				$cartItems['NEW_ITEM-SERVICE[' . $this->index . ']'] = $idsHelper->getFieldValue('service', $product, 1);
            }
			if (Mage::getStoreConfig('ids/custom/enabled', $storeId)) {
				$cartItems['NEW_ITEM-' . strtoupper(Mage::getStoreConfig('ids/custom/name')) . '[' . $this->index . ']'] = $idsHelper->getFieldValue('custom', $product, 1);
            }
			if (Mage::getStoreConfig('ids/item_type/enabled', $storeId)) {
				$cartItems['NEW_ITEM-ITEM_TYPE[' . $this->index . ']'] = $idsHelper->getFieldValue('item_type', $product, 35);
            }
			if (Mage::getStoreConfig('ids/parent_id/enabled', $storeId)) {
				$cartItems['NEW_ITEM-PARENT_ID[' . $this->index . ']'] = $idsHelper->getFieldValue('parent_id', $product, 35);
            }

			// Product Options
            $cartItems['NEW_ITEM-LONGTEXT_'. $this->index .':132[]'] = $idsHelper->prepareString($product->getShortDescription());

            $helper = Mage::helper('catalog/product_configuration');
			$options = array();
            if($options = $helper->getOptions($item)) {
				foreach ($options as $option) {
					$cartItems['NEW_ITEM-LONGTEXT_' . $this->index . ':132[]'] .= ' // ' . $idsHelper->prepareString($option['label']) . ': ' . $idsHelper->prepareString($option['value']);
                }
            }

			if($item->getOptionByCode('bundle_option_ids')) {
				$options = array();
                $helper = Mage::helper('bundle/catalog_product_configuration');
                $options = $helper->getOptions($item);
				if (!empty($options)) {
					foreach ($options as $option) {
						$cartItems['NEW_ITEM-LONGTEXT_' . $this->index .':132[]'] .= ' // ' . $idsHelper->prepareString($option['label']) . ': ' . $idsHelper->prepareString(implode(', ', $option['value']));
                    }
                }
	        }

			// Next Item
			$this->index++;
		}

        if(Mage::getStoreConfig('ids/configuration/logging')) {
            Mage::log(date('Y-m-d H:i:s') . ' -> Link.php', null, 'ids.log');
            Mage::log($cartItems, null, 'ids.log');
        }

		return $cartItems;
	}				
}
