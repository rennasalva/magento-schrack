<?php
/**
 * MageDeveloper TYPO3connect Module
 * ---------------------------------
 *
 * @category    Mage
 * @package    MageDeveloper_TYPO3connect
 * @copyright   Magento Developers / magedeveloper.de <kontakt@magedeveloper.de>
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class MageDeveloper_TYPO3connect_Block_Adminhtml_Typo3connect_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        $this->_objectId    = 'uid';
        $this->_controller  = 'adminhtml_typo3connect';
        $this->_mode        = 'edit';
		$this->_blockGroup  = 'typo3connect'; 

        parent::__construct();
        $this->setTemplate('typo3connect/edit.phtml');
    }
}