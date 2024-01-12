<?php

class Schracklive_SchrackCustomer_Block_Address_Renderer_Account extends Mage_Customer_Block_Address_Renderer_Default {

	public function render(Mage_Customer_Model_Address_Abstract $address, $format=null) {
		switch ($this->getType()->getCode()) {
			case 'oneline':
				return parent::render($address, $format);
		}

		$address->getRegion();
		$address->getCountry();
		$address->explodeStreetAddress();

		$formater = new Varien_Filter_Template();
		$data = $address->getData();
		if ($this->getType()->getHtmlEscape()) {
			foreach ($data as $key => $value) {
				if (is_object($value)) {
					unset($data[$key]);
				} else {
					$data[$key] = $this->escapeHtml($value);
				}
			}
		}

		// Schrack Live: add switch variable (for "if")
		$data['accountAddress'] = $address->belongsToAccount();

		$formater->setVariables(array_merge($data, array('country' => $address->getCountryModel()->getName())));

		$format = !is_null($format) ? $format : $this->getFormat($address);

		//$format = '{{var lastname}}<br />{{var middlename}}<br />{{var firstname}} <br />' . $format;

		return $formater->filter($format);
	}

}
