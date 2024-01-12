<?php
ini_set('max_execution_time', '300');
ini_set('memory_limit', '512M');

class Schracklive_SchrackCatalog_DownloadController extends Mage_Core_Controller_Front_Action {

    private $_numberOfMediaItems = 0;

    public function getDownloadMediaDialogAction () {
        if (!$this->getRequest()->isAjax()) {
            die("wrong method");
        }
        $json = Mage::getModel('schrackcore/jsonresponse');
        $loggingEnabled = Mage::getStoreConfig('schrack/media_zip_download/enable_full_media_download_logging');

        $source = $this->getRequest()->getParam('source'); // e.g. 'cart.phtml'
        $fileTypeIgnoreList = array('onlinekatalog', 'thumbnails');
        $defaultCheckboxPreSelected = array( 'bedienungsanleitungen',
            'massbilder',
            'cadsymbol',
            'montageanleitungen',
            'caddrawing_expanded',
            'datenblaetter',
            'multiline',
            'energieeffizienzklasse',
            //'produktkataloge', removed, because too big!! And completely removed in template (popup)
            'garantie',
            'schaltplanzeichnungen',
            // 'katalogseiten', removed, because too big!! And completely removed in template (popup)
            'singleline',
            'knxdatenbank',
            'zeichnungen',
            'ldt',
            'zertifikate',
            'lvk',
            'barcode_labels',
            'onlinedatasheet',
            'knxapplikationsbeschreibung',
            '3d_daten'
        );

        $fileData = array();

        if ($source == 'cart.phtml') {
            $cart = Mage::getModel('checkout/cart')->getQuote();
            foreach ($cart->getAllItems() as $item) {
                $product = $item->getProduct();
                $attachments = $product->getAttachments();
                $sku = $product->getSku();

                foreach ($attachments as $key => $attachment) {
                    $filetype = $attachment->getData('filetype');
                    $mediaFilesize = $attachment->_getFileInfo();
                    $fileSize = isset($mediaFilesize['filesize']) ? (int)($mediaFilesize['filesize']) : 0;
                    $fileinfo = array($attachment->getData('url'), $attachment->getData('label'), $fileSize, $sku);
                    if (!in_array($filetype, $fileTypeIgnoreList)) {
                        $fileData[$filetype][] = $fileinfo;
                    }
                }

                // Collect all article numbers for printout as pdf:
                $fileData['barcode_labels'][] = $sku;
            }
            $resultTemplate = 'catalog/selectmediafilepopup.phtml';
        } elseif ($source == 'partslist.view.phtml' || strpos($source,'@detailview.html') !== false) {
            $productHelper = Mage::helper('schrackcatalog/product');
            $affectedItemsSKU = $this->getRequest()->getParam('affectedItems');

            if ( is_array($affectedItemsSKU) && !empty($affectedItemsSKU) ) {
                $sku2id = [];
                $id2sku = [];
                $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
                $sql = "SELECT sku, entity_id FROM catalog_product_entity WHERE sku IN ('" . implode("','",$affectedItemsSKU) . "')";
                $dbRes = $readConnection->fetchAll($sql);
                foreach ( $dbRes as $row ) {
                    $sku2id[$row['sku']] = $row['entity_id'];
                    $id2sku[$row['entity_id']] = $row['sku'];
                }

                $sku2attachments = [];
                $sql = "SELECT * FROM catalog_attachment WHERE entity_type_id = 4 AND entity_id IN (" . implode(",",$sku2id) . ") ORDER BY entity_id";
                $dbRes = $readConnection->fetchAll($sql);
                foreach ( $dbRes as $row ) {
                    $attachment = Mage::getModel('schrackcatalog/attachment');
                    $attachment->setData($row);
                    $id = $attachment->getEntityId();
                    $sku = $id2sku[$id];
                    if ( ! isset($sku2attachments[$sku]) ) {
                        $sku2attachments[$sku] = [];
                    }
                    $sku2attachments[$sku][] = $attachment;
                }
                foreach ( $affectedItemsSKU as $sku ) {
                    foreach ( $sku2attachments[$sku] as $attachment ) {
                        $filetype = $attachment->getData('filetype');
                        $mediaFilesize = $attachment->_getFileInfo();
                        $fileSize = isset($mediaFilesize['filesize']) ? (int)($mediaFilesize['filesize']) : 0;
                        $fileinfo = array($attachment->getData('url'), $attachment->getData('label'), $fileSize, $sku);
                        if ( !in_array($filetype, $fileTypeIgnoreList) ) {
                            $fileData[$filetype][] = $fileinfo;
                        }
                    }
                    $fileData['barcode_labels'][] = $sku;
                }
            }

            $resultTemplate = 'catalog/selectmediafilepopup.phtml';
        } else {
            $resultTemplate = 'page/html/error.phtml';
        }

        if (is_array($fileData) && !empty($fileData)) {
            $block = $this->getLayout()->createBlock('Schracklive_SchrackCatalog_Block_Selectmediafiletypepopup');
            $block->setMediaFileData($fileData);
            //var_dump($fileData);die();
            $block->setPreselectedFiletypes($defaultCheckboxPreSelected);
            $block->setIsRestricted(false);
            $this->loadLayout();

            $block->setTemplate($resultTemplate);
            $html = $block->toHtml();
            $json->setHtml($html);

            $json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
            $json->encodeAndDie();
        }
    }


