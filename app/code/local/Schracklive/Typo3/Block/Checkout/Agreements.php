<?php

class Schracklive_Typo3_Block_Checkout_Agreements extends Mage_Checkout_Block_Agreements
{

    public function getAgreements()
    {
        parent::getAgreements();

        $urlTypo3 = Mage::getStoreConfig('schrack/typo3/typo3url');
        $urlTerms = Mage::getStoreConfig('schrack/typo3/typo3termsurl');
        $termsLinkText = Mage::getStoreConfig('schrack/typo3/typo3termstext');

        if ($urlTypo3 && $urlTerms) {
            foreach ($this->getData('agreements') as $agreement) {
                $agreement->setCheckboxText(str_replace($termsLinkText, '<a href="' . $urlTypo3 . $urlTerms . '" target="_blank">' . $termsLinkText . '</a>', $agreement->getCheckboxText()));
            }
        }
    }
}
