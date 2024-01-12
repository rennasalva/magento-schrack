<?php

class Schracklive_SchrackCatalog_Helper_Preparator extends Mage_Core_Helper_Abstract {

    const PREPARATE_PRODUCTS = false;

    const NDX_STATUSLOCAL        =  0;
    const NDX_SHOWINVENTORY      =  1;
    const NDX_FORSALE            =  2;
    const NDX_QTY                =  3;
    const NDX_REGULARPRICE       =  4;
    const NDX_ISHIDDEN           =  5;
    const NDX_ISVALID            =  6;
    const NDX_PROMOVALIDTO       =  7;
    const NDX_REPLACEMENTSKU     =  8;
    const NDX_SCALEPRICES        =  9;
    const NDX_LONGTEXT           = 10;

    private $data = array(
    //                         STS                                  Stock WWS                                            STS                       detailDescription   2         3         4         5         6         7         8         9         0         1         2         3         4         5         6         7         8
    //     SKU                 statuslocal, showinventory, forsale, qty,  RegularPrice, isHidden, isValid, PromoValidTo, replacementSku scalePrice 123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890
        'XC01010101' => array( 'istausl',   true,          true,    100,  false,        0,        1,       null,         null,          false,     null ),
        'BM018102--' => array( 'std',       true,          false,   100,  false,        0,        1,       null,         'BM017163--',  true,      'Hier steht ein eigentlich recht langer Text, dessen einzige Aufgabe es ist, repräsentativ für eine Artikelbeschreibung zu sein, die mehr als 100 Zeichen Platzbedarf an den Tag legt.' ),
        'BM018104--' => array( 'std',       true,          false,   100,  true,         0,        1,       '2019-01-31', null,          false,      'Hier ist es kurz und bündig.' ),
        'BM018106--' => array( 'std',       true,          false,     0,  false,        0,        1,       null,         null,          false,      'max. Dauerspannung: 255Vac, In: 20kA (8/20)' ),
        'BM018110--' => array( 'std',       false,         false,   100,  false,        0,        1,       null,         null,          false,     null ),
        'BM018113--' => array( 'std',       true,          false,   100,  false,        1,        1,       null,         null,          false,     null ),
        'BM018116--' => array( 'gesperrt',  true,          false,   100,  false,        0,        0,       null,         'BM019102--',  false,     null ),
        'BM018120--' => array( 'std',       true,          false,   100,  false,        0,        0,       null,         null,          false,     null ),
        'BM018125--' => array( 'istausl',   true,          false,   100,  false,        0,        1,       null,         'BM019104--',  false,     null ),
        'BM018132--' => array( 'istausl',   true,          true,    100,  true,         0,        1,       null,         'BM019106--',  false,     null ),
        'BM018140--' => array( 'istausl',   true,          false,     0,  false,        0,        1,       null,         'BM019110--',  false,     null ),
        'BM018150--' => array( 'istausl',   true,          true,      0,  true,         0,        1,       null,         'BM019113--',  false,     null ),
        'BM018163--' => array( 'tot',       true,          false,   100,  false,        0,        1,       null,         'BM019116--',  false,     null ),
        'BM0171005-' => array( 'tot',       true,          false,   100,  false,        0,        1,       null,         null,          false,     null ),
// VISIO 50 Switches - Switching and Network Inserts:
        'EL999991--' => array( 'std',       true,          false,   100,  false,        0,        1,       null,         'EV103014--',  true,      'Here we have a text that is a bit longer than usually. It is about dragons and Georges and magic and witches, black ones and blue ones as well. All these figures will fight and love and so on.' ),
        'HSEMRJ6GWA' => array( 'std',       true,          false,   100,  true,         0,        1,       '2019-01-31', null,          false,      'This one is very short.' ),
        'EL161000Q-' => array( 'std',       true,          false,     0,  false,        0,        1,       null,         null,          false,     null ),
        'EL176211--' => array( 'std',       false,         false,   100,  false,        0,        1,       null,         null,          false,     null ),
        'EV100025--' => array( 'std',       true,          false,   100,  false,        1,        1,       null,         null,          false,     null ),
        'EV103002--' => array( 'gesperrt',  true,          false,   100,  false,        0,        1,       null,         'EV104007--',  false,     null ),
        'EV104008--' => array( 'std',       true,          false,   100,  false,        0,        0,       null,         null,          false,     null ),
        'EV104101--' => array( 'istausl',   true,          false,   100,  false,        0,        1,       null,         'EV114008--',  false,     null ),
        'EV106001--' => array( 'istausl',   true,          true,    100,  true,         0,        1,       null,         'EV104102--',  false,     null ),
        'EL999992--' => array( 'istausl',   true,          false,     0,  false,        0,        1,       null,         'EV106002--',  false,     null ),
        'HSEMRJ6GWT' => array( 'istausl',   true,          true,      0,  true,         0,        1,       null,         'EL999993--',  false,     null ),
        'EL176221--' => array( 'tot',       true,          false,   100,  false,        0,        1,       null,         'HSEMRJ6GWS',  false,     null ),
        'EV100027--' => array( 'tot',       true,          false,   100,  false,        0,        1,       null,         null,          false,     null )
    );

