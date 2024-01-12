<?php

class Orcamultimedia_Sapoci_Block_Validate extends Mage_Core_Block_Template {
	
	protected $_item;
	protected $_sapoci;

	public function _prepareLayout(){
		parent::_prepareLayout();

		$this->_sapoci = Mage::getSingleton('customer/session')->getData('sapoci');
		$this->_item = Mage::getModel('catalog/product')->load((int)$this->_sapoci['PRODUCTID']); 
    }


    public function getItem(){

    	$helper = Mage::helper('sapoci');
    	$item = array();

    	if(!$this->_item)
    		return $item;

    	$item['NEW_ITEM-DESCRIPTION[1]'] 		= substr($helper->prepareString($this->_item->getName()), 0, 40);
		$item['NEW_ITEM-QUANTITY[1]'] 			= substr(number_format((int)$this->_sapoci['QUANTITY'], 3, '.', ''), 0, 15);
		$item['NEW_ITEM-CURRENCY[1]'] 			= substr(Mage::app()->getStore()->getCurrentCurrencyCode(), 0, 5);
        $priceFactor                            = $this->_item->getQty() / $this->_item->getProduct()->getData('schrack_priceunit');
		// $item['NEW_ITEM-PRICE[1]'] 			= substr(number_format($this->_item->getFinalPrice((int)$this->_sapoci['QUANTITY']), 3, '.', ''), 0, 15);
        $unformattedPrice                       = str_replace(',', '.', Mage::helper('schrackcheckout')->formatPrice($this->_item->getProduct(), number_format( ($this->_item->getRowTotal() / $priceFactor), 2)));
        $item['NEW_ITEM-PRICE[1]'] 				= substr_replace($unformattedPrice, '.', -2, 0);
		$item['NEW_ITEM-VENDORMAT[1]'] 			= substr($this->_item->getSku(), 0, 40);
		$item['NEW_ITEM-EXT_PRODUCT_ID[1]'] 	= substr($this->_item->getId(), 0, 40);

        if ($this->_item->getSchrackOfferReference()) {
            $unformattedOfferPrice = $this->_item->getSchrackOfferPricePerUnit();
            $surcharges_per_unit = 0;

            if ($this->_item->getSchrackOfferSurcharge() > 0) {
                $surcharges_per_unit = number_format($this->_item->getSchrackOfferSurcharge(), 2);
            }

            $item['NEW_ITEM-PRICE[1]'] = number_format($unformattedOfferPrice, 2) + $surcharges_per_unit;
        } else {
            $item['NEW_ITEM-PRICE[1]'] = substr_replace($unformattedPrice, '.', -2, 0);
        }

        $storeId = null;
        $wwsCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();
        if ($wwsCustomerId == "703495" || $wwsCustomerId == 703495) {
            $storeId = 2;
            if (strtoupper(Mage::app()->getStore()->getCurrentCurrencyCode()) == 'RON') {
                $item['NEW_ITEM-CURRENCY[1]'] = 'ROL';
            }
        }
        if ($wwsCustomerId == "821931" || $wwsCustomerId == 821931) {
            $storeId = 2;
        }
        if ($wwsCustomerId == "180480" || $wwsCustomerId == 180480) {
            $storeId = 3;
            $customSku = $helper->getCustomerIndividualArticleNumber($wwsCustomerId, $item->getSku());
            if (strlen($customSku) > 3) {
                $cartItems['NEW_ITEM-DESCRIPTION[1]'] = '';
            } else {
                $cartItems['NEW_ITEM-DESCRIPTION[1]'] = substr($helper->prepareString($this->_item->getName()), 0, 40);
            }
        }

        Mage::log('SAP_OCI : Log Entry Point #005 -> WWS-ID = ' . $wwsCustomerId, null, 'sapoci.log');
		if(Mage::getStoreConfig('sapoci/vendor/enabled', $storeId))
			$item['NEW_ITEM-VENDOR[1]'] = $helper->getFieldValue('vendor', $this->_item, 10);
		if(Mage::getStoreConfig('sapoci/leadtime/enabled', $storeId))
			$item['NEW_ITEM-LEADTIME[1]'] = (int)$helper->getFieldValue('leadtime', $this->_item, 5);
		if(Mage::getStoreConfig('sapoci/matgroup/enabled', $storeId))
			$item['NEW_ITEM-MATGROUP[1]'] = $helper->getFieldValue('matgroup', $this->_item, 10);
		if(Mage::getStoreConfig('sapoci/manufactmat/enabled', $storeId))
			$item['NEW_ITEM-MANUFACTMAT[1]'] = $helper->getFieldValue('manufactmat', $this->_item, 40);
		if(Mage::getStoreConfig('sapoci/cust_field1/enabled', $storeId))
			$item['NEW_ITEM-CUST_FIELD1[1]'] = $helper->getFieldValue('cust_field1', $this->_item, 10);
		if(Mage::getStoreConfig('sapoci/cust_field2/enabled', $storeId))
			$item['NEW_ITEM-CUST_FIELD2[1]'] = $helper->getFieldValue('cust_field2', $this->_item, 10);
		if(Mage::getStoreConfig('sapoci/cust_field3/enabled', $storeId))
			$item['NEW_ITEM-CUST_FIELD3[1]'] = $helper->getFieldValue('cust_field3', $this->_item, 10);
		if(Mage::getStoreConfig('sapoci/cust_field4/enabled', $storeId))
			$item['NEW_ITEM-CUST_FIELD4[1]'] = $helper->getFieldValue('cust_field4', $this->_item, 20);
		if(Mage::getStoreConfig('sapoci/cust_field5/enabled', $storeId))
			$item['NEW_ITEM-CUST_FIELD5[1]'] = $helper->getFieldValue('cust_field5', $this->_item, 50);
		if(Mage::getStoreConfig('sapoci/ext_category_id/enabled', $storeId))
			$item['NEW_ITEM-EXT_CATEGORY_ID[1]'] = $helper->getFieldValue('ext_category_id', $this->_item, 60);
		if(Mage::getStoreConfig('sapoci/unit/enabled', $storeId))
			$item['NEW_ITEM-UNIT[1]'] = $helper->getFieldValue('unit', $this->_item, 3);
		if(Mage::getStoreConfig('sapoci/matnr/enabled', $storeId)) {
			$item['NEW_ITEM-MATNR[1]'] = $helper->getFieldValue('matnr', $this->_item, 40);
            if (strlen($customSku) > 3) {
                $item['NEW_ITEM-MATNR[1]'] = $customSku;
            }
        }
		if(Mage::getStoreConfig('sapoci/contract/enabled', $storeId))
			$item['NEW_ITEM-CONTRACT[1]'] = $helper->getFieldValue('contract', $this->_item, 10);
		if(Mage::getStoreConfig('sapoci/contract_item/enabled', $storeId))
			$item['NEW_ITEM-CONTRACT_ITEM[1]'] = $helper->getFieldValue('contract_item', $this->_item, 5);

        if(Mage::getStoreConfig('sapoci/ext_quote_id/enabled', $storeId)) {
            if ($this->_item->getSchrackOfferReference()) {
                $item['NEW_ITEM-EXT_QUOTE_ID[1]'] = $this->_item->getSchrackOfferReference();
            } else {
                $item['NEW_ITEM-EXT_QUOTE_ID[1]'] = $helper->getFieldValue('ext_quote_id', $this->_item, 35);
            }
        }

		if(Mage::getStoreConfig('sapoci/ext_quote_item/enabled', $storeId))
			$item['NEW_ITEM-EXT_QUOTE_ITEM[1]'] = $helper->getFieldValue('ext_quote_item', $this->_item, 35);
		if(Mage::getStoreConfig('sapoci/attachment/enabled', $storeId))
			$item['NEW_ITEM-ATTACHMENT[1]'] = $helper->getFieldValue('attachment', $this->_item, 255);
		if(Mage::getStoreConfig('sapoci/attachment_title/enabled', $storeId))
			$item['NEW_ITEM-ATTACHMENT_TITLE[1]'] = $helper->getFieldValue('attachment_title', $this->_item, 255);
		if(Mage::getStoreConfig('sapoci/attachment_purpose/enabled', $storeId))
			$item['NEW_ITEM-ATTACHMENT_PURPOSE[1]'] = $helper->getFieldValue('attachment_purpose', $this->_item, 1);
		if(Mage::getStoreConfig('sapoci/ext_schema_type/enabled', $storeId))
			$item['NEW_ITEM-EXT_SCHEMA_TYPE[1]'] = $helper->getFieldValue('ext_schema_type', $this->_item, 10);
		if(Mage::getStoreConfig('sapoci/ext_category/enabled', $storeId))
			$item['NEW_ITEM-EXT_CATEGORY[1]'] = $helper->getFieldValue('ext_category', $this->_item, 40);
		if(Mage::getStoreConfig('sapoci/sld_sys_name/enabled', $storeId))
			$item['NEW_ITEM-SLD_SYS_NAME[1]'] = $helper->getFieldValue('sld_sys_name', $this->_item, 60);
		if(Mage::getStoreConfig('sapoci/priceunit/enabled', $storeId))
			$item['NEW_ITEM-PRICEUNIT[1]'] = (int)$helper->getFieldValue('priceunit', $this->_item, 5);
		if(Mage::getStoreConfig('sapoci/manufactcode/enabled', $storeId))
			$item['NEW_ITEM-MANUFACTCODE[1]'] = $helper->getFieldValue('manufactcode', $this->_item, 10);
		if(Mage::getStoreConfig('sapoci/service/enabled', $storeId))
			$item['NEW_ITEM-SERVICE[1]'] = $helper->getFieldValue('service', $this->_item, 1);
		if(Mage::getStoreConfig('sapoci/custom/enabled', $storeId))
			$item['NEW_ITEM-' . strtoupper(Mage::getStoreConfig('sapoci/custom/name')) . '[1]'] = $helper->getFieldValue('custom', $this->_item, 1);
		if(Mage::getStoreConfig('sapoci/item_type/enabled', $storeId))
			$item['NEW_ITEM-ITEM_TYPE[1]'] = $helper->getFieldValue('item_type', $this->_item, 35);
		if(Mage::getStoreConfig('sapoci/parent_id/enabled', $storeId))
			$item['NEW_ITEM-PARENT_ID[1]'] = $helper->getFieldValue('parent_id', $this->_item, 35);

		// Product Options
        $item['NEW_ITEM-LONGTEXT_1:132[]'] = $helper->prepareString($this->_item->getShortDescription());

        if(Mage::getStoreConfig('sapoci/configuration/logging')) {
            Mage::log(date('Y-m-d H:i:s') . ' -> Validate.php', null, 'sapoci.log');
            Mage::log($item, null, 'sapoci.log');
        }

    	return $item;
    }


    public function getActionUrl(){
		return $this->_sapoci['HOOK_URL'];
	}
	

	public function getTarget(){
		if(isset($this->_sapoci['returntarget']) && !empty($this->_sapoci['returntarget']))
			return $this->_sapoci['returntarget'];
		
		return false;
	}
}
