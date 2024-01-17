<?php

class Schracklive_Typo3_Helper_SessionUrl
{

    public function replacePlaceholderUrls($html)
    {
        // Replace placeholders with magento URLs
        preg_match_all('/<a.*[^>]*href=(\"??)(shop:\/\/[^\" >]*?)\\1[^>]*>(.*)/siU', (string) $html, $matches);
        $urls = array_unique($matches[2]);
        foreach ($urls as $url) {
            $html = str_ireplace($url, Mage::getUrl((str_replace('shop://', '', strtolower($url)))), $html);
        }
        return $html;
    }

}
