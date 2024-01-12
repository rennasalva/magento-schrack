<?php

class Schracklive_SchrackCore_Helper_Model {
    const DIRTY_FLAG_PSEUDO_ATTRIBUTE = '___schrack___is___dirty___';

    public function isModified ( Mage_Core_Model_Abstract $model, $fieldsToCheck = false ) {
        if ( $model->getData(self::DIRTY_FLAG_PSEUDO_ATTRIBUTE) ) {
            return true;
        }
        if ( ! $model->getId() ) {
            return true;
        }
        $data = $model->getData();
        $origData = $model->getOrigData();
        if ( ! $origData ) { // not loaded from db.
            $model->load($model->getId());
            $model->setData($data);
            $origData = $model->getOrigData();
        }
        if ( $fieldsToCheck ) {
            $diff = array_merge(array_diff_assoc($data,$origData),array_diff_assoc($data,$origData));
            foreach ( $fieldsToCheck as $key ) {
                if ( array_key_exists($key,$diff) ) {
                    return true;
                }
            }
        } else {
            if ( ! $model->getId() ) {
                return true;
            }
            if ( count($data) != count($origData) ) {
                return true;
            }
            $diff = array_merge(array_diff_assoc($data,$origData),array_diff_assoc($data,$origData));
            if ( count($diff) > 0 ) {
                return true;
            }
        }
        return false;
    }


}