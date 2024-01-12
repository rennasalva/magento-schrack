<?php

class Schracklive_SchrackCustomer_Helper_Phone extends Mage_Customer_Helper_Data {

	public function validatePhoneNumbers(array $data) {
		return array_merge(
						$this->_validatePhonenumber($data, 'schrack_telephone', $this->__('Incomplete telephone number')),
						$this->_validatePhonenumber($data, 'schrack_fax', $this->__('Incomplete fax number')),
						$this->_validatePhonenumber($data, 'schrack_mobile_phone', $this->__('Incomplete mobile phone number'))
		);
	}

	protected function _validatePhoneNumber(array $data, $field, $errorHeader) {
		$errors = array();
		if (empty($data[$field.'_localnumber'])) {
			if (!empty($data[$field.'_country']) ||
					!empty($data[$field.'_areacode']) ||
					!empty($data[$field.'_extension'])) {
				$errors[] = $errorHeader.': '.$this->__('local number is missing');
			}
		} else {
			if (empty($data[$field.'_country'])) {
				$errors[] = $errorHeader.': '.$this->__('country code is missing');
			}
			if (empty($data[$field.'_areacode'])) {
				$errors[] = $errorHeader.': '.$this->__('area code is missing');
			}
		}

		return $errors;
	}

	public function setPhoneNumbers(array $data, $customer) {
        $data['schrack_telephone'] = str_replace('+', '', $data['schrack_telephone']);
        $data['schrack_fax'] = str_replace('+', '', $data['schrack_fax']);
        $data['schrack_mobile_phone'] = str_replace('+', '', $data['schrack_mobile_phone']);

	    if (isset($data['schrack_telephone']) && $data['schrack_telephone'] != '') {
            $data['schrack_telephone'] = '+' . $data['schrack_telephone'];
	    }

        if (isset($data['schrack_fax']) && $data['schrack_fax'] != '') {
            $data['schrack_fax'] = '+' . $data['schrack_fax'];
        }

        if (isset($data['schrack_mobile_phone']) && $data['schrack_mobile_phone'] != '') {
            $data['schrack_mobile_phone'] = '+' . $data['schrack_mobile_phone'];
        }

		$customer->setSchrackTelephone($data['schrack_telephone']);
		$customer->setSchrackFax($data['schrack_fax']);
		$customer->setSchrackMobilePhone($data['schrack_mobile_phone']);
	}
}
