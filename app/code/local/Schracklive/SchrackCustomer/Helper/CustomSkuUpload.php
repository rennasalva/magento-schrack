<?php

class Schracklive_SchrackCustomer_Helper_CustomSkuUpload extends Mage_Core_Helper_Abstract {

    /**
     *
     * @param string $inputName
     * @param string $subdirName
     * @param array $allowedExtensions
     * @return file name
     *
     */
    protected function _storeUploadedFile($inputName, $dirName, array $allowedExtensions) {
        $path = $dirName . DS;  //desitnation directory
        $fname = $_FILES[$inputName]['name']; //file name
        $uploader = new Varien_File_Uploader($inputName); //load class
        $uploader->setAllowedExtensions($allowedExtensions); //Allowed extension for file
        $uploader->setAllowCreateFolders(true); //for creating the directory if not exists
        $uploader->setAllowRenameFiles(true); //if true, uploaded file's name will be changed, if file with the same name already exists directory.
        $uploader->setFilesDispersion(false);
        $uploader->save($path, $fname); //save the file on the specified path
        return $path . $uploader->getUploadedFileName();
    }

    public function handleCsvUpload () {
        /** @var Schracklive_Schrack_Helper_Csv $csvHelper */
        $csvHelper = Mage::helper('schrack/csv');
        if ( isset($_FILES['csv']['name']) && $_FILES['csv']['name'] != '' ) {
            $fileName = $this->_storeUploadedFile('csv', sys_get_temp_dir(), ['csv']);
            $fp = fopen($fileName, "r");
            if ( !$fp ) {
                throw new Exception($this->__("Cannot read file") . " '$fileName'");
            }
            $skuMappingData = [];
            $first = true;
            $ndxSku = $ndxCustSku = false;
            while ( ($line = fgets($fp)) !== false ) {
                $line = rtrim($line);
                if ( $first ) {
                    while ( strlen($line) > 0 && (ord($line) < ord(' ') || ord($line) > ord('Z')) ) {
                        $line = substr($line, 1); // remove UTF-8 BOM and other possible non-printable stuff
                    }
                    $delim = $csvHelper->determineDelimiter($line);
                    $fields = explode($delim, $line);
                    for ( $i = 0; $i < count($fields); ++$i ) {
                        if ( $fields[$i] == $this->__('Article Number') ) {
                            $ndxSku = $i;
                        } else {
                            if ( $fields[$i] == $this->__('Custom Article Number') ) {
                                $ndxCustSku = $i;
                            }
                        }
                    }
                    if ( $ndxSku === false ) {
                        throw new Exception($this->__("Missing column") . " '" . $this->__('Article Number') . "'");
                    }
                    if ( !$ndxCustSku ) {
                        throw new Exception($this->__("Missing column") . " '" . $this->__('Custom Article Number') . "'");
                    }
                    $first = false;
                } else {
                    $fields = explode($delim, $line);
                    if ( isset($fields[$ndxCustSku]) && $fields[$ndxCustSku] > '' ) {
                        $skuMappingData[] = array($fields[$ndxCustSku], $fields[$ndxSku]);
                    }
                }
            }
            fclose($fp);

            $wwsId = Mage::getSingleton('customer/session')->getCustomer()->getSchrackWwsCustomerId();

            /** @var $connection Varien_Db_Adapter_Pdo_Mysql */
            $connection = Mage::getSingleton('core/resource')->getConnection('core_read');
            $connection->beginTransaction();
            $sql = "DELETE FROM schrack_custom_sku WHERE wws_customer_id = ?";
            $connection->query($sql, $wwsId);
            $sql = "INSERT INTO schrack_custom_sku (wws_customer_id, custom_sku, sku) VALUES (?,?,?)";
            foreach ( $skuMappingData as $skuMapping ) {
                $connection->query($sql, [$wwsId, $skuMapping[0], $skuMapping[1]]);
            }
            $connection->commit();
            /** @var Zend_Cache_Core $cache */
            $cache = Mage::app()->getCache();
            $cacheKey = "custom_skus_$wwsId";
            $cache->remove($cacheKey);
        } else {
            throw new Exception($this->__("No file transmitted!"));
        }
        return count($skuMappingData);
    }
}