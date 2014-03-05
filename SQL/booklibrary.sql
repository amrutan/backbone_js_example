-- phpMyAdmin SQL Dump
-- version 3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Mar 05, 2014 at 05:48 AM
-- Server version: 5.5.24-log
-- PHP Version: 5.3.13

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `booklibrary`
--

-- --------------------------------------------------------

--
-- Table structure for table `book`
--

CREATE TABLE IF NOT EXISTS `book` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_title` varchar(255) NOT NULL,
  `author` varchar(80) NOT NULL,
  `release_date` date NOT NULL,
  `keywords` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=24 ;

--
-- Dumping data for table `book`
--

INSERT INTO `book` (`id`, `book_title`, `author`, `release_date`, `keywords`) VALUES
(1, 'Javascript Step 1: The good parts', 'Douglas Crockford', '2014-02-27', 'JavaScript Programming'),
(2, 'The little book on coffee script', 'Alex McGraw', '2014-02-27', 'Coffeescript Programming'),
(9, 'test title edited', 'aaaa', '2014-03-02', 'test'),
(12, 'new test title', 'new test author', '2014-03-02', 'js'),
(13, 'test title 33', 'test author 33', '2014-03-02', 'js'),
(14, 'test title 33', 'test author 33', '2014-03-02', 'js'),
(20, 'test title 33', 'Douglas Crockford', '2014-03-02', 'js'),
(21, 'Javascript: The good parts', 'Douglas Crockford', '2014-03-02', 'Coffeescript Programming'),
(22, 'Backbone test title', 'test author', '2014-03-02', 'js');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