    // Creates a result array from selecetd media types:
    public function getDownloadMediaZipAction() {
        $json = Mage::getModel('schrackcore/jsonresponse');
        $barcodeLabels = array();
        $onlineDatasheets = array();
        $resultMediaFilenames = array();

        if (!$this->getRequest()->isAjax()) {
            die('error: no AJAX call detected');
        }

        // Breaks operation, if download is not allowed in magento configuration:
        if (!Mage::getStoreConfig('schrack/media_zip_download/enable_download')) {
            die('error: variable not defined in store config');
        }

        $loggingEnabled = Mage::getStoreConfig('schrack/media_zip_download/enable_full_media_download_logging');

        $allMediaFileData       = json_decode(base64_decode($this->getRequest()->getParam('allMediaFileData')), true);
        $selectedLabelFormat    = intval($this->getRequest()->getParam('labelFormat'));
        $printLabelWithQuantity = $this->getRequest()->getParam('printLabelWithQuantity');
        $source                 = $this->getRequest()->getParam('source');
        $partslistID            = $this->getRequest()->getParam('partslistID');

        // Selected by user:
        $mediaFileSelection = json_decode($this->getRequest()->getParam('mediaFileSelection'));

        foreach ($mediaFileSelection as $key => $filterMediaFiletype) {
            $resultMediaFiletypes[$filterMediaFiletype] = $allMediaFileData[$filterMediaFiletype];
        }

        if ($loggingEnabled) {
            Mage::log('All MediaFiles (without applied filters):', null, 'full_media_downlaod_info.log');
            Mage::log($allMediaFileData, null, 'full_media_downlaod_info.log');
        }

        if ($loggingEnabled) {
            Mage::log('Found MediaFiles (with applied filters):', null, 'full_media_downlaod_info.log');
            Mage::log($resultMediaFiletypes, null, 'full_media_downlaod_info.log');
        }

        foreach ($resultMediaFiletypes as $mediaFiletype => $mediaFileInformation) {
            if ($mediaFiletype == 'barcode_labels') {
                $barcodeLabels = $mediaFileInformation;
            } elseif ($mediaFiletype == 'onlinedatasheet') {
                if ($loggingEnabled) {
                    Mage::log($mediaFileInformation, null, 'full_media_downlaod_info.log');
                }
                foreach ($mediaFileInformation as $index => $onlinedatasheetFileinformation) {
                    // $mediaFileInformation[$index][0] ---> URL-Parameters (as surrogate for filename)
                    // $mediaFileInformation[$index][3] ---> SKU
                    $onlineDatasheets[$onlinedatasheetFileinformation[3]] = $onlinedatasheetFileinformation[0];
                }
            } else {
                foreach ($mediaFileInformation as $key => $mediaFilename) {
                    // Exception for mediatype 'onlinedatasheet':
                    // $mediaFilename[0] = real filename with path
                    // $mediaFilename[1] = description of the mediafile (label)
                    // $mediaFilename[3] = SKU

                    $resultMediaFilenames[] = array(str_replace('https', 'http',Mage::getStoreConfig('schrack/general/imageserver')) . $mediaFilename[0], $mediaFilename[1], $mediaFilename[3]);
                }
            }
        }

        if ($loggingEnabled) {
            Mage::log('Found Onlinedatasheets:', null, 'full_media_downlaod_info.log');
            if (is_array($onlineDatasheets) && !empty($onlineDatasheets)) {
                Mage::log($onlineDatasheets, null, 'full_media_downlaod_info.log');
            } else {
                Mage::log('NO OnlineDatasheets found!', null, 'full_media_downlaod_info.log');
            }
        }

        if (is_array($barcodeLabels) && !empty($barcodeLabels) && $printLabelWithQuantity == 'yes') {
            if ($source == 'cart.phtml') {
                $cart = Mage::getModel('checkout/cart')->getQuote();

                foreach ($cart->getAllItems() as $item) {
                    $skuFromQoute = $item->getProduct()->getSku();
                    $quantitySkuPairs[$skuFromQoute] = (int) $item->getQty();
                }
            } else if ($source == 'partslist.view.phtml') {
                $partslistID = $this->getRequest()->getParam('partslistID');
                $customer = Mage::getSingleton('customer/session')->getCustomer();
                $partslist = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId($customer, $partslistID);
                $collection = $partslist->getItemCollection();
                foreach ($collection as $item) {
                    // Partslist can have zero amount of products -> the remove product from printing list:
                    if ($item->getQty() > 0) {
                        $skuFromQoute = $item->getProduct()->getSku();
                        $quantitySkuPairs[$skuFromQoute] = (int) $item->getQty();
                    }
                }
            } else  if ( ($p = strpos($source,'@detailview.html')) !== false ) {
                $documentID = $this->getRequest()->getParam('documentID');
                $documentType = substr($source,0,$p);
                $orderHelper = Mage::helper('schracksales/order');
                $document = $orderHelper->getFullDocument($documentID,$documentType);
                foreach ( $document->getAllItems() as $item ) {
                    if ( $item->getQty() > 0 ) {
                        $skuFromQoute = $item->getSku();
                        $quantitySkuPairs[$skuFromQoute] = (int) $item->getQty();
                    }
                }
            }

            foreach ($barcodeLabels as $index => $sku) {
                if (isset($quantitySkuPairs[$sku])) {
                    $tempBarcodeLabels[] = $sku . '|' . $quantitySkuPairs[$sku];
                }
            }
            // Delete all data in old array:
            $barcodeLabels = array();
            // Assign new concatenated data to old array:
            $barcodeLabels = $tempBarcodeLabels;
        }

        $randomFilename = 'schrack_' . date('Y-m-d_H:i:s') . '_' . rand(100, 999) . '.zip';
        $randomFilename = str_replace(array(':', '-'), '_', $randomFilename);
        $randomPathFilename = Mage::getStoreConfig('schrack/media_zip_download/define_downloadpath') . $randomFilename;
        $mediaZipResult = $this->createMediaZip($resultMediaFilenames, $randomPathFilename, false, $barcodeLabels, $selectedLabelFormat, $onlineDatasheets);
        if ($mediaZipResult === true ) {
            echo $randomFilename;
        } else {
            die("error: media zip download file could not be created");
        }
    }

