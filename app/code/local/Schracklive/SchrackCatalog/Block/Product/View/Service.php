<?php

class Schracklive_SchrackCatalog_Block_Product_View_Service extends Mage_Catalog_Block_Product_View_Attributes {

	public function getAdditionalData(array $excludeAttr = array()) {
		$data = parent::getAdditionalData(array_merge($excludeAttr,array('schrack_url_foto', 'schrack_url_thumbnails')));
		$server = Mage::getStoreConfig('schrack/general/imageserver');
		$result = array();
		$product = $this->getProduct();
		$attachments = $product->getAttachmentsCollection();
		foreach ($attachments as $file) {
			$key = $file->getFiletype();
			if (($key != 'foto') && ($key != 'thumbnails')) {
				$path = $file->getUrl();
				$url = $server.$path;
				if (strpos($file->getLabel(),':')!==FALSE){
					list(,$filelabel)=explode(':',$file->getLabel());
				}else{
					$filelabel=$file->getLabel();
				}
				$data[$key]['label']=uc_words($file->getFiletype());
				$data[$key]['code']='';
				$data[$key]['value'] =
						'<a href="'.$url.'" target="_new">'.$filelabel."</a>";
				$result[$key] = $data[$key];
			}
		}
		return $result;
	}

}

?>
