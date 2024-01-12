<?php

class Schracklive_SchrackCatalog_Model_Import_Product extends Schracklive_SchrackCatalog_Model_Import_Entity {

	protected $entityCode = 'catalog_product';
	protected $_entityTypeId;

	public function __construct() {
		parent::__construct();

		$this->productModel = Mage::getModel('catalog/product');
		$this->websiteId = Mage::app()->getStore(true)->getWebsite()->getId();
		$this->stockItemModel = Mage::getModel('cataloginventory/stock_item');
		$this->productStatus = Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
		$this->_entityTypeId = Mage::getModel('eav/entity')->setType('catalog_product')->getTypeId();
	}

	protected function _getData($attribute, $name, $label, $groupId, $setId, $type, $position, $options) {
		$data = $this->_getBaseData($name, $label, $attribute->getId(), $type, $position);
		// if ((stripos($name, 'schrack_facet') === 0) || (stripos($name, 'schrack_url') === 0)) {
			$data['backend_type'] = $attribute->getBackendTypeByInput($data['frontend_input']);
            /*
		} else {
			$data['backend_type'] = 'static';
			if (!$attribute->getId()) {
				$conn = Mage::getSingleton('core/resource')->getConnection('core_write');
				try {
					if (strpos($name, '_position') === false) {
						$conn->query("ALTER TABLE catalog_product_entity ADD `".$name."` VARCHAR(255) NOT NULL DEFAULT ''");
					} else {
						$conn->query("ALTER TABLE catalog_product_entity ADD `".$name."` INT(10) NOT NULL DEFAULT 0");
					}
				} catch (Exception $e) {
					// Ignore Exception
					// TODO: Ignore only if exception says column exists
				}
			}
		}
             */
		if ($groupId) {
			$data['attribute_group_id'] = $groupId;
		}
		if ($setId) {
			$data['attribute_set_id'] = $setId;
		}
		if ($type == 'multiselect') {
			$data['option'] = $this->_getOptionData($attribute, $options);
		}
		return $data;
	}

	protected function _getBaseData($name, $label, $attributeId = null, $type = "text", $position = 20) {
		$data = array();
		if ($attributeId) {
			$data['attribute_id'] = $attributeId;
		}
		$data['is_global'] = "1";
		$data["default_value_text"] = "";
		$data["default_value_yesno"] = "0";
		$data["default_value_date"] = "";
		$data["default_value_textarea"] = "";
		$data['is_unique'] = "0";
		$data['is_required'] = "0";
		$data["is_configurable"] = "1";
		$data['is_visible_on_front'] = "1";
        $data['is_searchable'] = "1";
        $data['is_visible_in_advanced_search'] = "1";
        $data['is_comparable'] = "1";
        $data['is_filterable'] = ($type == "text") ? ("0") : ("1");
        $data['used_in_product_listing'] = "1";
		$data["is_filterable_in_search"] = "0";
		$data["is_used_for_price_rules"] = "0";
		$data['position'] = $position;
		$data["is_wysiwyg_enabled"] = "0";
		$data['is_html_allowed_on_front'] = "1";
		$data['frontend_label'] = array($label, "");
		$data['attribute_code'] = $name;
		$data["is_user_defined"] = "1";
		$data['entity_type_id'] = $this->_entityTypeId;
		$data['frontend_input'] = $type;
		$data['apply_to'] = array();
		if ($name == 'schrack_position') {
			$data['used_for_sort_by'] = "1";
		}
		if (isset($data['frontend_input']) && $data['frontend_input'] == 'multiselect') {
			$data['backend_model'] = 'eav/entity_attribute_backend_array';
		}
		return $data;
	}

	protected function _getOptionData($attribute, $newOptions = array()) {
		$optionData = null;
		$_source = $attribute->getSource();
		$count = 0;
		$_oldOptionArr = array('value' => array(), 'order' => array(), 'delete' => array());
		$optionData = array('value' => array(), 'order' => array(), 'delete' => array());
		if ($_source instanceof Mage_Eav_Model_Entity_Attribute_Source_Interface) {
			$oldOptions = $_source->getAllOptions(true, true);
			foreach ($oldOptions as $oldOption) {
				if ($oldOption['value'] != "") {
					$optionData['value'][$oldOption['value']] = array($oldOption['label'], ""); //Add the option to the new array
					$optionData['order'][$oldOption['value']] = ($count + 1);
					if (!in_array($oldOption['label'], $newOptions)) {
						$optionData['delete'][$oldOption['value']] = "1";
					} else {
						$optionData['delete'][$oldOption['value']] = "";
					}
					$_oldOptionArr['value'][$oldOption['value']] = array($oldOption['label']);
					$count++;
				}
			}
		}
		foreach ($newOptions as $newOption) {
			if (!in_array(array($newOption), $_oldOptionArr['value']) && $newOption != '') { //If the option doesn't exist already in Magento...
				$optionData['value']['option_'.$count] = array($newOption, ""); //Add the option to the new array
				$optionData['order']['option_'.$count] = ($count + 1);
				$optionData['delete']['option_'.$count] = "";
				$count++;
			}
		}
		return $optionData;
	}

