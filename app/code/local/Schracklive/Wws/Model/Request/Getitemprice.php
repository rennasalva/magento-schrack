<?php

class Schracklive_Wws_Model_Request_Getitemprice extends Schracklive_Wws_Model_Request_Abstract {

	protected $_soapMethod = 'get_item_price';
	/* arguments */
	protected $_wwsCustomerId = '';
	protected $_items = array();
	/* return values */
	protected $_priceInfos = array();

	public function __construct(array $arguments) {
		$this->_checkArgument($arguments, 'wwsCustomerId');
		$this->_checkArgument($arguments, 'products', 'array');

		$this->_wwsCustomerId = $arguments['wwsCustomerId'];
		$this->_items = $arguments['products'];
		parent::__construct($arguments);
	}

	protected function _buildArguments() {
		$arguments = array(
			'tt_item' => array(),
			$this->_wwsCustomerId,
		);
		foreach ($this->_items as $sku => $qty) {
		    if (!is_numeric($qty)) $qty = 1;
			$arguments['tt_item'][] = array(
				'ItemID' => $sku,
				'OrderQty' => $qty,
			);
		}
		$this->_soapArguments = $arguments;
	}

	protected function _isResponseValid() {
		if (!parent::_isResponseValid()) {
			return false;
		}

		$this->_isStatusOfAllRowsValid('tt_price');

		$this->_checkReturnedFieldsOfAllRows(
				'tt_price', array(
			'ItemID',
			'Price',
			'Surcharge',
			'AmountNet',
			'Currency',
			'PriceUnit',
			'PriceType',
			'IsValid',
			'IsHidden',
				)
		);

		$this->_checkReturnedFieldsOfAllRows(
				'tt_scale', array(
			'ItemID',
			'ScaleQty',
			'ScalePrice',
				)
		);

		return true;
	}

	protected function _processResponse() {
		foreach ($this->_soapResponse['tt_price'] as $priceInfo) {
			if (!isset($this->_items[$priceInfo->ItemID])) {
				throw Mage::exception('Schracklive_Wws', 'WWS sent an unexpected item id: '.$priceInfo->ItemID);
			}
			if ($priceInfo->_ok) {
                $fakedScaleArray = null;
                if ( Schracklive_SchrackCatalog_Helper_Preparator::PREPARATE_PRODUCTS ) {
                    $fakedScaleArray = Mage::helper('schrackcatalog/preparator')->prepareWwsPriceInfo($priceInfo);
                }
				$this->_priceInfos[$priceInfo->ItemID] = array(
					'currency' => $priceInfo->Currency, // not used yet
					'price' => (float)$priceInfo->Price, // without surcharge
                    'regularprice' => (float)$priceInfo->RegularPrice,
                    'promovalidto' => is_null($priceInfo->PromoValidTo) ? null : new Zend_Date($priceInfo->PromoValidTo,'Y-M-d'),
                            //DateTime::createFromFormat('Y-m-d',$priceInfo->PromoValidTo),
					'surcharge' => (float)$priceInfo->Surcharge,
					'priceunit' => (int)$priceInfo->PriceUnit,
					'amount' => (float)$priceInfo->AmountNet, // (price + surcharge) / priceunit * quantity
					'pricetype' => $priceInfo->PriceType, // 0=Listenpreis, 1=Konditionsprice
				);
                if ( $fakedScaleArray ) {
                    $this->_priceInfos[$priceInfo->ItemID]['prices'] = $fakedScaleArray;
                }
                if ( $priceInfo->xstatus === 221 ) { // "Artikel xyz nicht gefunden!" // who knows what happens if we try to catch other statuus here...
                    $this->_priceInfos[$priceInfo->ItemID]['xstatus'] = $priceInfo->xstatus;
                    $this->_priceInfos[$priceInfo->ItemID]['xerror'] = $priceInfo->xerror;
                }
			}
		}
		foreach ($this->_soapResponse['tt_scale'] as $tierPriceInfo) {
			if (!isset($this->_items[$tierPriceInfo->ItemID])) {
				throw Mage::exception('Schracklive_Wws', 'WWS sent an unexpected item id: '.$tierPriceInfo->ItemID);
			}
			if (isset($this->_priceInfos[$tierPriceInfo->ItemID])) {
				$this->_priceInfos[$tierPriceInfo->ItemID]['prices'][] = array(
					'qty' => $tierPriceInfo->ScaleQty,
					'price' => $tierPriceInfo->ScalePrice,
				);
			}
		}
		return true;
	}

	public function getPriceInfos() {
		return $this->_priceInfos;
	}

	protected function shouldMockWws () {
        $flag = Mage::getStoreConfig('schrack/wws/mock_soap_calls');
        return isset($flag) && intval($flag) === 1;
    }

	protected function mockWws () {
        $this->_soapResponse = array();
        $this->_soapResponse['exit_code'] = 1;
        $this->_soapResponse['exit_msg'] = '';
        $this->_soapResponse['tt_price'] = array();
        $priceCnt = 21.3;
        $i = 0;
        foreach ( $this->_soapArguments['tt_item'] as $requestItem ) {
            $sku = $requestItem['ItemID'];
            $isPrormo = in_array($sku,array('AS201040-5','BM018102--'));
            $obj = new stdClass();
            $obj->ItemID = $sku;
            $obj->Price = '' . $priceCnt;
            $obj->RegularPrice = $isPrormo ? '' . ($priceCnt * 1.5) : '0';
            $obj->PromoValidTo = $isPrormo ? '2017-08-31' : null;
            $obj->Surcharge = '0';
            $obj->PriceUnit = 1;
            $obj->Currency = 'EUR';
            $obj->PriceType = 0;
            $qty = $requestItem['OrderQty'];
            $obj->OrderQty = '' . $qty;
            $obj->AmountNet = '' . ($priceCnt * $qty);
            $obj->QtyUnit = '1';
            $obj->IsValid = 1;
            $obj->IsHidden = 0;
            $obj->xstatus = 1;
            $obj->xerror = '';
            $this->_soapResponse['tt_price'][] = $obj;

            // scales for every 2nd:
            if ( ++$i % 2 === 1 ) {
                $scalePrice = $priceCnt;
                for ( $i = 1; $i <= 10000; $i *= 10 ) {
                    $obj = new stdClass();
                    $obj->ItemID = $sku;
                    $obj->ScaleQty = $i;
                    $obj->ScalePrice = $scalePrice;
                    $scalePrice *= 0.8;
                    $this->_soapResponse['tt_scale'][] = $obj;
                }
            }
            $priceCnt += 0.7;
        }
    }
}
