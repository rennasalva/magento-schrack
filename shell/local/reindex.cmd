#!/bin/bash

php ..\indexer.php --reindex catalog_product_attribute
php ..\indexer.php --reindex catalog_product_price
php ..\indexer.php --reindex catalog_url
php ..\indexer.php --reindex catalog_category_flat
php ..\indexer.php --reindex catalog_category_product
php ..\indexer.php --reindex catalogsearch_fulltext
php ..\indexer.php --reindex cataloginventory_stock