    // Sends file to browser:
    public function startDownloadMediaZipAction() {
        $loggingEnabled = Mage::getStoreConfig('schrack/media_zip_download/enable_full_media_download_logging');
        $zipFilename = $this->getRequest()->getParam('filename');
        $zipFilename = str_replace(array(':', '-'), '_', $zipFilename);
        if ($loggingEnabled) {
            Mage::log('zipFilename = ' . $zipFilename, null, 'full_media_downlaod_info.log');
        }
        $zipFilepath = Mage::getStoreConfig('schrack/media_zip_download/define_downloadpath') . $zipFilename;
        if ($loggingEnabled) {
            Mage::log('zipFilepath = ' . $zipFilepath, null, 'full_media_downlaod_info.log');
        }
        $mediaFilename = basename($zipFilepath);
        if ($loggingEnabled) {
            Mage::log('mediaFilename = ' . $mediaFilename, null, 'full_media_downlaod_info.log');
        }

        header('Content-Description: File Transfer');
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=\"$mediaFilename\"");
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header("Content-Length: " . filesize($zipFilepath));

        if ($loggingEnabled) {
            Mage::log('Media Zip Filesize = ' . filesize($zipFilepath) . ' (Bytes)', null, 'full_media_downlaod_info.log');
        }

        $chunkSize = 1024 * 1024;
        ob_end_clean();
        $handle = fopen($zipFilepath, 'rb');
        $bufferCount = 0;
        while (!feof($handle))
        {
            $buffer = fread($handle, $chunkSize);
            $bufferCount += strlen($buffer);
            echo $buffer;
            ob_flush();
            flush();
        }
        fclose($handle);
        if ($loggingEnabled) {
            Mage::log('Media Zip Chunk Count (' . $zipFilepath . ') --> Chunks Total = ' . $bufferCount . ' (Bytes)', null, 'full_media_downlaod_info.log');
        }
        exit;
    }

    // Creates a compressed zip file:
    protected function createMediaZip($fileRecords = array(), $destination = '', $overwrite = false, $barcodeLabels = array(), $selectedLabelFormat = 3420, $onlineDatasheets = array()) {
        if ( $selectedLabelFormat == 9999 ) {
            $selectedLabelFormat = 'schrack';
        }
        $loggingEnabled = Mage::getStoreConfig('schrack/media_zip_download/enable_full_media_download_logging');

        // Checks, if the zip file already exists and overwrite is false, return false
        if (file_exists($destination) && !$overwrite) {
            return false;
        }
        // Vars:
        $valid_files = array();
        // If files were passed in:
        if (is_array($fileRecords)) {
            // Cycle through each file:
            foreach($fileRecords as $fileData) {
                $downloadAvailable = 'NOT available';

                // Make sure the file exists:
                if((bool)preg_match('~HTTP/1\.\d\s+200\s+OK~', @current(get_headers($fileData[0])))) {
                    $valid_files[] = $fileData;
                    $downloadAvailable = 'available';
                }

                if ($loggingEnabled) {
                    Mage::log($fileData[0] . '  -> Status = ' . $downloadAvailable, null, 'full_media_downlaod_info.log');
                }
            }
        }

        // If we have good files:
        if(count($valid_files || (is_array($barcodeLabels) && !empty($barcodeLabels) || (is_array($onlineDatasheets) && !empty($onlineDatasheets))))) {
            // Create the archives:
            $zip = new ZipArchive();

            if ($overwrite == true) {
                $flags = ZIPARCHIVE::OVERWRITE;
            } else {
                $flags = ZIPARCHIVE::CREATE;
            }

            $resultOfInitZipFile = $zip->open($destination, $flags);
            if ($loggingEnabled) {
                Mage::log('Destination of Zip-File : ' . $destination, null, 'full_media_downlaod_info.log');
                Mage::log('Creation Status of Zip-File : ' . $resultOfInitZipFile, null, 'full_media_downlaod_info.log');
            }

            if($resultOfInitZipFile !== true) {
                Mage::log('Zip-File could not be created! Error-Number = ' . $resultOfInitZipFile, null, 'full_media_downlaod_info.log');
                return false;
            } else {
                if ($loggingEnabled) {
                    Mage::log('Zip-File successfully created in path : ' . $destination, null, 'full_media_downlaod_info.log');
                }
            }

            // Add the files:
            $counter = 0;
            if(count($valid_files)) {
                foreach($valid_files as $file) {
                    if (stristr($file[0], '.knxprod')) {
                        $tempExtensionConstruct = substr($file[0], -9);
                    } else {
                        $tempExtensionConstruct = substr($file[0], -5);
                    }
                    $fileExtension = explode('.', $tempExtensionConstruct);
                    $localnamePrefix = iconv('utf-8', 'CP852//IGNORE', str_replace(array(':', '/', '_'), array('', '-', '-'), $file[2] . ' ' . $file[1]));
                    $localnameExtension =  '-' . $counter . '.' . $fileExtension[1];
                    if ($localnamePrefix == '') $localnamePrefix = $this->__('Technical Datasheet');

                    $localname = $localnamePrefix . $localnameExtension;
                    $this->_numberOfMediaItems += 1;
                    $zip->addFromString($localname, file_get_contents($file[0]));
                    $counter++;
                }
            }

            if (is_array($barcodeLabels) && !empty($barcodeLabels)) {

                $businessUnit = Mage::getStoreConfig('general/country/default');
                if ($businessUnit == 'DK') $businessUnit = 'COM';
                $labelServiceURL = Mage::getStoreConfig('schrack/media_zip_download/define_labelservicepath');  // 'http://sl-ps1.schrack.lan:8199/mq/labels?format=';

                $skuCommaSeparatedList = "";
                foreach ($barcodeLabels as $key => $sku) {
                    $skuCommaSeparatedList .= $sku . ';';
                }
                $skuCommaSeparatedList = substr($skuCommaSeparatedList, 0, -1);
                $this->_numberOfMediaItems += 1;
                $zip->addFromString(iconv('UTF-8', 'CP852//IGNORE' , $this->__('barcode_labels')) . '.pdf', file_get_contents($labelServiceURL . $selectedLabelFormat . '&businessunit=' . $businessUnit . '&articlenrs=' . $skuCommaSeparatedList));
                if ($loggingEnabled) {
                    Mage::log(date('Y-m-d H:i:s') . ' ' . $labelServiceURL . $selectedLabelFormat . '&businessunit=' . $businessUnit . '&articlenrs=' . $skuCommaSeparatedList, null, 'label_downloads.log');
                }
            }

            if (is_array($onlineDatasheets) && !empty($onlineDatasheets)) {
                $onlineDatasheetServiceURL = Mage::getStoreConfig('schrack/media_zip_download/define_onlinedatasheet_downloadpath');  // '//sl-ps1.schrack.lan:8199/mq/';

                foreach ($onlineDatasheets as $sku => $pathParameters) {
                    $this->_numberOfMediaItems += 1;
                    $zip->addFromString( iconv('UTF-8', 'CP852//IGNORE' , $sku . ' ' . $this->__('onlinedatasheet'))  .  '.pdf', file_get_contents('http:' . $onlineDatasheetServiceURL . $pathParameters));
                    if ($loggingEnabled) {
                        Mage::log(date('Y-m-d H:i:s') . ' ' . 'http:' . $onlineDatasheetServiceURL . $pathParameters, null, 'label_downloads.log');
                    }
                }
            }

            // DEBUG:
            // echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

            // Close the zip -- done!
            $zip->close();
            if ($loggingEnabled) {
                Mage::log(date('Y-m-d H:i:s') . ' ' . $destination . '  Filesize: ' . filesize($destination) . ' (Bytes)  Media-Items: ' . $this->_numberOfMediaItems, null, 'full_media_downlaod_info.log');
            }

            // Check to make sure the file exists:
            return file_exists($destination);
        }
        else
        {
            return false;
        }
    }

    public function downloadPdfAction() {
        $url_PDF_static = Mage::getStoreConfig('schrack/media_zip_download/define_onlinedatasheet_downloadpath');
        $country = Mage::getStoreConfig('general/country/default');
        $datasheet = 'typo/pdf?leadsfor=' . $this->getRequest()->getParam("customer");

        $url_PDF_download = 'http:' . $url_PDF_static . $datasheet . '&shop=' . $country;

        ob_end_flush();

        header('Content-type: application/pdf');
        header("Content-Disposition: attachment; filename=\"myAdvisor.pdf\"");

        echo file_get_contents($url_PDF_download);
        flush();
    }

    public function deliverOnlineDataSheetAction () {
        $loggingEnabled = Mage::getStoreConfig('schrack/media_zip_download/enable_full_media_download_logging');

        $businessUnit = Mage::getStoreConfig('general/country/default');
        if ($businessUnit == 'DK') $businessUnit = 'COM';

        // Bots should be excluded from Download:
        if ($this->getRequest()->getParam('realUser') == 'yes') {
            $onlineDatasheetPath  = 'http:' . Mage::getStoreConfig('schrack/media_zip_download/define_onlinedatasheet_downloadpath') . 'datasheet?';
            $onlineDatasheetPath .= 'articlenr=' . $this->getRequest()->getParam('articlenr');
            $onlineDatasheetPath .= '&businessunit=' . $businessUnit;
            ob_end_flush();
            Mage::log(date('Y-m-d H:i:s') . ' - ' . $onlineDatasheetPath, null, 'onlinedatasheet_paths.log');

            if ( strstr($_SERVER['HTTP_USER_AGENT'],'iPhone') || strstr($_SERVER['HTTP_USER_AGENT'],'iPad') ) {
                header('Content-type: application/pdf');
            } else {
                // It's just for bloody Chrome which doesn't work yet with application/pdf...
                header('Content-type: application/octet-stream');
            }

            header('Content-disposition: inline; filename="' . $this->__('Datasheet') . '-' . $this->getRequest()->getParam('articlenr') . '.pdf"');

            echo file_get_contents($onlineDatasheetPath);
            flush();
        }
    }
}

?>