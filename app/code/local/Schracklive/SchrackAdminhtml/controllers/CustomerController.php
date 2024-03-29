<?php

require_once('Mage/Adminhtml/controllers/CustomerController.php');

class Schracklive_SchrackAdminhtml_CustomerController extends Mage_Adminhtml_CustomerController {

    /**
     * Save customer action
     */
    public function saveAction()
    {
        $data = $this->getRequest()->getPost();
        if ($data) {
            $redirectBack = $this->getRequest()->getParam('back', false);
            $this->_initCustomer('customer_id');

            /** @var $customer Mage_Customer_Model_Customer */
            $customer = Mage::registry('current_customer');

            /** @var $customerForm Mage_Customer_Model_Form */
            $customerForm = Mage::getModel('customer/form');
            $customerForm->setEntity($customer)
                ->setFormCode('adminhtml_customer')
                ->ignoreInvisible(false)
            ;

            $formData = $customerForm->extractData($this->getRequest(), 'account');

            // Handle 'disable auto_group_change' attribute
            if (isset($formData['disable_auto_group_change'])) {
                $formData['disable_auto_group_change'] = empty($formData['disable_auto_group_change']) ? '0' : '1';
            }

            $errors = null;
            if ($customer->getId()&& !empty($data['account']['new_password'])
                && Mage::helper('customer')->getIsRequireAdminUserToChangeUserPassword()
            ) {
                //Validate current admin password
                if (isset($data['account']['current_password'])) {
                    $currentPassword = $data['account']['current_password'];
                } else {
                    $currentPassword = null;
                }
                unset($data['account']['current_password']);
                $errors = $this->_validateCurrentPassword($currentPassword);
            }

            if (!is_array($errors)) {
                $errors = $customerForm->validateData($formData);
            }

            if ($errors !== true) {
                foreach ($errors as $error) {
                    $this->_getSession()->addError($error);
                }
                $this->_getSession()->setCustomerData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array('id' => $customer->getId())));
                return;
            }

            $customerForm->compactData($formData);

            // Unset template data
            if (isset($data['address']['_template_'])) {
                unset($data['address']['_template_']);
            }

            $modifiedAddresses = array();
            if (!empty($data['address'])) {
                /** @var $addressForm Mage_Customer_Model_Form */
                $addressForm = Mage::getModel('customer/form');
                $addressForm->setFormCode('adminhtml_customer_address')->ignoreInvisible(false);

                foreach (array_keys($data['address']) as $index) {
                    $address = $customer->getAddressItemById($index);
                    if (!$address) {
                        $address = Mage::getModel('customer/address');
                    }

                    $requestScope = sprintf('address/%s', $index);
                    $formData = $addressForm->setEntity($address)
                        ->extractData($this->getRequest(), $requestScope);

                    // Set default billing and shipping flags to address
                    $isDefaultBilling = isset($data['account']['default_billing'])
                        && $data['account']['default_billing'] == $index;
                    $address->setIsDefaultBilling($isDefaultBilling);
                    $isDefaultShipping = isset($data['account']['default_shipping'])
                        && $data['account']['default_shipping'] == $index;
                    $address->setIsDefaultShipping($isDefaultShipping);

                    $errors = $addressForm->validateData($formData);
                    if ($errors !== true) {
                        foreach ($errors as $error) {
                            $this->_getSession()->addError($error);
                        }
                        $this->_getSession()->setCustomerData($data);
                        $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array(
                            'id' => $customer->getId())
                        ));
                        return;
                    }

                    $addressForm->compactData($formData);

                    // Set post_index for detect default billing and shipping addresses
                    $address->setPostIndex($index);

