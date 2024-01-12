<?php

class Schracklive_SchrackAdminhtml_Model_Admin extends Schracklive_SchrackAdminhtml_Model_Abstract
{
    public function flushCache()
    {
        try {
            $url = Mage::helper('schrack/backend')->getFrontendUrl('sd/Cache/flush');

            // HTTP is blocked from WAF (only HTTPS allowed):
            $url = str_replace('http:', 'https:', $url);

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_FAILONERROR, true);

            $response = curl_exec($ch);

            if ( curl_errno($ch) ) {
                Mage::log('CURL-Flush-ERROR: ' . curl_errno($ch) . " -> " . curl_error($ch), null, 'Cacheerror.log');
                Mage::log('CURL-Flush-ERROR: ' . $url . ' -> ', null, 'Cacheerror.log');
            }

            curl_close($ch);
        }
        catch (Exception $e) {
            $errorResult = array('error' => $e->getMessage(), 'code' => $e->getCode());
            Mage::log('EXCEPTION on flush cache: ' . $errorResult, null, 'Cacheerror.log');
            Mage::log('EXCEPTION on flush cache: ' . $url . ' -> ', null, 'Cacheerror.log');
        }

        if ( ! $response ) {
            Mage::log('!!! ERROR: remote flush failed (no response) !!!', null, 'Cacheerror.log');
            Mage::log('!!! ERROR: remote flush failed (no response) !!! -> ' . $url, null, 'Cacheerror.log');
        } else {
            Mage::log('SUCCESS (Backend Flush Call from Button)', null, 'Cachesuccess.log');
            Mage::log('SUCCESS (Backend Flush Call from Button) -> ' . $url, null, 'Cachesuccess.log');
        }
    }
}