-- phpMyAdmin SQL Dump
-- version 3.1.3.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Oct 25, 2009 at 11:22 AM
-- Server version: 5.1.33
-- PHP Version: 5.2.9

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `self_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(60) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=28 ;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`) VALUES
(7, 'khashayar', '2636735edd651071f75f5f68c00fea7ed74a9ad1', 'me@khashayar.me'),
(22, 'gholi', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'gholi@yahoo.com'),
(23, 'javid', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'javid@yahoo.com'),
(24, 'sara', '7c4a8d09ca3762af61e59520943dc26494f8941b', 'me@khashaxyar.me'),
(26, 'ندا', '38b043aa95997ab6e6d825f7ca9cabf2f319446f', 'neda@geroyan.com'),
(27, 'tabasom', 'a4bfd90079b4564e319dca5329d7e7e1a386e603', 'tabasom12@yahoo.com');
