<?php

class Schracklive_SchrackPage_Block_Html_Head extends Mage_Page_Block_Html_Head {

//    private $_hrefLangs = array(
//        'bs-ba' => 'https://www.schrack.ba/trgovina/',
//        'nl-be' => 'https://www.schrack.be/shop/',
//        'bg-bg' => 'https://www.schrack.bg/shop/',
//        'cs-cz' => 'https://www.schrack.cz/eshop/',
//        'de-at' => 'https://www.schrack.at/shop/',
//        'de-ch' => 'https://www.schrack.ch/shop/',
//        'de-de' => 'https://www.schrack-technik.de/shop/',
//        'en-gb' => 'https://www.schrack.com/shop/',
//        'hr-hr' => 'https://www.schrack.hr/trgovina/',
//        'hu-hu' => 'https://www.schrack.hu/shop/',
//        'pl-pl' => 'https://www.schrack.pl/sklep/',
//        'ro-ro' => 'https://www.schrack.ro/comenzi/',
//        'sr-rs' => 'https://www.schrack.rs/prodavnica/',
//        'ru-ru' => 'https://www.schrack-technik.ru/shop/',
//        'en-sa' => 'https://www.schrack.sa/shop/',
//        'sl-si' => 'https://www.schrack.si/trgovina/',
//        'sk-sk' => 'https://www.schrack.sk/eshop/',
//        'x-default' => 'https://www.schrack.com/shop/'
//    );

    private $_hrefLangs = array(
        'bs-ba' => 'http://127.0.0.1/trgovina/',
        'nl-be' => 'https://www.schrack.be/shop/',
        'bg-bg' => 'https://www.schrack.bg/shop/',
        'cs-cz' => 'https://www.schrack.cz/eshop/',
        'de-at' => 'https://www.schrack.at/shop/',
        'de-ch' => 'https://www.schrack.ch/shop/',
        'de-de' => 'https://www.schrack-technik.de/shop/',
        'en-gb' => 'http://127.0.0.1/shop/',
        'hr-hr' => 'https://www.schrack.hr/trgovina/',
        'hu-hu' => 'https://www.schrack.hu/shop/',
        'pl-pl' => 'https://www.schrack.pl/sklep/',
        'ro-ro' => 'https://www.schrack.ro/comenzi/',
        'sr-rs' => 'https://www.schrack.rs/prodavnica/',
        'ru-ru' => 'https://www.schrack-technik.ru/shop/',
        'en-sa' => 'https://www.schrack.sa/shop/',
        'sl-si' => 'https://www.schrack.si/trgovina/',
        'sk-sk' => 'https://www.schrack.sk/eshop/',
        'x-default' => 'http://127.0.0.1/shop/'
    );

    protected function _construct()	{
        parent::_construct();
        $currentPage = Mage::getSingleton('cms/page');
        //if ($currentPage && $currentPage->getIdentifier() == 'home') { // only limited to homepage, without the condition all pages create their hreflangs
            foreach ($this->_hrefLangs as $locale => $href) {
                $this->addItem('link_rel', $href, 'rel="alternate" hreflang="'.$locale.'"');
          //  }
        }
    }

    public function getRobots() {
        if (Mage::getStoreConfig('schrackdev/development/test')) {
            return 'NOINDEX,NOFOLLOW';
        } else {
            return parent::getRobots();
        }
    }

    public function getCssJsHtml() {
        list($countryCode) = explode('_',
            Mage::getStoreConfig('general/locale/code', Mage::getStoreConfig('schrack/shop/store')));
        $countryCode = strtolower($countryCode);
        $pathToShadowboxLanguageFile = "schrackdesign/Public/Javascript/shadowbox/$countryCode.js";
        if (@is_file(Mage::getDesign()->getSkinBaseDir().DS.$pathToShadowboxLanguageFile)) {
            $this->addItem('skin_js', $pathToShadowboxLanguageFile);
        }

        return parent::getCssJsHtml();
    }

    /**
     * Add Link element to HEAD entity
     *
     * @param string $rel forward link types
     * @param string $href URI for linked resource
     * @return Mage_Page_Block_Html_Head
     */
    public function addLinkRel($rel, $href)
    {
        $this->addItem('link_rel', $href, 'rel="' . $rel . '"', null, null, $rel);
        return $this;
    }

