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