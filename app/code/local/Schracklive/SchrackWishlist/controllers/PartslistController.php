<?php

/**
 * needs to be manually required...
 */
require_once('app/code/core/Mage/Wishlist/controllers/IndexController.php');

/**
 * IndexController
 *
 * @author c.friedl
 */
class Schracklive_SchrackWishlist_PartslistController extends Schracklive_SchrackWishlist_Controller_Partslist_Abstract {
    
    public function preDispatch()
    {
        parent::preDispatch();

        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
            if(!Mage::getSingleton('customer/session')->getBeforeWishlistUrl()) {
                Mage::getSingleton('customer/session')->setBeforeWishlistUrl($this->_getRefererUrl());
            }
        }
        if (!Mage::getStoreConfigFlag('wishlist/general/active')) {
            $this->norouteAction();
            return;
        }
    }
    
    /**
     * add many products at once, separated by ';'; currently only outputs json
     */
    public function batchRemoveAction() {
        if ($this->getRequest()->isAjax()) {
            $forceToSession = true;
            $successCount = 0;
            $jsonResponse = array();
            $params = $this->getRequest()->getParams();
            $selectedPartslistId = $this->getRequest()->getParam('id');

            $partslist = $this->_getPartslist($selectedPartslistId);
            if (isset($params['products'])) {
                $products = explode(';', $params['products']);
                foreach ($products as $product) {                  
                    list($sku, $dummy) = explode(':', $product);
                    $this->_removeProductFromPartslistBySku($sku, $partslist, $jsonResponse, true, $forceToSession); // we need force-to-session for page reloads here because we have individual message if there is only one product to add
                     ++$successCount;
                }
                
                if ($successCount > 1) {
                    $this->removeSuccessMessages($jsonResponse);
                    Mage::getSingleton('core/session')->getMessages(true);
                    $this->addSuccess($this->__('%d products were removed from your partslist.', $successCount), $jsonResponse, $forceToSession);
                }
            }
            $jsonResponse['ok'] = true;
            $this->_redirectRefererNoAjax(Mage::helper('core')->jsonEncode($jsonResponse));
        } else
            throw new Exception('invalid action for non-ajax request');
    }
    public function batchAddAction() {
        if ($this->getRequest()->isAjax()) {
            $forceToSession = false;
            $successCount = 0;
            $jsonResponse = array();
            $params = $this->getRequest()->getParams();
            $selectedPartslistId = $this->getRequest()->getParam('id');

            if (!strlen(Mage::app()->getRequest()->getParam('id'))) {
                $partslist = $this->_createPartslist();
                $jsonResponse['isNew'] = true;
                $name    = Mage::app()->getRequest()->getParam('name');
                $comment = $this->getRequest()->getParam('comment');
                $name    = preg_replace('/["\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $name);
                $comment = preg_replace('/["\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $comment);

                if (strlen($name)) {
                    $partslist->setDescription($name);
                }
                if (strlen($comment)) {
                    $partslist->setComment($comment);
                }

                $jsonResponse['listId'] = $partslist->getId();
            } else {
                $partslist = $this->_getPartslist($selectedPartslistId);
            }
            if (isset($params['products'])) {
                $products = explode(';', $params['products']);
                foreach ($products as $product) {
                    list($sku, $qty) = explode(':', $product);
                    $resultMessage = $this->_addProductToPartslistBySku($sku, $qty, $partslist, $jsonResponse, true, $forceToSession); // we need force-to-session for page reloads here because we have individual message if there is only one product to add
                    if ($resultMessage && isset($resultMessage['error'])) {
                        $this->addError($resultMessage['error'], $jsonResponse);
                    } else {
                        ++$successCount;
                    }
                }
                
                if ($successCount > 1) {
                    $this->removeSuccessMessages($jsonResponse);
                    Mage::getSingleton('core/session')->getMessages(true);
                    $this->addSuccess($this->__('%d products were added to your partslist.', $successCount), $jsonResponse, $forceToSession);
                }
            }
            $partslist->activate();
            $partslist->save();
            $jsonResponse['ok'] = true;
            $this->_redirectRefererNoAjax(Mage::helper('core')->jsonEncode($jsonResponse));
        } else
            throw new Exception('invalid action for non-ajax request');
    }
    
    /**
     * add many documents at once, separated by ';'; currently only outputs json
     */
    public function batchAddDocumentsAction() {
        if ($this->getRequest()->isAjax()) {
            $forceToSession = false;
            $jsonResponse = array();
            $successCount = 0;
            $selectedPartslistId = $this->getRequest()->getParam('id');

            try {
                if (!strlen(Mage::app()->getRequest()->getParam('id'))) {
                    $partslist = $this->_createPartslist();
                    $jsonResponse['isNew'] = true;                    
                    $name = Mage::app()->getRequest()->getParam('name');
					$comment = $this->getRequest()->getParam('comment');
                    if (strlen($name)) {
                        $partslist->setDescription($name);
                        $partslist->save();
                    }
					// Added by Nagarro to save comment from listing page
					if (strlen($comment)) {
                    $partslist->setComment($comment);
					}
				    $forceToSession = true;
                    $jsonResponse['listId'] = $partslist->getId();
                } else {
                    $partslist = $this->_getPartslist($selectedPartslistId);
                }
                $paramDocuments = $this->getRequest()->getParam('documents');
                //Mage::log('paramDocuments = ' . $paramDocuments, null, 'partslist_batch_add_documents_action.log');
                if (isset($paramDocuments)) {
                    $documents = explode(';', $paramDocuments);
                    foreach ($documents as $document) {
                        list ($docId, $type) = explode(':', $document);
                        if ($type == 'offer') $type = 'order';
                        //Mage::log('type = ' . $type. ' -- docId = ' . $docId . ' -- selectedPartslistId = ' . $selectedPartslistId, null, 'partslist_batch_add_documents_action.log');
                        $this->_addDocument($type, $docId, $partslist, $jsonResponse); // we do not need force-to-session here since we don't ever have individual messages for documents
                        ++$successCount;
                    }
                }
                if ($successCount > 0) {
                   $this->removeSuccessMessages($jsonResponse);
                    if ($successCount > 1)
                        $this->addSuccess($this->__('%d documents were added to your partslist.', $successCount), $jsonResponse, $forceToSession);
                    else
                        $this->addSuccess($this->__('1 document was added to your partslist.'), $jsonResponse, $forceToSession);
                }
                $jsonResponse['ok'] = true;
            } catch(Exception $e) {
                $this->addError($e->getMessage(), $jsonResponse, $forceToSession);
            }
            $this->_redirectRefererNoAjax(Mage::helper('core')->jsonEncode($jsonResponse));
        } else
            throw new Exception('invalid action for non-ajax request');
    }


    public function getProductslistAsSkulistByDocumentAction () {
        if ($this->_validateFormKey()) {
            $type       = $this->getRequest()->getParam('type');
            $documentId = $this->getRequest()->getParam('documentId');

            if ($type && $documentId) {
                try {
                    $helper = Mage::helper('schracksales/order');
                    if ($type == 'offer') {
                        $type = 'order';
                    }
                    $document = $helper->getFullDocument($documentId,$type);
                    //Mage::log($document, null, 'products_list_as_sku_list.log');
                    if ($document) {
                        $items = $document->getItemsCollection();
                    } else {
                        echo json_encode(array('error' => 'No such dcument with ID = ' . $documentId));
                        die();
                    }
                    $listSku = array();
                    if (is_array($items) && !empty($items)) {
                        foreach ($items as $item) {
                            try {
                                $sku = $item->getSku();
                                if (!in_array($sku, array('TRANSPORT-','MANIPULAT-','VERPACKUNG'))) {
                                    $listSku[] = $sku;
                                }
                            } catch (Exception $e) {
                                echo json_encode(array('error' => 'An error occurred while fetching a product-sku from a document: ' .  $e->getMessage()));
                                die();
                            }
                        }
                    } else {
                        echo json_encode(array('error' => 'No Items found for dcoument-id (#1) (PartslistController) : ' .  $documentId));
                        die();
                    }
                    if (is_array($listSku) && !empty($listSku)) {
                        echo json_encode($listSku);
                    } else {
                        echo json_encode(array('error' => 'No Items found for dcoument-id (#2) (PartslistController) : ' .  $documentId));
                        die();
                    }
                } catch (Exception $e) {
                    echo json_encode(array('error' => 'An error occurred while adding a document to a partslist: ' .  $e->getMessage()));
                    die();
                }
            } else {
                echo json_encode(array('error' => 'No Document-Id Or Type Given In Request'));
            }
        } else {
            echo json_encode(array('error' => 'invalid form_key'));
            die();
        }
    }


    /**
     * add multiple products from a csv file whose 1st column contains the sku, 
     * and the 2nd column the qty
     * 
     * @throws Exception
     */
    public function addCsvAction() {
        $failCount = 0;
        if ($this->getRequest()->getParam('id')) {
            $partslist = Mage::getModel('schrackwishlist/partslist')
                ->loadByCustomerAndId($this->_getSession()->getCustomer(), Mage::app()->getRequest()->getParam('id'));
        } else {
            $partslist = Mage::helper('schrackwishlist/partslist')->getActiveOrFirstPartslist();
        }
        $jsonResponse = array();
        $successCount = 0;
        if(isset($_FILES['csv']['name']) && $_FILES['csv']['name'] != '') {
            $tmpDir = sys_get_temp_dir();
            $fileName = $this->_storeUploadedFile('csv', $tmpDir, array('csv', 'txt'));
            $lines = file($fileName);
            unlink($fileName);
            $lines = Mage::helper('schrack/csv')->removeEmptyCsvLines($lines);

            if (count($lines) > Mage::getStoreConfig('sales/maximum_order/amount')) {
                // Too many items in cart: exceeded predefined limit! :
                $warningMessageText = $this->__('Too Many Items In Your File');
                Mage::getSingleton('core/session')->addError($warningMessageText);
            } else if (count($lines) > 0) {
                $delim        = Mage::helper('schrackcore/csv')->determineDelimiter($lines[0]);
                $successCount = 0;
                $skuContainer = array();
                $partlistCsvUploadHash = '';

                if ($this->getRequest()->getParam('partlist_csv_upload_hash')) {
                    $partlistCsvUploadHash = $this->getRequest()->getParam('partlist_csv_upload_hash');
                }
                foreach ($lines as $line) {
                    try {
                        if ( $this->_csvLineContainsData($line, $delim) ) {
                            if ( $delim && strchr($line, $delim) ) {
                                list($artNo, $qty) = str_getcsv($line, $delim);
                            } else {
                                $artNo = trim($line);
                                $qty = 0;
                            }

                            if (intval($qty) == 0) $qty = 1;

                            $product = Mage::getModel('schrackcatalog/product')->loadBySku($artNo);
                            if ( !($product && $product->getId()) ) {
                                $product = Mage::getModel('schrackcatalog/product')->loadByAttribute('schrack_ean', $artNo);
                            }
                            if ( !($product && $product->getId()) ) {
                                throw new Schracklive_SchrackCatalog_Model_NoSuchProductException($artNo);
                            }
                            $sku = $product->getSku();

                            $skuContainer[] = $sku;

                            if ( $product->isBestellartikel() ) {
                                $resultQtyData = $product->calculateClosestHigherQuantityAndDifference(intval($qty), true, array(), 'addCartQuantity12');
                                if ( is_array($resultQtyData) && !empty($resultQtyData) ) {
                                    $productMinQtyFromSupplier = $resultQtyData['minQtyFromSupplier'];
                                    $batchSizeFromSupplier = $resultQtyData['batchSizeFromSupplier'];
                                    $totalStockQuantity = $resultQtyData['totalStockQuantity'];
                                    $availableStockQuantity = $resultQtyData['availableStockQuantity'];
                                    $selectedQuantity = intval($qty);
                                    $closestHigherQuantity = $resultQtyData['closestHigherQuantity'];
                                    $differenceQuantity = $resultQtyData['differenceQuantity'];
                                    $showBothLimitMessage = $resultQtyData['showBothLimitMessage'];
                                    $previouslyExistingQuantity = intval($resultQtyData['previouslyExistingQuantity']);

                                    // Check, if there is a difference quantity. If not, than everything is okay, and bestellartikel has correct quantity:
                                    if ( $differenceQuantity == 0 && $showBothLimitMessage == false ) {
                                        $overrideIntensiveCheckForBestellArtikel = true;
                                    }
                                    if ( $showBothLimitMessage == true ) {
                                        $calculatedMinimumQuantity = $closestHigherQuantity;
                                    }
                                }

                                if ( $product->getCumulatedPickupableAndDeliverableQuantities() <= 0 ) {
                                    if ( $qty < $productMinQtyFromSupplier ) {
                                        $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s has been adjusted to the minimum quantity of %2$d.'), $product->getSku(), $productMinQtyFromSupplier);
                                    } else {
                                        $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $batchSizeFromSupplier);
                                    }
                                } else {
                                    if ( $previouslyExistingQuantity >= $totalStockQuantity ) {
                                        $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $batchSizeFromSupplier);
                                    } else {
                                        if ( ($previouslyExistingQuantity + $selectedQuantity) > $totalStockQuantity && ($previouslyExistingQuantity + $selectedQuantity) < $productMinQtyFromSupplier ) {
                                            $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s can be adjusted to stock quantity of %2$d or next package unit of %3$d.'), $sku, ($availableStockQuantity + $previouslyExistingQuantity), ($closestHigherQuantity + $previouslyExistingQuantity));
                                        } else {
                                            $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $batchSizeFromSupplier);
                                        }
                                    }
                                }

                                if ( $warningMessageText ) {
                                    // Mage::getSingleton('core/session')->addNotice($warningMessageText);
                                    // Mage::getSingleton('core/session')->addNotice($this->__('%s was added to your shopping cart.', Mage::helper('core')->escapeHtml($product->getName())));
                                }

                                // Finally set correctly calculated quantity:
                                $qty = $closestHigherQuantity;
                            } else {
                                $resultQtyData = $product->calculateClosestHigherQuantityAndDifference($qty, true, array(), 'addCartQuantity3');
                                if ( $resultQtyData['invalidQuantity'] == true ) {
                                    $qty = $resultQtyData['closestHigherQuantity'];
                                    $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $sku, '', $qty);
                                    Mage::getSingleton('core/session')->addNotice($warningMessageText);
                                }
                                $qty = Mage::helper('schrackcheckout/cart')->suggestQtyForDrums($product, $qty);
                            }

                            $dummyJr = array();
                            $resultMessage = $this->_addProductToPartslistBySku($sku, $qty, $partslist, $jsonResponse);
                            if ($resultMessage && isset($resultMessage['error'])) {
                                $this->addError($resultMessage['error'], $jsonResponse);
                            } else {
                                $successCount++;
                            }
                        }
                    } catch ( Schracklive_SchrackCatalog_Model_NoSuchProductException $nspEx ) {
                        Mage::getSingleton('core/session')->addError($this->__($nspEx->getMessageFormat(),$nspEx->getSku()));
                        $failCount++;
                    } catch ( Exception $e ) {
                        Mage::getSingleton('core/session')->addError($this->__('Could not read CSV line %s', $line));
                        $failCount++;
                    }
                }
                if ( $successCount > 0 ) {
                    if ($partlistCsvUploadHash && is_array($skuContainer) && !empty($skuContainer)) {
                        $serializedSkuContainer = serialize($skuContainer);

                        $writeConnection = Mage::getSingleton('core/resource')->getConnection('core_write');

                        $query  = "INSERT INTO customer_tracking";
                        $query .= " SET uuid = '" . $partlistCsvUploadHash . "',";
                        $query .= " data = '" . $serializedSkuContainer . "',";
                        $query .= " created = '" . date('Y-m-d H:i:s') . "'";

                        $writeConnection->query($query);
                    }
                    $partslist->save();
                    $this->addSuccess($this->__('Partslist saved'), $jsonResponse, true);
                }
            } else
                Mage::getSingleton('core/session')->addError($this->__('CSV File was empty.'));
        } else
            Mage::getSingleton('core/session')->addError($this->__('No upload file found.'));

        if ($successCount > 0) {
            if ($successCount > 1)
                $message = $this->__('%d products were added to your partslist.', $successCount);
            else
                $message = $this->__('1 product was added to your partslist.', $successCount);
            Mage::getSingleton('core/session')->addSuccess($message);

        }
        $this->_redirect('wishlist/partslist/view', array('id' => Mage::app()->getRequest()->getParam('id')));
    }

    public function downloadCsvAction() {
        Mage::helper('schrack/csv')->createCsvDownloadFromCurrentPartslist();
        /*
        $this->loadLayout();
        $layout = $this->getLayout();
        $block = $layout->getBlock('wishlist.partslist.downloadcsv');
        $html = $block->renderView();
        header('Content-Encoding: UTF-8');
        header('Content-type: text/csv; charset=UTF-8');
        header("Content-disposition: attachment; filename=partslist.csv");
        header("Pragma: public");
        header('Pragma: no-cache');
        header("Expires: 0");
        echo "\xEF\xBB\xBF"; // UTF-8 BOM

        die($html);
        */
    }

    public function indexAction() {
         $this->loadLayout();

         $this->renderLayout();
    }

    public function viewAction() {
         $this->loadLayout();
         $id = $this->getRequest()->getParam('id');
         if (isset($id)) {
            $model = Mage::getModel('schrackwishlist/partslist')->loadByCustomerAndId($this->_getSession()->getCustomer(), $id);
            if (!$model->getIsActive()) {
                $model->activate();
                $model->save();
            }
         }
         $this->renderLayout();
    }


    /**
     * create a new partslist
     */
    public function createAction() {
        $jsonResponse = array();
        $this->loadLayout();

		if ($this->getRequest()->isPost() || $this->getRequest()->isAjax()) {
            $model = Mage::getModel('schrackwishlist/partslist');
			try {
                $model->create($this->_getSession()->getCustomer()->getId(), $this->getRequest()->getParam('description'), $this->getRequest()->getParam('comment'));
                $jsonResponse['isNew'] = true;
				$this->addSuccess('Your partslist has been created.', $jsonResponse);
                $jsonResponse['listId'] = $model->getId();
                return $this->_redirectNoAjax(Mage::getUrl('*/*/view', array('id' => $model->getId())), Mage::helper('core')->jsonEncode($jsonResponse));
			} catch (Exception $e) {
				Mage::logException($e);
				$this->addError($e->getMessage(), $jsonResponse);
                return $this->_redirectNoAjax('wishlist/partslist/create', Mage::helper('core')->jsonEncode($jsonResponse));
			}
		}
        $this->renderLayout();
    }

    /**
     * list all wishlists
     */
    public function listAction() {
        $this->loadLayout();

        $headBlock = $this->getLayout()->getBlock('head');
        if ($headBlock) {
            $headBlock->setTitle($this->__('My Partslists'));
        }

        $this->renderLayout();
    }

    public function editAction() {
        $this->loadLayout();
        $selectedPartslistId = $this->getRequest()->getParam('id');

        if ($this->getRequest()->isAjax()) {
            $jsonResponse = array();
            try {
                $partslist = $this->_getPartslist($selectedPartslistId);
                // this belongs in the model, but then the escalation of class derivation gets hellish...
                $description = $this->getRequest()->getParam('description');
                $partslist->setDescription($description);
                $comment = $this->getRequest()->getParam('comment');
                if (isset($comment))
                    $partslist->setComment($comment);
                $jsonResponse['ok'] = true;
                $jsonResponse['listId'] = $partslist->getId();
                $partslist->save();
                $this->addSuccess($this->__('Partslist saved'), $jsonResponse, true);
            } catch (Exception $e) {
                Mage::logException($e);
                $this->addError($this->__($e->getMessage()), $jsonResponse, true);
            }
            return $this->_redirectNoAjax(Mage::getUrl('wishlist/partslist/edit', array('id' => $partslist->getId())), Mage::helper('core')->jsonEncode($jsonResponse));
        } else if ($this->getRequest()->isPost()) {
            if (!$this->_validateFormKey()) {
                return $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
            try {
                $partslist = $this->_getPartslist($selectedPartslistId);
                // this belongs in the model, but then the escalation of class derivation gets hellish...
                $description = $this->getRequest()->getParam('description');
                $partslist->setDescription($description);
                $comment = $this->getRequest()->getParam('comment');
                if (isset($comment))
                    $partslist->setComment($comment);
                $partslist->save();
                return $this->_redirect('*/*/view', array('id' => $this->getRequest()->getParam('id')));
            } catch (Exception $e) {
                Mage::logException($e);
                Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
                return $this->_redirect('wishlist/partslist/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        } else {
            $this->_setTitleFromPartslist($this->_getPartslist($selectedPartslistId));
        }

        $this->renderLayout();
    }

    /**
     * delete the whole partslist
     */
    public function deleteAction() {
        $selectedPartslistId = $this->getRequest()->getParam('id');

        try {
            $to = Mage::app()->getRequest()->getParam('forward');
            $partslist = $this->_getPartslist($selectedPartslistId);
            $partslist->delete();
            Mage::getSingleton('core/session')->addSuccess($this->__('Your partslist has been removed.'));
            $newList = Mage::getModel('schrackwishlist/partslist')->loadActiveListByCustomer($this->_getSession()->getCustomer());
            $this->_redirectUrl(Mage::getUrl('wishlist/partslist/view').'#'.$to);
        } catch(Exception $e) {
            Mage::getSingleton('core/session')->addError($this->__('An error occurred while deleting a partslist: %s', $this->__($e->getMessage())));
            $this->_redirect('wishlist/partslist/view', array('id' => $partslist->getId()));
        }
    }

    /**
     * Update partslist item comments
     * @Override
     */
    public function updateAction() {
        $post = $this->getRequest()->getPost();
        $selectedPartslistId = $this->getRequest()->getParam('id');

        if($post && (isset($post['description']) && is_array($post['description']))
                    || (isset($post['qty']) && is_array($post['qty']))
                ) {
            $partslist = $this->_getPartslist($selectedPartslistId);
            $updatedItems = 0;

            foreach ($post['description'] as $itemId => $description) {
                $item = Mage::getModel('schrackwishlist/partslist_item')->load($itemId);
                if ($item->getPartslistId() != $partslist->getId()) {
                    continue;
                }

                // Extract new values
                $description = (string) $description;
				// commented by Nagarro becuase user is not able to save blank comment
                /* if (!strlen($description)) {
                    $description = $item->getDescription();
                } */

                $qty = null;
                if (isset($post['qty'][$itemId])) {
                    $qty = $this->_processLocalizedQty($post['qty'][$itemId]);
                }
                if (is_null($qty)) {
                    $qty = $item->getQty();
                    if (!$qty) {
                        $qty = 1;
                    }
                } elseif (0 === $qty) {
                    try {
                        $item->delete();
                    } catch (Exception $e) {
                        Mage::logException($e);
                        Mage::getSingleton('customer/session')->addError(
                            $this->__('Can\'t delete item from partslist')
                        );
                    }
                } elseif ('' === $qty) {
                    $qty = null;
                }

                // Check that we need to save
                if (($item->getDescription() == $description) && ($item->getQty() == $qty)) {
                    continue;
                }
                try {
                    $item->setDescription($description)
                        ->setQty($qty)
                        ->save();
                    $updatedItems++;
                } catch (Exception $e) {
                    Mage::getSingleton('core/session')->addError(
                        $this->__('Can\'t save description %s', Mage::helper('core')->escapeHtml($description))
                    );
                }
            }

            // save partslist model for setting date of last update
            if ($updatedItems) {
                try {
                    $partslist->save();
                    Mage::helper('schrackwishlist/partslist')->calculate();
                }
                catch (Exception $e) {
                    Mage::getSingleton('core/session')->addError($this->__('Can\'t update partslist'));
                }
            }

            if (isset($post['save_and_share'])) {
                $this->_redirect('*/*/share');
                return;
            }
        }
        $this->_redirect('wishlist/partslist/view', array('id' => $this->_getPartslist($selectedPartslistId)->getId()));
    }

    public function activateAction() {
        $selectedPartslistId = $this->getRequest()->getParam('id');
        $partslist = $this->_getPartslist($selectedPartslistId);
        $partslist->activate();
        $this->_redirect('wishlist/partslist/view', array('id' => $partslist->getId()));
    }

     /**
      * Adding new item
      * if no id is set in the request, then we will first create a new partslist (which we will return in case we are ajax)
      */
    public function addAction() {
        $forceToSession = false; // whether or not to force messages to session instead of to json object
        $jsonResponse = array();
        $selectedPartslistId = intval($this->getRequest()->getParam('id'));

        if ($selectedPartslistId > 0) {
            $partslist = $this->_getPartslist($selectedPartslistId);
        } else {
            $partslist = $this->_createPartslist();
            $jsonResponse['isNew'] = true;
            $name = Mage::app()->getRequest()->getParam('name');
            $comment = $this->getRequest()->getParam('comment');
            if (strlen($name)) {
                $partslist->setDescription($name);
            }
            if (strlen($comment)) {
                $partslist->setComment($comment);
            }
            $forceToSession = true; // in case of new list, we ALWAYS refresh the page and therefore want to see the msg on the next refresh
        }

        if (!$partslist) {
            $this->_redirectNoAjax('*/*');
            return;
        }

        $productString = $this->getRequest()->getParam('product');
        $product = Mage::getModel('catalog/product')->loadBySku($productString);

        if (!$product) {
            $productStringToInt = (int) $this->getRequest()->getParam('product');
            $product = Mage::getModel('catalog/product')->load($productStringToInt);
        }

        if (!$product) {
            $this->addError($this->__('Cannot specify product.'));
            $this->_redirectNoAjax('*/');
            return;
        }

        $query = $this->getRequest()->getParam('query');
        if ( $query ) {
            $helper = Mage::helper('search/search');
            $helper->logSearchArticleSelection($query,$product->getSku());
        }

        Mage::helper('schrackwishlist/partslist')->addProduct($partslist, $product, $jsonResponse, $forceToSession);

        $this->_redirectRefererNoAjax(Mage::helper('core')->jsonEncode($jsonResponse));
    }

    protected function _redirectNoAjax($url, $body = null) {
        if ($this->getRequest()->isAjax())
            $this->getResponse()->setBody ($body);
        else
            $this->_redirect($url);
    }

    protected function _redirectRefererNoAjax($body = null, $defaultUrl = null) {
        if ($this->getRequest()->isAjax())
            $this->getResponse()->setBody ($body);
        else
            $this->_redirectReferer($defaultUrl);
    }

    public function addDocumentAction() {
        if ($this->getRequest()->isAjax()) {
            $jsonResponse = array();
            $type = $this->getRequest()->getParam('type');
            $documentId = $this->getRequest()->getParam('document');
            $this->_addDocument($type, $documentId, null, $jsonResponse);
            $jsonResponse['ok'] = true;
            $this->addSuccess('Document has been added to your partslist.', $jsonResponse);
            $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($jsonResponse));
        } else
            throw new Exception('invalid action for non-ajax request');
    }

    private function _addDocument($type, $documentId, $partslist = null, &$jsonResponse) {
        try {
            $document = null;
            if ($partslist === null) {
                $selectedPartslistId = $this->getRequest()->getParam('id');
                $partslist = $this->_getPartslist($selectedPartslistId);
            }
            $partslist->activate();
            $partslist->save();
            $helper = Mage::helper('schracksales/order');
            $document = $helper->getFullDocument($documentId,$type);
            //Mage::log('documentId = ' . $documentId . ' -- type = ' . $type, null, 'partslist_add_document.log');
            //Mage::log($document, null, 'partslist_add_Document.log');
            $items = $document->getItemsCollection();
            foreach ($items as $item) {
                // Check, if item is transport cost item:
                $itemURL = Mage::helper('schrackcustomer/order')->getProductUrlFromItem($item);
                // Overrides items with no URL:
                if (!$itemURL || $itemURL == null) continue;
                if (in_array($type, array('offer', 'order'))) {
                    $qty = $item->getQtyOrdered();
                } else {
                    $qty = $item->getQty();
                }
                try {
                    $resultMessage = $this->_addProductToPartslistBySku($item->getSku(), $qty, $partslist, $jsonResponse);
                    if ($resultMessage && isset($resultMessage['error'])) {
                        $this->addError($resultMessage['error'], $jsonResponse);
                    }
                } catch (Exception $e) {
                    $this->addError($this->__('An error occurred while adding a product from a document to a partslist: %s', $e->getMessage()), $jsonResponse);
                }
            }
            $this->removeSuccessMessages($jsonResponse);
        } catch (Exception $e) {
            //Mage::log($e->getMessage(), null, 'partslist_add_document_exception.log');
            $this->addError($this->__('An error occurred while adding a document to a partslist: %s', $e->getMessage(), $jsonResponse));
        }
    }

    /**
     * Remove item
     * can be by item-id, or by product-id
     */
    public function removeAction()
    {
        $selectedPartslistId = $this->getRequest()->getParam('id');
        $partslist = $this->_getPartslist($selectedPartslistId);

        if (strlen($this->getRequest()->getParam('product'))) {
            $productId = $this->getRequest()->getParam('product');
            $product = Mage::getModel('catalog/product')->load($productId);
            if (!$product->getId() || !$product->isVisibleInCatalog()) {
                throw new Exception($this->__('Cannot specify product.'));
            }
            $item = $partslist->getItemByProduct($product);
        } else {
            $id = (int) $this->getRequest()->getParam('item');
            $item = Mage::getModel('schrackwishlist/partslist_item')->load($id);
        }


        try {
            $item->delete();
            $partslist->save();
            if (!$this->getRequest()->isAjax()) {
                $message = $this->__('Product has been removed from your partslist.');
                Mage::getSingleton('core/session')->addSuccess($message);
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('customer/session')->addError(
                    $this->__('An error occurred while deleting the item from partslist: %s', $e->getMessage())
            );
        } catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError(
                    $this->__('An error occurred while deleting the item from partslist.')
            );
        }

        Mage::helper('schrackwishlist/partslist')->calculate($partslist);

        $this->_redirectRefererNoAjax(null, Mage::getUrl('wishlist/partslist/list'));
    }

    /**
     * Add partslist item to shopping cart (do NOT remove from partslist)
     *
     * If Product has required options - item removed from partslist and redirect
     * to product view page with message about needed defined required options
     *
     */
    public function cartAction()
    {
        $selectedPartslistId = $this->getRequest()->getParam('id');
        $partslist   = $this->_getPartslist($selectedPartslistId);
        if (!$partslist) {
            return $this->_redirect('*/*');
        }

        $itemId = (int) $this->getRequest()->getParam('item');

        /* @var $item Mage_Wishlist_Model_Item */
        $item = Mage::getModel('schrackwishlist/partslist_item')->load($itemId);

        if (!$item->getId() || $item->getPartslistId() != $partslist->getId()) {
            return $this->_redirect('*/*');
        }

        // Set qty
        $qtys = $this->getRequest()->getParam('qty');
        if (isset($qtys[$itemId])) {
            $qty = $this->_processLocalizedQty($qtys[$itemId]);
            if ($qty) {
                $item->setQty($qty)->save();
            }
        }

        /* @var $session Mage_Wishlist_Model_Session */
        $session    = Mage::getSingleton('core/session');
        $cart       = Mage::getSingleton('checkout/cart');

        $redirectUrl = Mage::getUrl('schrackwishlist/partslist/view', array('id' => $partslist->getId()));

        try {
            $postfixRedirectUrl = "";
            $showSuccessAddedToCartMessage = true;

            $options = Mage::getModel('schrackwishlist/partslist_item_option')->getCollection()
                    ->addItemFilter(array($itemId));
            $item->setOptions($options->getOptionsByItem($itemId));

            $product = Mage::getModel('catalog/product')->load($item->getProductId());

            // Only add valid quantities to cart:
            if (is_object($product) && fmod($qty, $product->calculateMinimumQuantityPackage()) != 0) {
                if ($product->isBestellartikel() && $product->getCumulatedPickupableAndDeliverableQuantities() <= 0) {
                    $showResultQuantity = $product->getBatchSizeFromSupplier();
                    $productMinQtyFromSupplier = $product->getMinQtyFromSupplier();
                    if ($qty < $productMinQtyFromSupplier) {
                        $warningMessageText = sprintf($this->__('QUANTITY: Your entered quantity of %1$s has been adjusted to the minimum quantity of %2$d.'), $product->getSku(), $productMinQtyFromSupplier);
                    } else {
                        $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $product->getSku(), 'this-string-is-not-used-by-output', $showResultQuantity);
                    }
                } else {
                    $showResultQuantity = $product->calculateMinimumQuantityPackage();
                    $warningMessageText = sprintf($this->__('Quantity of %1$s has been adjusted to %2$d (a multiple of %3$d).'), $product->getSku(), 'this-string-is-not-used-by-output', $showResultQuantity);
                }
                Mage::getSingleton('core/session')->addNotice($warningMessageText);
                Mage::getSingleton('core/session')->addNotice($this->__('The item has not been added to shopping cart. Please check quantity and packaging.'));

                $resultQtyData = $product->calculateClosestHigherQuantityAndDifference(intval($qty), true, array(), 'PartslistController::cartAction()');

                // Add new quantity parameter to URL:
                $postfixRedirectUrl = 'changeinvalidquantity/' . $item->getProductId() . '_' . $resultQtyData['closestHigherQuantity'] . '/';
                $showSuccessAddedToCartMessage = false;
            } else {
                $item->addToCart($cart, false);
                $cart->save()->getQuote()->collectTotals();
            }

            $partslist->save();

            Mage::helper('schrackwishlist/partslist')->calculate();

            if (Mage::helper('checkout/cart')->getShouldRedirectToCart()) {
                $redirectUrl = Mage::helper('checkout/cart')->getCartUrl();
            } else if ($this->_getRefererUrl()) {
                $redirectUrl = $this->_getRefererUrl();
            }

            // Remove old quantity from URL, and replace with new value (if exists!):
            if (stristr($redirectUrl, 'changeinvalidquantity')) {
                $redirectUrl = preg_replace('/changeinvalidquantity\/\d+_\d+\//', '', $redirectUrl);
            }

            $redirectUrl = $redirectUrl . $postfixRedirectUrl;

            Mage::helper('schrackwishlist/partslist')->calculate();
            if ($showSuccessAddedToCartMessage) {
                Mage::getSingleton('core/session')->addSuccess(Mage::helper('schrackwishlist/partslist')->__('Item has been added to cart.'));
            }
        } catch (Mage_Core_Exception $e) {
            if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_NOT_SALABLE) {
                $session->addError(Mage::helper('wishlist')->__('This product(s) is currently out of stock'));
            } else if ($e->getCode() == Mage_Wishlist_Model_Item::EXCEPTION_CODE_HAS_REQUIRED_OPTIONS) {
                Mage::getSingleton('catalog/session')->addNotice($e->getMessage());
                $redirectUrl = Mage::getUrl('*/*/configure/', array('id' => $item->getId()));
            } else {
                Mage::getSingleton('catalog/session')->addNotice($e->getMessage());
                $redirectUrl = Mage::getUrl('*/*/configure/', array('id' => $item->getId()));
            }
        } catch (Exception $e) {
            $this->_getSession()->addException($e, Mage::helper('schrackwishlist/partslist')->__('Cannot add item to shopping cart'));
        }

        Mage::helper('schrackwishlist/partslist')->calculate($partslist);
        return $this->_redirectUrl($redirectUrl);
    }

    public function allcartAction()
    {
        $selectedPartslistId = $this->getRequest()->getParam('id');
        $partslist = $this->_getPartslist($selectedPartslistId);
        if (!$partslist) {
            $this->_forward('noRoute');
            return ;
        }

        //$redirectUrl = Mage::getUrl('schrackwishlist/partslist/view', array('id' => $partslist->getId()));
        $redirectUrl = null;

        $collection = $partslist->getItemCollection()->setVisibilityFilter();
        $items2do = array();

        $skus = $this->getRequest()->getParam('sku');
        $qtys = $this->getRequest()->getParam('qty');
        foreach ( $collection as $item ) {
            /** @var Schracklive_SchrackWishlist_Model_Partslist_Item */
            if ( isset($skus) && ! isset($skus[$item->getId()]) ) {
                continue;
            }
            if ( isset($qtys) && isset($qtys[$item->getId()]) ) {
                $item->setQty($qtys[$item->getId()]);
            }
            $items2do[] = $item;
        }

        $addedItems = $this->_addPartlistItemsToCart($items2do,$redirectUrl);

        if ( $addedItems ) {
            try {
                $partslist->save();
            }
            catch (Exception $e) {
                Mage::getSingleton('core/session')->addError($this->__('Cannot update partslist'));
            }
        }
    }

    public function allcartsharedAction()
    {
        try {
            $partslist   = Mage::getModel('schrackwishlist/partslist')->load(Mage::app()->getRequest()->getParam('id'));
            $redirectUrl = Mage::getUrl('schrackwishlist/partslist/view');
            $collection = $partslist->getItemCollection()->setVisibilityFilter();
            $items2do = array();

            $skus = $this->getRequest()->getParam('sku');
            $qtys = $this->getRequest()->getParam('qty');
            foreach ( $collection as $item ) {
                /** @var Schracklive_SchrackWishlist_Model_Partslist_Item */
                if ( isset($skus) && ! isset($skus[$item->getId()]) ) {
                    continue;
                }
                if ( isset($qtys) && isset($qtys[$item->getId()]) ) {
                    $item->setQty($qtys[$item->getId()]);
                }
                $items2do[] = $item;
            }
        }
        catch(Exception $e) {
            Mage::Log($e->getTraceAsString());
            return false;
        }
        $addedItems = $this->_addPartlistItemsToCart($items2do,$redirectUrl);

        if ( $addedItems ) {
            try {
                $partslist->save();
            }
            catch (Exception $e) {
                Mage::getSingleton('core/session')->addError($this->__('Cannot update partslist'));
            }
        }
    }


    public function selectedplcartAction() {
        $redirectUrl = Mage::getUrl('schrackwishlist/partslist/list');
        $plIDs       = $this->getRequest()->getParam('plIDs');
        $plIdArray   = explode(',', $plIDs);
        $items2do    = array();
        $partslists  = array();
        $indexUrl    = null;

        foreach ( $plIdArray as $plID ) {
            $partsListModel = Mage::getModel('schrackwishlist/partslist');
            $partsListModel->load(intval($plID));
            $partslists[] = $partsListModel;
            $itemCollection = $partsListModel->getItemCollection()->setVisibilityFilter();
            foreach ( $itemCollection as $item ) {
                $product = $item->getProduct();
                $_product = Mage::getModel('catalog/product')->loadBySku($product->getSku());
                $result = $_product->calculateClosestHigherQuantityAndDifference(intval($item->getData('qty')), true, array(), 'PartslistController::selectedplcartAction()');

                if ($result['invalidQuantity']) {
                    Mage::getSingleton('core/session')->addError( sprintf($this->__('Your selected quantity for %1s is not a multiple of the packaging unit. Please select a multiple of %2$d.'), $product->getSku(), $_product->calculateMinimumQuantityPackage()) );
                    continue;
                }
                $items2do[] = $item;
            }
        }

        $addedItems = $this->_addPartlistItemsToCart($items2do,$redirectUrl);

        if ( $addedItems ) {
            try {
                $changedPartslistIDs = array();
                foreach ( $addedItems as $item ) {
                    $changedPartslistIDs[$item->getPartslistId()] = true;
                }
                foreach ( $partslists as $pl ) {
                    $id = $pl->getId();
                    if ( $changedPartslistIDs[$id] ) {
                        $pl->save();
                    }
                }
            }
            catch (Exception $e) {
                Mage::getSingleton('core/session')->addError($this->__('Cannot update partslist'));
                $redirectUrl = $indexUrl;
            }
        }
    }

    private function _addPartlistItemsToCart ( $items, $redirectUrl ) {
        $successMsg      = null;
        $errorMsgs       = array();
        $addedItems      = array();
        $notSalableItems = array();
        $hasOptionsItems = array();
        $helper          = Mage::helper('schrackwishlist/partslist');
        $session         = Mage::getSingleton('core/session');

        $helper->addPartlistItemsToCart($items,$successMsg,$errorMsgs,$addedItems,$notSalableItems,$hasOptionsItems);

        if ( $errorMsgs ) {
            $isMessageSole = (count($errorMsgs) == 1);
            if ( $isMessageSole && count($hasOptionsItems) == 1 ) {
                $item = $hasOptionsItems[0];
                $redirectUrl = $item->getProductUrl();
            } else {
                foreach ( $errorMsgs as $message ) {
                    $session->addError($message);
                }
            }
        }

        if ( $successMsg ) {
            $session->addSuccess($successMsg);
        }

        $this->_redirectUrl($redirectUrl);

        return $addedItems;
    }


    /**
	 * Removes all items from current list.
	 */
	public function truncateAction() {
	    $selectedPartslistId = $this->getRequest()->getParam('id');
        $partslist = $this->_getPartslist($selectedPartslistId);
		$partslist->truncate();

	    return $this->_redirect('*/*/view', array('id' => $selectedPartslistId));
    }

    public function batchAddProductsToCompareAction() {
        if ($this->getRequest()->isAjax()) {
            $successCount = 0;
            $jsonResponse = array();
            $params = $this->getRequest()->getParams();
            if (isset($params['products'])) {
                $products = explode(';', $params['products']);
                foreach ($products as $product) {
                    list($sku, $dummy) = explode(':', $product);
                    $product = Mage::getModel('catalog/product')
                        ->setStoreId(Mage::app()->getStore()->getId())
                        ->loadBySku($sku);

                    if ($product->getId()/* && !$product->isSuper()*/) {
                        Mage::getSingleton('catalog/product_compare_list')->addProduct($product);
                        /*Mage::getSingleton('catalog/session')->addSuccess(
                            $this->__('Product %s successfully added to compare list', Mage::helper('core')->escapeHtml($product->getName()))
                        );*/
                        Mage::dispatchEvent('catalog_product_compare_add_product', array('product'=>$product));
                    }
                    Mage::helper('catalog/product_compare')->calculate();
                }
            }
            $jsonResponse['ok'] = true;
            $this->_redirectRefererNoAjax(Mage::helper('core')->jsonEncode($jsonResponse));
        } else
            throw new Exception('invalid action for non-ajax request');
    }

    protected function _createPartslist() {
         $model = Mage::getModel('schrackwishlist/partslist');
         return $model->create($this->_getSession()->getCustomer()->getId(), $this->getRequest()->getParam('description'));
    }

    /**
     * Retrieve partslist object by logged-in customer and id from request
     *
     * @Override
     * @return Schracklive_SchrackWishlist_Model_Partslist
     */
    protected function _getPartslist($partslist_id) {
        try {
            $partslist = Mage::getModel('schrackwishlist/partslist')
                ->loadByCustomerAndId($this->_getSession()->getCustomer(), $partslist_id);
        } catch (Mage_Core_Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        } catch (Exception $e) {
            $this->_getSession()->addException($e,
                Mage::helper('core')->__($e->getMessage())
            );
            return false;
        }
        return $partslist;
    }

    protected function _getSession() {
         return Mage::getSingleton('customer/session');
    }

    protected function _setTitleFromPartslist($partslist) {
        try {
            $headBlock = $this->getLayout()->getBlock('head');
            if ($headBlock) {
                $headBlock->setTitle($this->__('My Partslist') . ' ' . $partslist->getDescription());
            }
        } catch (Exception $e) {
            Mage::logException($e);
            Mage::getSingleton('core/session')->addError($this->__($e->getMessage()));
            return $this->_redirect('wishlist/partslist/edit', array('id' => $this->getRequest()->getParam('id')));
        }
    }
