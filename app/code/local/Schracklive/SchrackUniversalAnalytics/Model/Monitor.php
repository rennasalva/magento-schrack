<?php

class Schracklive_SchrackUniversalAnalytics_Model_Monitor extends BlueAcorn_UniversalAnalytics_Model_Monitor {

	protected function getNormalAttributeValue($product, $name) {
		$value = parent::getNormalAttributeValue($product, $name);
		if (is_a($value, 'Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection')) {
			$value = $this->parseCategoryValue($value);
		}
		return $value;
	}

	public function generateProductData($item) {
		$product = Mage::getModel('catalog/product')->load($item->getProductId());

		if ($product->getVisibility() == 1) return null;

		$productOptions = $item->getProductOptions();
		if ($item instanceof Mage_Sales_Model_Order_Item) {
			$orderOptions = $productOptions;
		} else {
			$orderOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
		}

		$productData      = $this->parseObject($product, 'addProduct');
		$itemData         = $this->parseObject($item, 'addProduct');
		$itemData['variant'] = $this->extractAttributes($productOptions, $orderOptions);

		return array_filter(array_merge($productData, $itemData), 'strlen');
	}

	/**
	 * Build a list of product categories in hierarchical order.
	 *
	 * @name parseCategoryValue
	 * @param Mage_Catalog_Model_Resource_Eav_Mysql4_Category_Collection $objectCollection
	 * @return string
	 */
	protected function parseCategoryValue($objectCollection) {
		$objectCollection->addAttributeToSelect('name');
		$object = $objectCollection->getFirstItem();
		$names = Array();

		while ($object->getLevel() > 1) {
			$names[] = $object->getName();
			$object = $object->getParentCategory();
		}

		return implode(' / ', array_reverse($names));
	}

	/**
	 * Add generate an array of transaction data
	 *
	 * @name generateTransactionData
	 * @param Mage_Sales_Model_Order $order
	 * @return array
	 */
	public function generateTransactionData($order) {

		$trans = $this->helper->getTranslation('transaction');
		$data = Array();
		$attributeList = Array();

		foreach ($trans as $magentoAttr => $googleAttr) {
			if (!is_array($googleAttr)) {
				$attributeList = array($googleAttr);
			} else {
				$attributeList = array_keys($googleAttr);
				$googleAttr = $magentoAttr;
			}

			foreach ($attributeList as $subAttribute) {
				$data[$googleAttr] = $this->findAttributeValue($order, $subAttribute);
				if ($data[$googleAttr]) break;
			}
		}

		return $data;
	}
}