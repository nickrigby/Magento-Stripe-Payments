<?php
/**
 * District Commerce
 *
 * @category    District
 * @package     Stripe
 * @author      District Commerce <support@districtcommerce.com>
 * @copyright   Copyright (c) 2015 District Commerce (http://districtcommerce.com)
 * 
 */

$installer = $this;
$installer->startSetup();
$installer->run("
    CREATE TABLE `{$installer->getTable('stripe/customer')}` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `customer_id` int(10) unsigned NOT NULL,
      `token` varchar(255) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
    
    CREATE TABLE `{$installer->getTable('stripe/order_failed')}` (
      `id` int(10) unsigned NOT NULL auto_increment,
      `order_id` int(10) unsigned NOT NULL,
      `cc_type` varchar(255),
      `cc_last4` varchar(10),
      `amount` decimal(12,4),
      `reason` text,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup();