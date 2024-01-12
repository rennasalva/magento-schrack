<?php

require_once('shell.php');

/**
 * AD User Sync Shell Script
 *
 * @author      Martin Kutschker <mk@plan2.net>
 */
class Schracklive_Shell_getTransactionalSQL extends Schracklive_Shell {

    public function run() {
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');

        // 1. Template : Share Partslist CSV
        $query = "SELECT template_subject FROM core_email_template WHERE template_code LIKE 'Share Partslist CSV'";
        $sharePartslistCSVSubject = $readConnection->fetchOne($query);
        $query = "SELECT template_text FROM core_email_template WHERE template_code LIKE 'Share Partslist CSV'";
        $sharePartslistCSVContent = $readConnection->fetchOne($query);
        $sharePartslistCSVContent = str_replace("'", "&quot;", $sharePartslistCSVContent);

        $buildQuery  = "UPDATE magento_" . strtolower(Mage::getStoreConfig('schrack/general/country')) . ".core_email_template SET template_subject = '" . $sharePartslistCSVSubject . "',";
        $buildQuery .= " template_text = '" . $sharePartslistCSVContent . "'";
        $buildQuery .= " WHERE template_code LIKE 'Share Partslist CSV';" . "\n";

        // 2. Template : Share Partslist Account
        $query = "SELECT template_subject FROM core_email_template WHERE template_code LIKE 'Share Partslist Account'";
        $sharePartslistAccountSubject = $readConnection->fetchOne($query);
        $query = "SELECT template_text FROM core_email_template WHERE template_code LIKE 'Share Partslist Account'";
        $sharePartslistAccountContent = $readConnection->fetchOne($query);
        $sharePartslistAccountContent = str_replace("'", "&quot;", $sharePartslistAccountContent);

        $buildQuery .= "UPDATE magento_" . strtolower(Mage::getStoreConfig('schrack/general/country')) . ".core_email_template SET template_subject = '" . $sharePartslistAccountSubject . "',";
        $buildQuery .= " template_text = '" . $sharePartslistAccountContent . "'";
        $buildQuery .= " WHERE template_code LIKE 'Share Partslist Account';" . "\n";

        // 3 Template : Share Cart CSV
        $query = "SELECT template_subject FROM core_email_template WHERE template_code LIKE 'Share Cart CSV'";
        $shareCartCSVSubject = $readConnection->fetchOne($query);
        $query = "SELECT template_text FROM core_email_template WHERE template_code LIKE 'Share Cart CSV'";
        $shareCartCSVContent = $readConnection->fetchOne($query);
        $shareCartCSVContent = str_replace("'", "&quot;", $shareCartCSVContent);

        $buildQuery .= "UPDATE magento_" . strtolower(Mage::getStoreConfig('schrack/general/country')) . ".core_email_template SET template_subject = '" . $shareCartCSVSubject . "',";
        $buildQuery .= " template_text = '" . $shareCartCSVContent . "'";
        $buildQuery .= " WHERE template_code LIKE 'Share Cart CSV';";

        file_put_contents("/var/log/schracklive/transactionals_sql_" . strtolower(Mage::getStoreConfig('schrack/general/country')) . ".sql", $buildQuery);
    }
}
$shell = new Schracklive_Shell_getTransactionalSQL();
$shell->run();