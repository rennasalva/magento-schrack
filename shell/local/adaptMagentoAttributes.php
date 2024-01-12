<?php

require_once 'shell.php';

class Schracklive_Shell_AdaptMagentoAttributes extends Schracklive_Shell {

	protected $store;
	protected $eavConfig;

	protected function adapt() {
		Mage_Core_Model_Resource_Setup::applyAllUpdates();
		Mage_Core_Model_Resource_Setup::applyAllDataUpdates();

		$this->store = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);
		$this->eavConfig = Mage::getSingleton('eav/config');
		$this->adaptCustomerAttributes();
		$this->adaptCustomerAddressAttributes();
	}

	protected function adaptCustomerAttributes() {
		$attributes = array(
			'prefix' => array(
				'validate_rules' => array(
					'max_text_length' => 30,
				),
			),
			'firstname' => array(
				'is_required' => 0,
				'validate_rules' => array(
					'max_text_length' => 30,
				),
			),
			'middlename' => array(
				'validate_rules' => array(
					'max_text_length' => 30,
				),
			),
			'lastname' => array(
				'is_required' => 0,
				'validate_rules' => array(
					'max_text_length' => 30,
				),
			),
		);
		$getForms = function ($data) {
					$usedInForms = array(
						'customer_account_create',
						'customer_account_edit',
						'checkout_register',
					);
					if (!empty($data['adminhtml_only'])) {
						$usedInForms = array('adminhtml_customer');
					} else {
						$usedInForms[] = 'adminhtml_customer';
					}
					if (!empty($data['admin_checkout'])) {
						$usedInForms[] = 'adminhtml_checkout';
					}
					return $usedInForms;
				};
		$this->adaptModelAttributes('customer', $attributes, $getForms);
	}

	protected function adaptCustomerAddressAttributes() {
		$attributes = array(
			'prefix' => array(
				'validate_rules' => array(
					'max_text_length' => 30,
				),
			),
			'firstname' => array(
				'is_required' => 0,
				'validate_rules' => array(
					'max_text_length' => 30,
				),
			),
			'middlename' => array(
				'validate_rules' => array(
					'max_text_length' => 30,
				),
			),
			'lastname' => array(
				'is_required' => 0,
				'validate_rules' => array(
					'max_text_length' => 30,
				),
			),
			'telephone' => array(
				'is_required' => 0,
				'validate_rules' => array(
					'max_text_length' => 30,
				),
			),
			'fax' => array(
				'is_required' => 0,
				'validate_rules' => array(
					'max_text_length' => 30,
				),
			),
			'postcode' => array(
				'is_required' => 0,
			),
		);
		$getForms = function ($data) {
					$usedInForms = array(
						'adminhtml_customer_address',
						'customer_address_edit',
						'customer_register_address'
					);
					return $usedInForms;
				};
		$this->adaptModelAttributes('customer_address', $attributes, $getForms);
	}

	protected function adaptModelAttributes($model, array $attributes, $usedInFormsCallback) {
		foreach ($attributes as $attributeCode => $data) {
			$attribute = $this->eavConfig->getAttribute($model, $attributeCode);
			$attribute->setWebsite($this->store->getWebsite());
			$attribute->addData($data);
			$usedInForms = call_user_func($usedInFormsCallback, $data);
			$attribute->setData('used_in_forms', $usedInForms);
			$attribute->save();
		}
	}

	public function usageHelp() {
		return <<<USAGE
Usage:  php -f adaptMagentoAttributes.php -- [options]

Modifies validation rules etc. after a Magento upgrade.

  adapt        Adapt Magento attributes to Schrack demands
  help         This help

USAGE;
	}

	public function run() {
		if ($this->getArg('adapt')) {
			$this->adapt();
		} else {
			echo $this->usageHelp();
		}
	}

}

$shell = new Schracklive_Shell_AdaptMagentoAttributes();
$shell->run();
