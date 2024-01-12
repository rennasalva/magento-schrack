<?php
/**
 * You are allowed to use this API in your web application.
 *
 * Copyright (C) 2018 by customweb GmbH
 *
 * This program is licenced under the customweb software licence. With the
 * purchase or the installation of the software in your application you
 * accept the licence agreement. The allowed usage is outlined in the
 * customweb software licence which can be found under
 * http://www.sellxed.com/en/software-license-agreement
 *
 * Any modification or distribution is strictly forbidden. The license
 * grants you the installation in one application. For multiuse you will need
 * to purchase further licences at http://www.sellxed.com/shop.
 *
 * See the customweb software licence agreement for more details.
 *
 *
 * @category	Customweb
 * @package		Customweb_PayUnityCw
 */

class Customweb_PayUnityCw_Model_Source_Activebrands
{
	public function toOptionArray()
	{
		$options = array(
			array('value' => 'AMEX', 'label' => Mage::helper('adminhtml')->__("American Express")),
			array('value' => 'BCMC', 'label' => Mage::helper('adminhtml')->__("Bancontact")),
			array('value' => 'CARTEBLEUE', 'label' => Mage::helper('adminhtml')->__("Carte Bleue")),
			array('value' => 'DANKORT', 'label' => Mage::helper('adminhtml')->__("Dankort")),
			array('value' => 'DINERS', 'label' => Mage::helper('adminhtml')->__("Diners Club")),
			array('value' => 'DISCOVER', 'label' => Mage::helper('adminhtml')->__("Discover")),
			array('value' => 'JCB', 'label' => Mage::helper('adminhtml')->__("JCB")),
			array('value' => 'MAESTRO', 'label' => Mage::helper('adminhtml')->__("Maestro")),
			array('value' => 'MASTER', 'label' => Mage::helper('adminhtml')->__("MasterCard")),
			array('value' => 'MASTERDEBIT', 'label' => Mage::helper('adminhtml')->__("MasteCcard (Debit)")),
			array('value' => 'VISA', 'label' => Mage::helper('adminhtml')->__("VISA")),
			array('value' => 'VISADEBIT', 'label' => Mage::helper('adminhtml')->__("VISA (Debit)")),
			array('value' => 'VISAELECTRON', 'label' => Mage::helper('adminhtml')->__("VISA Electron")),
			array('value' => 'VPAY', 'label' => Mage::helper('adminhtml')->__("V PAY"))
		);
		return $options;
	}
}
