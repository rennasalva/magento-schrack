<?php

class Schracklive_SchrackCatalog_Model_Product extends Mage_Catalog_Model_Product {

    private static $_additionalEavAttributeCodesForLists = array('name','schrack_vpes','schrack_long_text_addition');
    private static $_allAttributes = null;

	protected $_attachmentCollection;
	protected $_attachments;
	protected $_changedAttachments = array();
    private $lastReplacementProduct = null;
    private $lastAlivePrecedingProduct = null;
    private $_preferredCategory = null;
    private $_vpesUnserialized = null;
    private $_downloadFileAttachment = null;
    private $_onlineCatalogAttachment = null;
    private $_accessoryProducts = null;
    private $_mainImageUrl = null;
    private $_mainArticle = null;
    private $_subArticleData = null;
    private $_productUrlWithChapterIfAvail = null;


    public static function getAdditionalEavAttributeCodesForLists () {
        return self::$_additionalEavAttributeCodesForLists;
    }

    public static function addAdditionalEavAttributeCodesForLists ( $collection ) {
        $collection->addAttributeToSelect(self::$_additionalEavAttributeCodesForLists,'left');
        return $collection;
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

	public function disableCache() {
		$this->setCacheTag('');
	}

	public function enableCache() {
		return $this->setCacheTag(null);
	}

	public function cleanCache($force_clean = false) {
		if ($force_clean === true) {
			$this->enableCache();
		}
		if ($this->_cacheTag) {
			if ($this->_cacheTag === true) {
				$tags = array();
			} else {
				$tags = array($this->_cacheTag);
			}
			Mage::app()->cleanCache($tags);
		}
	}

	public function getData($key='', $index=null) {

		if (($key == 'image') || ($key == 'schrack_url_foto')) {
			if ($file = $this->getAttachment('foto')) {
				$url = Mage::getStoreConfig('schrack/general/imageserver').$file->getUrl();
				if (isset($_SERVER['HTTPS'])) {
					$url = str_replace('http://', 'https://', $url);
				}
				return $url;
			}
		}
		if (($key == 'small_image') || ($key == 'thumbnail') || ($key == 'schrack_url_thumbnails')) {
			if ($file = $this->getAttachment('thumbnails')) {
				$url = Mage::getStoreConfig('schrack/general/imageserver').$file->getUrl();

				if (isset($_SERVER['HTTPS'])) {
					$url = str_replace('http://', 'https://', $url);
				}
				return $url;
			}
		}
		return parent::getData($key, $index);
	}

	public function getMetaTitle() {
		$metaTitle = $this->getData('meta_title');
		if (!$metaTitle) {
			$metaTitle = $this->getName();
		}
		return $metaTitle;
	}

/*
	public function getReferencedProducts() {
		$refernces = array();
		$ids = $this->_getResource()->getIdsByReference($this->getSku());
		if (is_array($ids)) {
			foreach ($ids as $id) {
				$refernces[] = Mage::getModel('catalog/product')->load($id);
			}
		}
		return $refernces;
	}
*/
    public function getLastReplacementProduct () {
        if ( $this->lastReplacementProduct == null ) {
            if ( Schracklive_SchrackCatalog_Helper_Preparator::PREPARATE_PRODUCTS ) {
                $this->lastReplacementProduct = Mage::helper('schrackcatalog/preparator')->getReplacementProduct($this);
                if ( $this->lastReplacementProduct )
                    return $this->lastReplacementProduct;
            }
            $sku = $this->getSku();
            $collection = Mage::getModel('catalog/product')->getCollection();
            $collection->addFieldToFilter('schrack_substitute', array('like' => "%$sku%"));
            // $collection->addFieldToFilter('schrack_sts_statuslocal', array('neq' => "tot"));
            $collection->addFieldToFilter('schrack_sts_statuslocal', array('neq' => "strategic_no"));
            $collection->addAttributeToFilter('status',array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
            $collection->addAttributeToSelect('*');
            $collection->setOrder('entity_id', Varien_Data_Collection::SORT_ORDER_DESC);
            $collection->setPageSize(1);
            if ( $collection->count() === 0 ) {
                $this->lastReplacementProduct = false;
            } else {
                $this->lastReplacementProduct = $collection->getFirstItem();
            }
        }
        return $this->lastReplacementProduct;
    }

    public function getLastAlivePrecedingProduct () {
        if ( $this->lastAlivePrecedingProduct == null ) {
            if ( Schracklive_SchrackCatalog_Helper_Preparator::PREPARATE_PRODUCTS ) {
                $this->lastAlivePrecedingProduct = Mage::helper('schrackcatalog/preparator')->getPrecedingProduct($this);
                if ( $this->lastAlivePrecedingProduct )
                    return $this->lastAlivePrecedingProduct;
            }
            $substitues = $this->getSchrackSubstitute();
            if ( $substitues == null || $substitues === '' ) {
                $this->lastAlivePrecedingProduct = false;
                return $this->lastAlivePrecedingProduct;
            }
            $substitueArray = explode(';',$substitues);
            $collection = Mage::getModel('catalog/product')->getCollection();
            $collection->addFieldToFilter('sku', array('in' => $substitueArray));
            $collection->addFieldToFilter('schrack_sts_statuslocal', array('eq' => "istausl"));
            $collection->addFieldToFilter('schrack_sts_forsale', array('eq' => 1));
            $collection->addAttributeToFilter('status',array('eq' => Mage_Catalog_Model_Product_Status::STATUS_ENABLED));
            $collection->addAttributeToSelect('*');
            $collection->setOrder('entity_id', Varien_Data_Collection::SORT_ORDER_DESC);
            $collection->setPageSize(1);
            if ( $collection->count() === 0 ) {
                $this->lastAlivePrecedingProduct = false;
            } else {
                $this->lastAlivePrecedingProduct = $collection->getFirstItem();
            }
        }
        return $this->lastAlivePrecedingProduct;
    }

	public function getIdByEan($ean) {
		return $this->_getResource()->getIdByEan($ean);
	}

    public function loadByAttribute($attribute, $value, $additionalAttributes='*') {
        $res = parent::loadByAttribute($attribute,$value,$additionalAttributes);
        if ( Schracklive_SchrackCatalog_Helper_Preparator::PREPARATE_PRODUCTS ) Mage::helper('schrackcatalog/preparator')->prepareProduct($res);
        return $res;
    }

	public function loadBySku($sku) {
		$res = $this->loadByAttribute("sku",$sku);
        return $res;
    }

	/**
	 * Retrieve price unit.
	 *
	 * @return int
	 */
	public function getSchrackPriceunit() {
		// TODO: unify attribute names in import
		if ($this->_getData('schrack_spec_pe') != '') {
			return $this->_getData('schrack_spec_pe');
		} elseif ($this->_getData('schrack_pe') != '') {
			return $this->_getData('schrack_pe');
		} elseif ($this->_getData('schrack_priceunit') != '') {
			return $this->_getData('schrack_priceunit');
		} else {
			return 1;
		}
	}

	public function getSchrackQtyunit() {
		// TODO: unify attribute names in import
		if ($this->_getData('schrack_spec_me') != '') {
			return $this->_getData('schrack_spec_me');
		} elseif ($this->_getData('schrack_me') != '') {
			return $this->_getData('schrack_me');
		} elseif ($this->_getData('schrack_qtyunit') != '') {
			return $this->_getData('schrack_qtyunit');
		} else {
			return Mage::helper('catalog')->__('pcs');
		}
	}

    public function getSchrackStsPlusDeliTime() {
        return $this->_getData('schrack_sts_plus_deli_time');
    }

	/**
	 * @return bool
	 */
	public function isCable() {
        return (bool)($this->getSchrackIsCable() == '1');
	}

	public function hasSubProducts () {
        return     isset($this->_data['schrack_sts_sub_article_skus'])
                && $this->_data['schrack_sts_sub_article_skus']
                && $this->_data['schrack_sts_sub_article_skus'] > '';
    }

	public function hasMainProduct () {
        return     isset($this->_data['schrack_sts_main_article_sku'])
                && $this->_data['schrack_sts_main_article_sku']
                && $this->_data['schrack_sts_main_article_sku'] > '';
    }

	public function hasFamily () {
        return $this->hasMainProduct() || $this->hasSubProducts();
    }

    public function isHideStockQantities () {
        return $this->getSchrackStsShowinventory() !== '1';
    }

    public function isDiscontinuation () {
        return $this->getSchrackStsStatuslocal() === 'istausl' || $this->getSchrackStsStatusglobal() === 'istausl';
    }

    public function isSale () {
        return $this->getSchrackStsForsale() === '1';
    }

    public function isRestricted () {
        return    $this->getSchrackStsStatuslocal() === 'gesperrt' || $this->getSchrackStsStatusglobal() === 'gesperrt'
			   || ($this->getSchrackStsManagedInventory() != null && $this->getSchrackStsManagedInventory() !== 'bestand' )
               || ($this->getSchrackStsIsDownload() != null  && $this->getSchrackStsIsDownload() == 1 && ! $this->getDownloadFile());
    }

    public function isDownload () {
        return    $this->getSchrackStsIsDownload() != null
               && $this->getSchrackStsIsDownload() == 1
               && $this->getDownloadFile();
    }

    public function isDead () {
        $status = $this->getSchrackStsStatuslocal();
        if (Mage::helper('sapoci')->isSapociCheckout()) {
            $exclusionPagetype = false;
            // Get defined Pagetypes for exceptions:
            // 1. Artikeldetailseite:
            if (Mage::registry('articleDetailPage') == 'yes') {
                $exclusionPagetype = true;
            }
            if ($status === 'strategic_no'){
                if ($exclusionPagetype == true) {
                    // Same behaviour like normal custumers...even for SAP_OCI User -> Article is dead !!
                    return true;
                } else {
                    // Do nothing
                }
            }
            return $status === 'tot' || $status === 'unsaleable' || $status === 'gesperrt';
        } else {
            return $status === 'tot' || $status === 'strategic_no' || $status === 'unsaleable' || $status === 'gesperrt';
        }
    }

    public function isLockedArticle () {
        // Behandlung gesperrter Artikel:
        $status = $this->getSchrackStsStatuslocal();
        if($status === 'gesperrt') {
            return true;
        } else {
            return false;
        }
    }

    public function isSchrackStsNotAvailable () {
        if(intval($this->getSchrackStsNotAvailable()) == 1) {
            return 1;
        } else {
            return 0;
        }
    }

    public function isAvailableRegardingInventoryAlternate () {
        if(intval($this->getSchrackStsNotAvailable()) == 1) {
            // What kind of Article ? Bestellartikel / Normaler Artikel:
            $stockHelper = Mage::helper('schrackcataloginventory/stock');
            $productHelper = Mage::helper('schrackcatalog/product');
            $res = $productHelper->getAvailibilityProductInfo(array($this->getSku()), 1);
            if ($this->isBestellArtikel()) {
                // Get inventory of LOCAL stock (if = 0, then return false => not available):
                // Get local stock number:
                $localStockNumber = $stockHelper->getLocalDeliveryStock()->getStockNumber();
                $localQuantity = $res[$this->getSku()][$localStockNumber]['qty'];
                if ($localQuantity == 0) {
                    return 0;
                }
            } else {
                // Get inventory of ALL stocks (if = 0, then return false => not available):$stockHelper = Mage::helper('schrackcataloginventory/stock');
                $pickupWarehouseIds   = array();
                $deliveryWarehouseIds = array();
                $pickupWarehouseIds   = $stockHelper->getPickupStockNumbers();
                $deliveryWarehouseIds = $stockHelper->getAllDeliveryStockNumbers();
                if (is_array($pickupWarehouseIds) && is_array($deliveryWarehouseIds)) {
                    $allWarehouseIds = array_merge($pickupWarehouseIds, $deliveryWarehouseIds);
                }
                $allQuantity = 0;
                foreach($allWarehouseIds as $index => $warehouseId) {
                    if (isset($res[$this->getSku()][$warehouseId])) {
                        $allQuantity = $allQuantity + $res[$this->getSku()][$warehouseId]['qty'];
                    }
                }

                if ($allQuantity == 0) {
                    return 0;
                }
            }
            // Fallback:
            return 1;
        } else {
            return 1;
        }
    }

    public function isArticleUnmanageable($selectedPickupStore) {
        if($this->getSchrackStsUnmanagedStocks()) {
            $arrUnmanageableStockNumbers = explode(';', $this->getSchrackStsUnmanagedStocks());
            if (is_array($arrUnmanageableStockNumbers) && !empty($arrUnmanageableStockNumbers)) {
                foreach($arrUnmanageableStockNumbers as $unmanagaeableStockNumber) {
                    if ($selectedPickupStore == $unmanagaeableStockNumber) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    // New Status for show product normally, but cannot put to cart and show warning information on product detail page
    public function isWebshopsaleable () {
	    if ( ! Mage::getStoreConfig('schrack/general/use_webshop_saleable') ) {
	        return true;
        }
        if(intval($this->getSchrackStsWebshopSaleable()) == 1) {
            return true;
        } else {
            return false;
        }
    }

    public function isSalable () {
        $res = parent::isSalable();
        if ( $res ) {
            $res = ! $this->isDead();
        }
        if ( $res ) {
            $res = ! $this->isRestricted();
        }
        return $res;
    }

    public function isBestellArtikel() {
        if ($this->getData('schrack_sortiment') == 'bestell') {
            return true;
        } else {
            return false;
        }
    }

    public function getMinQtyFromSupplier() {
        if (intval($this->getData('schrack_sts_min_order_qty') > 0 )) {
            return intval($this->getData('schrack_sts_min_order_qty'));
        } else {
            return 0;
        }
    }

    public function getBatchSizeFromSupplier() {
        if (intval($this->getData('schrack_sts_batch_size') > 0 )) {
            return intval($this->getData('schrack_sts_batch_size'));
        } else {
            return 0;
        }
    }

    public function getCumulatedPickupableAndDeliverableQuantities( $ignore3rdPartyStocks = false ) {
        $totalQuantityOfPickupsAndDeliverables = 0;
        $productHelper = Mage::helper('schrackcatalog/product');

        // Get info about article stock:
        $totalQuantityOfPickups = $productHelper->_getInfo()->getSummarizedPickupQuantities($this);
        if ($totalQuantityOfPickups == '' || $totalQuantityOfPickups == null) $totalQuantityOfPickups = 0;

        // Get info about deliverable articles:
        $totalQuantityOfDeliverables = $productHelper->_getInfo()->getSummarizedDeliveryQuantities($this, 1, $ignore3rdPartyStocks);
        if ($totalQuantityOfDeliverables == '' || $totalQuantityOfDeliverables == null) $totalQuantityOfDeliverables = 0;

        // Add both sides of articles:
        $totalQuantityOfPickupsAndDeliverables = $totalQuantityOfPickups + $totalQuantityOfDeliverables;

        return $totalQuantityOfPickupsAndDeliverables;
    }

    private function searchSpecialFiles () {
        if ( $this->_downloadFileAttachment == null ) {
            $this->_onlineCatalogAttachment = false;
            $firstNonPdf = false;
            foreach ( $this->getAttachmentsCollection() as $attachment ) {
                $url = $attachment->getUrl();
                $filetype = $attachment->getFiletype();
                if ( $url != null && strlen($url) > 4 && strtolower(substr($url, -4)) == '.pdf' ) {
                    if ( $this->_downloadFileAttachment == null ) {
                        $this->_downloadFileAttachment = $attachment;
                    }
                    continue;
                }
                if ( $filetype == 'onlinekatalog' ) {
                    $this->_onlineCatalogAttachment = $attachment;
                } else if ( $filetype != 'thumbnails' && $filetype != 'foto' ) {
                    $firstNonPdf = $attachment;
                };
            }
            if ( $this->_downloadFileAttachment == null ) {
                $this->_downloadFileAttachment = $firstNonPdf;
            }
        }
    }

    public function getDownloadFile () {
        if ( $this->_downloadFileAttachment == null ) {
            $this->searchSpecialFiles();
        }
        return $this->_downloadFileAttachment;
    }

    public function getOnlineCatalog () {
        if ( $this->_onlineCatalogAttachment == null ) {
            $this->searchSpecialFiles();
        }
        return $this->_onlineCatalogAttachment;
    }

    protected function getAttachmentCollection() {
		return Mage::getResourceModel('schrackcatalog/attachment_collection');
	}

	public function getAttachmentsCollection($refresh = false) {
		if (is_null($this->_attachmentCollection) || $refresh) {
			$this->_attachmentCollection = $this->getAttachmentCollection();
			if ($this->getId()) {
                $this->_attachmentCollection->setProductFilter($this)->load();
                if ( ! $this->getMainImageUrl() ) {
                    $placeholderImage = new Schracklive_SchrackCatalog_Model_Attachment();
                    $placeholderImage->setFiletype('foto');
                    $url = Schracklive_SchrackCatalog_Helper_Image::getFullPlaceholderUrl();
                    $placeholderImage->setUrl($url);
                    $placeholderImage->setLabel($this->getDescription());
                    $this->_attachmentCollection->addItem($placeholderImage);
                    $this->_mainImageUrl = $url;
                }
            }
		}
		return $this->_attachmentCollection;
	}

	public function getAttachments() {
		$this->_attachments = $this->getAttachmentsCollection()->getItems();
		return $this->_attachments;
	}

	public function getAttachment($fileType) {
		if (!is_null($this->_attachmentCollection)) {
			foreach ($this->_attachmentCollection as $value) {
				if ($value->getFiletype() == $fileType) {
					return $value;
				}
			}
		}
		return null;
	}

	public function getAttachmentByUrl($url) {
		if (!is_null($this->_attachmentCollection)) {
			foreach ($this->_attachmentCollection as $value) {
				if ($value->getUrl() === $url) {
					return $value;
				}
			}
		}
		return null;
	}

	public function getUrlToAttachmentMap () {
        $res = array();
		if (!is_null($this->_attachmentCollection)) {
			foreach ($this->_attachmentCollection as $value) {
                $res[$value->getUrl()] = $value;
			}
		}
		return $res;
	}

    public static function returnThumbnailsOnly($att) {
        return ($att->getFiletype() === 'thumbnails');
    }
    public static function returnFotosOnly($att) {
        return ($att->getFiletype() === 'foto');
    }
    /**
     * returns a datastructure comprised of all images with their respective thumbnails
     */
    public function getImageAttachments($withMainImage = true) {
        $attachments = $this->getAttachments();
        $thumbnails = array_values(array_filter($attachments, array('Schracklive_SchrackCatalog_Model_Product', 'returnThumbnailsOnly')));
        $fotos = array_values(array_filter($attachments, array('Schracklive_SchrackCatalog_Model_Product', 'returnFotosOnly')));
        $attachments = array();
        if (!$withMainImage) {
            array_shift ($thumbnails);
            array_shift ($fotos);
        }
        $max = count($thumbnails) > count($fotos) ? count($thumbnails) : count($fotos);
        for ($i = 0; $i < $max; ++$i) {
            $att = array('thumbnail' => isset($thumbnails[$i]) ? $thumbnails[$i] : null, 'foto' => isset($fotos[$i]) ? $fotos[$i] : null);
            $attachments[] = $att;
        }
        return $attachments;
    }

    public function getMainImageUrl () {
        if ( ! $this->_mainImageUrl ) {
            foreach ( $this->getAttachmentsCollection() as $att ) {
                if ( $att->getFiletype() === 'foto' ) {
                    $this->_mainImageUrl = $att->getUrl();
                    break;
                }
            }
        }
        return $this->_mainImageUrl;
    }

	public function addAttachment(Schracklive_SchrackCatalog_Model_Attachment $attachment) {
		$this->_changedAttachments[] = $attachment->getUrl();
		$attachment->setEntityTypeId($this->getEntityTypeId($this->getEntityTypeId()));
		if (!is_null($this->_attachmentCollection)) {
			foreach ($this->_attachmentCollection as &$value) {
				//check for update
				if ($value->getUrl() == $attachment->getUrl()) {
					$value->setFiletype($attachment->getFiletype());
					$value->setLabel($attachment->getLabel());
					return;
				}
			}
		}
        else {
            $this->_attachmentCollection = $this->getAttachmentCollection();
        }
		$this->_attachmentCollection->addItem($attachment);
	}

	public function cleanAttachments() {
		if (!is_null($this->_attachmentCollection)) {
			foreach ($this->_attachmentCollection as &$value) {
				if (!in_array($value->getUrl(), $this->_changedAttachments)) {
					$this->removeAttachment($value);
				}
			}
            $this->_attachmentCollection->clear();
		}
	}

	public function removeAttachment(Schracklive_SchrackCatalog_Model_Attachment $attachment) {
		$attachment->delete();
	}

	public function load($id, $fields = null) {
        if ( $fields && ! is_array($fields) ) {
            $fields = array($fields);
        }
		parent::load($id, $fields);
		$this->_attachmentCollection = null;
		$this->getAttachmentsCollection();
        if ( Schracklive_SchrackCatalog_Helper_Preparator::PREPARATE_PRODUCTS ) Mage::helper('schrackcatalog/preparator')->prepareProduct($this);
		return $this;
	}

	public function saveAttachments() {
		if (!is_null($this->_attachmentCollection)) {
			foreach ($this->_attachmentCollection as &$value) {
				$value->setEntityId($this->getId());
				$value->setEntityTypeId($this->getEntityTypeId());
                $value->save();
			}
			// $this->_attachmentCollection->save();
		}
	}

	public function save() {
		parent::save();
		$this->saveAttachments();
		return $this;
	}

	public function formatUrlKey($str) {
		if (Mage::getStoreConfigFlag('schrack/web/utf8_rewrites')) {
			return Mage::helper('schrackcatalog')->formatUtf8UrlKey($str);
		} else {
			return parent::formatUrlKey($str);
		}
	}

    public function getCuttedSchrackLongTextAddition ( $maxSize ) {
        $val = $this->getSchrackLongTextAddition();
        if ( is_null($val)  ) {
            return null;
        }
        $len = mb_strlen($val);
        if ( $len === 0 ) {
            return null;
        }
        if ( $len > $maxSize ) {
            $val = mb_strcut($val,0,$maxSize - 3);
            $val .= '...';
        }
        return $val;
    }

    /**
     * return the best category we can for this product:
     * - from schrackMainCategory
     *      - first category
     *          - from schrackProductgroup
     *
     * @return Schracklive_SchrackCatalog_Model_Category
     */
    public function getPreferredCategory ()
    {
        if (is_null($this->_preferredCategory)) {
            $this->_preferredCategory = Mage::getModel('schrackcatalog/category');

            $mainCategoryId = $this->getSchrackMainCategoryEntityId();
            if (isset($mainCategoryId)) {
                $this->_preferredCategory->load($mainCategoryId);
            }

            if (!$this->_preferredCategory->getId()) {
                $catIds = $this->getCategoryIds();
                if (count($catIds) > 0) {
                    $this->_preferredCategory->load($catIds[0]);
                }
            }

            if (!$this->_preferredCategory->getId()) {
                $categoryId = $this->getSchrackProductgroup();
                $this->_preferredCategory->load($categoryId);
            }
        }
        return $this->_preferredCategory;
    }

    public function getAccessorySKUs () {
        $neccesary = $this->getSchrackAccessoriesNecessary();
        $neccesaryExploded = $neccesary ? explode(';',$neccesary) : array();
        $optional = $this->getSchrackAccessoriesOptional();
        $optionalExploded = $optional ? explode(';',$optional) : array();
        return array_unique(array_merge($neccesaryExploded,$optionalExploded));
    }

    public function getAccessoryProducts () {
        if ( ! $this->_accessoryProducts ) {
            $accessorySKUs = $this->getAccessorySKUs();
            if ( count($accessorySKUs) == 0 ) {
                return Mage::getModel('catalog/product')->getCollection()->addFieldToFilter('entity_id',-1);
            }
            $this->_accessoryProducts = Mage::getModel('catalog/product')->getCollection(); // Mage::getResourceModel('catalog/product_collection');
            $this->_accessoryProducts->setStoreId($this->getStoreId());
            $this->_accessoryProducts->addFieldToFilter('sku', array('in' => $accessorySKUs));
            $this->_accessoryProducts->addFieldToFilter('schrack_sts_statuslocal', array('nin' => array('tot', 'strategic_no','unsaleable')));
            $this->_accessoryProducts = self::addAdditionalEavAttributeCodesForLists($this->_accessoryProducts);
            $field = "FIELD(sku,'".implode("','",$accessorySKUs)."')";
            $this->_accessoryProducts->getSelect()->order(new Zend_Db_Expr($field));
        }
        return $this->_accessoryProducts;
    }


    /**
     * for the whole cart, determine whether the qty of this product is inside the limits for the packingunit suggestion
     *
     * @param $qty
     * @return bool
     */
    public function isQtyInsidePackingunitLimit($qty) {
        return ( $this->getPackingunitDifference($qty) > 0 );
    }

    /**
     * for the whole cart, determine whether the difference of the qty of this product with the next packingunit
     *
     * @param $qty
     * @return mixed
     */
    public function getPackingunitDifference($qty) {
        $factor = $this->getPackingunitFactor($qty);
        if ( $factor > 0 ) {
            return ( $this->_getNonzeroPackingUnit() * $factor - $qty );
        } else {
            return 0;
        }
    }


    /**
     * get factor to multiply packing unit so we reach the next multiple, if applicable
     *
     * @param $qty
     * @return float|int
     */
    public function getPackingunitFactor($qty) {
        $unit = $this->_getNonzeroPackingUnit();

        $factor = floor($qty / $unit) + 1;
        $upperBound = $factor * $unit;
        $lowerBound = $this->getPackingUnitLowerBound($unit, $factor);

        if ( $qty >= $lowerBound && $qty < $upperBound ) {
            return $factor;
        } else {
            return 0;
        }
    }

    /**
     * calculate the lower bound limit for the packing unit arithmetic
     *
     * @param $unit
     * @param $factor
     * @return mixed
     */
    private function getPackingUnitLowerBound($unit, $factor) {
        $limitPercent = Mage::getStoreConfig("schrack/product/packingunitLimitPercent");
        $res = ($unit * $factor) - ($unit * (100 - $limitPercent) / 100);
        return $res;
    }

    /**
     * avoid division by zero, just return 1 instead... yeah...
     */
    private function _getNonzeroPackingUnit($resultingQuantity = 1) {
        $vpes = $this->getSchrackVpesUnserialized();

        foreach ( $vpes as $vpe ) {
            $multiplier = 1;
            foreach ( $vpe as $level ) {
                $qty = $level['quantity'] * $multiplier;
                $multiplier *= $level['quantity'];
                if ( $level['salable'] && $level['deliverable'] && $level['type'] != 'PAL' && $qty > 1 && ($resultingQuantity == 1 || $qty < $resultingQuantity) ) {
                    $resultingQuantity = $qty;
                }
            }
        }

        return $resultingQuantity;
    }


    public function calculateClosestHigherQuantityAndDifference ($selectedQuantity = 1, $overridePackingunitLimitPercent = false, $vpesAsParam = array(), $requestHint = '') {
        //var_dump($requestHint); die();
        $debug = false;
        $result = array();
        $result['bestellArtikel']            = false;
        $result['selectedQuantity']          = $selectedQuantity;
        $result['closestHigherQuantity']     = $selectedQuantity;
        $result['showHigherQuantityMessage'] = false;
        $result['invalidQuantity']           = false;
        $result['showBothLimitMessage']      = false;
        $collectedQuantities                 = array();
        $quantityFromThisProductInCart       = 0;

        // Bestellartikel benötigen Spezialbehandlung:
        if ($this->isBestellArtikel()) {
            // Special request route and defined response:
            if ($requestHint == 'ProductController::checkValidQuantityAction()') {
                return array('invalidQuantity' => false);
            }

            $result['bestellArtikel'] = true;
            $totalQuantityOfArticlesInStock = $this->getCumulatedPickupableAndDeliverableQuantities();

            // Is this article already in stock?
            if ($totalQuantityOfArticlesInStock > 0) {
                $result['totalStockQuantity']    = $totalQuantityOfArticlesInStock;
                $result['batchSizeFromSupplier'] = $this->getBatchSizeFromSupplier();
                $result['minQtyFromSupplier']    = $this->getMinQtyFromSupplier();

                // Get cart:
                if (stristr($requestHint, 'addCartQuantity')) {
                    $cart = Mage::getModel('checkout/cart')->getQuote();
                    foreach ($cart->getAllItems() as $item) {
                        $productSKUfromCart = $item->getProduct()->getSku();
                        // If this product was previously added to cart and already has amount:
                        if ($productSKUfromCart == $this->getSku()) {
                            $quantityFromThisProductInCart = $item->getQty();
                            // Add to seleceted quantity for correct calculation:
                            if ($debug) Mage::log('Selected Quantity #1 = ' . $selectedQuantity, null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                            $selectedQuantity = $selectedQuantity + $quantityFromThisProductInCart;
                            if ($debug) Mage::log('Selected Quantity #2 = ' . $selectedQuantity, null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                        }
                    }
                } else {
                    // Special case: updated quantity from cart:
                    $quantityFromThisProductInCart = 0;
                }

                // Case: stock available (offer to customer):
                if ($totalQuantityOfArticlesInStock >= $selectedQuantity) {
                    $result['availableStockQuantity'] = $totalQuantityOfArticlesInStock - $quantityFromThisProductInCart;
                    // Ordered quantity is available for customer:
                    // Just take normal VPES and do nothing here!
                    // DEBUG:
                    if ($debug) Mage::log('(case 1) total amount in stock: ' . $totalQuantityOfArticlesInStock . ' -> selected amount: ' . $selectedQuantity, null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                    if ($debug) Mage::log($result, null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                } else {
                    // Customer wants to order more than available (show only max. available quantity)
                    // 1. case: valid quantity step = minumum order quantity from supplier + total number in stock:
                    $result['showHigherQuantityMessage']  = true;
                    $result['invalidQuantity']            = true;
                    $result['showBothLimitMessage']       = true;
                    $result['previouslyExistingQuantity'] = $quantityFromThisProductInCart;
                    if (($totalQuantityOfArticlesInStock - $quantityFromThisProductInCart) <= 0) {
                        $result['availableStockQuantity'] = 0;
                        $totalQuantityOfArticlesInStock   = 0;
                    } else {
                        $result['availableStockQuantity'] = $totalQuantityOfArticlesInStock - $quantityFromThisProductInCart;
                        $totalQuantityOfArticlesInStock   = $totalQuantityOfArticlesInStock - $quantityFromThisProductInCart;
                    }

                    if ($quantityFromThisProductInCart == 0) {
                        if ($debug) Mage::log('Selected Quantity (result_1) = ' . $selectedQuantity, null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                        $resultFromCalculation = $this->calculateMatchingQuantityForBestellArticle($selectedQuantity);
                        if ($debug) Mage::log('result_1', null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                        if ($debug) Mage::log($resultFromCalculation, null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                    } else {
                        if ($debug) Mage::log('Selected Quantity (result_2) = ' . $selectedQuantity . ' - totalQuantityOfArticlesInStock (=' . $totalQuantityOfArticlesInStock . ')', null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                        $resultFromCalculation = $this->calculateMatchingQuantityForBestellArticle($selectedQuantity - $totalQuantityOfArticlesInStock);
                        if ($debug) Mage::log('result_2', null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                        if ($debug) Mage::log($resultFromCalculation, null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                    }
                    $minOrderQuantityFromSupplier = $resultFromCalculation['closestHigherQuantity'];
                    // 2. case: valid quantity step = minumum order quantity from supplier:
                    if ($result['invalidQuantity'] == true) {
                        // Try to find valid lower value for minimum quantity of supplier:
                        if ($resultFromCalculation['closestHigherQuantity'] == $selectedQuantity) {
                            $result['closestHigherQuantity']     = $selectedQuantity;
                            $result['invalidQuantity']           = false;
                            $result['showBothLimitMessage']      = false;
                            $result['showHigherQuantityMessage'] = false;
                            $result['differenceQuantity']        = 0;
                        } else {
                            if($selectedQuantity > $minOrderQuantityFromSupplier && $selectedQuantity <= ($minOrderQuantityFromSupplier + $totalQuantityOfArticlesInStock)) {
                                $result['closestHigherQuantity']     = $selectedQuantity - $quantityFromThisProductInCart;
                                $result['invalidQuantity']           = false;
                                $result['showBothLimitMessage']      = false;
                                $result['showHigherQuantityMessage'] = false;
                                $result['differenceQuantity']        = 0;
                            } else {
                                /* DLA 20180117: seems wrong and senseless to me:
                                $nextHigherMinimumQuantity = $resultFromCalculation['closestHigherQuantity'] - $totalQuantityOfArticlesInStock;
                                if ($nextHigherMinimumQuantity >= ($selectedQuantity - $quantityFromThisProductInCart)) {
                                    $resultFromCalculation['closestHigherQuantity'] = $nextHigherMinimumQuantity;
                                }
                                */
                            }
                        }
                    }

                    $result = array_merge($result, $resultFromCalculation);
                    if ($debug) Mage::log('Closest Higher Quantity #1 = ' . $result['closestHigherQuantity'], null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                    $result['closestHigherQuantity'] = $result['closestHigherQuantity'] - $result['previouslyExistingQuantity'];
                    if ($debug) Mage::log('Closest Higher Quantity #2 = ' . $result['closestHigherQuantity'], null, 'calculateClosestHigherQuantityAndDifference_debug.log');

                    // DEBUG:
                    if ($debug) Mage::log('(case 2-1) total amount in stock: ' . $totalQuantityOfArticlesInStock . ' -> selected amount altogether (incl. cart): ' . $selectedQuantity, null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                    if ($debug) Mage::log($result, null, 'calculateClosestHigherQuantityAndDifference_debug.log');

                    return $result;
                }
            } else {
                // Get cart:
                if (stristr($requestHint, 'addCartQuantity')) {
                    $cart = Mage::getModel('checkout/cart')->getQuote();
                    if ($debug) Mage::log('addCartQuantityRequestHint', null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                    if ($debug) Mage::log($cart->getAllItems(), null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                    foreach ($cart->getAllItems() as $item) {
                        $productSKUfromCart = $item->getProduct()->getSku();
                        // If this product was previously added to cart and already has amount:
                        if ($productSKUfromCart == $this->getSku()) {
                            $quantityFromThisProductInCart = $item->getQty();
                            // Add to seleceted quantity for correct calculation:
                            $selectedQuantity = $selectedQuantity + $quantityFromThisProductInCart;
                        }
                    }
                }

                // Case: no stock -> min-quantity + Losgröße (NORMAL-Case):
                $result = $this->calculateMatchingQuantityForBestellArticle($selectedQuantity);

                // DEBUG:
                if ($debug) Mage::log('(case 2-2) total amount in stock: ' . $totalQuantityOfArticlesInStock . ' -> selected amount: ' . $selectedQuantity, null, 'calculateClosestHigherQuantityAndDifference_debug.log');
                if ($debug) Mage::log($result, null, 'calculateClosestHigherQuantityAndDifference_debug.log');

                if ($selectedQuantity < $this->getMinQtyFromSupplier()) {
                    $result['showBothLimitMessage'] = true;
                } else {
                    $result['showBothLimitMessage'] = false;
                }

                if ($quantityFromThisProductInCart > 0) {
                    $result['closestHigherQuantity'] = $result['closestHigherQuantity'] - $quantityFromThisProductInCart;
                }
                $result['minQtyFromSupplier'] = $this->getMinQtyFromSupplier();

                return $result;
            }
        }

        // VPES can be taken from arguments or initialized internally:
        if (is_array($vpesAsParam) && !empty($vpesAsParam)) {
            $vpes = $vpesAsParam;
        } else {
            $vpes = $this->getSchrackVpesUnserialized();
        }

        // Preparation of all possible values (amount) and sorting them.
        // It is necessary to multiply alL ascending amounts to the previous amount.
        // Result will be the complete content of single pieces in the :
        foreach ($vpes as $vpe) {
            $dynamicQuantityAsMultiplier = 1;

            $saleableCnt = 0;
            foreach ($vpe as $level) {
                if ($level['salable'] && $level['deliverable']) {
                    ++$saleableCnt;
                }
            }
            foreach ($vpe as $level) {
                if ($level['type'] != 'PAL' || $saleableCnt == 1) {
                    if ($dynamicQuantityAsMultiplier > 1) {
                        $dynamicQuantityAsMultiplier = $dynamicQuantityAsMultiplier * $level['quantity'];
                    } else {
                        $dynamicQuantityAsMultiplier = $level['quantity'];
                    }
                    if ($level['salable'] && $level['deliverable']) {
                        // Only add to valid quantities, if deliverable and salable:
                        array_push($collectedQuantities, $dynamicQuantityAsMultiplier);
                    }
                }
            }
        }

        if (is_array($collectedQuantities) && !empty($collectedQuantities)) {

            $limitPercent = Mage::getStoreConfig("schrack/product/packingunitLimitPercent");

            sort($collectedQuantities);
            $matchingQuantity = false;
            $multiplier       = 1;
            $helperValue      = 0;

            while (false === $matchingQuantity) {
                foreach ($collectedQuantities as $key => $numberOfPiecesInPackage) {
                    if ($numberOfPiecesInPackage == 1) continue;
                    $helperValue = $multiplier * $numberOfPiecesInPackage;
                    $minimalHelperValue = $multiplier * $collectedQuantities[0];

                    // Tries to find a quantity value that is nearest to the next multiple available package (depending by defined minimum percentage)
                    if ($overridePackingunitLimitPercent == false && $selectedQuantity >= ($limitPercent / 100 * $helperValue) && $selectedQuantity < $helperValue) {
                        $result['closestHigherQuantity']     = $helperValue;
                        $result['differenceQuantity']        = $helperValue - $selectedQuantity;
                        $result['showHigherQuantityMessage'] = true;
                        $result['invalidQuantity']           = true;

                        $matchingQuantity = true;
                        break;
                    } elseif ($selectedQuantity == $helperValue || $selectedQuantity == $minimalHelperValue) {
                        $result['differenceQuantity']        = 0;
                        $result['showHigherQuantityMessage'] = false;
                        $result['invalidQuantity']           = false;

                        $matchingQuantity = true;
                        break;
                    } elseif ($overridePackingunitLimitPercent == true && $selectedQuantity < $minimalHelperValue) {
                        $result['closestHigherQuantity']     = $minimalHelperValue;
                        $result['differenceQuantity']        = $minimalHelperValue - $selectedQuantity;
                        $result['showHigherQuantityMessage'] = true;
                        $result['invalidQuantity']           = true;

                        $matchingQuantity = true;
                        break;
                    }
                 }
                $multiplier++;

                // This is a (hopefully useless) protection to prevent endless loop in case of
                // exotic constellation, that leads to never reach break convention:
                if ($multiplier == 200) $matchingQuantity = true;
            }
        }

        if ($debug) Mage::log('RESULT:', null, 'calculateClosestHigherQuantityAndDifference_debug.log');
        if ($debug) Mage::log($result, null, 'calculateClosestHigherQuantityAndDifference_debug.log');
        return $result;
    }


    public function calculateMinimumQuantityPackage () {
        $result = array();
        $collectedQuantities = array();
        $collectedAvailableQuantities = array();
        // Take VPES logic:
        $normalCalculation = true;

        // Bestellartikel benötigen Spezialbehandlung:
        if ($this->isBestellArtikel()) {
            // Get info about article stock quantity:
            $totalQuantityOfArticlesInStock = $this->getCumulatedPickupableAndDeliverableQuantities();
            if ($totalQuantityOfArticlesInStock <= 0) {
                if($this->getBatchSizeFromSupplier() > 0) {
                    $collectedAvailableQuantities[0] = $this->getBatchSizeFromSupplier();
                } else {
                    $collectedAvailableQuantities[0] = $this->calculateMatchingQuantityForBestellArticle(0, 'Product::calculateMinimumQuantityPackage()');
                }
                $normalCalculation = false;
            }
        }

        if ($normalCalculation == true) {
            $vpes = $this->getSchrackVpesUnserialized();
            if ( ! $vpes ) {
                $collectedAvailableQuantities = array(1);
            } else {

                // Preparation of all possible values (amount) and sorting them.
                // It is necessary to multiply alL ascending amounts to the previous amount.
                // Result will be the complete content of single pieces in the :
                foreach ( $vpes as $vpe ) {
                    $dynamicQuantityAsMultiplier = 1;
                    $saleableCnt = 0;
                    foreach ( $vpe as $level ) {
                        if ( $level['deliverable'] && $level['salable'] ) {
                            ++$saleableCnt;
                        }
                    }
                    $isSingleVpe = $saleableCnt == 1;
                    foreach ( $vpe as $level ) {
                        $dynamicQuantityAsMultiplier = $dynamicQuantityAsMultiplier * $level['quantity'];
                        array_push($collectedQuantities, $dynamicQuantityAsMultiplier);
                        if (    $level['deliverable'] && $level['salable']
                             && ($level['type'] != 'PAL' || $isSingleVpe) ) {
                            array_push($collectedAvailableQuantities, $dynamicQuantityAsMultiplier);
                        }
                    }
                }
            }
        }

        sort($collectedAvailableQuantities);
        // Take the minimum quantity:
        if (isset($collectedAvailableQuantities[0])) {
            $result = $collectedAvailableQuantities[0];
        }

        return $result;
    }

    public function calculateMinimumQuantityPackageToDisplay () {
        if ( $this->isBestellArtikel() && $this->getCumulatedPickupableAndDeliverableQuantities() < 1 ) {
            return $this->getMinQtyFromSupplier();
        } else {
            return $this->calculateMinimumQuantityPackage();
        }
    }

    public function calculateMatchingQuantityForBestellArticle($selectedQuantity = 0, $requestSource = '') {
        $debug = false;
        $minOrderQuantity = $this->getMinQtyFromSupplier();
        $batchSize = $this->getBatchSizeFromSupplier();
        $result['bestellArtikel']        = true;
        $result['selectedQuantity']      = $selectedQuantity;
        $result['batchSizeFromSupplier'] = $batchSize;
        $result['closestHigherQuantity'] = $selectedQuantity;

        if ($requestSource == 'Product::calculateMinimumQuantityPackage()') {
            return $minOrderQuantity;
        }

        if (intval($minOrderQuantity) <= 0) {
            $minOrderQuantity = 1;
        }
        if (intval($batchSize) <= 0) {
            $batchSize = 1;
        }

        if ($selectedQuantity >= $minOrderQuantity) {
            $dynamicQuantity = $minOrderQuantity;
            $finishLoop = false;
            $multiplier = 1;

            while (false == $finishLoop) {
                if ($dynamicQuantity == $selectedQuantity) {
                    $result['differenceQuantity']        = 0;
                    $result['showHigherQuantityMessage'] = false;
                    $result['showBothLimitMessage']      = false;
                    $result['invalidQuantity']           = false;
                    $finishLoop = true;
                }

                if ($dynamicQuantity > $selectedQuantity) {
                    $result['closestHigherQuantity']     = $dynamicQuantity;
                    $result['differenceQuantity']        = $dynamicQuantity - $selectedQuantity;
                    $result['showHigherQuantityMessage'] = true;
                    $result['invalidQuantity']           = true;
                    $finishLoop = true;
                }

                if ($finishLoop == false) {
                    // Increase quantity, until equal or next matching quantity:
                    $dynamicQuantity = $minOrderQuantity + $multiplier * $batchSize;
                    $multiplier++;
                }
            }
        } else {
            $result['closestHigherQuantity']     = $minOrderQuantity;
            $result['differenceQuantity']        = $minOrderQuantity - $selectedQuantity;
            $result['showHigherQuantityMessage'] = true;
            $result['invalidQuantity']           = true;
        }

        if ($debug) {
            Mage::log($result, null, 'calculateMatchingQuantityForBestellArticle_debug.log');
        }

        return $result;
    }

    public function getSchrackPackingunit ($qty = 1) {
        return $this->_getNonzeroPackingUnit($qty);
    }

    public function getSchrackVpesUnserialized () {
        if ( ! isset($this->_vpesUnserialized) ) {
            if ( isset($this->_data['schrack_vpes']) ) {
                $this->_vpesUnserialized = @unserialize($this->_data['schrack_vpes']);
            } else {
                $this->_vpesUnserialized = false;
                $sku = $this->getSku();
                $sku2vpeMap = Mage::registry('sku2vpeMap');
                if ( is_array($sku2vpeMap) && isset($sku2vpeMap[$sku]) ) {
                    $this->_vpesUnserialized = @unserialize($sku2vpeMap[$sku]);
                }
            }
        }
        return $this->_vpesUnserialized;
    }

    public function saveNative ( $reloadAttributes = false ) {
        if ( ! isset($this->_data['entity_id']) ) {
            return $this->save(); // currently insert is nut supported
        } else if ( $this->_hasDataChanges ) {
            $allAttributes = self::getAllAttributes($reloadAttributes);
            $updateEntity = false;
            $insertsUpdates = array();
            $deletes = array();

            foreach ( $this->_data as $key => $val ) {
                if ( $key == 'stock_item' ) {
                    continue;
                }
                if ( ! isset($allAttributes[$key]) || $allAttributes[$key] == null ) {
                    continue;
                }
                $attribute = $allAttributes[$key];
                $type = $attribute->getBackendType();
                if ( ! self::isSupportedBackendType($type) ) {
                    return $this->save(); // SNH: other types like media_gallery have different columns
                }
                if ( ! array_key_exists($key,$this->_origData) ) {
                    $insertsUpdates[$type][$key] = $val;
                } else {
                    if ( $this->_origData[$key] != $val ) {
                        if ( $type == 'static' ) {
                            $updateEntity = true;
                        } else {
                            $insertsUpdates[$type][$key] = $val;
                        }
                    }
                }
            }

            foreach ( $this->_origData as $key => $val ) {
                if ( ! array_key_exists($key,$this->_data) ) {
                    $attribute = $allAttributes[$key];
                    if ( ! $attribute ) {
                        throw new Exception("Attribute $key is not known to Magento!");
                    }
                    $type = $attribute->getBackendType();
                    if ( $type != 'static' ) {
                        $deletes[$type][$key] = $val;
                    } else {
                        $updateEntity = true;
                    }
                }
            }

            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $writeConnection->beginTransaction();

            try {
                // save attribs
                foreach ( $insertsUpdates as $backendType => $changes ) {
                    $this->applyAttributeChanges($backendType,$changes,$writeConnection,$allAttributes);
                }

                // delete attribs
                foreach ( $deletes as $backendType => $changes ) {
                    $this->applyDeletes($backendType,$changes,$writeConnection,$allAttributes);
                }

                // save entity if necessary
                $this->saveStatics($updateEntity,$allAttributes,$writeConnection);

                if ( count($this->getCategoryIds()) < 1 ) {
                    // We do only deletes yet, because ProtoImporter does the relations later on...
                    $entityId = $this->getEntityId();
                    $sql = "DELETE FROM catalog_category_product WHERE product_id = $entityId;";
                    $writeConnection->query($sql);
                }

                // invalidate indexes
                $sql = "UPDATE `index_process` SET `status` = 'require_reindex' WHERE process_id IN (1,2,3,4,5,6,8,9);";
                $writeConnection->query($sql);

                $writeConnection->commit();
                $this->_hasDataChanges = false;
            } catch ( Exception $ex ) {
                $writeConnection->rollback();
                Mage::logException($ex);
            }
        }
    }

    private function applyAttributeChanges ( $backendType, $changes, $writeConnection, $allAttributes ) {
        if ( ! is_array($changes) || count($changes) < 1 ) {
            return;
        }
        $entityTypeId = $this->getEntityTypeId();
        $storeId = Mage::app()->getStore()->getStoreId();
        $entityId = $this->getEntityId();
        $first = true;
        $sql = "INSERT INTO catalog_product_entity_$backendType (entity_type_id,attribute_id,store_id,entity_id,value) VALUES";
        foreach ( $changes as $attributeCode => $value ) {
            $attributeId = $allAttributes[$attributeCode]->getAttributeId();
            if ( $first ) {
                $first = false;
            } else {
                $sql .= ',';
            }
            $value = str_replace("'","''",$value);
            $sql .= " ($entityTypeId,$attributeId,$storeId,$entityId,'$value')";
            if ( $attributeCode == 'url_key' && $storeId != Schracklive_SchrackCatalog_Model_Protoimport_Base::DEFAULT_STORE_ID ) {
                $sql .= ", ($entityTypeId,$attributeId," . Schracklive_SchrackCatalog_Model_Protoimport_Base::DEFAULT_STORE_ID . ",$entityId,'$value')";
            }
        }
        $sql .= " ON DUPLICATE KEY UPDATE value=VALUES(value);";
        $writeConnection->query($sql);
    }

    private function applyDeletes ( $backendType, $changes, $writeConnection, $allAttributes ) {
        if ( !is_array ($changes) || count ($changes) < 1 ) {
            return;
        }

        $entityId = $this->getEntityId();
        $first = true;
        $sql = "DELETE FROM catalog_product_entity_$backendType WHERE entity_id = $entityId AND attribute_id IN (";
        foreach ( $changes as $attributeCode => $value ) {
            $attributeId = $allAttributes[$attributeCode]->getAttributeId();
            if ( $first ) {
                $first = false;
            } else {
                $sql .= ',';
            }
            $sql .= $attributeId;
        }
        $sql .= ");";
        $writeConnection->query($sql);
    }

    private static function getAllAttributes ( $reloadAttributes ) {
        if ( self::$_allAttributes == null || $reloadAttributes ) {
            $res = Mage::getResourceModel('catalog/product_attribute_collection')->getItems();
            self::$_allAttributes = array();
            foreach ( $res as $attr ) {
                self::$_allAttributes[$attr->getAttributeCode()] = $attr;
            }
        }
        return self::$_allAttributes;
    }

    private static function isSupportedBackendType ( $type ) {
        return    $type == 'static'
               || $type == 'varchar'
               || $type == 'text'
               || $type == 'int'
               || $type == 'decimal'
               || $type == 'datetime';
    }

    /**
     * @param $updateEntity
     * @param $allAttributes
     * @param $writeConnection
     */
    private function saveStatics ( $updateEntity, $allAttributes, $writeConnection ) {
        $entityId = $this->getEntityId();
        $sql = "UPDATE catalog_product_entity SET updated_at = NOW()";
        if ( $updateEntity ) {
            foreach ( $allAttributes as $key => $attr ) {
                if ( $attr->getBackendType() == 'static' && strncmp($key,'schrack_',8) == 0 ) {
                    $val = isset($this->_data[$key]) ? $this->_data[$key] : null;
                    $part = ", $key = '$val'";
                    $sql .= $part;
                }
            }
        }
        $sql .= " WHERE entity_id = $entityId;";
        $writeConnection->query($sql);
    }

    public function getProductUrl ( $useSid = null ) {
        $url = parent::getProductUrl($useSid);
        $url = Mage::helper('schrackcore/url')->ensureCurrentProtocol($url);
        return $url;
    }

    public function getProductUrlWithChapterIfAvail () {
        if ( ! isset($this->_productUrlWithChapterIfAvail) ) {
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $catId = false;
            $requestPath = false;
            $mainCategoryId = $this->getSchrackMainCategoryEntityId();
            if ( $mainCategoryId && $mainCategoryId > '' ) {
                if ( is_numeric($mainCategoryId) ) {
                    $catId = $mainCategoryId;
                } else {
                    $sql = " SELECT entity_id FROM  catalog_category_entity_varchar"
                        . " WHERE attribute_id IN (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 AND attribute_code = 'schrack_group_id')"
                        . " AND value LIKE '%$mainCategoryId' LIMIT 1;";
                    $catId = $readConnection->fetchOne($sql);
                }
            }
            if ( $catId ) {
                $sql = " SELECT request_path FROM core_url_rewrite "
                    . " WHERE category_id = ? AND product_id = ? AND store_id = ? LIMIT 1;";
                $requestPath = $readConnection->fetchOne($sql, [$catId, $this->getId(), $this->getStoreId()]);
            } else {
                $sql = " SELECT request_path FROM core_url_rewrite "
                    . " WHERE category_id IS NOT NULL AND product_id = ? AND store_id = ? ORDER BY length(request_path) LIMIT 1;";
                $requestPath = $readConnection->fetchOne($sql, [$this->getId(), $this->getStoreId()]);
            }
            if ( $requestPath ) {
                $this->_productUrlWithChapterIfAvail = Mage::getBaseUrl() . $requestPath;
            } else {
                $this->_productUrlWithChapterIfAvail = $this->getProductUrl();
            }
        }
        return $this->_productUrlWithChapterIfAvail;
    }

    public function checkQtyForMatchingSubarticle ( $qty ) {
        if ( $this->hasSubProducts() ) {
            $qty = intval($qty);
            $subArticleData = $this->getSubProductData();
            foreach ( $subArticleData as $row ) {
                if ( intval($row['size']) === $qty ) {
                    $subProduct = Mage::getModel('catalog/product')->load($row['id']);
                    return $subProduct;
                }
            }
        }
        return false;
    }

    public function getGreatestSubArticleSize () {
        $maxSize = 0;
        $subArticleData = $this->getSubProductData();
        foreach ( $subArticleData as $row ) {
            if ( intval($row['size']) > $maxSize ) {
                $maxSize = intval($row['size']);
            }
        }
        return $maxSize;
    }

    public static function getSubProductDataForSku ( $sku ) {
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $sql = " SELECT entity_id AS id, schrack_sts_main_vpe_size AS size"
             . " FROM catalog_product_entity"
             . " WHERE schrack_sts_main_article_sku = ? "
             . " AND schrack_sts_statuslocal NOT IN ('tot', 'strategic_no', 'unsaleable')";
        return $readConnection->fetchAll($sql,$sku);
    }

    public static function getSubProductStandardSizeFlagArrayForSku ( $sku ) {
        $res = array();
        $subArticleData = self::getSubProductDataForSku($sku);
        foreach ( $subArticleData as $row ) {
            $size = intval($row['size']);
            if ( $size > 0 ) {
                $res[$size] = true;
            }
        }
        return $res;
    }

    private function getSubProductData () {
        if ( ! isset($this->_subArticleData) ) {
            if ( $this->hasSubProducts() ) {
                $this->_subArticleData = self::getSubProductDataForSku($this->getSku());
            } else {
                $this->_subArticleData = array();
            }
        }
        return $this->_subArticleData;
    }

    public function getSaleableMainArticle () {
        if ( ! $this->hasMainProduct() ) {
            return null;
        }
        if ( ! $this->_mainArticle ) {
            $sku = $this->getSchrackStsMainArticleSku();
            $this->_mainArticle = Mage::getModel('catalog/product')->loadBySku($sku);
        }
        if ( $this->_mainArticle && ! $this->_mainArticle->isDead() ) {
            return $this->_mainArticle;
        } else {
            return null;
        }
    }

    public function getMainVpeName () {
        if ( $this->hasMainProduct() ) { // only for sub products
			return Mage::helper('catalog')->__($this->getSchrackStsMainVpeType());
        } else if ( $this->isCable() || $this->hasSubProducts() ) {
            return Mage::helper('catalog')->__('Yard ware');
        }
        return false;
    }

    public static function getQtyLabelFromQtyUnitId ( $qtyUnitId ) {
        $qtyUnitId = strtolower($qtyUnitId);
        if ( $qtyUnitId == 'm' || $qtyUnitId == 'kg' ) {
            return Mage::helper('catalog')->__('Amount');
        } else {
            return Mage::helper('catalog')->__('Quantity');
        }
    }

    public function getQtyLabel () {
        return self::getQtyLabelFromQtyUnitId($this->getSchrackQtyunitId());
    }

    public function getCategoryId4googleTagManager () {
        $stsId = $this->getSchrackMainCategoryStsId();
        $res = Schracklive_SchrackCatalog_Model_Category::prepareId4googleTagManager($stsId);
        return $res;
    }

    public function getSchrackMainCategoryEntityId () {
        return $this->getData('schrack_main_category_eid');
    }

    public function getSchrackMainCategoryStsId () {
        $catGroupId = $this->getData('schrack_main_category_id');
        $res = self::getSchrackMainCategoryStsIdFromValue($catGroupId);
        return $res;
    }

    public function getSchrackMainCategoryId () {
        $catGroupId = $this->getData('schrack_main_category_id');
        return $catGroupId;
    }

    public static function getSchrackMainCategoryStsIdFromValue ( $catGroupId ) {
        if ( ! is_null($catGroupId) && $catGroupId != '0' ) {
            if ( is_numeric($catGroupId) ) {
                // fallback for old entity id's (should meanwhile not be any more...)
                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $sql = " SELECT value FROM catalog_category_entity_varchar"
                     . " WHERE entity_id = ? AND attribute_id IN "
                     . " (SELECT attribute_id FROM eav_attribute WHERE entity_type_id = 3 "
                     . "                                               AND attribute_code = 'schrack_group_id')";
                $stsId = $readConnection->fetchOne($sql,$catGroupId);
                return $stsId;
            } else {
                return $catGroupId;
            }
        }
        return null;
    }
}
