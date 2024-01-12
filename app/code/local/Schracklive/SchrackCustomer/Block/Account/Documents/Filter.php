<?php

class Schracklive_SchrackCustomer_Block_Account_Documents_Filter extends Schracklive_SchrackCustomer_Block_Account_Documents {

    protected $_sessionParams;

    /**
     *
     * @var Schracklive_SchrackSales_Helper_Order
     */
    private $_orderHelper;

    public function __construct() {
        parent::__construct();
        $this->_sessionParams = Mage::getSingleton('customer/session')->getData('documentParams');
        $this->_orderHelper = Mage::helper('schracksales/order');
        $this->_arrayHelper = Mage::helper('schrackcore/array');
    }

    /**
     * find out the action name we were called for, so we can generically
     * display the correct filtering options
     * @return boolean
     */
    protected function getActionName() {
        return $this->getRequest()->getActionName();
    }

    protected function getTypeOffers() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'offers') === '1');
        return $isParamSet;
    }

    protected function getTypeOrders() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'orders') === '1');
        return $isParamSet;
    }

    protected function getTypeDeliveries() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'deliveries') === '1');
        return $isParamSet;
    }

    protected function getTypeInvoices() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'invoices') === '1');
        return $isParamSet;
    }

    protected function getTypeCreditmemos() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'creditmemos') === '1');
        return $isParamSet;
    }

    protected function getStatusOffered() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'offered') === '1');
        return $isParamSet;
    }

    protected function getStatusOrdered() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'ordered') === '1');
        return $isParamSet;
    }

    protected function getStatusOpen() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'open') === '1');
        return $isParamSet;
    }

    protected function getStatusCommissioned() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'commissioned') === '1');
        return $isParamSet;
    }

    protected function getStatusDelivered() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'delivered') === '1');
        return $isParamSet;
    }

    protected function getStatusInvoiced() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'invoiced') === '1');
        return $isParamSet;
    }

    protected function getStatusCredited() {
        $isParamSet = ($this->_arrayHelper->arrayDefault($this->_sessionParams, 'credited') === '1');
        return $isParamSet;
    }

    private function _getSearchParameters() {
        switch ($this->getRequest()->getActionName()) {
            case 'offers':
                $presetKey = 'type_offers';
                break;
            case 'orders':
                $presetKey = 'type_orders';
                break;
            case 'shipments':
                $presetKey = 'type_deliveries';
                break;
            case 'invoices':
                $presetKey = 'type_invoices';
                break;
            case 'creditmemos':
                $presetKey = 'type_creditmemos';
                break;
            default:
                $presetKey = null;
        }
        $searchRequest = $this->_getSearchRequest($presetKey);


        /**
         * @var Schracklive_SchrackSales_Helper_Order_SearchParameters
         */
        $searchParameters = new Schracklive_SchrackSales_Helper_Order_SearchParameters();

        $searchParameters->getOfferDocs = $searchRequest['type_offers'];
        $searchParameters->getOrderDocs = $searchRequest['type_orders'];
        $searchParameters->getInvoiceDocs = $searchRequest['type_invoices'];
        $searchParameters->getDeliveryDocs = $searchRequest['type_deliveries'];
        $searchParameters->getCreditMemoDocs = $searchRequest['type_creditmemos'];

        $searchParameters->isOffered = $searchRequest['status_open'];
        $searchParameters->isOrdered = $searchRequest['status_ordered'];
        $searchParameters->isCommissioned = $searchRequest['status_commissioned'];
        $searchParameters->isDelivered = $searchRequest['status_delivered'];
        $searchParameters->isInvoiced = $searchRequest['status_invoiced'];
        $searchParameters->isCredited = $searchRequest['status_credited'];
        $searchParameters->fromDate = $searchRequest['date_from'];
        $searchParameters->toDate = $searchRequest['date_to'];
        return $searchParameters;
    }

    protected function getCountOfferDocs() {
        return $this->_orderHelper->getCountOfferDocs($this->_getSearchParameters());
    }

    protected function getCountOrderDocs() {
        return $this->_orderHelper->getCountOrderDocs($this->_getSearchParameters());
    }

    protected function getCountDeliveryDocs() {
        return $this->_orderHelper->getCountDeliveryDocs($this->_getSearchParameters());
    }

    protected function getCountInvoiceDocs() {
        return $this->_orderHelper->getCountInvoiceDocs($this->_getSearchParameters());
    }

    protected function getCountCreditMemoDocs() {
        return $this->_orderHelper->getCountCreditMemoDocs($this->_getSearchParameters());
    }

    protected function getCountOpen() {
        return $this->_orderHelper->getCountOpen($this->_getSearchParameters());
    }

    protected function getCountOffers() {
        return $this->_orderHelper->getCountOffers($this->_getSearchParameters());
    }

    protected function getCountCommissioned() {
        return $this->_orderHelper->getCountCommissioned($this->_getSearchParameters());
    }

    protected function getCountDelivered() {
        return $this->_orderHelper->getCountDelivered($this->_getSearchParameters());
    }

    protected function getCountInvoiced() {
        return $this->_orderHelper->getCountInvoiced($this->_getSearchParameters());
    }

    protected function getCountCredited() {
        return $this->_orderHelper->getCountCredited($this->_getSearchParameters());
    }

    protected function createCategoryInput($category, $name) {
        $param = $this->_arrayHelper->arrayDefault($this->_sessionParams, $name);
        return <<<EOT
<input type="hidden" name="$name" id="$category-$name" value="$param" />
EOT;
    }

    protected function createFilterSpan($name, $isActive, $count, $formName, $categoryName) {
        $text = "{$this->__($name)} <span class=\"count\">($count)</span>";
        $onClick = "setSwitch('$formName', '$categoryName', '$name');return false;";
        return $this->createBasicFilterSpan($categoryName, $name, $text, $isActive, $onClick);
    }

    protected function createBasicFilterSpan($class, $name, $text, $isActive, $onClick) {
        $thisLink = $this->getUrl('*/*/*');
        if ($isActive) {
            return <<<EOT
<span class="$class active" id="$class-$name">
    <a href="$thisLink" onClick="$onClick">
        $text
    </a>
</span>    
EOT;
        } else {
            return <<<EOT
<span class="$class" id="$class-$name">
    <a id="$name" href="$thisLink" onClick="$onClick">
        $text
    </a>
</span>    
EOT;
        }
    }
    	protected function createFilterRow($name, $isActive, $count, $formName, $categoryName) {	// Written by Nagarro to return RWD filter format HTML
		$text = "{$this->__($name)} <span class=\"count\">($count)</span>";
		$onClick = "setSwitch('$formName', '$categoryName', '$name');return false;";
		return $this->createBasicFilterRow($categoryName, $name, $count, $isActive, $onClick);
	}

	protected function createBasicFilterRow($class, $name, $count, $isActive, $onClick) {	// Written by Nagarro to return RWD filter format Radio Button format HTML
            $thisLink = $this->getUrl('*/*/*');
            $name = ucwords($name);
            return <<<EOT
<input type="radio" onClick="$onClick"> $name ($count)    
EOT;
                
	}
	}


	
	
