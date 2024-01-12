<?php

class Orcamultimedia_Sapoci_Block_Search extends Mage_Core_Block_Template {
	
	protected $_sapoci;
	protected $_productCollection;

	public function _prepareLayout(){
		parent::_prepareLayout();

		$this->_sapoci = Mage::getSingleton('customer/session')->getData('sapoci');
		$this->_productCollection = Mage::getSingleton('catalogsearch/query')->getResultCollection()->addAttributeToSelect('*');
    }


    public function getSearchItems(){
    	$helper = Mage::helper('sapoci');
    	$items = array();

    	$index = 1;

    	if(!$this->_productCollection)
    		return $items;

    	foreach ($this->_productCollection as $item) {

            if ($item->getProduct() == null) {
                continue;
            } else {
                $items['NEW_ITEM-DESCRIPTION[' . $index . ']'] 	  = substr($helper->prepareString($item->getName()), 0, 40);
                $items['NEW_ITEM-QUANTITY[' . $index . ']'] 	  = substr(number_format(1, 3, '.', ''), 0, 15);
                $priceFactor                                      = $item->getQty() / $item->getProduct()->getData('schrack_priceunit');
                $items['NEW_ITEM-CURRENCY[' . $index . ']'] 	  = substr(Mage::app()->getStore()->getCurrentCurrencyCode(), 0, 5);
                //$items['NEW_ITEM-PRICE[' . $index . ']'] 	      = substr(number_format($item->getFinalPrice(1), 3, '.', ''), 0, 15);
                $unformattedPrice                                 = str_replace(',', '.', Mage::helper('schrackcheckout')->formatPrice($item->getProduct(), number_format( ($item->getRowTotal() / $priceFactor), 2)));
                $items['NEW_ITEM-VENDORMAT[' . $index . ']'] 	  = substr($item->getSku(), 0, 40);
                $items['NEW_ITEM-EXT_PRODUCT_ID[' . $index . ']'] = substr($item->getId(), 0, 40);

                if ($item->getSchrackOfferReference()) {
                    $unformattedOfferPrice = $item->getSchrackOfferPricePerUnit();
                    $surcharges_per_unit = 0;

                    if ($item->getSchrackOfferSurcharge() > 0) {
                        $surcharges_per_unit = number_format($item->getSchrackOfferSurcharge(), 2);
                    }

                    $items['NEW_ITEM-PRICE[' . $index . ']'] = number_format($unformattedOfferPrice, 2) + $surcharges_per_unit;
                } else {
                    $items['NEW_ITEM-PRICE[' . $index . ']'] = substr_replace($unformattedPrice, '.', -2, 0);
                }

                $storeId = null;
                $wwsCustomerId = Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();
                if ($wwsCustomerId == "703495" || $wwsCustomerId == 703495) {
                    $storeId = 2;
                    if (strtoupper(Mage::app()->getStore()->getCurrentCurrencyCode()) == 'RON') {
                        $cartItems['NEW_ITEM-CURRENCY[' . $index . ']'] = 'ROL';
                    }
                }
                if ($wwsCustomerId == "821931" || $wwsCustomerId == 821931) {
                    $storeId = 2;
                }
                if ($wwsCustomerId == "180480" || $wwsCustomerId == 180480) {
                    $storeId = 3;
                    $customSku = $helper->getCustomerIndividualArticleNumber($wwsCustomerId, $item->getSku());
                    if (strlen($customSku) > 3) {
                        $cartItems['NEW_ITEM-DESCRIPTION[' . $index . ']'] = '';
                    } else {
                        $cartItems['NEW_ITEM-DESCRIPTION[' . $index . ']'] = substr($helper->prepareString($item->getName()), 0, 40);
                    }
                }

                Mage::log('SAP_OCI : Log Entry Point #002 -> WWS-ID = ' . $wwsCustomerId, null, 'sapoci.log');
                if(Mage::getStoreConfig('sapoci/vendor/enabled', $storeId))
                    $items['NEW_ITEM-VENDOR[' . $index . ']'] = $helper->getFieldValue('vendor', $item, 10);
                if(Mage::getStoreConfig('sapoci/leadtime/enabled', $storeId))
                    $items['NEW_ITEM-LEADTIME[' . $index . ']'] = (int)$helper->getFieldValue('leadtime', $item, 5);
                if(Mage::getStoreConfig('sapoci/matgroup/enabled', $storeId))
                    $items['NEW_ITEM-MATGROUP[' . $index . ']'] = $helper->getFieldValue('matgroup', $item, 10);
                if(Mage::getStoreConfig('sapoci/manufactmat/enabled', $storeId))
                    $items['NEW_ITEM-MANUFACTMAT[' . $index . ']'] = $helper->getFieldValue('manufactmat', $item, 40);
                if(Mage::getStoreConfig('sapoci/cust_field1/enabled', $storeId))
                    $items['NEW_ITEM-CUST_FIELD1[' . $index . ']'] = $helper->getFieldValue('cust_field1', $item, 10);
                if(Mage::getStoreConfig('sapoci/cust_field2/enabled', $storeId))
                    $items['NEW_ITEM-CUST_FIELD2[' . $index . ']'] = $helper->getFieldValue('cust_field2', $item, 10);
                if(Mage::getStoreConfig('sapoci/cust_field3/enabled', $storeId))
                    $items['NEW_ITEM-CUST_FIELD3[' . $index . ']'] = $helper->getFieldValue('cust_field3', $item, 10);
                if(Mage::getStoreConfig('sapoci/cust_field4/enabled', $storeId))
                    $items['NEW_ITEM-CUST_FIELD4[' . $index . ']'] = $helper->getFieldValue('cust_field4', $item, 20);
                if(Mage::getStoreConfig('sapoci/cust_field5/enabled', $storeId))
                    $items['NEW_ITEM-CUST_FIELD5[' . $index . ']'] = $helper->getFieldValue('cust_field5', $item, 50);
                if(Mage::getStoreConfig('sapoci/ext_category_id/enabled', $storeId))
                    $items['NEW_ITEM-EXT_CATEGORY_ID[' . $index . ']'] = $helper->getFieldValue('ext_category_id', $item, 60);
                if(Mage::getStoreConfig('sapoci/unit/enabled', $storeId))
                    $items['NEW_ITEM-UNIT[' . $index . ']'] = $helper->getFieldValue('unit', $item, 3);
                if(Mage::getStoreConfig('sapoci/matnr/enabled', $storeId)) {
                    $items['NEW_ITEM-MATNR[' . $index . ']'] = $helper->getFieldValue('matnr', $item, 40);
                    if (strlen($customSku) > 3) {
                        $items['NEW_ITEM-MATNR[' . $index . ']'] = $customSku;
                    }
                }
                if(Mage::getStoreConfig('sapoci/contract/enabled', $storeId))
                    $items['NEW_ITEM-CONTRACT[' . $index . ']'] = $helper->getFieldValue('contract', $item, 10);
                if(Mage::getStoreConfig('sapoci/contract_item/enabled', $storeId))
                    $items['NEW_ITEM-CONTRACT_ITEM[' . $index . ']'] = $helper->getFieldValue('contract_item', $item, 5);

                if (Mage::getStoreConfig('sapoci/ext_quote_id/enabled', $storeId)) {
                    if ($item->getSchrackOfferReference()) {
                        $items['NEW_ITEM-EXT_QUOTE_ID[' . $this->index . ']'] = $item->getSchrackOfferReference();
                    } else {
                        $items['NEW_ITEM-EXT_QUOTE_ID[' . $this->index . ']'] = $helper->getFieldValue('ext_quote_id', $item, 35);
                    }
                }

                if(Mage::getStoreConfig('sapoci/ext_quote_item/enabled', $storeId))
                    $items['NEW_ITEM-EXT_QUOTE_ITEM[' . $index . ']'] = $helper->getFieldValue('ext_quote_item', $item, 35);
                if(Mage::getStoreConfig('sapoci/attachment/enabled', $storeId))
                    $items['NEW_ITEM-ATTACHMENT[' . $index . ']'] = $helper->getFieldValue('attachment', $item, 255);
                if(Mage::getStoreConfig('sapoci/attachment_title/enabled', $storeId))
                    $items['NEW_ITEM-ATTACHMENT_TITLE[' . $index . ']'] = $helper->getFieldValue('attachment_title', $item, 255);
                if(Mage::getStoreConfig('sapoci/attachment_purpose/enabled', $storeId))
                    $items['NEW_ITEM-ATTACHMENT_PURPOSE[' . $index . ']'] = $helper->getFieldValue('attachment_purpose', $item, 1);
                if(Mage::getStoreConfig('sapoci/ext_schema_type/enabled', $storeId))
                    $items['NEW_ITEM-EXT_SCHEMA_TYPE[' . $index . ']'] = $helper->getFieldValue('ext_schema_type', $item, 10);
                if(Mage::getStoreConfig('sapoci/ext_category/enabled', $storeId))
                    $items['NEW_ITEM-EXT_CATEGORY[' . $index . ']'] = $helper->getFieldValue('ext_category', $item, 40);
                if(Mage::getStoreConfig('sapoci/sld_sys_name/enabled', $storeId))
                    $items['NEW_ITEM-SLD_SYS_NAME[' . $index . ']'] = $helper->getFieldValue('sld_sys_name', $item, 60);
                if(Mage::getStoreConfig('sapoci/priceunit/enabled', $storeId))
                    $items['NEW_ITEM-PRICEUNIT[' . $index . ']'] = (int)$helper->getFieldValue('priceunit', $item, 5);
                if(Mage::getStoreConfig('sapoci/manufactcode/enabled', $storeId))
                    $items['NEW_ITEM-MANUFACTCODE[' . $index . ']'] = $helper->getFieldValue('manufactcode', $item, 10);
                if(Mage::getStoreConfig('sapoci/service/enabled', $storeId))
                    $items['NEW_ITEM-SERVICE[' . $index . ']'] = $helper->getFieldValue('service', $item, 1);
                if(Mage::getStoreConfig('sapoci/custom/enabled', $storeId))
                    $items['NEW_ITEM-' . strtoupper(Mage::getStoreConfig('sapoci/custom/name')) . '[' . $index . ']'] = $helper->getFieldValue('custom', $item, 1);
                if(Mage::getStoreConfig('sapoci/item_type/enabled', $storeId))
                    $items['NEW_ITEM-ITEM_TYPE[' . $index . ']'] = $helper->getFieldValue('item_type', $item, 35);
                if(Mage::getStoreConfig('sapoci/parent_id/enabled', $storeId))
                    $items['NEW_ITEM-PARENT_ID[' . $index . ']'] = $helper->getFieldValue('parent_id', $item, 35);

                // Product Options
                $items['NEW_ITEM-LONGTEXT_'. $index .':132[]'] = $helper->prepareString($item->getShortDescription());

                $index++;
            }
        }

        if(Mage::getStoreConfig('sapoci/configuration/logging')) {
            Mage::log(date('Y-m-d H:i:s') . ' -> Link.php', null, 'sapoci.log');
            Mage::log($items, null, 'sapoci.log');
        }

    	return $items;
    }


    public function getActionUrl(){
		return $this->_sapoci['HOOK_URL'];
	}


	public function getTarget(){
		if (isset($this->_sapoci['returntarget']) && !empty($this->_sapoci['returntarget'])) {
			return $this->_sapoci['returntarget'];
        } else {
		    return false;
        }
	}
}
