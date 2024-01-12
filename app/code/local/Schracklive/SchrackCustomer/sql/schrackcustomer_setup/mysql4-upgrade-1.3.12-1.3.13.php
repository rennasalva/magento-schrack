<?php //

$installer = $this;

$installer->startSetup();

$conn = Mage::getSingleton('core/resource')->getConnection('common_db');

/*
$conn->query(<<<EOF
   ALTER TABLE customer_tracking ADD INDEX IDX_CUSTOMERTRACKING_SESSION_CREATED_AT (session_id, created_at)
EOF
);

$conn->query(<<<EOF
   ALTER TABLE customer_tracking DROP INDEX IDX_CUSTOMERTRACKING_SESSION_ID
EOF
);
*/

$installer->endSetup(); 

