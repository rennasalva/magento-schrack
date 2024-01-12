<?php

class Schracklive_Typo3_Block_Catalog_Category_CmsContent extends Schracklive_Typo3_Block_Abstract
{

    public function __construct()
    {
        parent::__construct();
        $this->_data['cache_key'] = 'cmsContent_' . $this->getIdentifier();
    }

    protected function _toHtml()
    {
        /** @var Schracklive_Typo3_Helper_Category $helper */
        $helper = Mage::helper('typo3/category');
        $helper->setSchrackGroupId($this->getIdentifier());
        $html = $helper->getHtml();
        $this->setData('cache_lifetime', $helper->getCacheTime()); // Set block cache time to same
        if (isset($html) && strlen(trim($html)) > 0) {
            $html = "<!-- cms content for " . $this->getIdentifier() . " -->" . $html . "\n";
        } else {
            $html = "<!-- got no cms content for " . $this->getIdentifier() . " -->";
        }

        return $html;
    }

    protected function getIdentifier()
    {
        $currentCategory = Mage::registry('current_category');
        if ($currentCategory) {
            return $currentCategory->getSchrackGroupId(); // to stay backward compatible in typo3...
        }

        throw new Exception('Unable to get identifier');
    }
}
