<?php

class Schracklive_Schrack_CsvController extends Mage_Core_Controller_Front_Action
{

    public function csvFromSkusAction () {
        $skus = explode(';',base64_decode($this->getRequest()->getParam('l')));
        $fileName = base64_decode($this->getRequest()->getParam('f'));
        $qtys = $this->getRequest()->getParam('q');
        if ( $qtys ) {
            $qtys = explode(';',base64_decode($qtys));
        }
        Mage::helper('schrack/csv')->createCsvDownloadFromSkus($skus,$fileName,$qtys);
    }

    public function csvFromSkusNewAction () {
        $params = $this->getRequest()->getParams();
        $fileName = 'articles.csv';
        if ( isset($params['filename']) ) {
            $fileName = $params['filename'];
        }
        $skus = array();
        $qtys = array();
        if ( isset($params['products']) ) {
            $products = explode(';', $params['products']);
            foreach ( $products as $productData ) {
                list($sku, $qty) = explode(':', $productData);
                $skus[] = $sku;
                $qtys[] = $qty;
            }
        }
        Mage::helper('schrack/csv')->createCsvDownloadFromSkus($skus,$fileName,$qtys);
    }


    public function csvFromDocumentAction () {
        $documentId = $this->getRequest()->getParam('documentId');
        $documentType = $this->getRequest()->getParam('type');
        Mage::helper('schrack/csv')->createCsvDownloadByDocument($documentId, $documentType);
    }

    public function getArticlesFromCsvPartlisUploadAction () {
        if (!$this->getRequest()->isPost() || !$this->getRequest()->isAjax()) {
            die('No AJAX');
        }

        $uuid = $this->getRequest()->getParam('uuid');

        if ($uuid) {
            $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');
            $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');

            $query  = "SELECT data FROM customer_tracking";
            $query .= " WHERE uuid LIKE '" . $uuid . "'";

            $serializedArticlesFromCsvUpload = $readConnection->fetchOne($query);

            if ($serializedArticlesFromCsvUpload) {
                $deleteQuery = "DELETE FROM customer_tracking WHERE uuid LIKE '" . $uuid . "'";
                $writeConnection->query($deleteQuery);
            }
        }

        echo json_encode(array('result' => unserialize($serializedArticlesFromCsvUpload)));
        die;
    }
}
