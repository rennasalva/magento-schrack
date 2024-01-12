<?php

class Schracklive_SchrackCatalog_Model_Attachment extends Mage_Core_Model_Abstract {

	protected $fileInfo = null;

	protected function _construct() {
		parent::_construct();
		$this->_init('schrackcatalog/attachment');
	}

    public function _getFileInfo() {
		$url = Mage::getStoreConfig('schrack/general/imageserver').$this->getUrl();
		$fileData = Mage::getModel('schrackcatalog/filedata');
		$fileInfo = array();
		$fileInfo['mimetype'] = '';
		$fileInfo['filesize'] = '';

		$fileData->loadByUrl($url);
		// renew file infos when they are elder than 1 month
		$outDated = $fileData->getId() && $fileData->getUpdatedAt() <  date("Y-m-d",strtotime("-1 month", time()));
		if ( $fileData->getId() && ! $outDated ) {
			$fileInfo['mimetype'] = $fileData->getMimetype();
			$fileInfo['filesize'] = $fileData->getFilesize();
			if ($fileInfo['mimetype'] && $fileInfo['filesize']) {
				return $fileInfo;
			}
		}

		$hasUrlParams = strpos($url,'?') !== false; // media server cannot handle url parameters
		if ( ($url != null) && ($url != '') && (! $hasUrlParams) ) {
            $url = str_replace('https://','http://',$url); // running else into certificate problems with wrong server name in php 7.2
			$file = @fopen($url, 'r');
			if ($file) {
                $urlDoesNotExists = false;
				$headers = stream_get_meta_data($file);
				$fileData->setUrl($url);
				foreach ($headers['wrapper_data'] as $header) {
					if (strpos(strtolower($header), 'content-type') !== FALSE) {
						$fileInfo['mimetype'] = trim(substr($header, strpos($header, ':') + 1));
						$fileData->setMimetype($fileInfo['mimetype']);
					}
					if (strpos(strtolower($header), 'content-length') !== FALSE) {
						$fileInfo['filesize'] = trim(substr($header, strpos($header, ':') + 1));
						$fileData->setFilesize($fileInfo['filesize']);
					}
				}
                if ( $fileInfo['mimetype'] === 'image/jpeg' && $fileInfo['filesize'] === '737') {
                    $urlDoesNotExists = true;
                    //throw new Exception("file '$url' does not exist on image server");
                }
				if ( ! $urlDoesNotExists ) {
                    $fileData->save();
                }
                @fclose($file);
			}
		}

		return $fileInfo;
	}

	public function getHumanFiletype() {
        try {
            if ($this->fileInfo == null) {
                $this->fileInfo = $this->_getFileInfo();
            }
            $filetype = '';
            $matching = array(
                'application/pdf' => 'pdf',
                'application/msword' => 'doc',
                'application/doc' => 'doc',
                'video/avi' => 'film',
                'application/msexcel' => 'xls',
                'application/vnd.ms-excel' => 'xls',
                'application/vnd.ms-powerpoint' => 'ppt',
                'application/mspowerpoint' => 'ppt',
                'image/jpeg' => 'jpg',
                'image/gif' => 'gif',
                'image/png' => 'png',
                'application/x-zip' => 'vd'
            );

            if (isset($matching[$this->fileInfo['mimetype']])) {
                $filetype = $matching[$this->fileInfo['mimetype']];
            } else {
                list($filetype) = explode('/', $this->fileInfo['mimetype']);
            }

            return $filetype;
        } catch ( Exception $e ) {
            Mage::logException($e);
            return Mage::helper('catalog')->__('(unknown)');
        }
	}

	public function getHumanFilesize() {
        try {
            if ($this->fileInfo == null) {
                $this->fileInfo = $this->_getFileInfo();
            }
            $result = array();
            $units = array("B", "KB", "MB", "GB", "TB");
            $c = 0;
            $b = (float) $this->fileInfo['filesize'];
            foreach ($units as $k => $u) {
                if (($b / pow(1024, $k)) >= 1) {
                    $result['bytes'] = $b / pow(1024, $k);
                    $result['units'] = $u;
                    $c++;
                }
            }

            if (isset($result['bytes'])) {
                return number_format($result['bytes'], 2, ',', '.') . ' ' . $result['units'];
            } else {
                return number_format(0, 2, ',', '.') . ' B';
            }


        } catch ( Exception $e ) {
            Mage::log($this->getUrl(), null, 'failed_attachment_downloads.log');
            Mage::logException($e);
            return Mage::helper('catalog')->__('(unknown)');
        }
	}

    public function getUrl() {
        $url = parent::getUrl();
        // $url = preg_replace('#foto/#', 'thumb400/', $url); DLA20180125: not longe necessary
        return $url;
    }
}

?>
