<?php
require_once 'Mage/Catalog/controllers/ProductController.php';

class Schracklive_SchrackCatalog_ProductController extends Mage_Catalog_ProductController {

    public function checkQuantityAction() {
        $resultOK = '{"result":"OK"}';
        $resultMessageInfo = array();
        $newQuantity = 0;

        $desiredQuantity = $this->getRequest()->getParam('desiredQuantity');
        $sku = $this->getRequest()->getParam('sku');
        $product = Mage::getModel('catalog/product')->loadBySku($sku);
        if ( ! $product ) {
            // must be a DMAT or something else, not in Webshop
            echo $resultOK;
            return;
        }

        $salesUnit = Mage::helper('schrackcatalog/product')->getSalesUnit($product);

        if ($desiredQuantity < $salesUnit) {
            $newQuantity = $salesUnit;
        } else {
            // Compare desired quantity with real available fixed minimum sales unit:
            if (fmod($desiredQuantity, $salesUnit) != 0) {
                $diff = fmod($desiredQuantity, $salesUnit);
                $newQuantity = ($desiredQuantity - $diff) / $salesUnit;
            }
        }

        if ($newQuantity > 0) {
            $msg = sprintf($this->__('Your selected quantity for %1s is not a multiple of the packaging unit. Please select a multiple of %2$d.'), $sku, $salesUnit);
            $resultMessageInfo = ["MessageText" => $msg, "newQuantity" => $newQuantity];
            echo json_encode($resultMessageInfo);
        } else {
            // Nothing needs to be changed (quantity is okay)
            echo $resultOK;
        }
    }


    public function viewAction()
    {
        $productId = $this->getRequest()->getParam('id');
        $product = Mage::getModel('catalog/product')->loadByAttribute('entity_id',$productId);
        $session = Mage::getSingleton('customer/session');

        if (!$product) {
            $this->norouteAction();
            return;
        }
        if ( Mage::registry('product') ) {
            Mage::unregister('product');
        }
        Mage::register('product', $product);

        if ( $q = $this->getRequest()->getParam('q') ) {
            $helper = Mage::helper('search/search');
            $helper->logSearchArticleSelection($q,$product);
        }

        if (!$session)
            throw new Exception('session not found');

        Mage::helper('schrackcustomer/tracking')->track($session, $product);

        // set category in case this is a direct view, so we can show the correct pillar in category navigation
        if (Mage::getSingleton('catalog/layer')) {
            $cc = Mage::getSingleton('catalog/layer')->getCurrentCategory();
            if ( !$cc || intval($cc->getId()) === 2 ) {
                $cc = $product->getPreferredCategory();
                if ($cc && $cc->getId()) {
                    Mage::getSingleton('catalog/layer')->setCurrentCategory($cc);
                }
            }
        }

        if ( $product->isDead() ) {
            if ( $product->isLockedArticle() ) {
                $msg = $this->__('The researched Product is actually not available.');
            } else {
                $msg = $this->__('The researched Product is not longer available.');
            }
            $msg .= ' ';
            if ( $product->getLastReplacementProduct() ) {
                $msg .= $this->__('Below you can see a replacing product.');
            } else {
                $msg .= $this->__('If you have any questions, please consult your contact person.');
            }
            Mage::getSingleton('core/session')->addSuccess($msg);
        }

        if ($product->isWebshopsaleable() == false) {
            $msg = $this->__('This product is currently not available for online orders. Please contact your advisor.');
            Mage::getSingleton('core/session')->addSuccess($msg);
        }

        $manNo = $product->getSchrackStsPrintedManufacturerNumber();
        if ( isset($manNo) && $manNo > '' && strcasecmp($this->getRequest()->getParam('q'),$manNo) === 0 && $manNo != $product->getSku()) {
            $msg = sprintf($this->__("You searched for the item number %s. The searched product's Schrack Technik SKU is %s."),$manNo,$product->getSku());
            Mage::getSingleton('core/session')->addSuccess($msg);
        }

        $res =  parent::viewAction();
        return $res;
    }