/**
     * 
     * @param type $product_helper
     * @param type $productId
     * @param type $qty
     */
    protected function _addProductToPartslistById( $product_helper, $productId, $qty, $partslist, &$jsonResponse, $addSuccessMessages = true, $forceToSession = false) {
        $product = $product_helper->load( $productId );
        $selectedPartslistId = $this->getRequest()->getParam('id');

        if ($product) {            
            $sku = $product->getSku();
            $categories = $product->getCategoryIds();
            if (!$categories) {
                $resultMessage = array('error' => $this->__('Product %s can not be added to partslist.', $sku). ' (#01)');
                return $resultMessage;
            }
            $category = Mage::getModel('catalog/category')->load($categories[0]);
            if (!Mage::helper('schrackcustomer/order')->productCanBeAddedToLists($product)) {
                $resultMessage = array('error' => $this->__('Product %s can not be added to partslist.', $sku). ' (#02)');
                return $resultMessage;
            }
            if (!$partslist) {
                $partslist = $this->_getPartslist($selectedPartslistId);
            }

            $suggestion = Mage::helper('schrackcheckout/cart')->getSuggestionForProductAndQty($product, $qty);
            if (isset($suggestion['newQty']))
                $qty = $suggestion['newQty'];
            if (isset($suggestion['messages'])) {
                foreach ($suggestion['messages'] as $msg)
                    $this->addSuccess($msg, $jsonResponse, $forceToSession);
            }
            
            $partslist->addNewItem($product, array('qty' => $qty));
            if ($addSuccessMessages) {
                $message = $this->__('%s was added to your partslist.', Mage::helper('core')->escapeHtml($product->getName()));
                $this->addSuccess($message, $jsonResponse, $forceToSession);
            }
        } else {
            $this->addError(str_replace('%s', $productId, $this->__('Product number %s not found.')), $jsonResponse, $forceToSession);
        }
    }
    
    
    /**
     * 
     * @param string $sku
     * @param float $qty
     */
    protected function _addProductToPartslistBySku($sku, $qty, $partslist, &$jsonResponse, $addSuccessMessages = true, $forceToSession = false) {
        $product_helper = Mage::getModel('schrackcatalog/product');
        $productId = $product_helper->getIdBySku($sku);

        if ($productId) {
            $resultMessage = $this->_addProductToPartslistById($product_helper, $productId, $qty, $partslist, $jsonResponse, $addSuccessMessages, $forceToSession);
            return $resultMessage;
        } else {
            throw new ProductNotFoundException('Unable to find product for sku ' . $sku);
        }
    }
    
     protected function _removeProductFromPartslistBySku($sku, $partslist, &$jsonResponse, $addSuccessMessages = true, $forceToSession = false) {
        $product_helper = Mage::getModel('schrackcatalog/product');
        $productId = $product_helper->getIdBySku($sku);

        if ($productId) {
            $this->_removeProductFromPartslistById($productId, $partslist, $jsonResponse, $addSuccessMessages, $forceToSession);
        }
        else
            throw new Exception('Unable to find product for sku ' . $sku);
    }
    
    protected function _removeProductFromPartslistById($productId, $partslist, $jsonResponse, $addSuccessMessages, $forceToSession) {
        $product = Mage::getModel('catalog/product')->load($productId);
        if (!$product->getId()) {
            throw new Exception($this->__('Cannot specify product.'));
        }
        $item = $partslist->getItemByProduct($product);

        
        try {
            $item->delete();
            $partslist->getItemCollection()->save();
            if ($addSuccessMessages) {
                $message = $this->__('Product has been removed from your partslist.');
                $this->addSuccess($message, $jsonResponse, $forceToSession);  
            }
        } catch (Mage_Core_Exception $e) {
            Mage::getSingleton('customer/session')->addError(
                    $this->__('An error occurred while deleting the item from partslist: %s', $e->getMessage())
            );
        } catch (Exception $e) {
            Mage::getSingleton('customer/session')->addError(
                    $this->__('An error occurred while deleting the item from partslist.')
            );
        }

    }

    public function setItemDescriptionAction() {
        $jsonResponse = array();
	
		if ($this->getRequest()->isAjax()) {            
            $itemId = $this->getRequest()->getParam('itemId');
            $description = $this->getRequest()->getParam('description');
            $item = Mage::getModel('schrackwishlist/partslist_item')->load($itemId);
			try {
                $item->setDescription($description);
                $item->save();
                $jsonResponse = array('ok');
                return $this->_redirectNoAjax(Mage::getUrl('*/*/view'), Mage::helper('core')->jsonEncode($jsonResponse));
			} catch (Exception $e) {
				Mage::logException($e);
				$this->addError($e->getMessage(), $jsonResponse);
                return $this->_redirectNoAjax('wishlist/partslist/create', Mage::helper('core')->jsonEncode($jsonResponse));
			}
		}
    }
    
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

    
    /**
     * heuristically try to determine whether the given text might be a csv line
     * we can use
     * 
     * @param string $line
     */
    protected function _csvLineContainsData($line,$delim) {
        // return (preg_match('/^"?\w+[\w\-]*"?[,;\\t]"?\d+((.|,)\d+)?"?/', $line) === 1);
        if ( ! $line ) {
            return false;
        }
        $line = trim($line);
        if ( strlen($line) < 1 ) {
            return false;
        }
        if ( $delim ) {
            $word = explode($delim,$line)[0];
        } else {
            $word = $line;
        }
        $l = strlen($word);
        if ( $l < 10 || $l > 15 ) {
            return false;
        }
        if ( preg_match('/.*[a-z].*/', $word) === 1 ) {
            return false;
        }
        return true;
    }
    
    private function addError($msg, &$jsonResponse, $forceToSession = false) {
        $msg = $this->__($msg);
        if ($this->getRequest()->isAjax() && !$forceToSession) {
            if (!isset($jsonResponse['errors']) || !is_array($jsonResponse['errors']))
                $jsonResponse['errors'] = array();
            array_push($jsonResponse['errors'], $msg);
        } else
            Mage::getSingleton('core/session')->addError($msg);        
    }
    private function addSuccess($msg, &$jsonResponse, $forceToSession = false) {
        $msg = $this->__($msg);
        if ($this->getRequest()->isAjax() && !$forceToSession) {
            if (!isset($jsonResponse['messages']) || !is_array($jsonResponse['messages']))
                $jsonResponse['messages'] = array();
            array_push($jsonResponse['messages'], $msg);
        } else
            Mage::getSingleton('core/session')->addSuccess($msg);
    }
    
    private function removeSuccessMessages(&$jsonResponse) {
        $jsonResponse['messages'] = array();
    }

    public function getPartslistByAjaxAction() {
        $partslistResult = array('error' => 'default');

        if ($this->_validateFormKey()) {
            if (Mage::app()->getRequest()->isAjax()) {
                $partlistId = intval($this->getRequest()->getParam('partlistid'));
                if ($partlistId) {
                    $partslistResult = array();
                    $partslist   = Mage::getModel('schrackwishlist/partslist')->load($partlistId);
                    $collection = $partslist->getItemCollection();

                    if (Mage::getStoreConfig('ec/config/active')) $trackingEnabled = 'enabled'; else $trackingEnabled = 'disabled';

                    foreach ($collection as $item ) {
                        $product = $item->getProduct();
                        $partslistResult[] = array(
                            'trackingEnabled' => $trackingEnabled,
                            'currencyCode' => Mage::app()->getStore()->getCurrentCurrencyCode(),
                            'sku' => $product->getSku(),
                            'name' => $product->getName(),
                            'price' => number_format((float) str_replace(',', '.', $product->getFinalPrice()), 2, '.', ''),
                            'category' => $product->getCategoryId4googleTagManager(),
                            'quantity' => (string) intval($item->getQty())
                        );
                    }

                    //$partslistResult = array('success' => 'partlist-ID = ' . $partlistId);
                } else {
                    $partslistResult = array('error' => 'no partlist found for ID = ' . $partlistId);
                }
            } else {
                $partslistResult = array('error' => 'no AJAX detected');
            }
        } else {
            $partslistResult = array('error' => 'illegal access');
        }

        echo json_encode($partslistResult);
        die();
    }
  
    public function testAction() {
         $this->loadLayout();
         $this->renderLayout();      
    }
}

class ProductNotFoundException extends Exception {}
class NoQtyException extends Exception {}
?>