<?php

class Schracklive_Account_Block_Adminhtml_Account_Edit_Tab_Addresses extends Mage_Core_Block_Text // Mage_Adminhtml_Block_Widget // 
{

    protected function _construct()
    {
        parent::_construct();

		$account = Mage::registry('account_account_data');

		$text = '';
		foreach ($account->getAddresses() as $address) {
			$text .= $address->format('html');
		}
		$this->setText($text);

		/*
		$this->setTemplate('account/tab/addresses.phtml');
		$this->assign('addressCollection', array()); // array(Mage::getModel('account/address'))); // $account->getAddresses());
		*/
    }

	protected function isReadonly()
	{
	/*
        $account = Mage::registry('account_account_data');
        return $account->isReadonly();
	*/
		return false;
	}

}
