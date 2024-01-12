<?php

class Schracklive_Schrack_Helper_Csv extends Mage_Core_Helper_Abstract {

    /**
     * given a list of possible delimiters, find the one that's first in the string
     * @param string $line the csv line
     * @param array $delims array of delimiter characters
     * @throws Exception
     */
    public function determineDelimiter($line, array $delims = null) {
        if ($delims === null)
            $delims = array(';', ',', "\t");
        $i = 0;
        $pos = array();
        foreach ($delims as $delim) {
            $_pos = strpos($line, $delim);
            if ($_pos !== false)
                $pos[$i] = $_pos;
            ++$i;
        }
        if ( count($pos) == 0 ) {
            return false;
        }
        $helper = Mage::helper('schrackcore/array');
        $resI = $helper->findIndexOfMinValue($pos);
        return $delims[$resI];
    }

    public function removeEmptyCsvLines ( $lines ) {
        $res = array();
        foreach ( $lines as $line ) {
            $tmpLine = trim(str_replace(';','',$line));
            if ( $tmpLine > ' ' ) {
                $res[] = $line;
            }
        }
        return $res;
    }

	public function createCsvDownload ( Mage_Core_Controller_Varien_Action $controller, $blockName, $fileName ) {
		$controller->loadLayout();
		$layout = $controller->getLayout();
		$block = $layout->getBlock($blockName);
		$html = $block->renderView();
	    $this->initCsvDownload($fileName);
		die($html);
	}

	public function createCsvDownloadFromCart ( Mage_Checkout_Model_Cart $cart ) {
	    $fileName = 'cart.csv';
	    $this->initCsvDownload($fileName);
        $customer       = Mage::getSingleton('customer/session')->getCustomer();
        $priceHelper    = Mage::helper('schrackcatalog/price');
        $chkHelper      = Mage::helper('schrackcheckout');
        $imgHelper      = Mage::helper('catalog/image');
        $showListPrice  = $priceHelper->doShowListPrice();

        $this->echoCsv($this->__('Article Number'));
        $this->echoCsv($this->__('Qty'));
        $this->echoCsv($this->__('Qty Unit'));
        $this->echoCsv($this->__('Product Name'));
        $this->echoCsv($this->__('Image'));
        $this->echoCsv($this->__('Price'));
        $this->echoCsv($this->__('Surcharge'));
        $this->echoCsv($this->__('Subtotal'));
        if ( $showListPrice ) {
            $this->echoCsv($this->__('List Price'));
        }
        $this->echoCsv($this->__('Catalog Product Link'));
        $this->echoCsvNL();

        foreach( $cart->getItems() as $item ) {
            $product    = $item->getProduct();
            $productImg = $imgHelper->init($product, 'image');
            $currency   = $priceHelper->getCurrencyForCustomer($product, $customer);

            $this->echoCsv($this->escapeHtml($product->getSku()));
            $this->echoCsv(str_replace('.', ',', $item->getQty()));
            $this->echoCsv($product->getSchrackQtyunit());
            $this->echoCsv($product->getName());
            if (strtoupper(Mage::getStoreConfig('schrack/general/country')) == 'PL') {
                $this->echoCsv('=HIPERŁĄCZE(""' . (string) $productImg . '"")');
            } else {
                $this->echoCsv('=HYPERLINK(""' . (string) $productImg . '"")');
            }
            $this->echoCsv(($chkHelper->isPriceAvailable($product, $item->getSchrackBasicPrice())? ($currency . ' ') : '')
                            . $chkHelper->formatPrice($product, $item->getSchrackBasicPrice()));
            $this->echoCsv(($chkHelper->isPriceAvailable($product, $item->getSchrackRowTotalSurcharge()) ? ($currency . ' ') : '')
                            . $chkHelper->formatPrice($product, $item->getSchrackRowTotalSurcharge()));
            $this->echoCsv(($chkHelper->isPriceAvailable($product, $item->getRowTotal()) ? ($currency . ' ') : '')
                            . $chkHelper->formatPrice($product, $item->getRowTotal()));
            if ( $showListPrice ) {
                $this->echoCsv(($chkHelper->isPriceAvailable($product, $product->getPrice()) ? ($currency . ' ') : '')
                                . $chkHelper->formatPrice($product, $product->getPrice()));
            }
            if (strtoupper(Mage::getStoreConfig('schrack/general/country')) == 'PL') {
                $this->echoCsv('=HIPERŁĄCZE(""' . Mage::getUrl('sd/sd') . '?a=' . $product->getSku() . '"")');
            } else {
                $this->echoCsv('=HYPERLINK(""' . Mage::getUrl('sd/sd') . '?a=' . $product->getSku() . '"")');
            }
            $this->echoCsvNL();
        }
        die();
    }

