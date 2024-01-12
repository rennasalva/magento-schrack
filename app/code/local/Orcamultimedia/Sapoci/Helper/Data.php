<?php
/**
 * orcamultimedia
 * http://www.orca-multimedia.de
 * 
 * @author		Thomas Wild
 * @package	Orcamultimedia_Sapoci
 * @copyright	Copyright (c) 2012 orcamultimedia Thomas Wild (http://www.orca-multimedia.de)
 * 
**/

class Orcamultimedia_Sapoci_Helper_Data extends Mage_Core_Helper_Abstract {

    public $session;

	public function getActionUrl(){
		return $this->session['sapoci']['HOOK_URL'];
	}


    public function isSapociCheckout() {
        $this->session = Mage::getSingleton('customer/session');

        if (Mage::getStoreConfig('sapoci/always_active/enabled') == 1) {
            $this->session->setData('sapoci', array('HOOK_URL' => 'https://www.orca-multimedia.de/sapoci/hook.php'));

            return true;
        }


        if (isset($this->session['sapoci']['HOOK_URL']) && !empty($this->session['sapoci']['HOOK_URL'])) {
            return true;
        }

        return false;
    }


	public function getTarget(){
		if (isset($this->session['sapoci']['returntarget']) && !empty($this->session['sapoci']['returntarget'])) {
			return $this->session['sapoci']['returntarget'];
        }
		
		return false;
	}


