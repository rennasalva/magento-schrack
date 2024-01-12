<?php

class Schracklive_SchrackCatalog_Model_Category extends Mage_Catalog_Model_Category {

    private static $_idMap = null;

	protected $_attachmentCollection;
	protected $_attachments;
	protected $_changedAttachments = array();
	protected $_productCollection = null;
	protected $_allProductIDs = null;
    protected $_accessoryCount = -1;

	public function load($id, $field = null) {
		parent::load($id, $field);
		$this->_attachmentCollection = null;
        if ( $this->getId() ) {
            $this->getAttachmentsCollection();
        }
		return $this;
	}

	protected function setCacheTag($tag = null) {
		static $oldCacheTag = null;
		if ($oldCacheTag === null) {
			$oldCacheTag = $this->_cacheTag;
		}
		if ($tag === null) {
			$this->_cacheTag = $oldCacheTag;
		} else {
			$this->_cacheTag = $tag;
		}
	}

	protected function getAttachmentCollection() {
		return Mage::getResourceModel('schrackcatalog/attachment_collection');
	}

	public function getAttachmentsCollection() {
		if (is_null($this->_attachmentCollection)) {
			$this->_attachmentCollection = $this->getAttachmentCollection();
            if ( ! $this->getId() ) {
                $this->save();
            }
			if ( $this->getId() ) {
                $this->_attachmentCollection->setCategoryFilter($this)->load();
            }
            else {
                throw new Exception('No ID for category got!');
            }
		}
		return $this->_attachmentCollection;
	}

	public function getAttachments() {
		$this->_attachments = $this->getAttachmentsCollection()->getItems();
		return $this->_attachments;
	}

	public function addAttachment(Schracklive_SchrackCatalog_Model_Attachment $attachment) {
        $this->getAttachmentsCollection();
		$this->_changedAttachments[] = $attachment->getLabel();
		//$attachment->setEntityTypeId($this->getEntityTypeId($this->getEntityTypeId()));
		foreach ($this->_attachmentCollection as &$value) {
			//check for update
			if ($value->getLabel() == $attachment->getLabel()) {
				$value->setFiletype($attachment->getFiletype());
				$value->setUrl($attachment->getUrl());
				return;
			}
		}
		$this->_attachmentCollection->addItem($attachment);
	}

	public function cleanAttachments() {
        $this->getAttachmentsCollection();
		if (!is_null($this->_attachmentCollection)) {
            foreach ($this->_attachmentCollection as &$value) {
                if (!in_array($value->getLabel(), $this->_changedAttachments)) {
                    $this->removeAttachment($value);
                }
            }
        }
	}

	public function removeAttachment(Schracklive_SchrackCatalog_Model_Attachment $attachment) {
		$attachment->delete();
	}

	public function save() {
        $position = $this->getPosition();
		parent::save();
        if ( isset($position) && $position > 0 && $position !== $this->getPosition() ) {
            $this->setPosition($position);
            parent::save();
        }
		if (!is_null($this->_attachmentCollection)) {
			foreach ($this->_attachmentCollection as &$value) {
				$value->setEntityId($this->getId());
				$value->setEntityTypeId($this->getEntityTypeId());
			}
			$this->_attachmentCollection->save();
		}
		return $this;
	}

	public function getProductCollection() {
		return $this->getProductCollectionImpl();
	}

	public function getProductCollectionWithoutSolr() {
		return $this->getProductCollectionImpl(false);
    }

	private function getProductCollectionImpl ( $useSolr = true ) {
		if ( ! $this->_productCollection ) {
			$this->_productCollection = $this->getProductCollectionFromSource($useSolr);
			Schracklive_SchrackCatalog_Model_Product::addAdditionalEavAttributeCodesForLists($this->_productCollection);
			$this->addFilterAndOrderToProductCollection($this->_productCollection);
		}
		return $this->_productCollection;
	}

	private function getProductCollectionFromSource ( $useSolr = true ) {
		$res = null;
		if ( $this->isPromotionProductsCategory() ) {
			$res = Mage::helper('catalog/product')->getPromotionProductCollection();
		} else if ( $useSolr ) {
			$res = Mage::helper('schrackcatalogsearch')->getProductCollection($this);
			if ( !$res ) {
				$res = parent::getProductCollection();
			}
		} else {
			$res = parent::getProductCollection();
		}
		return $res;
	}

	private function addFilterAndOrderToProductCollection ( &$collection ) {
		// DLA 20160930: not longe needed, we use schrack_sts_statuslocal for that:
		// $collection->addAttributeToFilter('status', 1);
		// $collection->addAttributeToFilter('visibility', array('in' => array(Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG, Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)));
		$collection->addAttributeToFilter('schrack_sts_statuslocal', array('nin' => array('tot', 'strategic_no','unsaleable')));
		// DLA 20160930: seems that we have no such attribute??
		// $collection->setOrder('position', 'asc');
		return $collection;
	}


	public function disableCache() {
		$this->setCacheTag('');
	}

	public function enableCache() {
		return $this->setCacheTag(null);
	}

	public function cleanCache($force_clean = false) {
		if ($force_clean === true) $this->enableCache();
		if ($this->_cacheTag) {
			if ($this->_cacheTag === true) {
				$tags = array();
			} else {
				$tags = array($this->_cacheTag);
			}
			Mage::app()->cleanCache($tags);
		}
	}

	public function getMetaTitle() {
		$metaTitle = $this->getData('meta_title');
		if (!$metaTitle) {
			$metaTitle = $this->getName();
		}
		return $metaTitle;
	}

