CREATE TABLE `xcreate_categories` (
  `cat_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cat_pid` int(11) unsigned NOT NULL DEFAULT '0',
  `cat_name` varchar(255) NOT NULL,
  `cat_slug` varchar(255) NOT NULL DEFAULT '',
  `cat_description` text,
  `cat_image` varchar(255) DEFAULT NULL,
  `cat_template` varchar(255) DEFAULT NULL,
  `cat_list_template` varchar(255) DEFAULT NULL,
  `cat_weight` int(11) NOT NULL DEFAULT '0',
  `cat_created` int(11) NOT NULL DEFAULT '0',
  `cat_updated` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`cat_id`),
  KEY `cat_pid` (`cat_pid`),
  KEY `cat_weight` (`cat_weight`),
  UNIQUE KEY `cat_slug` (`cat_slug`)
) ENGINE=InnoDB;

CREATE TABLE `xcreate_items` (
  `item_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `item_cat_id` int(11) unsigned NOT NULL,
  `item_title` varchar(255) NOT NULL,
  `item_slug` varchar(255) NOT NULL DEFAULT '',
  `item_description` text,
  `item_uid` int(11) unsigned NOT NULL,
  `item_created` int(11) NOT NULL DEFAULT '0',
  `item_updated` int(11) NOT NULL DEFAULT '0',
  `item_published` int(11) NOT NULL DEFAULT '0',
  `item_status` tinyint(1) NOT NULL DEFAULT '0',
  `item_hits` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `item_slug` (`item_slug`),
  KEY `item_cat_id` (`item_cat_id`),
  KEY `item_uid` (`item_uid`),
  KEY `item_status` (`item_status`),
  KEY `item_published` (`item_published`)
) ENGINE=InnoDB;

CREATE TABLE `xcreate_fields` (
  `field_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `field_cat_id` int(11) unsigned NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_label` varchar(255) NOT NULL,
  `field_type` varchar(50) NOT NULL,
  `field_description` text,
  `field_required` tinyint(1) NOT NULL DEFAULT '0',
  `field_repeatable` tinyint(1) NOT NULL DEFAULT '0',
  `field_default_value` text,
  `field_validation` varchar(100) DEFAULT NULL,
  `field_weight` int(11) NOT NULL DEFAULT '0',
  `field_status` tinyint(1) NOT NULL DEFAULT '1',
  `field_created` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`field_id`),
  KEY `field_cat_id` (`field_cat_id`),
  KEY `field_type` (`field_type`),
  KEY `field_weight` (`field_weight`)
) ENGINE=InnoDB;

CREATE TABLE `xcreate_field_options` (
  `option_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `option_field_id` int(11) unsigned NOT NULL,
  `option_value` varchar(255) NOT NULL,
  `option_label` varchar(255) NOT NULL,
  `option_weight` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`option_id`),
  KEY `option_field_id` (`option_field_id`),
  KEY `option_weight` (`option_weight`)
) ENGINE=InnoDB;

CREATE TABLE `xcreate_field_values` (
  `value_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `value_item_id` int(11) unsigned NOT NULL,
  `value_field_id` int(11) unsigned NOT NULL,
  `value_index` int(11) NOT NULL DEFAULT '0',
  `value_text` text,
  `value_file` TEXT DEFAULT NULL,
  `value_created` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`value_id`),
  KEY `value_item_id` (`value_item_id`),
  KEY `value_field_id` (`value_field_id`),
  KEY `value_index` (`value_index`)
) ENGINE=InnoDB;

CREATE TABLE `xcreate_ratings` (
  `rating_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `rating_item_id` int(11) unsigned NOT NULL,
  `rating_uid` int(11) unsigned NOT NULL DEFAULT '0',
  `rating_ip` varchar(45) NOT NULL DEFAULT '',
  `rating_score` tinyint(1) NOT NULL DEFAULT '0',
  `rating_created` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`rating_id`),
  KEY `rating_item_id` (`rating_item_id`),
  KEY `rating_uid` (`rating_uid`),
  UNIQUE KEY `unique_member_vote` (`rating_item_id`,`rating_uid`),
  KEY `rating_ip` (`rating_ip`)
) ENGINE=InnoDB;
