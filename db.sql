DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

insert  into `config`(`id`,`key`,`value`) values (1,'email_host','host.sitehatchery.com'),(2,'email_username','reports@acfaom.org'),(3,'email_password','1lovec0ffee!'),(4,'imap_search_filter_for_order_search_emails','FROM \"info@acfaom.org\" SUBJECT \"Impexium Query Results: Order Search (for automatic processing)\" UNDELETED'),(5,'imap_search_filter_for_merchandise_purchases_emails','FROM \"info@acfaom.org\" SUBJECT \"Impexium Query Results: List Of Merchandise Purchases (for automatic processing)\" UNDELETED'),(6,'book_product_codes','BOOKPRODUCTION');

DROP TABLE IF EXISTS `cron_logs`;

CREATE TABLE `cron_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(255) NOT NULL,
  `started_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `emails`;

CREATE TABLE `emails` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cron_id` int(11) unsigned NOT NULL,
  `unique_id` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `is_processed` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`,`subject`,`cron_id`),
  KEY `cron_id` (`cron_id`),
  CONSTRAINT `emails_ibfk_1` FOREIGN KEY (`cron_id`) REFERENCES `cron_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `order`;

CREATE TABLE `order` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email_id` int(11) unsigned NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `order_date` datetime NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `customer_id` varchar(255) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `phone_number` varchar(255) DEFAULT NULL,
  `email_address` varchar(255) DEFAULT NULL,
  `carrier_code` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `email_id` (`email_id`),
  CONSTRAINT `order_ibfk_1` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `order_products`;

CREATE TABLE `order_products` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `email_id` int(11) unsigned NOT NULL,
  `order_id` varchar(255) NOT NULL,
  `product_code` varchar(255) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` varchar(255) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`,`product_code`),
  KEY `email_id` (`email_id`),
  CONSTRAINT `order_products_ibfk_1` FOREIGN KEY (`email_id`) REFERENCES `emails` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 30 May 2019
ALTER TABLE order_products ADD COLUMN shipping_method VARCHAR(255) NULL AFTER quantity;
INSERT INTO config (`key`, `value`) VALUES ('flush_data_prior_days', '30')

-- 03 June 2019
INSERT INTO `config` (`key`, `value`) VALUES ('omnipress_username', '14184A');
INSERT INTO `config` (`key`, `value`) VALUES ('omnipress_password', 'omnipress1234!');
UPDATE `config` SET `value`='ACFAOM_ReviewText_ThirdEdition' WHERE `key`='book_product_codes';

-- 06 June 2019
ALTER TABLE `order` ADD COLUMN `is_pushed` TINYINT(4) DEFAULT 0 NOT NULL AFTER `carrier_code`;

-- 07 June 2019
DROP TABLE IF EXISTS `shipping_methods`;
CREATE TABLE `shipping_methods` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `carrier_code` varchar(25) NOT NULL,
  `carrier_description` varchar(255) NOT NULL,
  `carrier` varchar(25) NOT NULL,
  `service_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `carrier_description` (`carrier_description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
insert  into `shipping_methods`(`id`,`carrier_code`,`carrier_description`,`carrier`,`service_type`) values (1,'U01','Next Day Air','UPS','Domestic'),(2,'U43','Next Day Air Saver','UPS','Domestic'),(3,'U07','2nd Day Air','UPS','Domestic'),(4,'U21','3 Day Select','UPS','Domestic'),(5,'U11','Ground','UPS','Domestic'),(6,'U63','Worldwide Express Plus','UPS','International'),(7,'U49','Worldwide Express','UPS','International'),(8,'U98','Worldwide Saver','UPS','International'),(9,'U54','Worldwide Expedited','UPS','International');
