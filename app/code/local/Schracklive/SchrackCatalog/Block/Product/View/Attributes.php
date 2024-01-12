<?php

class Schracklive_SchrackCatalog_Block_Product_View_Attributes extends Mage_Catalog_Block_Product_View_Attributes {

    protected function _construct() {
        parent::_construct();
        $this->addData(array(
            'cache_lifetime'    => 3600,
        ));
        $this->setCacheKey('catalog_product_view_attributes_'. md5(serialize(Mage::app()->getRequest()->getParams())) . '_' . Mage::getSingleton('customer/session')->getCustomer()->getId());
    }

    var $schrackHideAttribs = array(
		'schrack_spec_vpe' => true,
		'schrack_spec_pe' => true,
		'schrack_spec_me' => true,
        'schrack_vklw' => true,
        'schrack_keyword_foreign' => true,
        'schrack_keyword_foreign_hidden' => true,
	);

    /**
     * @param $attribute
     * @return mixed
     */
    private static function _getAttributeLabel($attribute)
    {
        $label = $attribute->getStoreLabel();
        if (!$label) {
            $label = $attribute->getFrontendLabel();
            return $label;
        }
        return $label;
    }

    public function getAdditionalData ( array $excludeAttr = array() ) {
        Varien_Profiler::start('Schracklive_SchrackCatalog_Block_Product_View_Attributes::getAdditionalData()');
		$data = array();
		$labels = array();
		$product = $this->getProduct();

        $category = $product->getPreferredCategory();

        $neededAttributes = explode(',', $category->getSchrackPropertyList());
        $neededAttributeNDX = array_flip($neededAttributes);
        $neededAttributeNDX['price'] = -3;       // ensure price and ean and sort them first
        $neededAttributeNDX['schrack_ean'] = -2;
        $neededAttributeNDX['schrack_vpes'] = 20000; // show on bottom; // -> temp. disabled until we have the type attribute

		// $attributes = $product->getAttributes();

        $showListPrice = Mage::helper('schrackcatalog/price')->doShowListPrice();
        if ( ! $showListPrice ) {
            unset($neededAttributeNDX['price']);
        }

        $intersectionAttributeCodes = array_intersect(array_keys($product->getData()),array_keys($neededAttributeNDX));
        $sqlAttributeCodes = "'" . implode("','",$intersectionAttributeCodes) . "'";
        $typeID = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        // DLA 20160928: performance boost for Magento's slow "give me all 500 attribute objects" approach:
        $sql =  "SELECT eav.attribute_code, cateav.is_visible_on_front, eav.frontend_input, eav.frontend_label, cateav.position FROM eav_attribute eav"
             . " JOIN catalog_eav_attribute cateav ON eav.attribute_id = cateav.attribute_id"
             . " WHERE attribute_code in ($sqlAttributeCodes)"
             . " AND entity_type_id = $typeID;";
        $attributes = $readConnection->fetchAll($sql);

        if ( Mage::getStoreConfig('schrack/shop/show_green_stamp') && $product->getSchrackStsGreenStamp() != null && $product->getSchrackStsGreenStamp() > '' ) {
            $attributeCode = 'schrack_sts_green_stamp';
            $greenStampLab = $this->__('green_stamp_lable');
            $val = 'green_stamp_value_' . $product->getSchrackStsGreenStamp();
            $greenStampVal = $this->__($val);
            if ( $val != $greenStampVal ) {
                $data[$attributeCode] = array(
                    'label' => html_entity_decode($greenStampLab),
                    'value' => html_entity_decode($greenStampVal),
                    'code' => $attributeCode,
                    'position' => -1
                );
            }
        }

        foreach ($attributes as $attribute) {
            $attributeCode = $attribute['attribute_code'];

			if (    $attribute['is_visible_on_front']
                 && isset($neededAttributeNDX[$attributeCode])
                 && !in_array($attributeCode,$excludeAttr) ) {


                if ( $attributeCode === 'schrack_vpes' ) {
                    if ( !intval($product->isCable())) {
                        $value = $this->_createSchrackVpesAttributeValue($attribute, $product);
                        if ($value) {
                            $label = $attribute['frontend_label'];
                            $data[$attributeCode] = array(
                                'label' => html_entity_decode($this->__($label)),
                                'value' => html_entity_decode($value),
                                'code' => $attributeCode,
                                'position' => $neededAttributeNDX[$attributeCode]
                            );
                        }
                    }
                } else {
                    switch ( $attribute['frontend_input'] ) {
                        case 'multiselect':
                        case 'select':
                        case 'dropdown':
                            $value = $product->getAttributeText($attributeCode);
                            if (is_array($value)) {
                                $value = implode(', ', $value);
                            }
                            break;
                        default:
                            $value = $product->getData($attributeCode);
                            break;
                    }
                    if ( $attribute['frontend_input'] == 'price' && is_string($value) ) {
                        $value = Mage::app()->getStore()->convertPrice($value, true);
                    }
                    if ( is_string($value) && strlen($value) ) {
                        if ( ! isset($this->schrackHideAttribs[$attributeCode]) ) {
                            $labels[$attributeCode] = $attribute['frontend_label'];
                            $positions[$attributeCode] = $attribute['position'];
                            $label = $attribute['frontend_label'];
                            $data[$attributeCode] = array(
                                'label' => html_entity_decode($label),
                                'value' => html_entity_decode($value),
                                'code' => $attributeCode,
                                'position' => $neededAttributeNDX[$attributeCode]
                            );
                        }
                    }
                }
			}
		}

        uasort($data, function($a, $b){
                          return ($a['position'] < $b['position']) ? -1 : 1;
                      }
        );

        Varien_Profiler::stop('Schracklive_SchrackCatalog_Block_Product_View_Attributes::getAdditionalData()');
		return $data;
	}