	public function cartItems() {
        if (Mage::getStoreConfig('sapoci/configuration/logging')) {
            Mage::log(date('Y-m-d H:i:s') . ' -> Data.php::cartItems()', null, 'sapoci.log');
        }

		$session = Mage::getSingleton('checkout/session');
		
		$cartItems = array();
		$index = 1;
		
		foreach ($session->getQuote()->getAllVisibleItems() as $item) {
			
			// Get data of cart item
			$cartItems['NEW_ITEM-DESCRIPTION[' . $index . ']'] 		= substr($this->cleanString($item->getName()), 0, 40);
			$cartItems['NEW_ITEM-QUANTITY[' . $index . ']'] 		= $item->getQty();
            $cartItems['NEW_ITEM-PRICEUNIT[' . $index . ']'] 		= $item->getProduct()->getData('schrack_priceunit');
            $priceFactor                                            = $item->getQty() / $item->getProduct()->getData('schrack_priceunit');
            $schrackQtyUnit                                         = $item->getProduct()->getData('schrack_qtyunit');
            //---------- Special Case #1: convert quantity unit Stk in ISO Value
            $schrackQtyUnitStringsStk = array("ks","Stk","pc","Pc","Kom","db","szt.","buc.","шт.","kos","kom");
            if(in_array($schrackQtyUnit, $schrackQtyUnitStringsStk) ){
                $schrackQtyUnit = 'PCE';
            }
            //-------- Special Case #2: convert quantity unit Meter in ISO Value
            $schrackQtyUnitStringsM = array("M","m","m.","mb","м");
            if(in_array($schrackQtyUnit, $schrackQtyUnitStringsM) ){
                $schrackQtyUnit = 'MTR';
            }
            //------------------------------------------------------------------
			$cartItems['NEW_ITEM-UNIT[' . $index . ']'] 			= $schrackQtyUnit;
			$cartItems['NEW_ITEM-CURRENCY[' . $index . ']'] 		= $item->getStore()->getCurrentCurrencyCode();
			// $cartItems['NEW_ITEM-PRICE[' . $index . ']'] 			= $item->getBaseCalculationPrice() * $item->getProduct()->getData('schrack_priceunit');
			$unformattedPrice                                       = str_replace(array(',', '.'), '', Mage::helper('schrackcheckout')->formatPrice($item->getProduct(), number_format( ($item->getRowTotal() / $priceFactor), 2)));
            if ($item->getSchrackOfferReference()) {
                $unformattedOfferPrice = $item->getSchrackOfferPricePerUnit();
                $surcharges_per_unit = 0;

                if ($item->getSchrackOfferSurcharge() > 0) {
                    $surcharges_per_unit = number_format($item->getSchrackOfferSurcharge(), 2);
                }

                $cartItems['NEW_ITEM-PRICE[' . $index . ']'] = number_format($unformattedOfferPrice, 2) + $surcharges_per_unit;
            } else {
			    $cartItems['NEW_ITEM-PRICE[' . $index . ']'] = substr_replace($unformattedPrice, '.', -2, 0);
            }

            // Get more product data of item
            $product = Mage::getModel('catalog/product')->load($item->getProductId());

            $storeId= null;
            $wwsCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();
            if ($wwsCustomerId == "703495" || $wwsCustomerId == 703495) {
                $storeId = 2;
                if (Mage::getStoreConfig('sapoci/cust_field1/enabled', $storeId)) {
                    $cartItems['NEW_ITEM-CUST_FIELD1[' . $index . ']'] = Mage::getStoreConfig('schrack/sales/vat');
                }
                if (strtoupper($item->getStore()->getCurrentCurrencyCode()) == 'RON') {
                    $cartItems['NEW_ITEM-CURRENCY[' . $index . ']'] = 'ROL';
                }
            } elseif ($wwsCustomerId == "821931" || $wwsCustomerId == 821931) {
                $storeId = 2;
            } elseif ($wwsCustomerId == "180480" || $wwsCustomerId == 180480) {
                $storeId = 3;
            } else {
                $cartItems['NEW_ITEM-TAX[' . $index . ']'] = Mage::getStoreConfig('schrack/sales/vat');
                if (Mage::getStoreConfig('sapoci/custom_field_1/enabled', $storeId)) {
                    $cartItems['NEW_ITEM-CUST_FIELD1[' . $index . ']'] = $this->getFieldValue('cust_field1', $product);
                }
            }
            Mage::log('SAP_OCI : Log Entry Point #003 -> WWS-ID = ' . $wwsCustomerId, null, 'sapoci.log');

            if (Mage::getStoreConfig('sapoci/matnr/enabled', $storeId)) {
                $cartItems['NEW_ITEM-MATNR[' . $index . ']'] = $item->getSku();
                if($wwsCustomerId == "180480" || $wwsCustomerId == 180480) {
                    $customSku = $this->getCustomerIndividualArticleNumber($wwsCustomerId, $item->getSku());
                    if (strlen($customSku) > 3) {
                        $cartItems['NEW_ITEM-MATNR[' . $index . ']']       = $customSku;
                        $cartItems['NEW_ITEM-DESCRIPTION[' . $index . ']'] = '';
                    } else {
                        $cartItems['NEW_ITEM-MATNR[' . $index . ']']       = '';
                        $cartItems['NEW_ITEM-DESCRIPTION[' . $index . ']'] = substr($this->cleanString($item->getName()), 0, 40);
                    }
                }
            }

			// Get local delivery stock (lokales Zentrallager), and extract the delivery time (in hours) to full days:
			$stockHelper = Mage::helper('schrackcataloginventory/stock');
			$deliveryHours = $stockHelper->getLocalDeliveryStock()->getData('delivery_hours');
			$cartItems['NEW_ITEM-LEADTIME[' . $index . ']'] = ceil($deliveryHours / 24);

			// User defined fields
            if (Mage::getStoreConfig('sapoci/vendormat/enabled', $storeId)) {
                $cartItems['NEW_ITEM-VENDORMAT[' . $index . ']'] = $item->getSku();
            }

            if (Mage::getStoreConfig('sapoci/ext_product_id/enabled', $storeId)) {
                $cartItems['NEW_ITEM-EXT_PRODUCT_ID[' . $index . ']'] = $item->getSku();
            }

            if(Mage::getStoreConfig('sapoci/ext_quote_id/enabled', $storeId)) {
                if ($item->getSchrackOfferReference()) {
                    $cartItems['NEW_ITEM-EXT_QUOTE_ID[' . $index . ']'] = $item->getSchrackOfferReference();
                } else {
                    $cartItems['NEW_ITEM-EXT_QUOTE_ID[' . $index . ']'] = $this->getFieldValue('ext_quote_id', $product);
                }
            }

			if (Mage::getStoreConfig('sapoci/vendor/enabled', $storeId)) {
				$cartItems['NEW_ITEM-VENDOR[' . $index . ']'] = $this->getFieldValue('vendor', $product);
            }

			if (Mage::getStoreConfig('sapoci/matgroup/enabled', $storeId)) {
				$cartItems['NEW_ITEM-MATGROUP[' . $index . ']'] = $this->getFieldValue('matgroup', $product);
            }

			if (Mage::getStoreConfig('sapoci/manufactmat/enabled', $storeId)) {
				$cartItems['NEW_ITEM-MANUFACTMAT[' . $index . ']'] = $this->getFieldValue('manufactmat', $product);
            }

			if (Mage::getStoreConfig('sapoci/cust_field2/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD2[' . $index . ']'] = $this->getFieldValue('cust_field2', $product);
            }

			if (Mage::getStoreConfig('sapoci/cust_field3/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD3[' . $index . ']'] = $this->getFieldValue('cust_field3', $product);
            }

			if (Mage::getStoreConfig('sapoci/cust_field4/enabled', $storeId)) {
				$cartItems['NEW_ITEM-CUST_FIELD4[' . $index . ']'] = $this->getFieldValue('cust_field4', $product);
            }

            if (Mage::getStoreConfig('sapoci/cust_field5/enabled', $storeId)) {
                $cartItems['NEW_ITEM-CUST_FIELD5[' . $index . ']'] = $this->getFieldValue('cust_field5', $product);
            }

			// Product Options
            $cartItems['NEW_ITEM-LONGTEXT_'. $index .':132[]'] 		= $this->cleanString($product->getShortDescription());
			
			$helper = Mage::helper('catalog/product_configuration');
			$options = array();
			if ($options = $helper->getOptions($item))
				foreach ($options as $option)
					$cartItems['NEW_ITEM-LONGTEXT_'. $index .':132[]'] .= ' // ' . $this->cleanString($option['label']) . ': ' . $this->cleanString($option['value']);
				 
			if ($item->getOptionByCode('bundle_option_ids')){
				$options = array();
				$helper = Mage::helper('bundle/catalog_product_configuration');
				$options = $helper->getOptions($item);
				if (!empty($options))
					foreach ($options as $option)
						$cartItems['NEW_ITEM-LONGTEXT_'. $index .':132[]'] .= ' // ' . $this->cleanString($option['label']) . ': ' . $this->cleanString(implode(', ', $option['value']));
			}
			
			
			// Next Item
			$index++;
		}

        if (Mage::getStoreConfig('sapoci/configuration/logging')) {
            Mage::log(date('Y-m-d H:i:s') . ' -> Data.php::cartItems()', null, 'sapoci.log');
            Mage::log($cartItems, null, 'sapoci.log');
        }

		return $cartItems;
	}


