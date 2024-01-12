<?php

require_once dirname(dirname(__FILE__)) . '/abstract.php';

class Schracklive_Shell_RenameAttributes extends Mage_Shell_Abstract {

    
    var $_data = array(
        'catalog_product' => array( // entity name
            'schrack_productgroup' => array( // attribute name
                'AT' => 'Produktgruppe',
                'CZ' => 'Produktová skupina',
                'BG' => 'Продуктова група',
                'BA' => 'Grupa proizvoda',
                'HR' => 'Grupa proizvoda',
                'HU' => 'Termékcsoport',
                'BE' => 'Productgroep',
                'PL' => 'grupa produktowa',
                'RO' => 'Grupă de produse',
                'SK' => 'Produktová skupina',
                'SI' => 'skupina izdelkov',
                'RS' => 'Produkt grupa'
            )
        )
    );
    
	public function run() {
        $magentoCountryCode = strtoupper(Mage::getStoreConfig('general/country/default'));
        
        foreach ( $this->_data as $entityName => $attrs ) {
            foreach ( $attrs as $attrName => $vals ) {
                foreach ( $vals as $country => $value ) {
                    if ( $country === $magentoCountryCode ) {
                        $attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode($entityName,$attrName);
                        if ( $attributeId ) {
                            $attribute = Mage::getModel('catalog/resource_eav_attribute')->load($attributeId);
                            $attribute->setFrontendLabel($value);
                            $attribute->save();
                        }
                    }
                }
            }
        }
        
        
	}
    

}

$shell = new Schracklive_Shell_RenameAttributes();
$shell->run();
