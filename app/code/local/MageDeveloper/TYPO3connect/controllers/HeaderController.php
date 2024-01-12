<?php

class MageDeveloper_TYPO3connect_HeaderController extends Mage_Core_Controller_Front_Action {
    public function customermenuAction() {        
        $menuUrl = Mage::getUrl('*/*/innercustomermenu');
        $html = <<<EOL
<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery.ajax('$menuUrl', {
            'type': 'GET', 'success' : function(data) { jQuery('.customer-menu').html(data); }
        });
    });
</script>
EOL;
        echo($html);
        die;
    }
    public function innercustomermenuAction() {        
        $this->loadLayout();
        $block = $this->getLayout()->getBlock('top.links');        
        header('Content-Type:text/html; charset=UTF-8');
        $html = $block->toHtml();
        echo($html);
        die;
    }
	
    public function startmenuAction() {
        $this->loadLayout();
        $block = $this->getLayout()->getBlock('startmenu');
        $html = $block->toHtml();
        header('Content-Type:text/html; charset=UTF-8');
        echo($html);
        die;
    }
		
}