	public function getAttribute($name) {
		$attribute = parent::getAttribute($name);
		if (!$attribute && (stripos($name, 'schrack_facet_') === 0)) {
			if ((strrpos($name, '_multi') !== null)) {
				$_i = strrpos($name, '_multi');
				$attribute = $this->getAttribute(substr($name, 0, $_i));
				if (!$attribute) {
					$this->getAttribute(substr($name, 0, $_i).'_single');
				}
			} elseif ((strrpos($name, '_single') !== null)) {
				$_i = strrpos($name, '_single');
				$attribute = $this->getAttribute(substr($name, 0, $_i));
				if (!$attribute) {
					$this->getAttribute(substr($name, 0, $_i).'_multi');
				}
			}
		}
		return $attribute;
	}

	public function createAttribute($name, $label, $groupId, $setId, $type = "text", $position = 20, $options = array()) {
		// Try loading the attribute
		$attribute = $this->getAttribute($name);
		// If not loaded, create new object
		if (!is_object($attribute)) {
			$attribute = Mage::getModel('catalog/resource_eav_attribute');
		}
		// Set attribute data
		$data = $this->_getData($attribute, $name, $label, $groupId, $setId, $type, $position, $options);
		$attribute->addData($data);
		// Save & return
        try {
            $attribute->save();
        }
        catch ( Mage_Core_Exception  $ex ) {
            echo "create of attribute '".$name."' failed.".PHP_EOL;
            throw $ex;
        }
		return $attribute;
	}

	public function setProductStatus($status) {
		$this->productStatus = $status;
	}

	public function addProduct($data, $storeId = 1, $attributeSetId = null, $oldProduct=null) {
		

		$conn = Mage::getSingleton('core/resource')->getConnection('core_write');
		$productId = null;
		$product = Mage::getModel('catalog/product');
		if ($oldProduct != null) {
			$product = $oldProduct;
			unset($data['sku']);
			$productId = $product->getId();
		}
		$attachments=null;
		if (isset ($data['attachments'])) {
			$attachments = $data['attachments'];
			unset($data['attachments']);
			/*if (!$productId) $product->getAttachmentsCollection();
			foreach ($attachments as $attachment) {
				$product->addAttachment($attachment);
			}*/
			//if ($oldProduct) $product->cleanAttachments();
		}
		
		
		$product->disableCache();
		$product->addData($data);
		$product->setAttributeSetId($attributeSetId);
		$product->setStatus($this->productStatus);
		$product->setTypeId($data['type']);
		$product->setTaxClassId(0); //none

		if (!$productId) $product->setCreatedAt(strtotime('now'));
		$product->setUpdatedAt(strtotime('now'));
		$product->setWebsiteIds(array($this->websiteId));
		$product->setCategoryIds($data['categories']);

		/* add attchments */

		
		
		$product->save();
		if($attachments){
			$product->getAttachmentsCollection();
			foreach ($attachments as $attachment) {
                                $product->addAttachment($attachment);
                        }
			$product->cleanAttachments();
			$product->save();
		}
		$category_string = "";
		$url_paths = $conn->fetchAll('select replace(url_path,".html","") as path from catalog_category_flat_store_1 where entity_id in (select category_id from catalog_category_product where product_id='.$product->getId().')');
		foreach ($url_paths as $url_path) {
			$path = substr($url_path['path'], strpos($url_path['path'], '/'));
			$category_string.=$path.",";
		}
		$conn->query("UPDATE catalog_product_entity SET schrack_category_names='".$category_string."' WHERE entity_id=".$product->getId());
		if (!$productId) {
			echo "S";
			$stockItem = clone $this->stockItemModel;
			$stockItem = $stockItem->loadByProduct($product);
			$stockItem->assignProduct($product);
			$stockItem->setData('is_in_stock', 1);
			$stockItem->setData('stock_id', 1);
			$stockItem->setData('store_id', 1);
			$stockItem->setData('manage_stock', 0);
			$stockItem->setData('use_config_manage_stock', 1);
			$stockItem->setData('min_sale_qty', 0);
			$stockItem->setData('use_config_min_sale_qty', 1);
			//$stockItem->setData('max_sale_qty', 1000000);
			$stockItem->setData('use_config_max_sale_qty', 1);
			$stockItem->save();
		}
	}

}
