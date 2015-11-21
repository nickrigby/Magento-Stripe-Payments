<?php

$installer = $this;
$installer->startSetup();
$installer->run("
    CREATE TABLE `{$installer->getTable('stripe/customer')}` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `customer_id` int(10) unsigned NOT NULL,
      `token` varchar(255) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup();