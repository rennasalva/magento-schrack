<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package    Mage_Customer
 * @copyright   Copyright (c) 2009 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Customer address controller
 *
 * @category    Mage
 * @package    Mage_Customer
 * @author      Magento Core Team <core@magentocommerce.com>
 */

require_once 'Mage/Customer/controllers/AddressController.php';

class Schracklive_SchrackCustomer_AddressController extends Mage_Customer_AddressController
{
    /**
     * Retrieve customer session object
     *
     * @return Mage_Customer_Model_Session
     */
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function preDispatch()
    {
        parent::preDispatch();

        if (!Mage::getSingleton('customer/session')->authenticate($this)) {
            $this->setFlag('', 'no-dispatch', true);
        }
    }

    /**
     * Customer addresses list
     */
    public function indexAction()
    {
        $filter = $this->getRequest()->getParam('filter');
        Mage::register('address_filter',$filter);
        if (count($this->_getSession()->getCustomer()->getAddresses())) {
            $this->loadLayout();
            $this->_initLayoutMessages('customer/session');
            $this->_initLayoutMessages('catalog/session');

            if ($block = $this->getLayout()->getBlock('address_book')) {
                $block->setRefererUrl($this->_getRefererUrl());
            }
            $this->renderLayout();
        }
        else {
            $this->getResponse()->setRedirect(Mage::getUrl('*/*/new'));
        }
    }

    public function editAction()
    {
        Mage::log(__FILE__ . ':' . __LINE__,null,'count_address_modifications.log');
        $this->_forward('form');
    }

    public function newAction()
    {
        Mage::log(__FILE__ . ':' . __LINE__,null,'count_address_modifications.log');
        $this->_forward('form');
    }

    /**
     * Address book form
     */
    public function formAction()
    {
        $this->loadLayout();
        $this->_initLayoutMessages('customer/session');
        if ($navigationBlock = $this->getLayout()->getBlock('customer_account_navigation')) {
            $navigationBlock->setActive('customer/address');
        }
        $this->renderLayout();
    }

