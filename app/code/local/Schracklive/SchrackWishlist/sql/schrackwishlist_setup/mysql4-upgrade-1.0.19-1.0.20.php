<?php

$installer = $this;

$installer->startSetup();

$conn = $installer->getConnection();

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer DROP has_own_company_info;
EOF
);

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer DROP uidnummer;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer DROP dvrnummer;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer DROP firmenbuchnummer;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer DROP firmenbuchgericht;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer DROP kammerzugehoerigkeit;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer DROP geschaeftsfuehrer;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer DROP grundlegenderichtung;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer DROP aufsichtsbehoerde;
EOF
);
$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer DROP vorschriften;
EOF
);

$installer->run(<<<EOF
    ALTER TABLE endcustomerpartslist_customer ADD bottom_banner_url VARCHAR(255) NULL DEFAULT NULL;
EOF
);

$installer->endSetup();
