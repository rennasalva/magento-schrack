-- phpMyAdmin SQL Dump
-- version 4.2.8.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 19, 2016 at 07:41 AM
-- Server version: 5.6.28-log
-- PHP Version: 5.3.5

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `mage`
--

-- --------------------------------------------------------

--
-- Table structure for table `anowave_ab`
--

DROP TABLE IF EXISTS `anowave_ab`;
CREATE TABLE IF NOT EXISTS `anowave_ab` (
`ab_id` int(6) NOT NULL,
  `ab_experiment` varchar(255) DEFAULT NULL,
  `ab_experiment_theme` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `anowave_ab_attributes`
--

DROP TABLE IF EXISTS `anowave_ab_attributes`;
CREATE TABLE IF NOT EXISTS `anowave_ab_attributes` (
`ab_attribute_id` int(6) NOT NULL,
  `ab_id` int(6) NOT NULL,
  `ab_attribute_code` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `anowave_ab_data`
--

DROP TABLE IF EXISTS `anowave_ab_data`;
CREATE TABLE IF NOT EXISTS `anowave_ab_data` (
`data_id` bigint(21) NOT NULL,
  `data_ab_id` int(6) DEFAULT NULL,
  `data_product_id` int(10) unsigned NOT NULL,
  `data_attribute_code` varchar(255) DEFAULT NULL,
  `data_attribute_content` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `anowave_ab_store`
--

DROP TABLE IF EXISTS `anowave_ab_store`;
CREATE TABLE IF NOT EXISTS `anowave_ab_store` (
`ab_primary_id` int(6) NOT NULL,
  `ab_id` int(6) NOT NULL,
  `ab_store_id` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `anowave_ab`
--
ALTER TABLE `anowave_ab`
 ADD PRIMARY KEY (`ab_id`);

--
-- Indexes for table `anowave_ab_attributes`
--
ALTER TABLE `anowave_ab_attributes`
 ADD PRIMARY KEY (`ab_attribute_id`), ADD UNIQUE KEY `ab_id` (`ab_id`,`ab_attribute_code`);

--
-- Indexes for table `anowave_ab_data`
--
ALTER TABLE `anowave_ab_data`
 ADD PRIMARY KEY (`data_id`), ADD KEY `data_product_id` (`data_product_id`), ADD KEY `data_ab_id` (`data_ab_id`);

--
-- Indexes for table `anowave_ab_store`
--
ALTER TABLE `anowave_ab_store`
 ADD PRIMARY KEY (`ab_primary_id`), ADD UNIQUE KEY `ab_id` (`ab_id`,`ab_store_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `anowave_ab`
--
ALTER TABLE `anowave_ab`
MODIFY `ab_id` int(6) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `anowave_ab_attributes`
--
ALTER TABLE `anowave_ab_attributes`
MODIFY `ab_attribute_id` int(6) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `anowave_ab_data`
--
ALTER TABLE `anowave_ab_data`
MODIFY `data_id` bigint(21) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `anowave_ab_store`
--
ALTER TABLE `anowave_ab_store`
MODIFY `ab_primary_id` int(6) NOT NULL AUTO_INCREMENT;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `anowave_ab_attributes`
--
ALTER TABLE `anowave_ab_attributes`
ADD CONSTRAINT `anowave_ab_attributes_ibfk_1` FOREIGN KEY (`ab_id`) REFERENCES `anowave_ab` (`ab_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `anowave_ab_data`
--
ALTER TABLE `anowave_ab_data`
ADD CONSTRAINT `anowave_ab_data_ibfk_1` FOREIGN KEY (`data_product_id`) REFERENCES `catalog_product_entity` (`entity_id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `anowave_ab_data_ibfk_2` FOREIGN KEY (`data_ab_id`) REFERENCES `anowave_ab` (`ab_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `anowave_ab_store`
--
ALTER TABLE `anowave_ab_store`
ADD CONSTRAINT `anowave_ab_store_ibfk_1` FOREIGN KEY (`ab_id`) REFERENCES `anowave_ab` (`ab_id`) ON DELETE CASCADE ON UPDATE CASCADE;
SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