	public function createCsvDownloadFromCurrentPartslist () {
	    $fileName = 'partslist.csv';
	    $this->initCsvDownload($fileName);

        $customer    = Mage::getSingleton('customer/session')->getCustomer();
        $priceHelper = Mage::helper('schrackcatalog/price');
        $chkHelper   = Mage::helper('schrackcheckout');
        $showListPrice  = $priceHelper->doShowListPrice();

        $this->echoCsv($this->__('Product'));
        $this->echoCsv($this->__('Qty'));
        $this->echoCsv($this->__('Qty Unit'));
        $this->echoCsv($this->__('Product Name'));
        $this->echoCsv($this->__('Image'));
        $this->echoCsv($this->__('Price'));
        if ( $showListPrice ) {
            $this->echoCsv($this->__('List Price'));
        }
        $this->echoCsv($this->__('Catalog Product Link'));
        $this->echoCsvNL();

        foreach ( $this->getCurrentPartslistItems() as $item ) {
            $product = $item->getProduct();
            $productImg = Mage::helper('catalog/image')->init($product, 'small_image')->constrainOnly(true)->resize(66, 66);
            $currency = $priceHelper->getCurrencyForCustomer($product, $customer);

            $this->echoCsv($this->escapeHtml($product->getSku()));
            $this->echoCsv(intval($item->getQty()));
            $this->echoCsv($product->getSchrackQtyunit());
            $this->echoCsv($product->getName());
            if (strtoupper(Mage::getStoreConfig('schrack/general/country')) == 'PL') {
                $this->echoCsv('=HIPERŁĄCZE(""' . (string) $productImg . '"")');
            } else {
                $this->echoCsv('=HYPERLINK(""' . (string) $productImg . '"")');
            }
            $this->echoCsv(($chkHelper->isPriceAvailable($product, $item->getSchrackBasicPrice()) ? ($currency . ' ') : '')
                            . ' ' . $chkHelper->formatPrice($product, $item->getSchrackBasicPrice()));
            if ( $showListPrice ) {
                $this->echoCsv(($chkHelper->isPriceAvailable($product, $product->getPrice()) ? ($currency . ' ') : '')
                                . $chkHelper->formatPrice($product, $product->getPrice()));
            }
            if (strtoupper(Mage::getStoreConfig('schrack/general/country')) == 'PL') {
                $this->echoCsv('=HIPERŁĄCZE(""' . Mage::getUrl('sd/sd') . '?a=' . $product->getSku() . '"")');
            } else {
                $this->echoCsv('=HYPERLINK(""' . Mage::getUrl('sd/sd') . '?a=' . $product->getSku() . '"")');
            }
            $this->echoCsvNL();
        }
        die();
    }