    /**
     * Add HEAD Item
     *
     * Allowed types:
     *  - js
     *  - js_css
     *  - skin_js
     *  - skin_css
     *  - rss
     *
     * @param string $type
     * @param string $name
     * @param string $params
     * @param string $if
     * @param string $cond
     * @return Mage_Page_Block_Html_Head
     */
    public function addItem($type, $name, $params=null, $if=null, $cond=null, $addToIndex=null, $before = false)
    {

        if ($type==='skin_css' && empty($params)) {
            $params = 'media="all"';
        }
        if ($addToIndex !== null) {
            $index = $type.'/'.$name.'/'.$addToIndex;
        } else {
            $index = $type.'/'.$name;
        }



        if (stristr($name, 'schrackdesign/Public/Javascript/packedOpcheckout.js.non-mobile-version')) {
            $name = '..' . $this->addAutoVersionQueryString('/skin/frontend/schrack/default/schrackdesign/Public/Javascript/packedOpcheckout.js');
            $type= 'js';
        }

        if (stristr($name, 'schrackdesign/Public/Javascript/packedFooter.js.non-mobile-version')) {
            $name = '..' . $this->addAutoVersionQueryString('/skin/frontend/schrack/default/schrackdesign/Public/Javascript/packedFooter.js');
            $type= 'js';
        }

        if (stristr($name, 'schrackdesign/Public/Stylesheets/rwd/allPacked.css.non-mobile-version')) {
            $developerMode = false;
            //$debugCSSWithSourceMaps = Mage::getModel('core/cookie')->get('source_maps_css');
            $debugCSSWithSourceMaps = 'disabled'; // --> set always to disabled, because sourcemaps are running also in dynamic CSS/JS creation process:
            if ($debugCSSWithSourceMaps == 'enabled') {
                $developerMode = true;
            }
            if ($developerMode) {
                $name = '../skin/frontend/schrack/default/schrackdesign/Public/Stylesheets/rwd/allPacked.css';
            } else {
                $name = '..' . $this->addAutoVersionQueryString('/skin/frontend/schrack/default/schrackdesign/Public/Stylesheets/rwd/allPacked.css');
                $type= 'js_css';
            }
        }

        if (stristr($name, 'calendar/calendar-win2k-1.css.non-mobile-version')) {
            $name = str_replace('/js/', '', $this->addAutoVersionQueryString('/js/calendar/calendar-win2k-1.css'));
            $type= 'js_css';
        }

        $this->_data['items'][$index] = array(
            'type'   => $type,
            'name'   => $name,
            'params' => $params,
            'if'     => $if,
            'cond'   => $cond,
        );

        //var_dump($this->_data['items'][$index]);
        return $this;
    }


    public function addJs($name, $params = "", $referenceName = '*',$before = null)
    {
        if (stristr($name, '../skin/frontend/schrack/default/schrackdesign/Public/Javascript/allPacked.js.non-mobile-version')) {
            $name = '..' . $this->addAutoVersionQueryString('/skin/frontend/schrack/default/schrackdesign/Public/Javascript/allPacked.js');
        }

        if (stristr($name, '../skin/frontend/schrack/default/schrackdesign/Public/Javascript/commonPacked.js.non-mobile-version')) {
            $name = '..' . $this->addAutoVersionQueryString('/skin/frontend/schrack/default/schrackdesign/Public/Javascript/commonPacked.js');
        }

        $this->addItem('js', $name, $params);
        return $this;
    }


    private function addAutoVersionQueryString($ressourceFilepath){
        $fullSystemRessourceFilepath = Mage::getBaseDir('base') . $ressourceFilepath;
        if(strpos($ressourceFilepath, '/') !== 0 || !file_exists($fullSystemRessourceFilepath)) {
            return $ressourceFilepath;
        }

        $filename = basename($ressourceFilepath);

        $path = str_replace($filename, '', $fullSystemRessourceFilepath);
        $ressourcePath = str_replace($filename, '', $ressourceFilepath);

        $dateFileTimeIdentier = date('Ymd_His', filemtime($fullSystemRessourceFilepath));
        $newFullSystemRessourceFilepath = $path . $dateFileTimeIdentier . '_' . $filename;

        if (!file_exists($newFullSystemRessourceFilepath)) {
            shell_exec("chmod 770 " . $path);
            shell_exec("cp " . $fullSystemRessourceFilepath . ' ' . $newFullSystemRessourceFilepath);
            shell_exec("chmod 570 " . $path);

            // Only notify TYPO, if we changed this from Frontend (block Magento-Backend for do that!):
            $mageBackend = false;

            if (file_exists(Mage::getBaseDir('etc') . DS . 'backend_marker.txt')) {
                $mageBackend = true;
                Mage::log('This is Backend', null, "versioned_file_change_host.log");
            } else {
                Mage::log('This is Frontend', null, "versioned_file_change_host.log");
            }

            if ($mageBackend == false) {
                $this->notifyTypo($filename,$dateFileTimeIdentier . '_');
            }
        }

        // Cleanup old scripts:
        Mage::helper('schrackpage/tools')->cleanupDeprecatedRessources();

        return $ressourcePath . $dateFileTimeIdentier . '_' . $filename;
    }

    private function notifyTypo ( $fileName, $preFix ) {
        $data = [$fileName => $preFix];
        $json = json_encode($data);
        $url = Mage::getStoreConfig('schrack/typo3/typo3url') . Mage::getStoreConfig('schrack/typo3/notifyverionfilesupdate');

        Mage::log("Sending versioned file change: $json", null, 'versioned_file_change.log');
        $trace = (new Exception())->getTraceAsString();
        Mage::log("Stacktrace: $trace", null, 'versioned_file_change.log');
        Mage::log("getHostname: ".gethostname(), null, 'versioned_file_change.log');

        $ch = curl_init($url);
        // Set Proxy
        // Test Enviroment
        if (substr(gethostname(), 0, 2) == 'tl') {
            curl_setopt($ch, CURLOPT_PROXY, '172.22.4.250:8080');
        }
        // Live Enviroment
        if (substr(gethostname(), 0, 2) == 'sl') {
            curl_setopt($ch, CURLOPT_PROXY, '172.30.0.250:8080');
        }
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json)
            ]
        );

        $responseBody = curl_exec($ch);

        if ( ! curl_errno($ch) ) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            Mage::log("Transferred versioned file change (#1) to TYPO-URL: " . $url, null, 'versioned_file_change.log');
            Mage::log("Got versioned file change response HTTP: $httpCode, Body: $responseBody", null ,'versioned_file_change.log');
        } else {
            $error = curl_error($ch);
            Mage::log("Transferred versioned file change (#2) to TYPO-URL: " . $url, null, 'versioned_file_change.log');
            Mage::log("Got versioned file change error: $error",null,'versioned_file_change.log');
        }
    }
}