	public function getName() {
		$s = parent::getName();
		if ( ! $this->isDiscontinuedProductsCategory() && ! $this->isPromotionProductsCategory() ) {
			return $s;
		} else {
			return Mage::helper('catalog')->__($s);
		}
	}

	/**
	 * @param Schracklive_SchrackCatalog_Model_Product $referenceProduct
	 * @param int                                       $offset position in category relative to reference product
	 * @throws RuntimeException
	 * @return Schracklive_SchrackCatalog_Model_Product
	 */
	public function getProductAtOffsetOrDefault(Schracklive_SchrackCatalog_Model_Product $referenceProduct, $offset) {
		$productIds = $this->_getProductIds();
		$productPosition = array_search($referenceProduct->getId(), $productIds);
		if (($productPosition !== false) && isset($productIds[$productPosition + $offset])) {
			return Mage::getModel('catalog/product')->load($productIds[$productPosition + $offset],'name');
		}

		if ($offset < 0 && isset($productIds[count($productIds) - 1])) {
			return Mage::getModel('catalog/product')->load($productIds[count($productIds) - 1],'name');
		} elseif ($offset > 0 && isset($productIds[0])) {
			return Mage::getModel('catalog/product')->load($productIds[0],'name');
		} else {
			throw new RuntimeException('Invalid offset '.$offset.' for '.count($productIds));
		}
	}

	protected function _getProductIds() {
		if ( ! $this->_allProductIDs ) {
			$collection = $this->getProductCollectionFromSource();
			$this->addFilterAndOrderToProductCollection($collection);
			$collection->getSelect()->reset(Zend_Db_Select::COLUMNS);
			$collection->getSelect()->columns('entity_id');
			$this->_allProductIDs = $collection->getAllIdsCache();
		}
		return $this->_allProductIDs;
	}

	/**
	 * Format URL key from name or defined key
	 *
	 * @param string $str
	 * @return string
	 */
	public function formatUrlKey($str) {
	    if ( trim($str) == '' ) {
	        $str = 'first';
        }
		if (Mage::getStoreConfigFlag('schrack/web/utf8_rewrites')) {
			return Mage::helper('schrackcatalog')->formatUtf8UrlKey($str);
		} else {
			return parent::formatUrlKey($str);
		}
	}
    
    /**
     * get schrackStrategicPillarId either from this or parent
     */
    public function getRealPillar() {
        $cat = $this;
        $level = $cat->getLevel();
        if (intval($level) > 2)
            $cat = $cat->getParentCategory();
        $pillar = $cat->getSchrackStrategicPillar();
        return $pillar;
    }

	public function isDiscontinuedProductsCategory() {
		$schrackGroupId = explode('-', $this->getSchrackGroupId());
		return (isset($schrackGroupId) && isset($schrackGroupId[1]) && $schrackGroupId[1] == '999') ? true : false;
	}

	public function isPromotionProductsCategory() {
		return $this->getSchrackGroupId() == '_PROMOS_' || parent::getName() == 'PROMOTIONS_TOP';
	}

	public function isPromotionProductsTopCategory() {
		return $this->getName() == 'PROMOTIONS_TOP';
	}

	public static function isCatalogCategoryID ( $id ) {
		return is_string($id) && strncmp($id,'87-01-12',8) === 0;
    }

	public function isCatalogCategory () {
		return self::isCatalogCategoryID($this->getSchrackGroupId());
	}

	public function getSchrackShortGroupId () {
        $id = $this->getData('schrack_short_group_id');
        if ( ! $id ) {
            $id = $this->getData('schrack_group_id');
            $p = strrpos($id,'/');
            if ( $p !== false ) {
                $id = substr($id,$p + 1);
            }
            $this->setData('schrack_short_group_id',$id);
        }
        return $id;
    }

    public function getAccessoryCount () {
        if ( $this->_accessoryCount == -1 ) {
            $sql = "SELECT count(product_id) FROM catalog_category_product WHERE category_id = ? AND schrack_sts_is_accessory = 1;";
            $this->_accessoryCount = intval(Mage::getSingleton('core/resource')->getConnection('core_read')->fetchOne($sql,array($this->getId())));
        }
        return $this->_accessoryCount;
    }

    public function setAccessoryCount ( $x ) {
        $this->_accessoryCount = intval($x);
    }

    public function getId4googleTagManager () {
        $id = $this->getSchrackGroupId();
        $id = self::prepareId4googleTagManager($id);
        return $id;
    }

    public static function prepareId4googleTagManager ( $id ) {
        $id = str_replace('/','_',$id);
        return $id;
    }

    public static function getIdMap () {
        if ( ! self::$_idMap ) {
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $sql = " SELECT entity_id, value AS schrack_id FROM catalog_category_entity_varchar WHERE attribute_id ="
                 . "   (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id')"
                 . " ORDER BY entity_id DESC";
            // order by ensures that at the end of the day we use always the first created group with that schrack id
            $dbRres = $readConnection->fetchAll($sql);
            self::$_idMap = [];
            foreach ( $dbRres as $row ) {
                $entityID = $row['entity_id'];
                $schrackID = $row['schrack_id'];
                $p = strrpos($schrackID, '#');
                if ( $p !== false ) {
                    $schrackID = substr($schrackID, $p + 1);
                }
                self::$_idMap[$schrackID] = $entityID;
            }
        }
        return self::$_idMap;
    }
}

