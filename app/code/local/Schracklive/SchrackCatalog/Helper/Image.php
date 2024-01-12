<?php

class Schracklive_SchrackCatalog_Helper_Image extends Mage_Catalog_Helper_Image {

    const CART                                  =  10;
    const CART_APP                              =  11;
    const PARTLIST_PAGE                         =  20;
    const PRODUCT_CATEGORY_PAGE                 =  30;
    const PRODUCT_CATEGORY_PAGE_APP_THUMBNAIL   =  31;
    const PRODUCT_CATEGORY_PAGE_APP_BIG         =  32;
    const PRODUCT_DEFAULT                       =  40;
    const PRODUCT_DETAIL_PAGE_MAIN              =  50;
    const PRODUCT_DETAIL_PAGE_MAIN_ZOOM         =  51;
    const PRODUCT_DETAIL_PAGE_RELATED           =  60;
    const PRODUCT_DETAIL_PAGE_RELATED_MOUSEOVER =  70;
    const PRODUCT_DETAIL_PAGE_THUMBNAIL         =  80;
    const PRODUCT_DETAIL_PAGE_THUMBNAIL_DESKTOP =  85;
    const PRODUCT_LISTING_PAGE_MAIN             =  90;
    const PRODUCT_LISTING_PAGE_MOUSEOVER        = 100;
    const PRODUCT_LISTING_PAGE_PRINT            = 110;
    const SEARCH_RESULT_PAGE_MAIN               = 120;
    const SEARCH_RESULT_PAGE_MOUSEOVER          = 130;
    const SEARCH_RESULT_PAGE_QUICKVIEW          = 140;


    public static function getFullPlaceholderUrl () {
        $url = Mage::getSingleton('catalog/product_media_config')->getBaseMediaUrl() . '/placeholder/' . Mage::getStoreConfig("catalog/placeholder/image_placeholder");
        return $url;
    }

    public static function getImageUrl ( $fileName, $type = null ) {
        if ( filter_var($fileName,FILTER_VALIDATE_URL) ) {
            return $fileName; // just for placeholder images
        }
        if ( ! $fileName || $fileName == '' ) {
            return self::getFullPlaceholderUrl();
        }
        if ( ($p = strrpos($fileName,'/')) !== false ) {
            $fileName = substr($fileName,$p + 1);
        }
        $folder = 'foto';
        switch ( $type ) {
            case self::CART                                     : $folder = '110x130';  break;
            case self::CART_APP                                 : $folder = '65x65';    break;
            case self::PARTLIST_PAGE                            : $folder = '110x130';  break;
            case self::PRODUCT_CATEGORY_PAGE                    : $folder = '260x145';  break;
            case self::PRODUCT_CATEGORY_PAGE_APP_THUMBNAIL      : $folder = '65x65';    break;
            case self::PRODUCT_CATEGORY_PAGE_APP_BIG            : $folder = '340x380';  break;
            case self::PRODUCT_DEFAULT                          : $folder = '180x120';  break;
            case self::PRODUCT_DETAIL_PAGE_MAIN                 : $folder = '340x380';  break;
            case self::PRODUCT_DETAIL_PAGE_MAIN_ZOOM            : $folder = '1190x1330';break;
            case self::PRODUCT_DETAIL_PAGE_RELATED              : $folder = '180x120';  break;
            case self::PRODUCT_DETAIL_PAGE_RELATED_MOUSEOVER    : $folder = '340x380';  break;
            case self::PRODUCT_DETAIL_PAGE_THUMBNAIL            : $folder = '90x90';    break; // DLA20210604: changed from '65x65' to avoid more picture loads
            case self::PRODUCT_DETAIL_PAGE_THUMBNAIL_DESKTOP    : $folder = '90x90';    break;
            case self::PRODUCT_LISTING_PAGE_MAIN                : $folder = '150x165';  break;
            case self::PRODUCT_LISTING_PAGE_MOUSEOVER           : $folder = '340x380';  break;
            case self::PRODUCT_LISTING_PAGE_PRINT               : $folder = '65x65';    break;
            case self::SEARCH_RESULT_PAGE_MAIN                  : $folder = '90x90';    break;
            case self::SEARCH_RESULT_PAGE_MOUSEOVER             : $folder = '340x380';  break;
            case self::SEARCH_RESULT_PAGE_QUICKVIEW             : $folder = '340x380';  break;
        }
        $imageRoot = Mage::getStoreConfig('schrack/general/imageserver');
        if ( substr($imageRoot,-1) != '/' ) {
            $imageRoot .= '/';
        }
        $res = $imageRoot . $folder . '/' . $fileName;
        return $res;
    }

	public function __toString() {
		if (preg_match('|^https?://|', $this->_getModel()->getBaseFile())) {
			return $this->_getModel()->getBaseFile();
		} else {
			return parent::__toString();
		}
	}

	public function init(Mage_Catalog_Model_Product $product, $attributeName, $imageFile=null) {
		Varien_Profiler::start('Schracklive_SchrackCatalog_Helper_Image::init');
		$this->_reset();
		$this->_setModel(Mage::getModel('catalog/product_image'));
		$this->_getModel()->setDestinationSubdir($attributeName);

		$product->getAttachmentsCollection();
		$this->setProduct($product);

		$this->setWatermarkImageOpacity(Mage::getStoreConfig("design/watermark/{$attributeName}_imageOpacity"));
		$this->setWatermarkPosition(Mage::getStoreConfig("design/watermark/{$attributeName}_position"));
		$this->setWatermarkSize(Mage::getStoreConfig("design/watermark/{$attributeName}_size"));

		if ($imageFile) {
			$this->setImageFile($imageFile);
		} else {
			// add for work original size
			$this->_getModel()->setBaseFile($product->getData($attributeName));
		}
		Varien_Profiler::stop('Schracklive_SchrackCatalog_Helper_Image::init');
		return $this;
	}

	public function getWidth() {
		return $this->_getModel()->getWidth();
	}

	public function getHeight() {
		return $this->_getModel()->getHeight();
	}
    
    public function getBaseUrl() {
        return $this->getProduct()->getData($this->_getModel()->getDestinationSubdir());
    }

}
