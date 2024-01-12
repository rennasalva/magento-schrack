<?php

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Europe/Vienna');
require_once '../../app/Mage.php';
require_once('../../vendor/autoload.php');

class Entity {

	protected $entityCode = '';
	protected $skeletonAttributeSetName = "Default"; // comes with Magento

	public function __construct() {
		ini_set("memory_limit", "3096M");
		Mage::app('admin');

		$this->attributeSetModel = Mage::getModel('eav/entity_attribute_set');
		$this->attributeModel = Mage::getModel('catalog/entity_attribute');
		$this->entityType = Mage::getModel('eav/entity')->setType($this->entityCode)->getTypeId();
		$this->optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection');
		$this->skeletonAttributeSetId = $this->getSkeletonAttributeSetId();
		$this->stores = $this->getStores();
	}

	public function getSkeletonAttributeSetId() {
		$sets = clone $this->attributeSetModel;
		$sets = $sets->getResourceCollection()
						->setEntityTypeFilter($this->entityType)
						->addFieldToFilter("attribute_set_name", $this->skeletonAttributeSetName)
						->load()->toArray();
		return $sets['items'][0]['attribute_set_id'];
	}

	public function getStores() {
		$stores = array();
		foreach (Mage::app()->getStores() as $store) {
			$stores[] = $store->getId();
		}
		return $stores;
	}

	public function getAttribute($name) {
		$attribute = clone $this->attributeModel;
		if (is_numeric($name)) {
			$attribute->load($name);
		} else {
			$attribute->loadByCode($this->entityType, $name);
		}
		if (!is_numeric($attribute->getAttributeId())) {
			return false;
		} else {
			return $attribute;
		}
	}

	public function addAttributeToSet($attr, $attset) {
		if (!is_numeric($attset)) {
			$attset = $this->getAttributeSetId($attset);
		}
		if ($attset == false) return false;
		$groups = Mage::getModel('eav/entity_attribute_group')->getResourceCollection()
						->setAttributeSetFilter($attset)
						->load()->toArray();
		$attribute = $this->getAttribute($attr);
		$attribute->setAttributeGroupId($groups['items'][0]["attribute_group_id"])
				->setAttributeSetId($attset)->save();
	}

	public function getAttributeSetId($attset) {
		$collection = Mage::getResourceModel('eav/entity_attribute_set_collection')
						->setEntityTypeFilter($this->entityType);
		foreach ($collection as $item) {
			if ($item->getAttributeSetName() == $attset) {
				return $item->getAttributeSetId();
			}
		}
		return false;
	}

	public function createAttributeSet($name) {
		$id = $this->getAttributeSetId($name);
		if ($id != false) return $id;
		$modelSet = clone $this->attributeSetModel;
		$modelSet->setAttributeSetName($name)->setEntityTypeId($this->entityType)->save();
		$modelSet->initFromSkeleton($this->skeletonAttributeSetId)->save();
		return $modelSet->getAttributeSetId();
	}

	public function getAttributeValueId($name, $value) {
		$attribute = $this->getAttribute($name);
		$optionCollection = Mage::getResourceModel('eav/entity_attribute_option_collection')
						->setAttributeFilter($attribute->getId())
						->setPositionOrder('desc', true)
						->load();
		$optionCollection = $optionCollection->toOptionArray();
		foreach ($optionCollection as $option) {
			if ($option['label'] == $value) return $option['value'];
		}
		return false;
	}

	function xml2array($contents, $get_attributes=1) {
		if (!$contents) return array();

		if (!function_exists('xml_parser_create')) {

			return array();
		}
		//Get the XML parser of PHP - PHP must have this module for the parser to work
		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, $contents, $xml_values);
		xml_parser_free($parser);

		if (!$xml_values) return;
		$xml_array = array();
		$parents = array();
		$opened_tags = array();
		$arr = array();

		$current = &$xml_array;

