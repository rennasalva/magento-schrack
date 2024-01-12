<?php

/**
 *  * You are allowed to use this API in your web application.
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
 */

//require_once 'Customweb/Payment/BackendOperation/Form/Abstract.php';
//require_once 'Customweb/Form/Element.php';
//require_once 'Customweb/Form/ElementGroup.php';
//require_once 'Customweb/I18n/Translation.php';
//require_once 'Customweb/Form/Control/Html.php';
//require_once 'Customweb/Form/WideElement.php';



/**
 * @BackendForm
 */
class Customweb_PayUnity_BackendOperation_Form_Setup extends Customweb_Payment_BackendOperation_Form_Abstract {

	public function getTitle(){
		return Customweb_I18n_Translation::__("Setup");
	}

	public function getElementGroups(){
		return array(
			$this->getSetupGroup(),
			$this->getUrlGroup(),
		);
	}
		
	private function getUrlGroup() {
		$group = new Customweb_Form_ElementGroup();
		$group->setTitle('Webhook Configuration');
		
		$control = new Customweb_Form_Control_Html('info', Customweb_I18n_Translation::__(
				"You can configure under the Webhook in the backend of PayUnity under Administration > Webhooks."
				));
		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);
				
		$control = new Customweb_Form_Control_Html('webhookURL', $this->getEndpointAdapter()->getUrl('process', 'webhook'));
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__("Webhook URL"), $control);
		$element->setRequired(false);
		$group->addElement($element);
		
		$control = new Customweb_Form_Control_Html('notificationTypes', Customweb_I18n_Translation::__("Select 'PA' and 'DB' for PAYMENTS and 'ALL' for REGISTRATIONS."));
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__("Notification types"), $control);
		$element->setRequired(false);
		$group->addElement($element);
		
		$control = new Customweb_Form_Control_Html('includedFields', Customweb_I18n_Translation::__("Select 'ALL'"));
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__("Fields to include"), $control);
		$element->setRequired(false);
		$group->addElement($element);
		
		$control = new Customweb_Form_Control_Html('wrapper', Customweb_I18n_Translation::__("Set to 'none'."));
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__("Wrapper for encrypted notifcation"), $control);
		$element->setRequired(false);
		$group->addElement($element);
		
		$control = new Customweb_Form_Control_Html('secret', Customweb_I18n_Translation::__('Copy the shown encryption key into the configuration.'));
		$element = new Customweb_Form_Element(Customweb_I18n_Translation::__("Secret for Encryption"), $control);
		$element->setRequired(false);
		$group->addElement($element);
		
		return $group;
	}
	

	private function getSetupGroup(){
		$group = new Customweb_Form_ElementGroup();
		$group->setTitle(Customweb_I18n_Translation::__("Short Installation Instructions:"));
		
		$control = new Customweb_Form_Control_Html('description', 
				Customweb_I18n_Translation::__(
						'This is a brief instruction of the main and most important installation steps, which need to be performed when installing the PayUnity module. For detailed instructions regarding additional and optional settings, please refer to the enclosed instructions in the zip. '));
		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);
		
		$control = new Customweb_Form_Control_Html('steps', $this->createOrderedList($this->getSteps()));
		
		$element = new Customweb_Form_WideElement($control);
		$group->addElement($element);
		return $group;
	}

	private function getSteps(){
		return array(
			Customweb_I18n_Translation::__(
					'Enter the Security Sender, UserID and Password that is provided to you by PayUnity.'),
			Customweb_I18n_Translation::__(
					'Activate the payment method and Enter the Channel ID in the respective field for every payment method that you activate.') 
		);
	}

	private function createOrderedList(array $steps){
		$list = '<ol>';
		foreach ($steps as $step) {
			$list .= "<li>$step</li>";
		}
		$list .= '</ol>';
		return $list;
	}
}