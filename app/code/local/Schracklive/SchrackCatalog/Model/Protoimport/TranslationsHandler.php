<?php
use com\schrack\queue\protobuf\Message;


class Schracklive_SchrackCatalog_Model_Protoimport_TranslationsHandler extends Schracklive_SchrackCatalog_Model_Protoimport_Base {

    private $localeMapping = array(
        'nl_BE' => array('*' => 'nl_NL'),
        // 'nl_NL' => array('*' => 'en_GB'),
        'hr_BA' => array('*' => 'bs_BA'),
        'en_UK' => array('SA' => 'ar_SA', '*' => 'en_GB')

    );

    public function handle ( Message &$importMsg ) {
        if ( ! $importMsg->hasTranslationlocales() ) {
            return;
        }

        $locales = $importMsg->getTranslationlocalesList();
        foreach ( $locales as $translationLocale ) {
            if ( ! $translationLocale->hasTranslations() ) {
                continue;
            }
            $files = array();
            $locale = $translationLocale->getLocale();
            $translations = $translationLocale->getTranslations();
            foreach ( $translations as $translationModule ) {
                $module = $translationModule->getModul();
                $translationMap = $translationModule->getTranslationMap();
                $res = $this->handleTranslationMap($locale,$module,$translationMap);
                if ( $res ) {
                    $files[] = $res;
                }
            }
        }
        echo PHP_EOL;
    }

    private function handleTranslationMap ( $locale, $module, $translationMap ) {
        echo '.';
        $fileName = $this->buildPath($locale,$module);
        $fp = null;
        try {
            $fp = fopen($fileName,"wt");
            foreach ( $translationMap as $val ) {
                $line = $this->mkCsvLine($val->getKey(),$this->prepareTranslation($val->getTranslation()));
                fputs($fp,$line);
            }
        } catch ( Exception $ex ) {
            Mage::logException($ex);
            fclose($fp);
            return false;
        }
        fclose($fp);
        return $fileName;
    }

    private function getMappedLocale ( $locale ) {
        if ( isset($this->localeMapping[$locale]) ) {
            $res = null;
            $dflt = null;
            $shopCountry = Mage::getStoreConfig('schrack/general/country');
            foreach ( $this->localeMapping[$locale] as $k => $v ) {
                if ( strcasecmp($k,$shopCountry) === 0 ) {
                    $res = $v;
                    break;
                } else if ( $k === '*' ) {
                    $dflt = $v;
                }
            }
            if ( ! $res ) {
                return $dflt;
            } else {
                return $res;
            }
        }
        return $locale;
    }

    private function buildPath ( $locale, $module ) {
        $locale = str_replace('-', '_', $locale);
        $locale = $this->getMappedLocale($locale);
        $localeDir = Mage::getBaseDir('locale');
        $fileName = $localeDir  . DS . $locale . DS;
        if ( ! is_dir($fileName) ) {
            $found = false;
            /* DLA 20160530: do not trust these automatics - we'll see. how mapping works...
            $country = explode('_',$locale)[1];
            $files = scandir($localeDir);
            foreach ( $files as $file ) {
                if ( substr($file,- strlen($country)) == $country ) {
                    $fileName = $localeDir  . DS . $file . DS;
                    if ( is_dir($fileName) ) {
                        $found = true;
                        break;
                    }
                }
            }
            if ( ! $found ) {
                $language = explode('_',$locale)[0];
                foreach ( $files as $file ) {
                    if ( substr($file,strlen($language)) == $language ) {
                        $fileName = $localeDir  . DS . $file . DS;
                        if ( is_dir($fileName) ) {
                            $found = true;
                            break;
                        }
                    }
                }
            }
            */
            if ( ! $found ) {
                throw new Exception("cannot determine dir for locale $locale");
            }
        }
        $fileName .= 'local' . DS . $module . '.csv';
        return $fileName;
    }

    private function mkCsvLine ( $k, $v ) {
        return $this->prepareCsvEnclosure($k) . ',' . $this->prepareCsvEnclosure($v) . PHP_EOL;
    }

    private function prepareCsvEnclosure ( $str ) {
        $str = str_replace('"','""',$str);
        return '"' . $str . '"';
    }

    private function prepareTranslation ( $str ) {
        $str = str_replace("\r","",$str);
        $str = str_replace("\n","<br>",$str);
        return $str;
    }

    public function getSchrack2MagentoIdMap () {}
    protected function doHandle ( Message &$importMsg ) {}
    protected function delete ( $magentoId ) {}

}
