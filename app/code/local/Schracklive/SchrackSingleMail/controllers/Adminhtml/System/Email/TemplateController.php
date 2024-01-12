<?php

include_once("Mage/Adminhtml/controllers/System/Email/TemplateController.php");

class Schracklive_SchrackSingleMail_Adminhtml_System_Email_TemplateController
    extends Mage_Adminhtml_System_Email_TemplateController {

// Methode source copied from Magento core base class:
    public function saveAction()
    {
        $request = $this->getRequest();
        $id = $this->getRequest()->getParam('id');

        $template = $this->_initTemplate('id');
        if (!$template->getId() && $id) {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('adminhtml')->__('This Email template no longer exists.')
            );
            $this->_redirect('*/*/');
            return;
        }

        try {
            $template->setTemplateSubject($request->getParam('template_subject'))
                ->setTemplateCode($request->getParam('template_code'))
// inserted by Schrack:
                 ->setSchrackEyepinNewsletterId($request->getParam('schrack_eyepin_newsletter_id'))
// :inserted by Schrack
                ->setTemplateText($request->getParam('template_text'))
                ->setTemplateStyles($request->getParam('template_styles'))
                ->setModifiedAt(Mage::getSingleton('core/date')->gmtDate())
                ->setOrigTemplateCode($request->getParam('orig_template_code'))
                ->setOrigTemplateVariables($request->getParam('orig_template_variables'));

            if (!$template->getId()) {
                $template->setAddedAt(Mage::getSingleton('core/date')->gmtDate());
                $template->setTemplateType(Mage_Core_Model_Email_Template::TYPE_HTML);
            }

            if ($request->getParam('_change_type_flag')) {
                $template->setTemplateType(Mage_Core_Model_Email_Template::TYPE_TEXT);
                $template->setTemplateStyles('');
            }

            $template->save();
            Mage::getSingleton('adminhtml/session')->setFormData(false);
            Mage::getSingleton('adminhtml/session')->addSuccess(
                Mage::helper('adminhtml')->__('The email template has been saved.')
            );
            $this->_redirect('*/*');
        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->setData(
                'email_template_form_data',
                $this->getRequest()->getParams()
            );
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            $this->_forward('new');
        }
    }

}