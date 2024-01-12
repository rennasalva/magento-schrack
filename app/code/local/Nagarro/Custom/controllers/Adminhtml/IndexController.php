<?php
require_once "Mage/Adminhtml/controllers/IndexController.php";  
class Nagarro_Custom_Adminhtml_IndexController extends Mage_Adminhtml_IndexController{
    /**
    * Forgot administrator password action
    */
    public function forgotpasswordAction()
    {
        $email = '';
        $params = $this->getRequest()->getParams();
        if (!empty($params)) {
            $email = (string)$this->getRequest()->getParam('email');

            if ($this->_validateFormKey()) {
                if (!empty($email)) {
                    $collection = Mage::getResourceModel('admin/user_collection');
                    /* @var $collection Mage_Admin_Model_Mysql4_User_Collection */
                    $collection->addFieldToFilter('email', $email);
                    $collection->load(false);

                    if ($collection->getSize() > 0) {
                        foreach ($collection as $item) {
                            $user = Mage::getModel('admin/user')->load($item->getId());
                            if ($user->getId()) {
                                $pass = Mage::helper('core')->getRandomString(7);
                                $user->setPassword($pass);
                                $user->save();
                                $user->setPlainPassword($pass);
                                $user->sendNewPasswordEmail();
                                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('adminhtml')->__('A new password was sent to your email address. Please check your email and click Back to Login.'));
                                $email = '';
                            }
                            break;
                        }
                    } else {
                        Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Cannot find the email address.'));
                    }
                } else {
                    $this->_getSession()->addError($this->__('Invalid email address.'));
                }
            } else {
                $this->_getSession()->addError($this->__('Invalid Form Key. Please refresh the page.'));
            }
        }

        $data = array(
            'email' => $email
        );
        $this->_outTemplate('forgotpassword', $data);
    }


}
				