    /**
     * @param $attribute
     * @param $product
     * @return null|string "<vpe * qtyunit>, <vpe * qtyunit>"
     */
    private function _createSchrackVpesAttributeValue($attribute, $product)
    {
        $schrackVpes = $product->getSchrackVpesUnserialized();
        if ( ! $schrackVpes ) {
            return null;
        }
        $resultVpes = array();
        foreach ( $schrackVpes as $key => $subVpes ) {
            $lastQty = null;
            foreach ( $subVpes as $vpe ) {
                if ( isset($vpe['quantity']) ) {
                    $qty = intval($vpe['quantity']);
                    if ( isset($lastQty) ) {
                        $qty *= $lastQty;
                    }
                } else {
                    $qty = 1;
                }
                $lastQty = $qty;
                $qtyStr = $qty . ' ' . $product->getSchrackQtyunit();
                if (    isset($vpe['deliverable']) && $vpe['deliverable'] === true
                     && isset($vpe['type'])        && $vpe['type']        !== 'PAL' ) {
                    $resultVpes[$qty] = $qtyStr;
                }
            }
        }
        if ( count($resultVpes) == 0 ) {
            return null;
        }
        ksort($resultVpes,SORT_NUMERIC);
        $res = implode(', ',$resultVpes);
        return $res;
   }

    public function getFamilyLinkHtmls ( Schracklive_SchrackCatalog_Model_Product $product ) {
        $currentStoreId = Mage::app()->getStore()->getId();
        $res = array();
        $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $mainSku = $product->hasMainProduct() ? $product->getSchrackStsMainArticleSku() : $product->getSku();
        $sql = "SELECT DISTINCT entity_id, sku, schrack_sts_main_vpe_type AS vpe, url.request_path AS url FROM catalog_product_entity prod"
             . " JOIN core_url_rewrite url ON (url.product_id = prod.entity_id AND url.category_id IS NULL)"
             . " WHERE schrack_sts_main_article_sku = ? AND sku <> ? AND schrack_sts_statuslocal NOT IN ('tot','strategic_no','unsaleable')"
             . " AND url.store_id = ?"
             . " ORDER BY sku";
        $dbRes = $connection->fetchAll($sql,array($mainSku,$product->getSku(),$currentStoreId));
        foreach ( $dbRes as $row ) {
            $res[] = $this->mkLinkHtml($row);
        }
        if ( $product->hasMainProduct() ) {
            $sql = "SELECT entity_id, sku, schrack_sts_main_vpe_type AS vpe, url.request_path AS url FROM catalog_product_entity prod"
                 . " JOIN core_url_rewrite url ON (url.product_id = prod.entity_id AND url.category_id IS NULL)"
                 . " WHERE sku = ? AND schrack_sts_statuslocal NOT IN ('tot','strategic_no','unsaleable')"
                 . " AND url.store_id = ?";
            $dbRes = $connection->fetchAll($sql,array($product->getSchrackStsMainArticleSku(),$currentStoreId));
            foreach ( $dbRes as $row ) {
                $row['vpe'] = 'Other lengths, possible with cutting costs';
                $res[] = $this->mkLinkHtml($row);
                break; // should not happen that there is more than one...
            }
        }
        return $res;
    }

    private function mkLinkHtml ( $row ) {
        return '<a href="' . Mage::getUrl($row['url']) .'">'. $row['sku'] . '</a> <span style="color: black; white-space: nowrap;">' . $this->__($row['vpe']) . '</span><br/>';
    }
}