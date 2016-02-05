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
        `date` datetime NOT NULL default '0000-00-00 00:00:00',
        `order_id` int(10) unsigned NOT NULL,
        `customer_id` int(10) unsigned default NULL,
        `cc_type` varchar(255),
        `cc_last4` varchar(10),
        `amount` decimal(12,4),
        `message` text,
        `type` varchar(255),
        `code` varchar(255),
        `token` varchar(255),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");
$installer->endSetup();
