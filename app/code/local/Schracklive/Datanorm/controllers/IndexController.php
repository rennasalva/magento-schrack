<?php

class Schracklive_Datanorm_IndexController extends Mage_Core_Controller_Front_Action
{
    const LOCALES_WITH_COMMA_SEPARATOR = "az_AZ be_BY bg_BG bs_BA ca_ES crh_UA cs_CZ da_DK de_AT de_BE de_DE de_LU el_CY el_GR es_AR es_BO es_CL es_CO es_CR es_EC es_ES es_PY es_UY es_VE et_EE eu_ES eu_ES@euro ff_SN fi_FI fr_BE fr_CA fr_FR fr_LU gl_ES hr_HR ht_HT hu_HU id_ID is_IS it_IT ka_GE kk_KZ ky_KG lt_LT lv_LV mg_MG mk_MK mn_MN nb_NO nl_AW nl_NL nn_NO pap_AN pl_PL pt_BR pt_PT ro_RO ru_RU ru_UA rw_RW se_NO sk_SK sl_SI sq_AL sq_MK sr_ME sr_RS sr_RS@latin sv_SE tg_TJ tr_TR tt_RU@iqtelif uk_UA vi_VN wo_SN";


    function indexAction () {
        $model = Mage::getModel('datanorm/main');
        $isLoggedIn = $model->isLoggedIn();
        if ($isLoggedIn) {
            $mayGetCustomerPrices = $model->mayGetCustomerPrices();
            if (!$mayGetCustomerPrices) {
                Mage::getSingleton('core/session')->addNotice($this->__('Your account is only authorized for list prices.'));
            }
        } else {
            Mage::getSingleton('core/session')->addNotice($this->__('Because you are not logged in, you will get only list prices.'));
        }

        $this->loadLayout();
        $block = $this->getLayout()->getBlock('datanorm_form_index');
        $block->assign('isLoggedIn', $isLoggedIn);
        $this->renderLayout();
    }

    function downloadAction () {
        $args = array( Schracklive_Datanorm_Model_Main::EXPORT_FUNCTION => Schracklive_Datanorm_Model_Main::EXPORT_FUNCTION_DATANORM );

        if ($this->getRequest()->isPost()) {
            $postData = $this->getRequest()->getPost();

            $args[Schracklive_Datanorm_Model_Main::INCLUDE_PICTURE_URLS]                = (bool) ($postData['SelectImgURLs'] == 'true');
            $args[Schracklive_Datanorm_Model_Main::ENCODE_UTF8]                         = (bool) ($postData['EncodeUTF8'] == 'true');
            $args[Schracklive_Datanorm_Model_Main::GROUP_ARTICLES_BY_SCHRACK_STRUCTURE] = (bool) ($postData['GroupArticlesBySchrackStructure'] == 'true');
            $args[Schracklive_Datanorm_Model_Main::USE_EDS_ARTICLE_NUMBERS]             = (bool) ($postData['UseEdsArticleNumbers'] == 'true');
            $args[Schracklive_Datanorm_Model_Main::WITHOUT_LONG_TEXT]                   = (bool) ($postData['WithoutLongText'] == 'true');
            $args[Schracklive_Datanorm_Model_Main::WITHOUT_RESELL_PRICES]               = (bool) ($postData['WithoutUVP'] == 'true');
        }

        $this->commonDownload($args);
    }

    function downloadCvsAction () {
        $locale = Mage::app()->getLocale()->getLocaleCode();
        if ( stripos(self::LOCALES_WITH_COMMA_SEPARATOR,$locale) !== false ) {
            $delimiter = ";";
        } else {
            $delimiter = ",";
        }

        $args = array(
                    Schracklive_Datanorm_Model_Main::EXPORT_FUNCTION => Schracklive_Datanorm_Model_Main::EXPORT_FUNCTION_CSV,
                    Schracklive_Datanorm_Model_Main::DELIMITER_4_CSV => $delimiter
        );
        $this->commonDownload($args);
    }


    function downloadXmlAction () {
        $args = array(Schracklive_Datanorm_Model_Main::EXPORT_FUNCTION => Schracklive_Datanorm_Model_Main::EXPORT_FUNCTION_XML);
        $this->commonDownload($args);
    }

    private function commonDownload ( $furtherArgs ) {
        $model = Mage::getModel('datanorm/main');
        try {
            $resFilepath = $model->callGet($furtherArgs);
            if (is_string($resFilepath)) { // assuming all was ok, get result is url of zip
                $result = json_encode(array( 'downloadFilePath' => $resFilepath));
                echo $result;
                exit(0);
            } else { // somethin went wrong
                $msg = sprintf($this->__('Error %d. Please try again later or contact your Schrack contact person.'), $resFilepath);
                $result = json_encode(array('error' => $msg));
                echo $result;
                exit(0);
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $result = json_encode(array('error' => $this->__('Datanorm could not be fetched.')));
            echo $result;
            exit(0);
        }
    }
}
?>