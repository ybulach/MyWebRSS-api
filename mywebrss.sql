-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Client: localhost
-- Généré le: Ven 03 Mai 2013 à 15:48
-- Version du serveur: 5.5.24-log
-- Version de PHP: 5.3.13

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Base de données: `mywebrss`
--

-- --------------------------------------------------------

--
-- Structure de la table `articles`
--

CREATE TABLE IF NOT EXISTS `articles` (
  `article_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `feed_ref` bigint(10) NOT NULL,
  `article_url` varchar(255) NOT NULL,
  `article_guid` varchar(255) NOT NULL,
  `article_title` varchar(255) NOT NULL,
  `article_description` text NOT NULL,
  `article_image` varchar(255) NOT NULL,
  `article_date` int(10) NOT NULL,
  PRIMARY KEY (`article_id`),
  UNIQUE KEY `feed_ref_2` (`feed_ref`,`article_guid`),
  KEY `feed_ref` (`feed_ref`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `feeds`
--

CREATE TABLE IF NOT EXISTS `feeds` (
  `feed_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `feed_url` varchar(255) NOT NULL,
  `feed_title` varchar(255) NOT NULL,
  `feed_description` text NOT NULL,
  `feed_date` int(10) NOT NULL,
  PRIMARY KEY (`feed_id`),
  UNIQUE KEY `feed_url` (`feed_url`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `tokens`
--

CREATE TABLE IF NOT EXISTS `tokens` (
  `token_id` varchar(40) NOT NULL,
  `user_ref` bigint(10) NOT NULL,
  `token_date` int(10) NOT NULL,
  PRIMARY KEY (`token_id`),
  KEY `user_ref` (`user_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` bigint(10) NOT NULL AUTO_INCREMENT,
  `user_email` varchar(255) NOT NULL,
  `user_pass` varchar(40) NOT NULL,
  `user_date` int(10) NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_email` (`user_email`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `user_feeds`
--

CREATE TABLE IF NOT EXISTS `user_feeds` (
  `user_ref` bigint(10) NOT NULL,
  `feed_ref` bigint(10) NOT NULL,
  PRIMARY KEY (`user_ref`,`feed_ref`),
  KEY `feed_ref` (`feed_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`feed_ref`) REFERENCES `feeds` (`feed_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `tokens`
--
ALTER TABLE `tokens`
  ADD CONSTRAINT `tokens_ibfk_1` FOREIGN KEY (`user_ref`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `user_feeds`
--
ALTER TABLE `user_feeds`
  ADD CONSTRAINT `user_feeds_ibfk_3` FOREIGN KEY (`feed_ref`) REFERENCES `feeds` (`feed_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `user_feeds_ibfk_1` FOREIGN KEY (`user_ref`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
