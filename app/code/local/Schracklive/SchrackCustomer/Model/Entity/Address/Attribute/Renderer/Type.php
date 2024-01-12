<?php

class Schracklive_SchrackCustomer_Model_Entity_Address_Attribute_Renderer_Type extends Varien_Data_Form_Element_Select {

	public function getElementHtml() {
		if ($this->getValue() == 1) {
			foreach ($this->getValues() as $option) {
				if ($option['value'] == 1) {
					$this->setType('hidden');
					$this->setExtType('hiddenfield');
					$this->unsRenderer();

					$html = $this->_escape($option['label']).'<input id="'.$this->getHtmlId().'" name="'.$this->getName()
							.'" value="'.$this->getEscapedValue().'" '.$this->serialize($this->_getInputHtmlAttributes()).'/>'."\n";
					$html.= $this->getAfterElementHtml();

					return $html;
				}
			}
			return '';
		} else {
			return parent::getElementHtml();
		}
	}


	protected function _getInputHtmlAttributes() {
		return array('type', 'title', 'class', 'style', 'onclick', 'onchange', 'disabled', 'readonly', 'tabindex');
	}

}