	public function cleanString($data){
		$data = preg_replace('/<br\s*\/?>/i', "\n", $data);
		$data = str_replace("&nbsp;"," ",$data);
		$data = str_replace("&uuml;","ü",$data);
		$data = str_replace("&Uuml;","Ü",$data);
		$data = str_replace("&auml;","ä",$data);
		$data = str_replace("&Auml;","Ä",$data);
		$data = str_replace("&ouml;","ö",$data);
		$data = str_replace("&Ouml;","Ö",$data);
		$data = str_replace("&szlig;","ß",$data);
		$data = str_replace("&quot;","'",$data);
		$data = $this->stripTags($data, null, true);
		/* $data = utf8_encode($data); */
		
		return $data;
	}
	
	
	public function getFieldValue($field, $product){
        $storeId = null;
        $wwsCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();
        if ($wwsCustomerId == "703495" || $wwsCustomerId == 703495) {
            $storeId = 2;
        }
        if ($wwsCustomerId == "821931" || $wwsCustomerId == 821931) {
            $storeId = 2;
        }
        if ($wwsCustomerId == "180480" || $wwsCustomerId == 180480) {
            $storeId = 3;
        }

        Mage::log('SAP_OCI : Log Entry Point #004 -> WWS-ID = ' . $wwsCustomerId, null, 'sapoci.log');
		$value = Mage::getStoreConfig('sapoci/'.$field.'/value', $storeId);
		
		if (stripos($value, '@@') !== false){
			preg_match("/(.*)@@(.*)@@(.*)/i", $value, $result);
			if (is_array($result))
				$value = $result[1] . $product->getResource()->getAttribute($result[2])->getFrontend()->getValue($product) . $result[3];
		}
		
		$value = $this->cleanString($value);
		
		return $value;
	}


    public function getIsPunchout(){

        $session = Mage::getSingleton('customer/session');
        if (isset($session['sapoci']['HOOK_URL']) && !empty($session['sapoci']['HOOK_URL']))
            return true;

        return false;
    }


    public function prepareString($string){
        $string = strip_tags($string);
        $string = html_entity_decode($string);
        $quotes = array('"', "'");
        $string = str_replace($quotes, '', $string);

        // $string = utf8_encode($string);
        if (Mage::getStoreConfig('sapoci/configuration/transliteration')){
            setlocale(LC_CTYPE, 'de_DE');
            $string = iconv('UTF-8', 'UTF-8//TRANSLIT', $string);
        }

        return $string;
    }


    public function getCustomerIndividualArticleNumber($wws_customerid, $normal_sku) {
        $schrackCustomSku = null;
        if ($wws_customerid && $normal_sku) {
            $query  = "SELECT custom_sku FROM schrack_custom_sku WHERE wws_customer_id LIKE '" . $wws_customerid . "'";
            $query .= " AND sku LIKE '" . $normal_sku .  "'";

            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $schrackCustomSku = $readConnection->fetchOne($query);

            return $schrackCustomSku;
        }
    }


    // DEPRECATED
    /*
    public function getFieldValue($field, $product, $length = 255){
        $value = Mage::getStoreConfig('sapoci/' . $field . '/value');

        if (stripos($value, 'product.') === 0){
            $value = substr($value, 8);
            if ($value == 'tax_class_id'){
                $taxClassId = $product->getData("tax_class_id");
                $taxClasses = Mage::helper("core")->jsonDecode( Mage::helper("tax")->getAllRatesByProductClass() );
                $value = number_format($taxClasses["value_" . $taxClassId], 3, '.', '');
            }else{
                $value = $product->getResource()->getAttribute($value)->getFrontend()->getValue($product);
            }
        }

        if (stripos($value, 'customer.') === 0){
            $session = Mage::getSingleton('customer/session');
            $customer = $session->getCustomer();
            $value = substr($value, 9);
            // $value = $customer->getData($value);
            $value = $customer->getResource()->getAttribute($value)->getFrontend()->getValue($customer);
        }

        if (stripos($value, 'quote.') === 0){
            $quote = Mage::getSingleton('checkout/session')->getQuote()->getData();
            $value = substr($value, 6);
            if (isset($quote[$value]))
                $value = $quote[$value];
        }

        $value = $this->prepareString($value);
        $value = substr($value, 0, $length);

        return $value;
    }
    */
	
}