		//Go through the tags.
		foreach ($xml_values as $data) {
			unset($attributes, $value); //Remove existing values, or there will be trouble
			// "extract" imports the array keys as variables into the current name space:
			// tag(string), type(string), level(int), attributes(array).
			extract($data);

			$result = '';
			if ($get_attributes) {//The second argument of the function decides this.
				$result = array();
				if (isset($value)) $result['value'] = $value;

				//Set the attributes too.
				if (isset($attributes)) {
					foreach ($attributes as $attr => $val) {
						if ($get_attributes == 1)
								$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
							/**  :TODO: should we change the key name to '_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too */
					}
				}
			} elseif (isset($value)) {
				$result = $value;
			}

			//See tag status and do the needed.
			if ($type == "open") {// Start of element '<tag>'
				$parent[$level - 1] = &$current;

				if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
					$current[$tag] = $result;
					$current = &$current[$tag];
				} else { //There was another element with the same tag name
					if (isset($current[$tag][0])) {
						array_push($current[$tag], $result);
					} else {
						$current[$tag] = array($current[$tag], $result);
					}
					$last = count($current[$tag]) - 1;
					$current = &$current[$tag][$last];
				}
			} elseif ($type == "complete") { // Empty element '<tag />'
				//See if the key is already taken.
				if (!isset($current[$tag])) { //New Key
					$current[$tag] = $result;
				} else { //If taken, put all things inside a list(array)
					if ((is_array($current[$tag]) and $get_attributes == 0) //If it is already an array...
							or (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) {
						array_push($current[$tag], $result); // ...push the new element into that array.
					} else { //If it is not an array...
						$current[$tag] = array($current[$tag], $result); //...Make it an array using using the existing value and the new value
					}
				}
			} elseif ($type == 'close') { // End of element '</tag>'
				$current = &$parent[$level - 1];
			}
		}

		return($xml_array);
	}

}

class magento extends Entity {

	protected $entityCode = 'catalog_product';

	public function __construct() {
		parent::__construct();

		$this->productModel = Mage::getModel('catalog/product');
		$this->websiteId = Mage::app()->getStore(true)->getWebsite()->getId();
		$this->stockItemModel = Mage::getModel('cataloginventory/stock_item');
		$this->productStatus = Mage_Catalog_Model_Product_Status::STATUS_ENABLED;
	}

