DROP TABLE IF EXISTS `test_purchases`;
CREATE TABLE `test_purchases` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `creator_id` INT(11) UNSIGNED NOT NULL,
  `number` VARCHAR(40) NOT NULL,
  `currency` VARCHAR(3) NOT NULL,
  `monetary_source` TEXT,
  `is_frozen` INT(1) UNSIGNED NOT NULL DEFAULT 0,
  `is_deleted` INT(1) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY  (`id`),
  KEY `fk_user_id` (`creator_id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_store_purchases`;
CREATE TABLE `test_store_purchases` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` VARCHAR(40) NOT NULL,
  `test_store_id` INT(10) UNSIGNED NULL,
  `test_purchase_id` INT(10) UNSIGNED NULL,
  `is_deleted` INT(1) UNSIGNED NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_payments`;
CREATE TABLE `test_payments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_purchase_id` INT(10) UNSIGNED NULL,
  `payment_id` VARCHAR(255) NOT NULL,
  `method` VARCHAR(20) NOT NULL,
  `status` VARCHAR(20) NOT NULL,
  `raw_response` TEXT,
  `is_deleted` INT(1) UNSIGNED NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY  (`id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_purchase_items`;
CREATE TABLE `test_purchase_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_store_purchase_id` INT(10) UNSIGNED NULL,
  `reference_id` INT(10) UNSIGNED NULL,
  `reference_model` VARCHAR(40) NULL,
  `price` DECIMAL(10,2) NULL,
  `quantity` INT(11) NULL,
  `type` VARCHAR(255) NULL,
  `is_payable` INT(1) UNSIGNED NOT NULL,
  `is_discount` INT(1) UNSIGNED NOT NULL,
  `is_deleted` INT(1) UNSIGNED NOT NULL,
  `created_at` DATETIME,
  `updated_at` DATETIME,
  PRIMARY KEY  (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_users`;
CREATE TABLE `test_users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(254) NOT NULL,
  `username` VARCHAR(32) NOT NULL DEFAULT '',
  `password` VARCHAR(64) NOT NULL,
  `logins` INT(10) UNSIGNED NOT NULL DEFAULT '0',
  `last_login` INT(10) UNSIGNED,
  `facebook_uid` VARCHAR(100),
  `twitter_uid` VARCHAR(100),
  `last_login_ip` VARCHAR(40),
  PRIMARY KEY  (`id`),
  UNIQUE KEY `uniq_username` (`username`),
  UNIQUE KEY `uniq_email` (`email`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_stores`;
CREATE TABLE `test_stores` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(254) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_products`;
CREATE TABLE `test_products` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(254) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) NOT NULL,
  `test_store_id` INT(10) UNSIGNED NULL,
  PRIMARY KEY  (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `test_variations`;
CREATE TABLE `test_variations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(254) NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `test_product_id` INT(10) UNSIGNED NULL,
  PRIMARY KEY  (`id`)
) ENGINE=INNODB  DEFAULT CHARSET=utf8;


# Dump of table test_payments
# ------------------------------------------------------------

INSERT INTO `test_payments` (`id`, `test_purchase_id`, `payment_id`, `method`, `status`, `raw_response`, `is_deleted`)
VALUES
  (1,1,'11111','emp','paid','[\"asd\"]',0);

# Dump of table test_products
# ------------------------------------------------------------

INSERT INTO `test_products` (`id`, `name`, `price`, `currency`, `test_store_id`)
VALUES
  (1,'Chair',290.40,'GBP',1),
  (2,'Rug',30.00,'GBP',1),
  (3,'Matrass',130.99,'EUR',1);

# Dump of table test_purchases
# ------------------------------------------------------------

INSERT INTO `test_purchases` (`id`, `creator_id`, `number`, `currency`, `monetary_source`, `is_frozen`, `is_deleted`)
VALUES
  (1,1,'CNV7IC','EUR','C:33:"OpenBuildings\\Monetary\\Source_ECB":775:{a:33:{s:3:"USD";s:6:"1.3355";s:3:"JPY";s:6:"132.35";s:3:"BGN";s:6:"1.9558";s:3:"CZK";s:6:"25.665";s:3:"DKK";s:6:"7.4588";s:3:"GBP";s:7:"0.85910";s:3:"HUF";s:6:"298.98";s:3:"LTL";s:6:"3.4528";s:3:"LVL";s:6:"0.7027";s:3:"PLN";s:6:"4.2323";s:3:"RON";s:6:"4.4423";s:3:"SEK";s:6:"8.7140";s:3:"CHF";s:6:"1.2358";s:3:"NOK";s:6:"8.0940";s:3:"HRK";s:6:"7.5520";s:3:"RUB";s:7:"44.1375";s:3:"TRY";s:6:"2.6640";s:3:"AUD";s:6:"1.4879";s:3:"BRL";s:6:"3.2059";s:3:"CAD";s:6:"1.4114";s:3:"CNY";s:6:"8.1759";s:3:"HKD";s:7:"10.3579";s:3:"IDR";s:8:"14723.80";s:3:"ILS";s:6:"4.8086";s:3:"INR";s:7:"85.5050";s:3:"KRW";s:7:"1489.41";s:3:"MXN";s:7:"17.4804";s:3:"MYR";s:6:"4.4085";s:3:"NZD";s:6:"1.7185";s:3:"PHP";s:6:"59.013";s:3:"SGD";s:6:"1.7120";s:3:"THB";s:6:"42.749";s:3:"ZAR";s:7:"13.6968";}}',1,0),
  (2,1,'AAV7IC','GBP','',0,0);

# Dump of table test_store_purchases
# ------------------------------------------------------------

INSERT INTO `test_store_purchases` (`id`, `number`, `test_store_id`, `test_purchase_id`, `is_deleted`)
VALUES
  (1,'3S2GJG',1,1,0),
  (2,'AA2GJG',1,2,0);

# Dump of table test_purchase_items
# ------------------------------------------------------------

INSERT INTO `test_purchase_items` (`id`, `test_store_purchase_id`, `reference_id`, `reference_model`, `price`, `quantity`, `type`, `is_payable`, `is_discount`, `is_deleted`)
VALUES
  (1,1,1,'test_product',200.00,1,'product',1,0,0),
  (2,1,1,'test_variation',200.00,1,'product',1,0,0),
  (3,2,1,'test_product',NULL,1,'product',1,0,0);

# Dump of table test_stores
# ------------------------------------------------------------

INSERT INTO `test_stores` (`id`, `name`)
VALUES
  (1,'Example Store'),
  (2,'Empty Store');

# Dump of table test_users
# ------------------------------------------------------------

INSERT INTO `test_users` (`id`, `email`, `username`, `password`, `logins`, `last_login`, `facebook_uid`, `twitter_uid`, `last_login_ip`)
VALUES
  (1,'admin@example.com','admin','f02c9f1f724ebcf9db6784175cb6bd82663380a5f8bd78c57ad20d5dfd953f15',5,1374320224,'facebook-test','','10.20.10.1');


# Dump of table test_variations
# ------------------------------------------------------------
INSERT INTO `test_variations` (`id`, `name`, `price`, `test_product_id`)
VALUES
  (1,'Red',295.40,1),
  (2,'Green',298.90,1);