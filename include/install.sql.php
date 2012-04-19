<?php /*

DROP TABLE IF EXISTS `wht_lang`;
CREATE TABLE `wht_lang` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(64) collate utf8_hungarian_ci NOT NULL,
  `value` varchar(1024) collate utf8_hungarian_ci NOT NULL,
  `date_create` int(11) NOT NULL default '0',
  `date_modify` int(11) NOT NULL default '0',
  `user_create_id` int(11) NOT NULL default '0',
  `user_modify_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

INSERT INTO `wht_lang` VALUES (49, 'FORMAT_NAME', '%1$s %2$s', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (111, 'TITLE_SITE', '[TESZT] keresek.hu', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (176, 'LANG_TITLES', 'Magyar,English', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (177, 'CURRENCY_TITLES', 'Forint (Ft),Euro (€)', 1267446947, 1267454577, 1, 1);
INSERT INTO `wht_lang` VALUES (203, 'PRICE_DECIMAL_PLACES_EUR', '2', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (204, 'PRICE_DECIMAL_SEPARATOR_EUR', '.', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (205, 'PRICE_THOUSAND_SEPARATOR_EUR', ',', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (208, 'DATE_FORMAT_LONG', '%Y. %B %e., %A', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (209, 'DATE_FORMAT_LOCALE', 'hu_HU.UTF8', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (258, 'PRICE_THOUSAND_SEPARATOR_HUF', ' ', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (256, 'PRICE_FORMAT_HUF', '<nobr>%s&nbsp;Ft</nobr>', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (255, 'PRICE_FORMAT_EUR', '<nobr>€&nbsp;%s</nobr>', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (254, 'PRICE_DECIMAL_SEPARATOR_HUF', ',', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (252, 'PRICE_DECIMAL_PLACES_HUF', '0', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (250, 'META_KEYWORDS_DEFAULT', 'keresek,keresek.hu', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (249, 'META_DESCRIPTION_DEFAULT', 'keresek.hu - Hirdessen nálunk! Ingyen, korlátok nélkül.', 1267446947, 1267446947, 1, 1);
INSERT INTO `wht_lang` VALUES (259, 'DATETIME_FORMAT', '%Y.%m.%d. %R', 0, 0, 1, 1);
INSERT INTO `wht_lang` VALUES (260, 'TITLE_REGIST', 'Regisztrálás', 0, 0, 1, 1);
INSERT INTO `wht_lang` VALUES (261, 'TITLE_REGIST_SUCCESS', 'Sikeres regisztrálás', 0, 0, 1, 1);
INSERT INTO `wht_lang` VALUES (262, 'TITLE_PROFILE', 'Beállítások', 0, 0, 1, 1);
INSERT INTO `wht_lang` VALUES (263, 'TITLE_LOST_PASSWORD', 'Elfelejtett jelszó', 0, 0, 1, 1);

DROP TABLE IF EXISTS `wht_log_email`;
CREATE TABLE `log_email` (
  `id` int(11) NOT NULL auto_increment,
  `date` datetime NOT NULL,
  `to` varchar(128) collate utf8_hungarian_ci NOT NULL,
  `subject` varchar(128) collate utf8_hungarian_ci NOT NULL,
  `message` varchar(4096) collate utf8_hungarian_ci NOT NULL,
  `status` varchar(16) collate utf8_hungarian_ci NOT NULL,
  `comments` varchar(512) collate utf8_hungarian_ci default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

DROP TABLE IF EXISTS `wht_page`;
CREATE TABLE `wht_page` (
  `id` int(11) NOT NULL auto_increment,
  `is_system` tinyint(1) NOT NULL default '0',
  `parent_id` int(11) NOT NULL default '0',
  `title` varchar(64) collate utf8_hungarian_ci NOT NULL default 'noname',
  `image` varchar(512) collate utf8_hungarian_ci NOT NULL,
  `quote` varchar(512) collate utf8_hungarian_ci NOT NULL,
  `description` text collate utf8_hungarian_ci,
  `meta_keywords` varchar(256) collate utf8_hungarian_ci NOT NULL,
  `meta_description` varchar(156) collate utf8_hungarian_ci NOT NULL,
  `date_publish` int(11) NOT NULL default '0',
  `date_hide` int(11) NOT NULL default '2147468400',
  `link` varchar(128) collate utf8_hungarian_ci default NULL,
  `bearing` int(11) NOT NULL default '5',
  `refered_page_ids` varchar(256) collate utf8_hungarian_ci default NULL,
  `theme_id` int(11) NOT NULL default '1',
  `option_mainmenu` tinyint(1) NOT NULL default '0',
  `option_submenu` tinyint(1) NOT NULL default '0',
  `option_basemenu` tinyint(1) NOT NULL default '0',
  `date_create` int(11) NOT NULL default '0',
  `date_modify` int(11) NOT NULL default '0',
  `user_create_id` int(11) NOT NULL default '0',
  `user_modify_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `parent_page_id` (`parent_id`),
  KEY `date_publish` (`date_publish`),
  KEY `date_publish_2` (`date_publish`),
  KEY `option_mainmenu` (`option_mainmenu`),
  KEY `option_sidemenu` (`option_submenu`),
  KEY `option_basemenu` (`option_basemenu`),
  KEY `parent_page_id_2` (`parent_id`,`bearing`,`title`),
  FULLTEXT KEY `description` (`description`),
  FULLTEXT KEY `title` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

INSERT INTO `wht_page` VALUES (1, 1, 0, 'Nyitólap', '', '', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur eget arcu tortor, quis suscipit neque. Aliquam mi metus, auctor sit amet ultrices at, iaculis vel turpis. Ut sodales cursus tellus sed ullamcorper. Ut nisl orci, mollis non placerat a, gravida nec dolor. Mauris interdum dictum pharetra. In quis metus risus. Morbi non urna nisi. Cras eu eleifend odio. Maecenas consectetur nulla in mauris sagittis interdum. Maecenas metus justo, facilisis id fermentum non, dignissim et metus. Curabitur ultrices velit ut sem pharetra vitae aliquam lacus suscipit. Maecenas leo velit, laoreet ac viverra sed, mattis in sapien. Duis at lacus dolor. Sed posuere dui ut tortor ullamcorper eleifend. Maecenas at urna erat, sit amet congue urna. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nullam rhoncus, lorem id semper iaculis, nisl tortor sollicitudin est, ut egestas nisi nibh vestibulum sapien. Integer nec odio vitae dui sagittis commodo. Nullam elementum neque vitae est facilisis volutpat tincidunt neque pulvinar.\r\n\r\nSed at elit felis, ut mattis eros. Duis vel nulla magna. Duis eros est, vulputate vel placerat ac, dapibus nec nisi. Donec euismod mauris quis sem dictum pellentesque. Fusce eu nunc quam. Sed vitae quam enim. Phasellus pulvinar auctor iaculis. Ut et neque sed odio convallis rutrum id non magna. In hac habitasse platea dictumst. Nulla tincidunt sem id sem euismod tristique. Fusce tempus quam nec ante tincidunt bibendum. Proin nisi tortor, consequat eu pretium id, pharetra sed tellus. Vivamus vehicula, lorem ac elementum aliquam, sapien odio fermentum odio, eget vestibulum ante turpis eget lacus. Donec in nisi eget ipsum convallis accumsan et quis ligula. Ut elementum erat at leo egestas et consequat ante sodales. Integer tincidunt venenatis enim, sed adipiscing lectus lacinia in.\r\n\r\nFusce vitae velit at purus ultrices posuere convallis sit amet leo. Nam id sapien a sem aliquam tempor vel lacinia sapien. Suspendisse ut purus odio. Phasellus magna urna, ultricies ac rhoncus ut, elementum sed nulla. Ut sem erat, sodales at eleifend eget, laoreet sed tellus. Vivamus mollis, enim ac egestas adipiscing, eros turpis feugiat tellus, vel aliquam elit nibh vitae turpis. Sed sem lorem, tempus id porta ac, ornare ac magna. Nam pretium libero at enim adipiscing quis hendrerit purus commodo. Mauris id purus velit. Aliquam viverra, ipsum in blandit tincidunt, mauris nisl dapibus ipsum, id pretium leo ligula vitae massa. Vivamus ut elit erat. In hac habitasse platea dictumst. Nullam rhoncus tellus a erat tincidunt porttitor. Quisque leo erat, hendrerit ut condimentum eget, condimentum varius eros. Vestibulum elementum rhoncus leo ut bibendum. Fusce accumsan aliquet laoreet. Morbi vel odio diam. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae;\r\n\r\nNam vitae risus justo. Sed nec nulla ut nunc congue accumsan nec ac enim. Suspendisse bibendum adipiscing tellus, venenatis tristique arcu volutpat id. Integer posuere volutpat auctor. Donec velit leo, suscipit at imperdiet quis, porttitor at nibh. Fusce bibendum scelerisque sem eu feugiat. In luctus aliquam orci, quis elementum lorem dapibus et. In eu eros sit amet velit porta varius. Curabitur sed magna lectus, in dignissim ipsum. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris sed hendrerit lorem.\r\n\r\nAliquam at eros vitae ligula semper porta sed sed odio. Fusce vitae nisi ut dolor fermentum mattis. Etiam libero dolor, tempor a sodales eu, placerat at libero. Nam semper eleifend mi, ac varius enim elementum porttitor. Quisque lacus augue, scelerisque lobortis hendrerit nec, vehicula ut sapien. Vestibulum et tortor eget est elementum fermentum vel vehicula enim. Proin a luctus libero. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia Curae; Aliquam condimentum venenatis enim, quis malesuada lectus pellentesque id. Nam magna magna, fermentum et euismod nec, vulputate vitae ligula. Curabitur tincidunt suscipit lorem nec dapibus. Cras aliquam, est volutpat dictum ultrices, dui nisi auctor tellus, vel viverra lorem nunc ac nunc.', '', '', 0, 2147468400, NULL, 5, NULL, 1, 0, 0, 1, 1264943948, 1264943948, 1, 1);

DROP TABLE IF EXISTS `wht_param`;
CREATE TABLE `wht_param` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(64) collate utf8_hungarian_ci NOT NULL,
  `value` varchar(1024) collate utf8_hungarian_ci NOT NULL,
  `date_create` int(11) NOT NULL default '0',
  `date_modify` int(11) NOT NULL default '0',
  `user_create_id` int(11) NOT NULL default '0',
  `user_modify_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci AUTO_INCREMENT=19 ;

INSERT INTO `wht_param` VALUES (1, 'OPEN_PAGE_ID', '1', 1267447345, 1267447345, 1, 1);
INSERT INTO `wht_param` VALUES (3, 'AUTOLOGIN_EXPIRE_SECONDS', '2592000', 1267447345, 1267447345, 1, 1);
INSERT INTO `wht_param` VALUES (6, 'LANG_ENABLED', 'hu,en', 1267447345, 1267447345, 1, 1);
INSERT INTO `wht_param` VALUES (7, 'CURRENCY_ENABLED', 'huf,eur', 1267447345, 1267447345, 1, 1);
INSERT INTO `wht_param` VALUES (8, 'LANG_POSTFIX', ',_en', 1267447345, 1267447345, 1, 1);
INSERT INTO `wht_param` VALUES (9, 'LANG_DEFAULT', 'hu', 1267447345, 1267447345, 1, 1);
INSERT INTO `wht_param` VALUES (10, 'CURRENCY_DEFAULT', 'huf', 1267447345, 1267447345, 1, 1);
INSERT INTO `wht_param` VALUES (12, 'DEFAULT_EMAIL_ADDRESS', 'info@keresek.hu', 1267447345, 1267447345, 1, 1);
INSERT INTO `wht_param` VALUES (13, 'DEFAULT_EMAIL_NAME', '[TESZT] keresek.hu', 1267447345, 1267447345, 1, 1);
INSERT INTO `wht_param` VALUES (17, 'META_DESCRIPTION_DEFAULT', 'keresek.hu - Hirdessen nálunk! Ingyen, korlátok nélkül.', 1267447345, 1267447345, 1, 1);
INSERT INTO `wht_param` VALUES (18, 'META_KEYWORDS_DEFAULT', 'keresek,keresek.hu', 1267447345, 1267447345, 1, 1);

DROP TABLE IF EXISTS `wht_plugin`;
CREATE TABLE `wht_plugin` (
  `id` int(11) NOT NULL auto_increment,
  `path` varchar(256) collate utf8_hungarian_ci NOT NULL,
  `title` varchar(128) collate utf8_hungarian_ci NOT NULL default 'noname',
  `version` varchar(32) collate utf8_hungarian_ci NOT NULL default '0.1',
  `description` varchar(1024) collate utf8_hungarian_ci NOT NULL,
  `option_active` tinyint(1) NOT NULL default '0',
  `date_create` int(11) NOT NULL default '0',
  `date_modify` int(11) NOT NULL default '0',
  `user_create_id` int(11) NOT NULL default '0',
  `user_modify_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `title` (`title`),
  KEY `option_active` (`option_active`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

DROP TABLE IF EXISTS `wht_theme`;
CREATE TABLE `wht_theme` (
  `id` int(11) NOT NULL auto_increment,
  `is_system` tinyint(1) NOT NULL default '0',
  `title` varchar(32) collate utf8_hungarian_ci NOT NULL default 'noname',
  `template_path` varchar(512) collate utf8_hungarian_ci NOT NULL,
  `date_create` int(11) NOT NULL default '0',
  `date_modify` int(11) NOT NULL default '0',
  `user_create_id` int(11) NOT NULL default '0',
  `user_modify_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `name` (`title`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

INSERT INTO `wht_theme` VALUES (1, 1, 'default', '/templates/default', 1267447376, 1267447376, 1, 1);

DROP TABLE IF EXISTS `wht_user`;
CREATE TABLE `wht_user` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(32) collate utf8_hungarian_ci NOT NULL,
  `password` varchar(32) collate utf8_hungarian_ci NOT NULL,
  `surname` varchar(64) collate utf8_hungarian_ci NOT NULL,
  `forename` varchar(64) collate utf8_hungarian_ci NOT NULL,
  `email` varchar(128) collate utf8_hungarian_ci default NULL,
  `user_group_id` int(11) NOT NULL default '1',
  `company` varchar(128) collate utf8_hungarian_ci NOT NULL,
  `taxnumber` varchar(16) collate utf8_hungarian_ci NOT NULL,
  `phone` varchar(16) collate utf8_hungarian_ci NOT NULL,
  `fax` varchar(16) collate utf8_hungarian_ci NOT NULL,
  `address` varchar(256) collate utf8_hungarian_ci NOT NULL,
  `lang` varchar(5) collate utf8_hungarian_ci NOT NULL default 'hu',
  `timezone` varchar(64) collate utf8_hungarian_ci NOT NULL default 'Europe/Budapest',
  `date_birth` int(11) NOT NULL default '0',
  `date_registered` int(11) NOT NULL default '0',
  `date_last_login` int(11) NOT NULL default '0',
  `option_banned` tinyint(1) NOT NULL default '0',
  `option_newsletter` tinyint(1) NOT NULL default '0',
  `option_email_from_registration` tinyint(1) NOT NULL default '0',
  `date_create` int(11) NOT NULL default '0',
  `date_modify` int(11) NOT NULL default '0',
  `user_create_id` int(11) NOT NULL default '0',
  `user_modify_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `username` (`username`),
  KEY `username_2` (`username`,`password`),
  KEY `option_newsletter` (`option_newsletter`),
  KEY `option_email_from_registration` (`option_email_from_registration`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

INSERT INTO `wht_user` VALUES (1, 'uborka', 'f2751f28ba7baef5f15d8b18a56d755a', 'Csuka', 'Ádám', 'uborka@ubipage.hu', 2, 'PTI Kft.', '14226623-2-13', '', '', '', 'hu', 'Europe/Budapest', 0, 1260215921, 1267783208, 0, 1, 1, 1260215921, 1263220939, 1, 1);

DROP TABLE IF EXISTS `wht_user_group`;
CREATE TABLE `wht_user_group` (
  `id` int(11) NOT NULL auto_increment,
  `is_system` tinyint(1) NOT NULL default '0',
  `title` varchar(32) collate utf8_hungarian_ci NOT NULL,
  `is_admin` tinyint(1) NOT NULL default '0',
  `date_create` int(11) NOT NULL default '0',
  `date_modify` int(11) NOT NULL default '0',
  `user_create_id` int(11) NOT NULL default '0',
  `user_modify_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

INSERT INTO `wht_user_group` VALUES (1, 1, 'felhasználó', 0, 1267447392, 1267447392, 1, 1);
INSERT INTO `wht_user_group` VALUES (2, 1, 'adminisztrátor', 1, 1267447392, 1267454048, 1, 1);

DROP TABLE IF EXISTS `wht_user_right`;
CREATE TABLE `wht_user_right` (
  `id` int(11) NOT NULL auto_increment,
  `user_group_id` int(11) NOT NULL,
  `menu` varchar(64) collate utf8_hungarian_ci NOT NULL,
  `rights` varchar(128) collate utf8_hungarian_ci default NULL,
  `date_create` int(11) NOT NULL default '0',
  `date_modify` int(11) NOT NULL default '0',
  `user_create_id` int(11) NOT NULL default '0',
  `user_modify_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `user_group_id` (`user_group_id`),
  KEY `user_group_id_2` (`user_group_id`,`menu`),
  KEY `menu` (`menu`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci;

INSERT INTO `wht_user_right` VALUES (1, 2, 'PAGE', 'access,edit,delete,new', 1267447409, 1267447409, 1, 1);
INSERT INTO `wht_user_right` VALUES (4, 2, 'THEME', 'access,edit,delete,new', 1267447409, 1267447409, 1, 1);
INSERT INTO `wht_user_right` VALUES (6, 2, 'PARAM', 'access,edit,delete,new', 1267447409, 1267447409, 1, 1);
INSERT INTO `wht_user_right` VALUES (7, 2, 'LANG', 'access,edit,delete,new', 1267447409, 1267447409, 1, 1);
INSERT INTO `wht_user_right` VALUES (8, 2, 'USER', 'access,edit,delete,new,email', 1267447409, 1267447409, 1, 1);
INSERT INTO `wht_user_right` VALUES (9, 2, 'USER_GROUP', 'access,edit,delete,new', 1267447409, 1267447409, 1, 1);
INSERT INTO `wht_user_right` VALUES (17, 2, 'LOG', 'access', 1267447409, 1267447409, 1, 1);
INSERT INTO `wht_user_right` VALUES (65, 2, 'PLUGIN', 'access,edit,delete', 1267447409, 1267447409, 1, 1);

*/ ?>
