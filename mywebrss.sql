SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `articles` (
  `article_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `feed_ref` bigint(10) NOT NULL,
  `article_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `article_guid` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `article_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `article_description` text COLLATE utf8_unicode_ci NOT NULL,
  `article_image` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `article_date` int(10) NOT NULL,
  PRIMARY KEY (`article_id`),
  UNIQUE KEY `feed_ref_2` (`feed_ref`,`article_guid`),
  KEY `feed_ref` (`feed_ref`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `feeds` (
  `feed_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `feed_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `feed_title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `feed_description` text COLLATE utf8_unicode_ci NOT NULL,
  `feed_date` int(10) NOT NULL,
  PRIMARY KEY (`feed_id`),
  UNIQUE KEY `feed_url` (`feed_url`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `tokens` (
  `token_id` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  `user_ref` bigint(10) NOT NULL,
  `token_date` int(10) NOT NULL,
  PRIMARY KEY (`token_id`),
  KEY `user_ref` (`user_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `user_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `user_pass` varchar(40) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_email` (`user_email`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_articles` (
  `user_ref` bigint(10) NOT NULL,
  `article_ref` bigint(10) NOT NULL,
  UNIQUE KEY `user_ref` (`user_ref`,`article_ref`),
  KEY `article_ref` (`article_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_feeds` (
  `user_ref` bigint(10) NOT NULL,
  `feed_ref` bigint(10) NOT NULL,
  PRIMARY KEY (`user_ref`,`feed_ref`),
  KEY `feed_ref` (`feed_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`feed_ref`) REFERENCES `feeds` (`feed_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `tokens`
  ADD CONSTRAINT `tokens_ibfk_1` FOREIGN KEY (`user_ref`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_articles`
  ADD CONSTRAINT `user_articles_ibfk_1` FOREIGN KEY (`user_ref`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_articles_ibfk_2` FOREIGN KEY (`article_ref`) REFERENCES `articles` (`article_id`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `user_feeds`
  ADD CONSTRAINT `user_feeds_ibfk_1` FOREIGN KEY (`user_ref`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user_feeds_ibfk_3` FOREIGN KEY (`feed_ref`) REFERENCES `feeds` (`feed_id`) ON UPDATE CASCADE;