	public function createCsvDownloadFromSkus ( array $skus, $fileName, array $qtys = null ) {
        $customer      = Mage::getSingleton('customer/session')->getCustomer();
        $priceHelper   = Mage::helper('schrackcatalog/price');
        $chkHelper     = Mage::helper('schrackcheckout');
        $showListPrice = $priceHelper->doShowListPrice();

	    $this->initCsvDownload($fileName);

        $this->echoCsv($this->__('Article Number'));
        if ( $qtys ) {
            $this->echoCsv($this->__('Qty'));
        }
        $this->echoCsv($this->__('Qty Unit'));
        $this->echoCsv($this->__('Product Name'));
        $this->echoCsv($this->__('Image'));
        // TODO: prices
        /*
        $this->echoCsv($this->__('Price'));
        $this->echoCsv($this->__('Surcharge'));
        $this->echoCsv($this->__('Subtotal'));
        */
        if ( $showListPrice ) {
            $this->echoCsv($this->__('List Price'));
        }
        $this->echoCsv($this->__('Catalog Product Link'));
        $this->echoCsvNL();
        $i = 0;
        foreach ( $skus as $sku ) {
            $product = Mage::getModel('catalog/product')->loadBySku($sku);
            if ( ! $product->getId() ) {
                continue;
            }
            $_name = $product->getName();
            $_hover_product_image = Mage::helper('catalog/image')->init($product, 'image');
            $_hover_product_image_url = (string)$_hover_product_image;
            $currency = $priceHelper->getCurrencyForCustomer($product, $customer);
            $this->echoCsv($this->escapeHtml($sku));
            if ( $qtys ) {
                $this->echoCsv(str_replace('.', ',', $qtys[$i])); // TODO: NLS
            }
            $this->echoCsv($product->getSchrackQtyunit());
            $this->echoCsv($_name);
            if (strtoupper(Mage::getStoreConfig('schrack/general/country')) == 'PL') {
                $this->echoCsv('=HIPERŁĄCZE(""' . $_hover_product_image_url . '"")');
            } else {
                $this->echoCsv('=HYPERLINK(""' . $_hover_product_image_url . '"")');
            }
            // TODO: prices
            /*
            $_currency = $_priceHelper->getCurrencyForCustomer($_product, $_customer);
            $this->echoCsv(($this->helper('schrackcheckout')->isPriceAvailable($_product, $_item->getSchrackBasicPrice()) ? ($_currency . ' ') : '') . ' ' . $this->helper('schrackcheckout')->formatPrice($_product, $_item->getSchrackBasicPrice()));
            $this->echoCsv(($this->helper('schrackcheckout')->isPriceAvailable($_product, $_item->getSchrackRowTotalSurcharge()) ? ($_currency . ' ') : '') . $this->helper('schrackcheckout')->formatPrice($_product, $_item->getSchrackRowTotalSurcharge()));
            $this->echoCsv(($this->helper('schrackcheckout')->isPriceAvailable($_product, $_item->getRowTotal()) ? ($_currency . ' ') : '') . $this->helper('schrackcheckout')->formatPrice($_product, $_item->getRowTotal()));
            */
            if ( $showListPrice ) {
                $this->echoCsv(($chkHelper->isPriceAvailable($product, $product->getPrice()) ? ($currency . ' ') : '')
                                . $chkHelper->formatPrice($product, $product->getPrice()));
            }
            if (strtoupper(Mage::getStoreConfig('schrack/general/country')) == 'PL') {
                $this->echoCsv('=HIPERŁĄCZE(""' . Mage::getUrl('sd/sd') . '?a=' . $product->getSku() . '"")');
            } else {
                $this->echoCsv('=HYPERLINK(""' . Mage::getUrl('sd/sd') . '?a=' . $product->getSku() . '"")');
            }
            $this->echoCsvNL();
            ++$i;
        }
        die();
	}