	public function createAttribute($name, $label, $type = "text", $position = 20, $options=null) {

		$attribute = $this->getAttribute($name);
		if (!$attribute && (stripos($name, 'schrack_search') === 0)) {
			if ((strrpos($name, '_multi') !== null)) {
				$_i = strrpos($name, '_multi');
				substr($name, 0, $_i);
				$attribute = $this->getAttribute(substr($name, 0, $_i));
			} else {
				if ((strrpos($name, '_single') !== null)) {
					$_i = strrpos($name, '_single');
					substr($name, 0, $_i);
					$attribute = $this->getAttribute(substr($name, 0, $_i));
				}
			}
		}
		$optionData = null;
		if ($options != null) {
			$_oldOptionArr = array('value' => array(), 'order' => array(), 'delete' => array());
			if ($attribute) {
				foreach ($attribute->getSource()->getAllOptions(true, true) as $option) {
					$_oldOptionArr['value'][$option['value']] = array($option['label']);
				}
			}
			$optionData = array('value' => array(), 'order' => array(), 'delete' => array());
			$count = 0;
			foreach ($options as $_newOption) {
				$count++;
				if (!in_array(Array($_newOption), $_oldOptionArr['value']) && $_newOption != '') { //If the option doesn't exist already in Magento...
					$optionData['value']['option_'.$count] = array($_newOption); //Add the option to the new array
					$optionData['order']['option_'.$count] = $count;
				}
			}
		}

		$data = array();
		$data['attribute_code'] = $name;
		$data['frontend_input'] = $type;
		$data['frontend_label'] = $label;
		$data['is_html_allowed_on_front'] = "1";
		$data['position'] = $position;
		$data['is_global'] = "1";
		$data['is_required'] = "0";
		if (stripos($name, 'schrack_url') === 0) {
			$data['is_searchable'] = "0";
			$data['is_comparable'] = "0";
			$data['is_visible_in_advanced_search'] = "0";
			$data['is_filterable'] = "0";
		} else {
			$data['is_searchable'] = "1";
			$data['is_comparable'] = "1";
			$data['is_visible_in_advanced_search'] = "1";
			$data['is_filterable'] = ($type == "text") ? ("0") : ("1");
		}
		$data['position'] = $position;
		$data['is_visible_on_front'] = "1";
		$data['is_unique'] = "0";

		if ($attribute != false) {
			if ((stripos($name, 'schrack_search') === 0) || (stripos($name, 'schrack_url') === 0)) {
				$data['backend_type'] = $attribute->getBackendTypeByInput($data['frontend_input']);
			} else {
				$data['backend_type'] = 'static';
			}
			if (is_array($optionData)) {
				$data['backend_model'] = 'eav/entity_attribute_backend_array';
				$attribute->setOption($optionData);
			}
			$attribute->addData($data);
			$attribute->save();
			return $attribute; //->getAttributeId();
		} else {
			$model = clone $this->attributeModel;

			if ((stripos($name, 'schrack_search') === 0) || (stripos($name, 'schrack_url') === 0)) {
				$data['backend_type'] = $model->getBackendTypeByInput($data['frontend_input']);
			} else {
				$data['backend_type'] = 'static';
				$conn = Mage::getSingleton('core/resource')->getConnection('core_write');
				$conn->query("ALTER TABLE catalog_product_entity ADD `".$name."` VARCHAR(255) NOT NULL DEFAULT ''");
			}
			if (is_array($optionData)) {
				echo "setting options for new product\n";
				$data['backend_model'] = 'eav/entity_attribute_backend_array';
				//$data['option'] = $optionData;
				$model->addData($data);
				$model->setOption($optionData);
			}else $model->addData($data);
			$model->setEntityTypeId($this->entityType);
			$model->setIsUserDefined(1);
			$model->save();
			return $model;
		}
	}

	public function setProductStatus($status) {
		$this->productStatus = $status;
	}

	public function addProduct($data, $storeId = 1, $attributeSetId = null, $oldProduct=null) {
		$conn = Mage::getSingleton('core/resource')->getConnection('core_write');
		$productId = null;
		$product = clone $this->productModel;
		if ($oldProduct != null) {
			$product = $oldProduct;
			unset($data['sku']);
			$productId = $product->getId();
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

		$product->save();
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
			$stockItem->setData('max_sale_qty', 1000000);
			$stockItem->setData('use_config_max_sale_qty', 1);
			$stockItem->save();
		}
	}

}

// dead code?
/*class Category extends magento {

	protected $entityCode = 'catalog_category';

	public function createAttribute($name, $label, $type='text') {
		$attribute = $this->getAttribute($name, TRUE);
		if ($attribute != false) {
			$data = array();
			$data['attribute_code'] = $name;
			$data['frontend_input'] = $type;
			$data['frontend_label'] = $label;
			$attribute->addData($data);
			$attribute->save();
			return $attribute; //->getAttributeId();
		} else {
			$model = clone $this->attributeModel;
			$data = array();
			$data['attribute_code'] = $name;
			$data['frontend_input'] = $type;
			$data['frontend_label'] = $label;
			$data['is_global'] = "1";
			$data['is_required'] = "0";
			$data['is_searchable'] = "0";
			$data['is_comparable'] = "0";
			$data['is_visible_in_advanced_search'] = "0";
			$data['is_filterable'] = "0";
			$data['is_html_allowed_on_front'] = "1";
			$data['is_visible_on_front'] = "1";
			$data['is_unique'] = "0";
			$data['backend_type'] = $model->getBackendTypeByInput($data['frontend_input']);
			$model->addData($data);
			$model->setEntityTypeId($this->entityType);
			$model->setIsUserDefined(1);
			$model->save();
			return $model;
		}
	}

}*/

?>