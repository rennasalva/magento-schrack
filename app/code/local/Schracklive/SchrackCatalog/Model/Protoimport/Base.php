<?php

use com\schrack\queue\protobuf\Message;

abstract class Schracklive_SchrackCatalog_Model_Protoimport_Base extends Schracklive_Schrack_Model_ProtoImportBase {

    const DEFAULT_STORE_ID      = 0;
    
    const PACKAGE_TYPE_FULL     = 'full';
    const PACKAGE_TYPE_PART     = 'part';
    const PACKAGE_TYPE_LAST     = 'last';
    
    const ACTION_INSERT_UPDATE  = 'insert-or-update';
    const ACTION_DELETE         = 'delete';
    const ACTION_KILL           = 'kill';

    const MEDIATYPE_FOTO        = 'foto';
    const MEDIATYPE_THUMB_66x66 = 'thumb66x66';
    const MEDIATYPE_CATALOGUE   = 'produktkataloge';
    const MEDIATYPE_DATA_SHEET  = 'datenblaetter';
    const MEDIATYPE_THUMBNAILS  = 'thumbnails';

    const LOG_FILE_NAME_BASE    = 'catalog_proto_import';
    const DUMP_FILE_NAME_BASE   = 'catalog';
    
    const ID_SEPARATOR          = '#';

    private static $protbufAutoloadSwitchedOn = false;
    
    // ###############################################
    protected $_DO_ONLY_THAT_SKU = null;
    // ###############################################
    
    function __construct ( $originTimestamp = null ) {
        parent::__construct('sts2ws.php',$originTimestamp);
    }

    protected function getLogFileBaseName () {
        return self::LOG_FILE_NAME_BASE;
    }

    protected function getDumpFileBaseName () {
        return self::DUMP_FILE_NAME_BASE;
    }

    public function setDoOnlyThatSku ( $id ) {
        $this->_DO_ONLY_THAT_SKU = strtoupper($id);
    }
    
    public static function isMediatypeThumbnail ( $val ) {
        return     $val === self::MEDIATYPE_THUMB_66x66
                || $val === self::MEDIATYPE_THUMBNAILS;
    }
    
    protected function getLastIdPart ( $fullId )
    {
        $p = strrpos($fullId, self::ID_SEPARATOR);
        if ( $p !== false ) {
            return substr($fullId, $p + 1);
        }
        return $fullId;
    }

    protected function getMagentoCategoryIdFromArray ( $stsId, array &$ar )
    {
        if ( !isset($ar[$stsId]) ) {
            $stsId = $this->getLastIdPart($stsId);
        }
        if ( isset($ar[$stsId]) ) {
            return $ar[$stsId];
        }
        return null;
    }

    protected static function prepareIncommingID ( $id ) {
        return $id;
    }

}

?>
