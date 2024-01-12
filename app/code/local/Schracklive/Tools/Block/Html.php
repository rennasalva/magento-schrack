<?php

class Schracklive_Tools_Block_Html extends Mage_Page_Block_Html {
    public function __construct () {
        parent::__construct();
    }

    public function printFrontendModel ( $fileName ) {
        $etcDir = Mage::getModuleDir('etc','Schracklive_Tools');
        $p = strrpos($etcDir,'/');
        $feModelFile = substr($etcDir,0,$p) . '/frontendmodels/' . $fileName;
        $json = file_get_contents($feModelFile);
        $phpData = json_decode($json);

        $phpDataTranslated = $this->translate($phpData);

        $skus = $this->getImmediateArticleSkus($phpDataTranslated);
        if ( is_array($skus) && count($skus) > 0 ) {
            $helper = Mage::helper('tools/articles');

            $fields = $this->getAdditionalFieldsToLoad($phpDataTranslated);

            if ( count($fields) > 0 ) {
                $extRes = $helper->getArticleDataExtended($skus,$fields);
                $additionalArticleData = $extRes['articles'];
                $valuesMap = array();
                foreach ( $additionalArticleData as $article ) {
                    foreach ( $phpDataTranslated->articleAttributeMetadata as $k => $v ) {
                        $values = $article->$k;
                        foreach ( $values as $value ) {
                            if ( !isset($valuesMap[$k]) ) {
                                $valuesMap[$k] = [];
                            }
                            $valuesMap[$k][$value] = true;
                        }
                    }
                }
                $labels = $extRes['labels'];
                foreach ( $labels as $attrName => $label ) {
                    $phpDataTranslated->articleAttributeMetadata->$attrName->label = $label;
                    $phpDataTranslated->articleAttributeMetadata->$attrName->properties = array_keys($valuesMap[$attrName]);
                    sort($phpDataTranslated->articleAttributeMetadata->$attrName->properties);
                }
            } else {
                $additionalArticleData = $helper->getAdditionalArticleData($skus);
            }

            $this->addAdditionalArticleData($phpDataTranslated,$additionalArticleData);
        }

        $json = json_encode($phpDataTranslated);
        echo $json;
    }

    private function getAdditionalFieldsToLoad ( $frontendModelPhp ) {
        $res = array();
        foreach ( $frontendModelPhp->articleAttributeMetadata as $field => $vals ) {
            if ( strncmp($field,'schrack_',8) == 0 ){
                $res[$field] = $vals;
            }
        }
        return $res;
    }

    private function getImmediateArticleSkus ( $frontendModelPhp ) {
        $res = array();
        foreach ( $frontendModelPhp->articles as $article ) {
            if (    isset($article->SKU) && $article->SKU[0] !== '@'
                 && isset($article->preload_additional_data) && $article->preload_additional_data ) {
                $res[] = $article->SKU;
            }
        }
        return $res;
    }

    private function addAdditionalArticleData ( &$frontendModelPhp, $additionalArticleData ) {
        foreach ( $frontendModelPhp->articles as $article ) {
            if ( isset($article->SKU) && $article->SKU[0] !== '@' ) {
                $sku = $article->SKU;
                if ( isset($additionalArticleData[$sku]) ) {
                    $additionalData = $additionalArticleData[$sku];
                    foreach ( $additionalData as $k => $v ) {
                        $article->$k = $v;
                    }
                }
            }
        }
        $frontendModelPhp->articlesQuickAcces = $additionalArticleData;
        return;
    }

    private function translate ( $data, $translateKeys = false ) {
        $isArray = is_array($data);
        $res = $isArray ? array() : new stdClass();
        foreach ( $data as $k => $v ) {
            if ( is_string($k) ) {
                if ( $k === 'translateKeys' ) {
                    $translateKeys = $v;
                    continue;
                }
                if ( $translateKeys ) {
                    $k = $this->__($k);
                }
            }
            if ( is_string($v) ) {
                $v = $this->__($v);
            } else if ( is_array($v) || is_object($v) ) {
                $v = $this->translate($v,$translateKeys);
            }
            if ( $isArray ) {
                $res[$k] = $v;
            } else {
                $res->$k = $v;
            }
        }
        return $res;
    }
}
