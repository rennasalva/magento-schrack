<?php

$installer = $this;
$installer->startSetup();

$installer->run("
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/highlighting_enabled', '1');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/highlighting_enabled_pages', '1');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/highlighting_fields', 'description_textS');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/highlighting_fields_pages', 'content');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/highlighting_length', '150');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/highlighting_snippets', '2');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/highlighting_wrap', '<span class=\"results-highlight\">|</span>');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/query_static_order_sale', '0');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/query_result_limit', '51');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/query_fields', 'sku_textTS^40.0 schrack_ean_textTM^30.0 schrack_keyword_foreign_textS^30.0 schrack_keyword_foreign_hidden_textS^30.0 name_textStS^500.0 name_textS^20.0 keyword_textStM^200.0 keyword_textM^10.0 description_textS^10.0 category_keyword_textM^5.0 category_name_textStS^10.0 category_names_search_textM^1.0 content^0.5');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/query_fields_dead', 'sku_textTS^40.0 schrack_ean_textTM^30.0');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/query_fields_pages', 'title_textStS^500.0 title^50.0 nav_title^40.0 tagsH2H3^30.0 subtitle^20.0 content^15.0 keywords^10.0 description^1.0');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/query_boost', 'recip(schrack_wws_ranking_intS,1,999999,90)^50.0');
    REPLACE INTO {$this->getTable('core_config_data')} (`path`, `value`) VALUES ('schrack/solr/query_boost_sale', 'recip(schrack_wws_ranking_intS,1,999999,90)^10.0');
    ");

$installer->endSetup();
