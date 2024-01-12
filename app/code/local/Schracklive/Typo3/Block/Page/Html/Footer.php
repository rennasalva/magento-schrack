<?php

class Schracklive_Typo3_Block_Page_Html_Footer extends Mage_Page_Block_Html_Footer
{

    /**
     * Processing block html after rendering (& after caching! Very important due to placeholders)
     *
     * @param string $html
     * @return  string
     */
    protected function _afterToHtml($html)
    {
        if ($html) {
            /** @var Schracklive_SchrackCustomer_Model_Customer $advisor */
            $advisor = Mage::helper('schrack')->getAdvisor();
            $search = array(
                '%advisor_photo%',
                '%advisor_name%',
                '%advisor_title%',
                '%advisor_email%',
                '%advisor_email_text%',
                '%advisor_phone%',
                '%advisor_phone_text%',
            );
            $replace = array(
                $advisor->getPhotoUrl('small'),
                $advisor->getName(),
                $advisor->getSchrackTitle(),
                $advisor->getEmail(),
                str_replace('@', '(at)', $advisor->getEmail()),
                urlencode(str_replace(' ', '', $advisor->getSchrackTelephone())),
                $advisor->getSchrackTelephone()
            );
            $html = str_replace($search, $replace, $html);
        }
        return $html;
    }
}