    public function formPostAction()
    {
        if (!$this->_validateFormKey()) {
            return $this->_redirect('*/*/');
        }

        // Save data
        if ($this->getRequest()->isPost()) {
            $addressPhoneData = "";
			$systemContact = $this->_getSession()->getCustomer()->getSystemContact();
			//die($systemContact->getId());

            // Create new default shipping address, based on billing address data:
            $defaultShipping = $this->getRequest()->getParam('default_shipping', false);
            $defaultShipping = ( $defaultShipping == false || $defaultShipping == '0' ? false : true );
            $addressMode = $this->getRequest()->getParam('address_mode', false);

            $data = $this->getRequest()->getPost();

            $dataTemp = array();
            foreach($data as $key => $value) {
                $value = preg_replace('/[\"\';\[\]<>\x00-\x09\x0B\x0C\x0E-\x1F\xE2\x7F\n\r]/','', $value);
                $dataTemp[$key] = $value;
            }
            $data = array();
            $data = $dataTemp;

            // DLA, 2015-11-23: don't believe, that the following "if" anytime can become true:
            if($addressMode == 'editdefaultbillingaddress' && $defaultShipping == true) {
                $newDefaultShippingAddress = Mage::getModel('customer/address')
                ->setData($data)
                ->setCustomerId($systemContact->getId())
                ->setIsDefaultBilling(false)
                ->setIsDefaultShipping(true);

                $newDefaultShippingAddress->setName1($data['name1']);
                $newDefaultShippingAddress->setName2($data['name2']);
                $newDefaultShippingAddress->setName3($data['name3']);

                $newDefaultShippingAddress->setId(null);
                $newDefaultShippingAddress->setSchrackWwsAddressNumber(null);

                // Schrack address types (schrack_type):
                // 1 --> Rechnungsadresse
                // 2 --> Firma
                // 3 --> Wohnhaus
                // 4 --> Lager
                // 5 --> Baustelle
                // 6 --> Sonstiges (OBSOLETE -> Deleted)
                // 7 --> Korrespondenzadresse (OBSOLETE -> Deleted)
                $newDefaultShippingAddress->setData('schrack_type', 2);

                try {
                    $addressValidation1 = $newDefaultShippingAddress->validate();
                    if (true === $addressValidation1) {
                        $newDefaultShippingAddress->save();
                        $this->_getSession()->addSuccess($this->__('The address was successfully saved'));
                        $this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure'=>true)));
                        return;
                    } else {
                        $this->_getSession()->setAddressFormData($data);
                        if (is_array($addressValidation1)) {
                            foreach ($addressValidation1 as $errorMessage) {
                                $this->_getSession()->addError($errorMessage);
                            }
                        } else {
                            $this->_getSession()->addError($this->__('Can\'t save address'));
                        }
                    }
                }
                catch (Mage_Core_Exception $e) {
                    $this->_getSession()->setAddressFormData($data)
                         ->addException($e, $e->getMessage());
                }
                catch (Exception $e) {
                    $this->_getSession()->setAddressFormData($data)
                         ->addException($e, $this->__('Can\'t save address'));
                }
            }

            $address = Mage::getModel('customer/address')
                ->setData($data)
                ->setCustomerId($systemContact->getId())
                ->setIsDefaultShipping($defaultShipping);

			$address->setName1($data['name1']);
			$address->setName2($data['name2']);
			$address->setName3($data['name3']);

            $addressId = $this->getRequest()->getParam('id');

            if (isset($data['phone_address_contact']) && $data['phone_address_contact'] != '') {
                $addressPhoneData = '+' . str_replace('+', '', $data['phone_address_contact']);
            }

            if ($addressId) {
                $customerAddress = $systemContact->getAddressById($addressId);
                if ($customerAddress->getId() && $customerAddress->getCustomerId() == $systemContact->getId()) {
                    $address->setId($addressId);
                }
                else {
                    $address->setId(null);
					$address->setSchrackWwsAddressNumber(null);
                }
            }
            else {
                $address->setId(null);
				$address->setSchrackWwsAddressNumber(null);
            }
            $oldDefaultShippingAddressId = $systemContact->getDefaultShipping();
            $currentDefaultShipping = $address->getId() == $oldDefaultShippingAddressId;
            if ( $defaultShipping && ! $currentDefaultShipping ) {
                // save and push it (later) also without other changes:
                $address->setData(Schracklive_SchrackCore_Helper_Model::DIRTY_FLAG_PSEUDO_ATTRIBUTE,true);
            }

            try {
                $addressValidation2 = $address->validate();
                if (isset($data['phone_address_contact'])) {
                    $address->setTelephone($addressPhoneData);
                }
                if (true === $addressValidation2) {
                    $address->save();
                    if ( $defaultShipping && ! $currentDefaultShipping ) {
                        // save and push the old default shipping address as well
                        $oldDefaultShippingAddress = Mage::getModel('customer/address')->load($oldDefaultShippingAddressId);
                        $oldDefaultShippingAddress->setData(Schracklive_SchrackCore_Helper_Model::DIRTY_FLAG_PSEUDO_ATTRIBUTE,true);
                        $oldDefaultShippingAddress->save();
                    }
                    $this->_getSession()->addSuccess($this->__('The address was successfully saved'));
                    $this->_redirectSuccess(Mage::getUrl('*/*/index', array('_secure'=>true)));
                    return;
                } else {
                    $this->_getSession()->setAddressFormData($data);
                    if (is_array($addressValidation2)) {
                        foreach ($addressValidation2 as $errorMessage) {
                            $this->_getSession()->addError($errorMessage);
                        }
                    } else {
                        $this->_getSession()->addError($this->__('Can\'t save address'));
                    }
                }
            }
            catch (Mage_Core_Exception $e) {
                $this->_getSession()->setAddressFormData($data)
                     ->addException($e, $e->getMessage());
            }
            catch (Exception $e) {
                $this->_getSession()->setAddressFormData($data)
                     ->addException($e, $this->__('Can\'t save address'));
            }
        }
        $this->_redirectError(Mage::getUrl('*/*/edit', array('id'=>$address->getId())));
    }

    public function deleteAction()
    {
        $addressId = $this->getRequest()->getParam('id', false);

        if ($addressId) {
            $address = Mage::getModel('customer/address')->load($addressId);

            // Validate address_id <=> customer_id
			$systemContact = Mage::getModel('customer/customer')->load(Mage::getSingleton('customer/session')->getCustomerId())->getSystemContact();

            if ($address->getCustomerId() != $systemContact->getId()) {
                $this->_getSession()->addError($this->__('The address does not belong to this customer'));
                $this->getResponse()->setRedirect(Mage::getUrl('*/*/index'));
                return;
            }

            $schrackWwsAddressNumber = $address->getSchrackWwsAddressNumber();
            // Validate address is not Rechnungsaddresse (must be urgently prevented):
            if ($schrackWwsAddressNumber === "0") {
                $this->_getSession()->addError($this->__('There was an error while deleting the address'));
                $this->getResponse()->setRedirect(Mage::getUrl('*/*/index'));
                return;
            }

            try {
                // Inform S4Y about address deletion:
                Mage::getSingleton('crm/connector')->putAddress($address, true);

                $address->delete();
                $this->_getSession()->addSuccess($this->__('The address was successfully deleted'));

            }
            catch (Exception $e){
                $this->_getSession()->addError($this->__('There was an error while deleting the address'));
            }
        }
        $this->getResponse()->setRedirect(Mage::getUrl('*/*/index'));
    }
}