    public function hasToBePrepared ( $sku ) {
        return self::PREPARATE_PRODUCTS && ! is_null($sku) && isset($this->data[$sku]);
    }

    public function prepareProduct ( $product ) {
        if ( ! is_object($product) ) {
            return $product;
        }
        $sku = $product->getSku();
        if ( ! $this->hasToBePrepared($sku) ) {
            return $product;
        }
        $vals = $this->data[$sku];
        $product->setSchrackStsStatuslocal($vals[self::NDX_STATUSLOCAL]);
        $product->setSchrackStsShowinventory($this->bool2bloodySqlString($vals[self::NDX_SHOWINVENTORY]));
        $product->setSchrackStsForsale($this->bool2bloodySqlString($vals[self::NDX_FORSALE]));
        $product->setSchrackLongTextAddition($vals[self::NDX_LONGTEXT]);
    }

    public function getStockQuantity ( $product, $warehouseId ) {
        if ( ! is_object($product) ) {
            return -1;
        }
        $sku = $product->getSku();
        if ( ! $this->hasToBePrepared($sku) || $warehouseId === '999' ) {
            return -1;
        }
        $vals = $this->data[$sku];
        return $vals[self::NDX_QTY];
    }

    public function prepareWwsPriceInfo ( $soapPriceInfo ) {
        if ( is_null($soapPriceInfo) ) {
            return false;
        }
        $sku = $soapPriceInfo->ItemID;
        if ( ! $this->hasToBePrepared($sku) ) {
            return false;
        }
        $vals = $this->data[$sku];
        $soapPriceInfo->Price = 150;
        if ( $vals[self::NDX_REGULARPRICE] ) {
            $soapPriceInfo->RegularPrice = 220;
        }
        $soapPriceInfo->IsHidden     = $vals[self::NDX_ISHIDDEN];
        $soapPriceInfo->IsValid      = $vals[self::NDX_ISVALID];
        $soapPriceInfo->PromoValidTo = $vals[self::NDX_PROMOVALIDTO];
        if ( ! $vals[self::NDX_SCALEPRICES] ) {
            return null;
        }
        $scales = array( array( 'qty' => 1, 'price' => 150 ), array( 'qty' => 100, 'price' => 130 ), array( 'qty' => 1000, 'price' => 110 ) );
        return $scales;
    }

    public function getReplacementProduct ( $product ) {
        if ( is_null($product) ) {
            return -1;
        }
        $sku = $product->getSku();
        if ( ! $this->hasToBePrepared($sku) ) {
            return null;
        }
        $vals = $this->data[$sku];
        $res = false;
        if ( $vals[self::NDX_REPLACEMENTSKU] ) {
            $res = Mage::getModel('catalog/product')->loadBySku($vals[self::NDX_REPLACEMENTSKU]);
        }
        return $res;
    }

    public function getPrecedingProduct ( $product ) {
        if ( is_null($product) ) {
            return -1;
        }
        $sku = $product->getSku();
        foreach ( $this->data as $key => $vals ) {
            if ( $sku === $vals[self::NDX_REPLACEMENTSKU] && $vals[self::NDX_STATUSLOCAL] !== 'tot' ) {
                $res = Mage::getModel('catalog/product')->loadBySku($key);
                return $res;
            }
        }
        return false;
    }

    public function getAdditionalPromotions () {
        $res = array();
        foreach ( $this->data as $key => $vals ) {
            if ( ! $vals[NDX_FORSALE] && ! $vals[NDX_STATUSLOCAL] && $vals[NDX_REGULARPRICE] && $vals[NDX_PROMOVALIDTO] ) {
                $obj = new stdClass();
                $obj->ItemID = $key;
                $obj->PromoTypes = '710193';
                $res[] = $obj;
            }
        }
        return $res;
    }

    private function bool2bloodySqlString ( $bool ) {
        if ( $bool )
            return '1';
        return '0';
    }
}