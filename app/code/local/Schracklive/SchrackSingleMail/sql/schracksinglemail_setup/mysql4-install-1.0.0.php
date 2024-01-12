<?php

$installer = $this;

$installer->startSetup();

$installer->run("
    ALTER TABLE core_email_template ADD COLUMN schrack_eyepin_newsletter_id VARCHAR(256) DEFAULT NULL AFTER orig_template_variables;
");


$installer->endSetup();
