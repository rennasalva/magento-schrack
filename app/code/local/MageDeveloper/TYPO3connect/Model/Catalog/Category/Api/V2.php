<?php
/**
 * MageDeveloper TYPO3connect Module
 * ---------------------------------
 *
 * @category    Mage
 * @package    MageDeveloper_TYPO3connect
 * @copyright   Magento Developers / magedeveloper.de <kontakt@magedeveloper.de>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MageDeveloper_TYPO3connect_Model_Catalog_Category_Api_V2 extends Mage_Catalog_Model_Category_Api_V2 {

	/** @var Schracklive_SchrackCore_Model_Translate */
	var $translateHelper = null;

	/**
	 * fetchall
	 * Get a full list of categories
	 * 
	 * @param string $storeCode Store Code
	 * @return array|bool
	 */ 
	public function fetchall($storeCode = null) {
		$storeCode = ($storeCode == null)?'default':$storeCode;
		$store = Mage::app()->getStore($storeCode);

		// Load corrent translation
		$this->translateHelper = Mage::getModel('core/translate')->setLocale(Mage::getStoreConfig('general/locale/code', $store->getId()))->init('frontend', true);

		$rootId = $store->getRootCategoryId();
		
		$category = Mage::getModel('catalog/category')
						->setStoreId($store->getId())
						->load($rootId);
						
	
		$category->setData('children', implode(',', $this->_getCategoryChildren($category)));
			
		$subcategories = $this->_getSubcategories($category, $store->getId());	
			
		$result = array();
		$result['ROOT'] = array();
		$result['ROOT'] = $category->getData();
		$result = array_merge($result, $subcategories);
		
		if (!empty($result)) {
            return json_encode($result);
        }            			
		
		return false;
	}
	
	/**
	 * Gets available category children ids
	 * 
	 * @param $category Category Model
	 * @return array
	 */
	public function _getCategoryChildren($category)
	{
		$children = array();
		
		foreach ($category->getChildrenCategories() as $_child)
		{
			$children[] = $_child->getId();
		}
		
		return $children;
	}
	
	/**
	 * _getSubcategories
	 * Get all subcategories from a given category model
	 * 
	 * @param $category Category Model
	 * @return array
	 */
	public function _getSubcategories($category, $storeId)
	{
		$subcategoryArr 	= array();	
		
		$_subcategories = $category->getChildrenCategories();
		
		if (count($_subcategories) > 0) {
			
			foreach ($_subcategories as $_subcategory) {

				/** @var Schracklive_SchrackCatalog_Model_Category $category */
				$category = Mage::getModel('catalog/category')->setStoreId($storeId)->load($_subcategory->getId());

				// We want the product ids of the category
				/*$productcollection = $category->getProductCollection();
				$productIds = array();
				foreach ($productcollection as $_product) {
					$productIds[] = $_product->getId();
				}*/


				// If category is allowed in the navigation menu
				if ($category->getIncludeInMenu()) {

					$c = array(
						'entity_id'		=> $category->getId(),
						'name'			=> $category->getName(),
						'description'	=> $category->getDescription(),
						'page_title'	=> $category->getPageTitle(),
						'thumbnail'		=> $category->getThumbnail(),
						'url'			=> Mage::helper('catalog/category')->getCategoryUrl($category),
						'url_key'		=> $category->getUrlKey(),
						'url_path'		=> $category->getUrlPath(),
						'product_count'	=> $category->getProductCount(),
						'product_ids'	=> '',
						'children'		=> $category->getChildren(),
    					'path'			=> $category->getPath(),
					);

					// Add hack to translate discontinued categories
					if ($category->isDiscontinuedProductsCategory()) {
						$c['name'] = $this->translateHelper->translate(array($c['name']));
					}
					
					$c = array_merge($category->getData(), $c);
                    $c['schrack_group_id'] = $category->getSchrackGroupId();
					
					$subcategoryArr[] = $c;
					
					if ($subcats = $this->_getSubcategories($category, $storeId)) {
						$subcategoryArr = array_merge($subcategoryArr, $subcats);
					}
					
				}

			}
			return $subcategoryArr;
		}
		return;
	}

}
	