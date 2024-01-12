<?php

    $path = str_replace('shell/local/GenerateSitemap.php', '', __FILE__);

    require_once $path. '/app/Mage.php';
    Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);

    $collection = Mage::getModel('sitemap/sitemap')->getCollection();

    foreach ($collection as $sitemap) {
        try {
            $sitemap->generateXml();
        }
        catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }