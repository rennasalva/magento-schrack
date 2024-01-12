<?php

class Schracklive_Typo3_Block_Cms_Content extends Schracklive_Typo3_Block_Abstract
{

    protected function getIdentifier()
    {
        $currentPage = Mage::getSingleton('cms/page');
        if ($currentPage) {
            return $currentPage->getIdentifier();
        }

        throw new Exception('Unable to get identifier');
    }

}
