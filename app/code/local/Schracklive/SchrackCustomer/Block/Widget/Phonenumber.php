<?php

class Schracklive_SchrackCustomer_Block_Widget_Phonenumber extends Mage_Customer_Block_Widget_Name {

	public function _construct() {
		parent::_construct();

		// default template location
		$this->setTemplate('customer/widget/phonenumber.phtml');
	}

	public function getClassName() {
		if (!$this->hasData('class_name')) {
			$this->setData('class_name', 'customer-addressname');
		}
		return $this->getData('class_name');
	}

	protected function splitNumberField($field) {

		$fieldValue = str_replace('+', '', $this->getObject()->getData($field));
		$numberArr = array();
		$codes = explode('/', $fieldValue);

		if (array_key_exists(1, $codes)) {
			$area = explode(' ', $codes[0]);
			$numbers = explode('-', $codes[1]);
			if (array_key_exists(1, $area)) {
				$numberArr['country'] = $area[0];
				$numberArr['area'] = $area[1];
			} elseif (strlen($area[0]) > 0 && $area[0][0] == '+') {
				$numberArr['country'] = $area[0];
			} else {
				$numberArr['area'] = $area[0];
			}
			$numberArr['localnumber'] = $numbers[0];
			if (array_key_exists(1, $numbers)) {
				$numberArr['extension'] = $numbers[1];
			}
		} else {
			$numbers = explode(' ', $fieldValue);
			$numberArr['country'] = $numbers[0];
			if (array_key_exists(1, $numbers)) {
				$numberArr['area'] = $numbers[1];
			}
			if (array_key_exists(2, $numbers)) {
				$numberArr['localnumber'] = $numbers[2];
			}
			if (array_key_exists(3, $numbers)) {
				$numberArr['extension'] = $numbers[3];
			}
		}
		return $numberArr;
	}

	public function getCountry($phonenumber) {
		$tmp = $this->splitNumberField($phonenumber);
		if (array_key_exists('country', $tmp)) {
			return $tmp['country'];
		}
	}

	public function getAreacode($phonenumber) {
		$tmp = $this->splitNumberField($phonenumber);
		if (array_key_exists('area', $tmp)) {
			return $tmp['area'];
		}
	}

	public function getLocalnumber($phonenumber) {
		$tmp = $this->splitNumberField($phonenumber);
		if (array_key_exists('localnumber', $tmp)) {
			return $tmp['localnumber'];
		}
	}

	public function getExtension($phonenumber) {
		$tmp = $this->splitNumberField($phonenumber);
		if (array_key_exists('extension', $tmp)) {
			return $tmp['extension'];
		}
	}

    public function getPhoneFaxMobileNumber($field) {
        $fieldValue = str_replace(array('+', ' ', '/', '-'), array('', '', '', ''), $this->getObject()->getData($field));
        return $fieldValue;
    }

}