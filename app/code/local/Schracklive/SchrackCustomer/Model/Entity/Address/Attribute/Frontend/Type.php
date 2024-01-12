<?php

class Schracklive_SchrackCustomer_Model_Entity_Address_Attribute_Frontend_Type extends Mage_Eav_Model_Entity_Attribute_Frontend_Abstract {

	public function getInputRendererClass() {
		return 'Schracklive_SchrackCustomer_Model_Entity_Address_Attribute_Renderer_Type';
	}

}