    public function createCsvDownloadByDocument ($documentId, $documentType) {
        if ( ! $documentId ) {
            die('ERROR: No DocumentId given!');
        }

        if ( ! $documentType ) {
            die('ERROR: No DocumentType given!');
        }

        $priceHelper    = Mage::helper('schrackcatalog/price');
        $_customer      = Mage::getSingleton('customer/session')->getCustomer();
        $documentTypeId = null;

        $helper = Mage::helper('schracksales/order');
        $document = $helper->getFullDocument($documentId, $documentType);
        $fileName = $this->__($documentType) . '_' . $document->getDocumentNumber() . '.csv';
        $items = $document->getItemsCollection();
        foreach ($items as $item) {
            if (in_array($documentType, array('offer', 'order'))) {
                $quantities[] = $item->getQtyOrdered();
            } else {
                $quantities[] = $item->getQty();
            }
            $descriptions[]           = $item->getDescription();
            $skus[]                   = $item->getSku();
            $schrackBasicPrices[]     = $item->getPrice();
            $schrackTotalSurcharges[] = $item->getSchrackSurcharge();
            $schrackBackorderQty[]    = intval($item->getSchrackBackorderQty(), 10);
            $schrackRowTotal[]        = $item->getRowTotal();
        }

	    $this->initCsvDownload($fileName);

        $this->echoCsv($this->__('SKU'));
        $this->echoCsv($this->__('Qty'));
        $this->echoCsv($this->__('Qty Unit'));
        $this->echoCsv($this->__('Product Name'));
        $this->echoCsv($this->__('Image'));
        if ( $documentType !== 'shipment' ) {
            $this->echoCsv($this->__('Price'));
            $this->echoCsv($this->__('Back Ordered'));
            $this->echoCsv($this->__('Subtotal'));
        }
        $this->echoCsv($this->__('Catalog Product Link'));

        $this->echoCsvNL();

        foreach ($skus as $index => $sku) {
            if (in_array($sku, array('TRANSPORT-','MANIPULAT-','VERPACKUNG'))) {
                continue;
            }
            $_product = Mage::getModel('catalog/product')->loadBySku($sku);
            $isShopProduct = $_product && $_product->getId();
            $_name = $isShopProduct ? $_product->getName() : $descriptions[$index];
            $_hover_product_image = $isShopProduct ? Mage::helper('catalog/image')->init($_product, 'image') : '';
            $_hover_product_image_url = $isShopProduct ? (string)$_hover_product_image : '';
            $this->echoCsv($this->htmlEscape($sku));
            $this->echoCsv(intval($quantities[$index]));
            $this->echoCsv($isShopProduct ? $_product->getSchrackQtyunit() : '');
            $this->echoCsv($_name);
            if (strtoupper(Mage::getStoreConfig('schrack/general/country')) == 'PL') {
                $this->echoCsv($isShopProduct ?'=HIPERŁĄCZE(""' . $_hover_product_image_url . '"")' : '');
            } else {
                $this->echoCsv($isShopProduct ?'=HYPERLINK(""' . $_hover_product_image_url . '"")' : '');
            }
            if ( $documentType !== 'shipment' ) {
                $_currency = $isShopProduct ? $priceHelper->getCurrencyForCustomer($_product, $_customer) : Mage::getStoreConfig('currency/options/default');
                $price = $isShopProduct ? Mage::helper('schrackcheckout')->formatPrice($_product, $schrackBasicPrices[$index]) : number_format($schrackBasicPrices[$index], 2);
                $this->echoCsv($_currency . ' ' . $price);
                // $this->echoCsv($_currency . ' ' . Mage::helper('schrackcheckout')->formatPrice($_product, $schrackTotalSurcharges[$index]));
                if ( $schrackBackorderQty[$index] > 0 ) {
                    $this->echoCsv($schrackBackorderQty[$index] . ' ' . ($_product ? $_product->getSchrackQtyunit() : ''));
                } else {
                    $this->echoCsv('');
                }
                $total = $isShopProduct ? Mage::helper('schrackcheckout')->formatPrice($_product, $schrackRowTotal[$index]) : number_format($schrackRowTotal[$index], 2);
                $this->echoCsv($_currency . ' ' . $total);
            }
            if (strtoupper(Mage::getStoreConfig('schrack/general/country')) == 'PL') {
                $this->echoCsv($isShopProduct ?'=HIPERŁĄCZE(""' . Mage::getUrl('sd/sd') . '?a=' . $_product->getSku() . '"")' : '');
            } else {
                $this->echoCsv($isShopProduct ?'=HYPERLINK(""' . Mage::getUrl('sd/sd') . '?a=' . $_product->getSku() . '"")' : '');
            }
            $this->echoCsvNL();
        }
        die();
    }

