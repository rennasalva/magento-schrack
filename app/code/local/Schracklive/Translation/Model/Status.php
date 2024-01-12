<?php

class Schracklive_Translation_Model_Status extends Varien_Object {

	const STATUS_ORIGINAL = 1;
	const STATUS_TRANSLATED = 2;

	static public function getOptionArray() {
		return array(
			self::STATUS_ORIGINAL => Mage::helper('translation')->__('Not translated'),
			self::STATUS_TRANSLATED => Mage::helper('translation')->__('Translated')
		);
	}

}

?>