    public function downloadPowerLossCsvAction () {
        if ( ! Mage::getSingleton('customer/session')->authenticate($this) ) {
            $helper = Mage::helper('core');
            $thisUrl = Mage::getUrl("catalog/product/downloadPowerLossCsv");
            $referer = $helper->urlEncode($thisUrl);
            $loginUrl = Mage::getUrl('customer/account/login', array('referer' => $referer));
            $this->_redirectUrl($loginUrl);
        } else {
            if ( $this->getRequest()->getParam('doDownload') === '1' ) {
                Mage::helper('schrack/csv')->createCsvDownload($this, 'catalog.product.powerlosscsv', $this->__('Power Loss') . '.csv');
            } else {
                $thisUrl = Mage::getUrl("catalog/product/downloadPowerLossCsv/",array('doDownload' => '1'));

                $this->loadLayout();
                $block = $this->getLayout()->getBlock('catalog.product.powerlosscsv.view');
                $block = $this->getLayout()->getBlock('head');
                $block->assign('refreshURL', $thisUrl);
                $this->renderLayout();
                // header('http-equiv="refresh" content="1; URL='.$thisUrl.'"'); // DLA20190508: removed, causes ERR_SPDY_PROTOCOL_ERROR in Chrome Version 73.0.3683

                /*
                $html = '<head><meta http-equiv="refresh" content="1; URL='.$thisUrl.'"></head><body>tralala</body>';
                die($html);
                */
            }
        }
    }