	public function createCsvDownloadCustomSKUs ( $onlyModified = false ) {
	    $fileName = 'custom_skus.csv';
	    $this->initCsvDownload($fileName);
        $customer = Mage::getSingleton('customer/session')->getCustomer();

        $this->echoCsv($this->__('Article Number'));
        $this->echoCsv($this->__('Product Name'));
        $this->echoCsv($this->__('Description'));
        $this->echoCsv($this->__('Catalog Product Link'));
        $this->echoCsv($this->__('Custom Article Number'));
        $this->echoCsvNL();

        $sql = " SELECT prod.sku, nam.value AS name, descr.value AS description, cust.custom_sku FROM catalog_product_entity prod"
             . " JOIN catalog_product_entity_varchar nam ON nam.entity_id = prod.entity_id AND nam.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'name' AND entity_type_id = 4)"
             . " JOIN catalog_product_entity_text descr ON descr.entity_id = prod.entity_id AND descr.attribute_id = (SELECT attribute_id FROM eav_attribute WHERE attribute_code = 'schrack_long_text_addition' AND entity_type_id = 4)"
             . ($onlyModified ? "" : " LEFT") // with left join we get all
             . " JOIN schrack_custom_sku cust ON cust.wws_customer_id = ? AND cust.sku = prod.sku"
             . " WHERE prod.sku > '9999999999' AND schrack_sts_statuslocal NOT IN ('unsaleable','strategic_no','gesperrt')"
             . " ORDER BY prod.sku";
        if ( $_SERVER['SERVER_NAME'] == 'www.schrack.at.local' ) {
            $sql .= " LIMIT 1000";
        }
        $dbRes = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($sql,$customer->getSchrackWwsCustomerId());

        foreach( $dbRes as $row ) {
            $sku = $row['sku'];
            $this->echoCsv($sku);
            $this->echoCsv($row['name']);
            $this->echoCsv($row['description']);
            if (strtoupper(Mage::getStoreConfig('schrack/general/country')) == 'PL') {
                $this->echoCsv('=HIPERŁĄCZE(""' . Mage::getUrl('sd/sd') . '?a=' . $sku . '"")');
            } else {
                $this->echoCsv('=HYPERLINK(""' . Mage::getUrl('sd/sd') . '?a=' . $sku . '"")');
            }
            $this->echoCsv($row['custom_sku']);
            $this->echoCsvNL();
        }
        die();
    }

    public function createCsvDownloadFromArray ( array $data, $fileName ) {
        $this->initCsvDownload($fileName);
        foreach ( $data as $row ) {
            foreach ( $row as $cell ) {
                $this->echoCsv($cell);
            }
            $this->echoCsvNL();
        }
        die();
    }

    private function getCurrentPartslistItems () {
        $partslist = Mage::helper('schrackwishlist/partslist')->getPartslist();
        $items = $partslist->getItemCollection()->addStoreFilter();
        $skus = array();
        $qtys = array();
        foreach ( $items as $item ) {
            $skus[] = $item->getProduct()->getSku();
            $qtys[] = $item->getQty();
        }
        $prices = Mage::helper('schrackcatalog/product')->getPriceProductInfo($skus,$qtys);
        foreach ( $items as $item ) {
            $sku = $item->getProduct()->getSku();
            $item->setSchrackBasicPrice($prices[$sku]['amount']);
        }
        return $items;
    }


	private $fieldCnt = 0;
    private $quotation = '"'; // TODO: NLS
    private $delimiter = ';'; // TODO: NLS

	private function echoCsv ( $s ) {
        if ( is_null($s) ) {
            $s = '';
        }
        $s = $this->quotation . $s . $this->quotation;
        if ( ++$this->fieldCnt > 1 ) {
            $s = $this->delimiter . $s;
        }
        echo $s;
    }

    private function echoCsvNL () {
        $this->fieldCnt = 0;
        echo PHP_EOL;
    }

    private function initCsvDownload ( $fileName ) {
        header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
        header("Content-disposition: attachment; filename=$fileName");
        header("Pragma: public");
        header("Expires: 0");
        echo "\xEF\xBB\xBF"; // UTF-8 BOM
    }
}
