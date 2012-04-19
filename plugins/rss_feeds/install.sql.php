<?php /*

DROP TABLE IF EXISTS `PREFIX_rss_feed`;
CREATE TABLE `PREFIX_rss_feed` (
  `id` int(11) NOT NULL auto_increment,
  `title` varchar(128) collate utf8_hungarian_ci NOT NULL,
  `source` varchar(256) collate utf8_hungarian_ci NOT NULL,
  `item_nums` varchar(128) collate utf8_hungarian_ci NOT NULL default '5',
  `option_enabled` tinyint(1) NOT NULL default '1',
  `bearing` int(11) NOT NULL default '5',
  `date_create` int(11) NOT NULL default '0',
  `date_modify` int(11) NOT NULL default '0',
  `user_create_id` int(11) NOT NULL default '0',
  `user_modify_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `option_enabled` (`option_enabled`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_hungarian_ci ;

INSERT INTO `PREFIX_core_param` VALUES (NULL, 'RSS_CACHE_FOLDER', '', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1,1);
INSERT INTO `PREFIX_core_param` VALUES (NULL, 'RSS_CDATA', 'strip', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1,1);
INSERT INTO `PREFIX_core_param` VALUES (NULL, 'RSS_CODE_PAGE', 'UTF-8', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1,1);
INSERT INTO `PREFIX_core_param` VALUES (NULL, 'RSS_DEFAULT_CODE_PAGE', 'UTF-8', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1,1);
INSERT INTO `PREFIX_core_param` VALUES (NULL, 'RSS_STRIPHTML', '0', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), 1,1);
INSERT INTO `PREFIX_core_param` VALUES (NULL, 'RSS_CACHE_TIME', '86400', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), '1', '1');

*/ ?>