    public function checkAvailabilityAction () {
        if (!$this->getRequest()->isAjax()) {
            die("wrong method");
        }
        $json = Mage::getModel('schrackcore/jsonresponse');
        $this->loadLayout();
        $sku = $this->getRequest()->getParam('sku');
        $qty = $this->getRequest()->getParam('qty');
        $drum = $this->getRequest()->getParam('drum');
        $drum = $drum > '' ? intval($drum) : -1;
        $productHelper = Mage::helper('schrackcatalog/product');
        try {
            $product = $productHelper->getProduct($sku, Mage::app()->getStore()->getStoreId(), 'sku');
        } catch (Exception $e) {
            Mage::logException($e);
            $json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_ERROR);
            $json->addError($this->__('Product not found'));
            $json->encodeAndDie();
        }
        $checkoutHelper = Mage::helper('schrackcheckout/cart');
        $resHtml = $checkoutHelper->detectAvailabilityProblemAndReturnPopupHtml($product,$qty,$drum);
        if ( $resHtml ) {
            $json->setHtml($resHtml);
        }
        $json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
        $json->encodeAndDie();
    }

    public function sendDiscontinuationInquiryAction () {
        $sku        = $this->getRequest()->getParam('sku');
        $qty        = $this->getRequest()->getParam('qty');
        $name       = $this->getRequest()->getParam('name');
        $email      = $this->getRequest()->getParam('email');
        $phone      = $this->getRequest()->getParam('phone');
        $text       = $this->getRequest()->getParam('text');

        $company    = $this->getRequest()->getParam('company');
        $customerNo = $this->getRequest()->getParam('customer-no');
        $country    = $this->getRequest()->getParam('country');

        if ( Mage::getSingleton('customer/session')->isLoggedIn() ) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $branch = $customer->getAccount()->getWwsBranchId();
        } else {
            $branch = '';
        }

        $advisor = Mage::helper('schrack')->getAdvisor();
        $productHelper = Mage::helper('schrackcatalog/product');
        $product = $productHelper->getProduct($sku,Mage::app()->getStore()->getStoreId(),'sku');
        $qty = Mage::helper('schrackcore/string')->numberFormat($qty);
        $qty .= ' ' . $product->getSchrackQtyunit();
        $productHelper->sendDiscontinuationInquiryEmail($name,$email,$phone,$company,$customerNo,$country,$branch,$text,$advisor,$sku,$qty);
        $msg = $this->__('Your request has been sent. Your contact person will contact you soon.');
        Mage::getSingleton('core/session')->addSuccess($msg);

        return $this->_redirectReferer();
    }


    public function sendDiscontinuationInquiryByAjaxAction () {
        if (!$this->getRequest()->isAjax()) {
            die("wrong method");
        }

        $json = Mage::getModel('schrackcore/jsonresponse');

        $sku        = $this->getRequest()->getParam('sku');
        $qty        = $this->getRequest()->getParam('qty');
        $name       = $this->getRequest()->getParam('name');
        $email      = $this->getRequest()->getParam('email');
        $phone      = $this->getRequest()->getParam('phone');
        $text       = $this->getRequest()->getParam('text');

        $company    = $this->getRequest()->getParam('company');
        $customerNo = $this->getRequest()->getParam('customer-no');
        $country    = $this->getRequest()->getParam('country');

        if ( Mage::getSingleton('customer/session')->isLoggedIn() ) {
            $customer = Mage::getSingleton('customer/session')->getCustomer();
            $branch = $customer->getAccount()->getWwsBranchId();
        } else {
            $branch = '';
        }

        $advisor = Mage::helper('schrack')->getAdvisor();
        $productHelper = Mage::helper('schrackcatalog/product');
        $product = $productHelper->getProduct($sku,Mage::app()->getStore()->getStoreId(),'sku');
        $qty = Mage::helper('schrackcore/string')->numberFormat($qty);
        $qty .= ' ' . $product->getSchrackQtyunit();
        $productHelper->sendDiscontinuationInquiryEmail($name,$email,$phone,$company,$customerNo,$country,$branch,$text,$advisor,$sku,$qty);

        $json->addMessage($this->__('Your request has been sent. Your contact person will contact you soon.'));
        $json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
        $json->encodeAndDie();
    }


    /**
     * Has nothing to do with the function above!!!
     * "mercateo availability" action
     * @throws Exception
     */
    public function getAvailabilityAction() {
        try {
            $helper = Mage::helper('schrackcatalog/product');
            $stockNo = $this->getRequest()->getParam('stockNo', 80);
            $sku = $this->getRequest()->getParam('sku');
            $product = Mage::getModel('catalog/product')->loadBySku($sku);
            if (!$product || !strlen($product->getSku())) {
                Mage::log("stockNo $stockNo, sku $sku -> could not be found!", null, 'mercateo.log');
                throw new Exception('product unfound.');
            }
             Mage::log("stockNo $stockNo, sku $sku -> " . str_replace('.', '', $helper->getFormattedDeliveryQuantity($product, $stockNo, false)), null, 'mercateo.log');
            echo str_replace('.', '', $helper->getFormattedDeliveryQuantity($product, $stockNo, false));
        } catch (Exception $e) {
            Mage::logException($e);
            echo "0";
        }
        die;        
    }


    public function checkValidQuantityAction()
    {
        $sku = $this->getRequest()->getParam('sku');
        $qty = intval($this->getRequest()->getParam('qty'));

        $product = Mage::getModel('catalog/product')->loadBySku($sku);

        if (!($product && $product->getId())) {
            throw new Exception($this->__('No such product with SKU: %s', $sku));
        }

        $result = $product->calculateClosestHigherQuantityAndDifference($qty, true, array(), 'ProductController::checkValidQuantityAction()');

        if ($result['invalidQuantity']) {
            echo json_encode('invalid_' . $result['closestHigherQuantity']);
        } else {
            echo json_encode('valid');
        }
    }

    public function getAllProductInfoAction () {
        /*
        if ( !$this->getRequest()->isAjax() ) {
            die("wrong method");
        }
        */
        $json = Mage::getModel('schrackcore/jsonresponse');
        // $this->loadLayout();
        $skus = $this->getRequest()->getParam('sku');
        if ( ! $skus ) {
            $json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_ERROR);
            $json->addError($this->__('Missing param sku'));
            $json->encodeAndDie();
        }
        if ( ! is_array($skus) ) {
            $skus = array($skus);
        }
        $productHelper = Mage::helper('schrackcatalog/product');
        try {
            $data = $productHelper->getAllProductInfo($skus,true);
        } catch (Exception $e) {
            Mage::logException($e);
            $json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_ERROR);
            $json->addError($e->getMessage());
            $json->encodeAndDie();
        }
        $json->setData('infos',$data);
        $json->setStatus(Schracklive_SchrackCore_Model_Jsonresponse::STATUS_OK);
        $json->encodeAndDie();
    }


    public function getProductAvailabilityInStoreAction () {
        if (!$this->getRequest()->isAjax()) {
            die("wrong method");
        }

        if (!$this->_validateFormKey()){
            die("wrong form key");
        }

        $warehouse             = $this->getRequest()->getParam('pickup_store');
        $skusInCart            = array();
        $skusWithQtyInCart     = array();
        $warehouseId           = null;
        $resultMissingArticles = null;
        
        if ($warehouse) {
            $warehouseId = str_replace('schrackpickup_warehouse', '', $warehouse);
        } else {
            die("no warehouse found");
        }

        $cart = Mage::getModel('checkout/cart')->getQuote();

        foreach ($cart->getAllItems() as $item) {
            $skusInCart[] = $item->getSku();
            $skusWithQtyInCart[$item->getSku()] = $item->getQty();
        }

        $productHelper = Mage::helper('schrackcatalog/product');
        $resultFromWWS = $productHelper->getAvailibilityProductInfo($skusInCart, 1);

        if ($resultFromWWS && is_array($resultFromWWS) && !empty($resultFromWWS)) {
            $index = 0;
            foreach ($resultFromWWS as $sku => $data) {
                if (isset($data[$warehouseId]) && isset($data[$warehouseId]['qty'])) {
                    $countOfArticlesInSelectedStore = $data[$warehouseId]['qty'];

                    // Building product from $sku, and detect, if store is unmanageable:
                    $product = Mage::getModel('catalog/product')->loadBySku($sku);
                    $isProductManageable = $product->isArticleUnmanageable($warehouseId);
                    $triggerProductUnmanageable = false;

                    // Case #1 : nothing in stock available (amount = 0):
                    if (!($countOfArticlesInSelectedStore > 0)) {
                        $resultMissingArticles[$index]['sku'] = $sku;
                        $resultMissingArticles[$index]['availableAmountInSelectedStock'] = 0;
                        // OnlyAmountMarker => 1 (amount) / 2 (amount + unmanaged) / 3 (unmanaged)
                        $resultMissingArticles[$index]['OnlyAmountMarker'] = 1;
                        $resultMissingArticles[$index]['selectedAmountInCart'] = $skusWithQtyInCart[$sku];
                        if ($isProductManageable == true) {
                            $resultMissingArticles[$index]['productIsUnmanageable'] = true;
                            // OnlyAmountMarker => 1 (amount) / 2 (amount + unmanaged) / 3 (unmanaged)
                            $resultMissingArticles[$index]['OnlyAmountMarker'] = 2;
                            $triggerProductUnmanageable = true;
                        }
                        $index++;
                    } else {
                        // Case #2 : some in stock available, but not enough:
                        if ($skusWithQtyInCart[$sku] > $countOfArticlesInSelectedStore) {
                            $resultMissingArticles[$index]['sku'] = $sku;
                            $resultMissingArticles[$index]['availableAmountInSelectedStock'] = $countOfArticlesInSelectedStore;
                            // OnlyAmountMarker => 1 (amount) / 2 (amount + unmanaged) / 3 (unmanaged)
                            $resultMissingArticles[$index]['OnlyAmountMarker'] = 1;
                            $resultMissingArticles[$index]['selectedAmountInCart'] = $skusWithQtyInCart[$sku];
                            if ($isProductManageable == true) {
                                $resultMissingArticles[$index]['productIsUnmanageable'] = true;
                                // OnlyAmountMarker => 1 (amount) / 2 (amount + unmanaged) / 3 (unmanaged)
                                $resultMissingArticles[$index]['OnlyAmountMarker'] = 2;
                                $triggerProductUnmanageable = true;
                            }
                            $index++;
                        }
                    }

                    if ($triggerProductUnmanageable == false) {
                        if ($isProductManageable == true) {
                            $resultMissingArticles[$index]['sku'] = $sku;
                            $resultMissingArticles[$index]['availableAmountInSelectedStock'] = 0;
                            $resultMissingArticles[$index]['selectedAmountInCart'] = $skusWithQtyInCart[$sku];
                            $resultMissingArticles[$index]['productIsUnmanageable'] = true;
                            // OnlyAmountMarker => 1 (amount) / 2 (amount + unmanaged) / 3 (unmanaged)
                            $resultMissingArticles[$index]['OnlyAmountMarker'] = 3;
                        }
                    }
                } else {
                    // Case #3 : some error occured, because WWS not responded as expected (e.g. : field/store missing completely)
                    $resultMissingArticles[$index]['sku'] = $sku;
                    $resultMissingArticles[$index]['availableAmountInSelectedStock'] = 0;
                    $resultMissingArticles[$index]['OnlyAmountMarker'] = 1;
                    $resultMissingArticles[$index]['selectedAmountInCart'] = $skusWithQtyInCart[$sku];
                    if ($isProductManageable == true) {
                        $resultMissingArticles[$index]['productIsUnmanageable'] = true;
                        // OnlyAmountMarker => 1 (amount) / 2 (amount + unmanaged) / 3 (unmanaged)
                        $resultMissingArticles[$index]['OnlyAmountMarker'] = 2;
                    }
                    $index++;
                }
            }
        }

        echo json_encode($resultMissingArticles);
    }

    public function getCutOffTimeAction() {
        if (!$this->getRequest()->isAjax()) {
            die("wrong method");
        }

        if (!$this->_validateFormKey()){
            die("wrong form key");
        }

        $result                  = array();
        $holidayDays             = array();
        $currentDayIsHolidayDay  = 0;
        $nextDayIsHolidayDay     = 0;
        $secondDayIsHolidayDay   = 0;
        $thirdDayIsHolidayDay    = 0;
        $fourthDayIsHolidayDay   = 0;
        $nextNormalDayOfWeek     = '';

        // **** Change debugMode to "true", for get critical data:
        $debugMode              = false;
        $simulationModeTime     = ''; // This overrides the current servertime (as UTC time), and allow custom todaytime (e.g. = '16:00:00')

        $gmtOffset = intval($this->getRequest()->getParam('timezone_offset')) * -1;
if ($debugMode == true) Mage::log('gmtOffset = ' . $gmtOffset, null, 'cut_off_times.log');
        $readConnection = Mage::getSingleton('core/resource')->getConnection('core_read');
        $cutOffTimesActivated = intval(Mage::getStoreConfig('schrack/cutofftimes/cutofftimes_module_activated'));

        if ($cutOffTimesActivated == 1) {
            $currentDatetimeOfClient = '';
            $blockSpecialCase        = false;

            // Calculation of current weekday in client:
            $currentWeekdayOfClientDay   = date('d', strtotime($gmtOffset . ' hours'));
            $currentWeekdayOfClientMonth = date('m', strtotime($gmtOffset . ' hours'));
            $currentWeekdayOfClientYear  = date('Y', strtotime($gmtOffset . ' hours'));
            $currentDatetimeOfClient     = date('Y-m-d H:i:s', strtotime($gmtOffset . ' hours'));

            $jd=gregoriantojd($currentWeekdayOfClientMonth, $currentWeekdayOfClientDay, $currentWeekdayOfClientYear);
            $currentWeekdayOfClient = jddayofweek($jd,1);

            $yesterdayWeekdayOfClientDay   = date('d', strtotime('-1 days ' . $gmtOffset . ' hours'));
            $yesterdayWeekdayOfClientMonth = date('m', strtotime('-1 days ' . $gmtOffset . ' hours'));
            $yesterdayWeekdayOfClientYear  = date('Y', strtotime('-1 days ' . $gmtOffset . ' hours'));

            $jd=gregoriantojd($yesterdayWeekdayOfClientMonth, $yesterdayWeekdayOfClientDay, $yesterdayWeekdayOfClientYear);
            $yesterdayWeekdayOfClient = jddayofweek($jd,1);

if ($debugMode == true) Mage::log('yesterdayWeekdayOfClient => ' . $yesterdayWeekdayOfClient, null, 'cut_off_times.log');
if ($debugMode == true) Mage::log('currentWeekdayOfClient => ' . $currentWeekdayOfClient, null, 'cut_off_times.log');
if ($debugMode == true) Mage::log('currentDatetimeOfClient => ' . $currentDatetimeOfClient = date('Y-m-d H:i:s', strtotime($gmtOffset . ' hours')), null, 'cut_off_times.log');

            $expirationDay = array();
            // Get cut-off-times:
            $expirationDay['Monday']    = Mage::getStoreConfig('schrack/cutofftimes/holiday_monday_time');
            $expirationDay['Tuesday']   = Mage::getStoreConfig('schrack/cutofftimes/holiday_tuesday_time');
            $expirationDay['Wednesday'] = Mage::getStoreConfig('schrack/cutofftimes/holiday_wednesday_time');
            $expirationDay['Thursday']  = Mage::getStoreConfig('schrack/cutofftimes/holiday_thursday_time');
            $expirationDay['Friday']    = Mage::getStoreConfig('schrack/cutofftimes/holiday_friday_time');
            $expirationDay['Saturday']  = Mage::getStoreConfig('schrack/cutofftimes/holiday_saturday_time');
            $expirationDay['Sunday']    = Mage::getStoreConfig('schrack/cutofftimes/holiday_sunday_time');

            if ($expirationDay['Monday'] == '' || $expirationDay['Tuesday'] == '' || $expirationDay['Wednesday'] == '' ||
            $expirationDay['Thursday'] == '' || $expirationDay['Friday'] == '' || $expirationDay['Saturday'] == '' || $expirationDay['Sunday'] == '') {
                Mage::log('Cut Off Time was not defined', null, 'cut_off_times.log');
                echo json_encode(array('result' => 'cutofftimes_not_defined'));
                die();
            }

            // Get custom defined holiday-days from Database (Value-Separator = ';'):
            $customDefinedHolidays = Mage::getStoreConfig('schrack/cutofftimes/holiday_custom_time');

            if (stristr($customDefinedHolidays, ';')) {
                // Check, if all backend values contain valid entries:
                foreach($expirationDay as $weekDay => $cutOffTime) {
                    $valid = false;
                    // 1. Cut-Off-Time is defined by Time:
                    if (stristr($cutOffTime, ':')) {
                        preg_match('/^([0-9]|0[0-9]|1[0-9]|2[0-3]):[0-5][0-9]$/', $cutOffTime);
                        $valid = true;
                    }
                    // 2. Cut-Off-Time is defined by "X":
                    if ($cutOffTime == 'x' || $cutOffTime == 'X') {
                        $valid = true;
                    }

                    if ($valid == false) {
                        Mage::log('Cut Off Time was incorrectly defined -> ' . $weekDay . ' = ' . $cutOffTime, null, 'cut_off_times.log');
                        echo json_encode(array('result' => 'cut_off_time_was_incorrectly_defined'));
                        die();
                    }
                }

                $holidayDays = explode(';', $customDefinedHolidays);
                if (is_array($holidayDays) && !empty($holidayDays)) {
                    foreach ($holidayDays as $index => $day){
                        if (!$day) unset($holidayDays[$index]);
                    }
                }
            }

            // Get holiday-days from Database, based on Import Data (service or manual import):
            $query  = "SELECT holiday_datetime FROM national_holidays";

            $queryResult = $readConnection->query($query);

            if ($queryResult->rowCount() > 0) {
                foreach ($queryResult as $recordset) {
                    $holidayDays[] = str_replace(' 00:00:00', '', $recordset['holiday_datetime']);
                }
            }

            $checkDaysInFuture = 14;
            for ($i = 0; $i < $checkDaysInFuture; $i++) {
                $dateInTwoWeeksDay   = date('d', strtotime('+' . $i . ' days'));
                $dateInTwoWeeksMonth = date('m', strtotime('+' . $i . ' days'));
                $dateInTwoWeeksYear  = date('Y', strtotime('+' . $i . ' days'));
                $dateInTwoWeeksDate  = date('Y-m-d', strtotime('+' . $i . ' days'));
                $jd=gregoriantojd($dateInTwoWeeksMonth, $dateInTwoWeeksDay, $dateInTwoWeeksYear);
                $dayOfWeek = jddayofweek($jd,1);
                $twoWeekDays[$dateInTwoWeeksDate] = $dayOfWeek;
                if ($expirationDay[$dayOfWeek] == 'x' || $expirationDay[$dayOfWeek] == 'X') {
                    $holidayDays[] = $dateInTwoWeeksDate;
if ($debugMode == true) Mage::log($holidayDays, null, 'cut_off_times.log');
                }
            }

if ($debugMode == true) Mage::log('twoWeekDays:', null, 'cut_off_times.log');
if ($debugMode == true) Mage::log($twoWeekDays, null, 'cut_off_times.log');

            // Find out, if current day is national holiday-day:
            $currentDate = date('Y-m-d', strtotime('+' . $gmtOffset . ' hours')); // Starting with today as calculation timepoint
            $currentDateOfClient = date('Y-m-d', strtotime('+' . $gmtOffset . ' hours')); // Starting with today as calculation timepoint
if ($debugMode == true) Mage::log('currentDate = ' . $currentDate, null, 'cut_off_times.log');
            if (in_array($currentDate, $holidayDays)) {
                $currentDayIsHolidayDay = 1;
if ($debugMode == true) Mage::log('currentDayIsHolidayDay = true', null, 'cut_off_times.log');
            } else {
                $nextNormalDayOfWeek = $twoWeekDays[$currentDate]; // -> TODAY

                $expirationHourMinute = $expirationDay[$nextNormalDayOfWeek];
                list($expirationHour, $expirationMinute) = explode(':', $expirationHourMinute);

                if ($simulationModeTime) {
                    $currentServerTime = $simulationModeTime;
                } else {
                    $currentServerTime = date('H:i:s');
                }

                list($hour, $minute, $second) = explode(':', $currentServerTime);

                if ((intval($hour) + $gmtOffset) > intval($expirationHour)) {
                    $nextNormalDayOfWeek = '';
                }
            }

            // Calculation of viewing alternate message:
            $timestopperAlternateMessage  = 'Valid On Orders At Workingdays (Mo - Do) Until #MO-TH-CUTOFFTIME#';
            $timestopperAlternateMessage .= ' And Friday Until #FR-CUTOFFTIME#';
            $timestopperAlternateMessageTranslated = $this->__($timestopperAlternateMessage);
            $timestopperAlternateMessageTranslated = str_replace('#MO-TH-CUTOFFTIME#', $expirationDay['Monday'], $timestopperAlternateMessageTranslated);
            $timestopperAlternateMessageTranslated = str_replace('#FR-CUTOFFTIME#', $expirationDay['Friday'], $timestopperAlternateMessageTranslated);
            $criteria_1                  = false;
            $criteria_2                  = false;

            // Criteria 1: Current Day is s holiday day
            if (in_array($currentDateOfClient, $holidayDays)) {
                $criteria_1 = true;
            }

            // Criteria 2.1: Current day is no holiday day:
            if (!in_array($currentDateOfClient, $holidayDays)) {
                // Criteria 2.2: ...AND current day is between Mo-Do OR current day is Fr:
                if (isset($expirationDay[$currentWeekdayOfClient])) {
                    $currectCutOffTime = $expirationDay[$currentWeekdayOfClient] . ':00';
                    $currectCutOffTimestamp = strtotime($currentDateOfClient . ' ' . $currectCutOffTime);
                    $currectMidnightimestamp = strtotime($currentDateOfClient . ' 23:59:59');
                    // Criteria 2.3: ...AND current time is after cut-off-time and before 23:59:59:
                    if(strtotime($currentDatetimeOfClient) > $currectCutOffTimestamp && $currentDatetimeOfClient < $currectMidnightimestamp) {
                        $criteria_2 = true;
                    }
                }
            }

            if ($criteria_1 == true || $criteria_2 == true) {
                $remainTimeStopTime = strtotime($currentDateOfClient . '23:59:59') - strtotime($currentDatetimeOfClient);
            } else {
                $remainTimeStopTime = null;
            }

            $nextDate = date('Y-m-d', strtotime('+1 days'));
            if (in_array($nextDate, $holidayDays) && $nextNormalDayOfWeek == '') {
                $nextDayIsHolidayDay = 1;
            } else {
                if ($nextNormalDayOfWeek == '') $nextNormalDayOfWeek = $twoWeekDays[$nextDate];
            }

            $secondDate = date('Y-m-d', strtotime('+2 days'));
            if (in_array($secondDate, $holidayDays) && $nextNormalDayOfWeek == '') {
                $secondDayIsHolidayDay = 1;
            } else {
                if ($nextNormalDayOfWeek == '') $nextNormalDayOfWeek = $twoWeekDays[$secondDate];
            }

            $thirdDate = date('Y-m-d', strtotime('+3 days'));
            if (in_array($thirdDate, $holidayDays) && $nextNormalDayOfWeek == '') {
                $thirdDayIsHolidayDay = 1;
            } else {
                if ($nextNormalDayOfWeek == '') $nextNormalDayOfWeek = $twoWeekDays[$thirdDate];
            }

            $fourthDate = date('Y-m-d', strtotime('+4 days'));
            if (in_array($fourthDate, $holidayDays) && $nextNormalDayOfWeek == '') {
                $fourthDayIsHolidayDay = 1;
            } else {
                if ($nextNormalDayOfWeek == '') $nextNormalDayOfWeek = $twoWeekDays[$fourthDate];
            }

            $extraHoursForSpecialCase = 0;
            // Special Cases:
            // #0. Today is Day Off (holiday or day = x):
            if (isset($expirationDay[$currentWeekdayOfClient])) {
                $xDayCurrectDayCompare = $expirationDay[$currentWeekdayOfClient];
                if ($xDayCurrectDayCompare == 'x' || $xDayCurrectDayCompare == 'X') {
                    $blockSpecialCase = true;
                }
            }
            if ($currentDayIsHolidayDay == 1 && $blockSpecialCase == false) {
                $extraHoursForSpecialCase += 24;
if ($debugMode == true) Mage::log('Position x023', null, 'cut_off_times.log');
            }

            // #1. Next Day is Day Off (holiday or day = x):
            if ($nextDayIsHolidayDay == 1) {
                $extraHoursForSpecialCase += 24;
if ($debugMode == true) Mage::log('Position x024', null, 'cut_off_times.log');
            }
            // #2. Second Day is Day Off (holiday or day = x):
            if ($secondDayIsHolidayDay == 1) {
                $extraHoursForSpecialCase += 24;
if ($debugMode == true) Mage::log('Position x025', null, 'cut_off_times.log');
            }
            // #3. Third Day is Day Off (holiday or day = x):
            if ($thirdDayIsHolidayDay == 1) {
                $extraHoursForSpecialCase += 24;
if ($debugMode == true) Mage::log('Position x026', null, 'cut_off_times.log');
            }
            // #4. Fourth Day is Day Off (holiday or day = x):
            if ($fourthDayIsHolidayDay == 1) {
                $extraHoursForSpecialCase += 24;
if ($debugMode == true) Mage::log('Position x027', null, 'cut_off_times.log');
            }

            if ($simulationModeTime) {
                $currentServerTime = $simulationModeTime;
            } else {
                $currentServerTime = date('H:i:s');
            }
            list($hour, $minute, $second) = explode(':', $currentServerTime);

            // $clientTime = (intval($hour) + $gmtOffset) . ':' . $minute . ':' . $second;
            // $result = array('result' => $clientTime);

if ($debugMode == true) Mage::log('nextNormalDayOfWeek Position 1 => ' . $nextNormalDayOfWeek, null, 'cut_off_times.log');

            // Get coressponding expiring time for today
            if ($nextNormalDayOfWeek == '') {
                $jd=gregoriantojd(date('m'), date('d'),date('Y'));
                $nextNormalDayOfWeek = jddayofweek($jd,1);
            }

if ($debugMode == true) Mage::log('nextNormalDayOfWeek Position 2 => ' . $nextNormalDayOfWeek, null, 'cut_off_times.log');

            $expirationHourMinute = $expirationDay[$nextNormalDayOfWeek];
            list($expirationHour, $expirationMinute) = explode(':', $expirationHourMinute);

            if (intval($expirationMinute) > intval($minute)) {
                $remainMinute = (intval($expirationMinute) - intval($minute));
                $subtractHour = 0;
            } else {
                $remainMinute = (60 - intval($minute)) + intval($expirationMinute);
                $subtractHour = 1;
            }

            if (intval($expirationHour) > (intval($hour) + $gmtOffset)) {
                if ($nextDayIsHolidayDay == 1) {
                    $extraHoursForSpecialCase += 24;
if ($debugMode == true) Mage::log('Position x028', null, 'cut_off_times.log');
                }
                $remainHour = intval($expirationHour) - (intval($hour) + $gmtOffset) - $subtractHour + $extraHoursForSpecialCase;
            }

            if (intval($expirationHour) == (intval($hour) + $gmtOffset) && $subtractHour == 0) {
                $remainHour = intval($expirationHour) - (intval($hour) + $gmtOffset) - $subtractHour + $extraHoursForSpecialCase;
if ($debugMode == true) Mage::log('Position x029', null, 'cut_off_times.log');
            }

            if ((intval($hour) + $gmtOffset) > intval($expirationHour) || intval($expirationHour) == (intval($hour) + $gmtOffset) && $subtractHour == 1) {
                $remainHour = (24 + (intval($expirationHour) - (intval($hour) + $gmtOffset))) - $subtractHour + $extraHoursForSpecialCase;
if ($debugMode == true) Mage::log('Position x030' . ' -> expirationHour : ' . $expirationHour . ' *** hour : ' . $hour . ' *** gmtOffset : ' . $gmtOffset . ' *** subtractHour : ' . $subtractHour . ' *** extraHoursForSpecialCase : ' . $extraHoursForSpecialCase, null, 'cut_off_times.log');
            }

            if (isset($expirationDay[$currentWeekdayOfClient])) {
                $xDayCurrectDayCompare = $expirationDay[$currentWeekdayOfClient];
                if ($xDayCurrectDayCompare == 'x' || $xDayCurrectDayCompare == 'X') {
                    $remainHour = $remainHour - 24;
if ($debugMode == true) Mage::log('xDayCurrectDayCompare => -24h', null, 'cut_off_times.log');
                    if (isset($expirationDay[$yesterdayWeekdayOfClient])) {
                        $xDayYesterdayDayCompare = $expirationDay[$yesterdayWeekdayOfClient];

                        if ($xDayYesterdayDayCompare == 'x' || $xDayYesterdayDayCompare == 'X') {
if ($debugMode == true) Mage::log('xDayYesterdayDayCompare => +24h', null, 'cut_off_times.log');
                            $remainHour = $remainHour + 24;
                        }
                    }
                }
            }

            $remainTime = ($remainHour * 60 * 60) + ($remainMinute * 60);
            if ($remainTime == 60) $remainTime = 61;
            if ($remainTimeStopTime == 60) $remainTimeStopTime = 61;

            $result = array('result' => $remainTime, 'timestopper_time_active' => $remainTimeStopTime, 'timestopper_alternate_message' => $timestopperAlternateMessageTranslated);
        } else {
            $result = array('result' => 'cut_off_times_not_active');
        }

        echo json_encode($result);
    }


    public function getCategoryId4googleTagManagerAction() {
        if (!$this->getRequest()->isAjax()) {
            die("wrong method");
        }

        if (!$this->_validateFormKey()){
            die("wrong form key");
        }

        $sku = $this->getRequest()->getParam('sku');
        $sku = str_replace(array('”', '"'), '', $sku);
        $product = Mage::getModel('catalog/product')->loadBySku($sku);
        //Mage::log('Get Product Data from SKU = ' . $sku, null, 'fetched_product_data.log');
        if ($product == false || $product == null) {
            Mage::log('No Product found for SKU = ' . $sku, null, 'fetched_product_data.log');
        }
        //Mage::log($product, null, 'fetched_product_data.log');
        $category = $product->getCategoryId4googleTagManager();

        echo json_encode(array('result' => $category));
    }

}

?>