                    if ($address->getId()) {
                        $modifiedAddresses[] = $address->getId();
                    } else {
                        $customer->addAddress($address);
                    }
                }
            }

            // Default billing and shipping
            if (isset($data['account']['default_billing'])) {
                $customer->setData('default_billing', $data['account']['default_billing']);
            }
            if (isset($data['account']['default_shipping'])) {
                $customer->setData('default_shipping', $data['account']['default_shipping']);
            }
            if (isset($data['account']['confirmation'])) {
                $customer->setData('confirmation', $data['account']['confirmation']);
            }

            // Mark not modified customer addresses for delete
            foreach ($customer->getAddressesCollection() as $customerAddress) {
                if ($customerAddress->getId() && !in_array($customerAddress->getId(), $modifiedAddresses)) {
                    $customerAddress->setData('_deleted', true);
                }
            }

            if (Mage::getSingleton('admin/session')->isAllowed('customer/newsletter')
                && !$customer->getConfirmation()
            ) {
                $customer->setIsSubscribed(isset($data['subscription']));
            }

            if (isset($data['account']['sendemail_store_id'])) {
                $customer->setSendemailStoreId($data['account']['sendemail_store_id']);
            }

            $isNewCustomer = $customer->isObjectNew();
            try {
                $sendPassToEmail = false;
                // Force new customer confirmation
                if ($isNewCustomer) {
                    $customer->setPassword($data['account']['password']);
                    $customer->setPasswordCreatedAt(time());
                    $customer->setForceConfirmed(true);
                    if ($customer->getPassword() == 'auto') {
                        $sendPassToEmail = true;
                        $customer->setPassword($customer->generatePassword());
                    }
                }

                Mage::dispatchEvent('adminhtml_customer_prepare_save', array(
                    'customer'  => $customer,
                    'request'   => $this->getRequest()
                ));

                $customer->save();

                // Send welcome email
                if ($customer->getWebsiteId() && (isset($data['account']['sendemail']) || $sendPassToEmail)) {
                    $storeId = $customer->getSendemailStoreId();
                    if ($isNewCustomer) {
                        $customer->sendNewAccountEmail('registered', '', $storeId);
                    } elseif ((!$customer->getConfirmation())) {
                        // Confirm not confirmed customer
                        $customer->sendNewAccountEmail('confirmed', '', $storeId);
                    }
                }

                if (!empty($data['account']['new_password'])) {
                    $newPassword = $data['account']['new_password'];
                    if ($newPassword == 'auto') {
					    $customer->setSchrackChangepwToken($customer->getRandomConfirmationKey());
					    $customer->save();
						$customer->sendResetPasswordEmail();
                    } else {
                        $customer->changePassword($newPassword);
                        $customer->sendPasswordReminderEmail();
                    }
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('The customer has been saved.')
                );
                Mage::dispatchEvent('adminhtml_customer_save_after', array(
                    'customer'  => $customer,
                    'request'   => $this->getRequest()
                ));

                if ($redirectBack) {
                    $this->_redirect('*/*/edit', array(
                        'id' => $customer->getId(),
                        '_current' => true
                    ));
                    return;
                }
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_getSession()->setCustomerData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array('id' => $customer->getId())));
                return;
            } catch (Exception $e) {
                $this->_getSession()->addException($e,
                    Mage::helper('adminhtml')->__('An error occurred while saving the customer.'));
                $this->_getSession()->setCustomerData($data);
                $this->getResponse()->setRedirect($this->getUrl('*/customer/edit', array('id'=>$customer->getId())));
                return;
            }
        }
        $this->getResponse()->setRedirect($this->getUrl('*/customer'));
    }

	/**
	 * Customer edit action
	 *
	 * @todo remove this custom action for Magento 1.4.2 or later
	 */
	public function editAction() {
		$this->_initCustomer();
		$this->loadLayout();

		$customer = Mage::registry('current_customer');

		// set entered data if was error when we do save
		$data = Mage::getSingleton('adminhtml/session')->getCustomerData(true);

        $schrackTelephone   = $customer->getData('schrack_telephone');
        $schrackFax         = $customer->getData('schrack_fax');
        $schrackMobilePhone = $customer->getData('schrack_mobile_phone');

        if ($schrackTelephone) {
            $customer->setData('schrack_telephone', '+' . str_replace(array('+', ' ', '/', '-'), array('', '', '', ''), $schrackTelephone));
        }
        if ($schrackFax) {
            $customer->setData('schrack_fax', '+' . str_replace(array('+', ' ', '/', '-'), array('', '', '', ''), $schrackFax));
        }
        if ($schrackMobilePhone) {
            $customer->setData('schrack_mobile_phone', '+' . str_replace(array('+', ' ', '/', '-'), array('', '', '', ''), $schrackMobilePhone));
        }

        $customerAddresses = $customer->getAddresses();
        if (is_array($customerAddresses) && !empty($customerAddresses)) {
            foreach ($customerAddresses as $address) {
                $addressTelephone    = $address->getData('telephone');
                $addressFax          = $address->getData('fax');
                $addressContactPhone = $address->getData('schrack_additional_phone');

                if ($addressTelephone) {
                    $address->setData('telephone', '+' . str_replace(array('+', ' ', '/', '-'), array('', '', '', ''), $addressTelephone));
                }
                if ($addressFax) {
                    $address->setData('fax', '+' . str_replace(array('+', ' ', '/', '-'), array('', '', '', ''), $addressFax));
                }
                if ($addressContactPhone) {
                    $address->setData('schrack_additional_phone', '+' . str_replace(array('+', ' ', '/', '-'), array('', '', '', ''), $addressContactPhone));
                }
            }
        }

		if (isset($data['account'])) {
			$customer->addData($data['account']);
		}
		if (isset($data['address']) && is_array($data['address'])) {
			// Schracklive: add address only if necessary (bug fix)
			foreach ($data['address'] as $addressId => $address) {
				if ($addressId == '_template_') {
					continue;
				}

				if ($addressModel = $customer->getAddressItemById($addressId)) {
					if ($addressModel->getId()) {
						$addressModel->addData($address);
					} else {
						$addressModel->setData($address)
								->setId($addressId);
						$customer->addAddress($addressModel);
					}
				}
			}
		}

		$this->_title($customer->getId() ? $customer->getName() : $this->__('New Customer'));

		/**
		 * Set active menu item
		 */
		$this->_setActiveMenu('customer/new');

		$this->renderLayout();
	}

}
