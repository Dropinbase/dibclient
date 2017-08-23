-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               5.6.17 - MySQL Community Server (GPL)
-- Server OS:                    Win64
-- HeidiSQL Version:             9.4.0.5125
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Dumping database structure for dib_demo
CREATE DATABASE IF NOT EXISTS `dib_demo` /*!40100 DEFAULT CHARACTER SET utf8 */;
USE `dib_demo`;

-- Dumping structure for table dib_demo.client
DROP TABLE IF EXISTS `client`;
CREATE TABLE IF NOT EXISTS `client` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `start_date` date DEFAULT NULL,
  `phone` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `aaa` date DEFAULT NULL,
  `address2` varchar(200) DEFAULT NULL,
  `city_town` varchar(30) DEFAULT NULL,
  `country` varchar(30) DEFAULT NULL,
  `zip_code` varchar(30) DEFAULT NULL,
  `notes` text,
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `country` (`country`),
  KEY `city_town` (`city_town`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8;

-- Dumping data for table dib_demo.client: ~5 rows (approximately)
/*!40000 ALTER TABLE `client` DISABLE KEYS */;
INSERT INTO `client` (`id`, `name`, `start_date`, `phone`, `email`, `aaa`, `address2`, `city_town`, `country`, `zip_code`, `notes`, `updated`) VALUES
	(3, 'Cheese Kingxxxxxx', '2016-12-13', '9058106691', 'cheese@swars.com', NULL, NULL, NULL, NULL, NULL, 'fddfdf', '2017-08-09 15:00:58'),
	(4, 'Green Products', '2016-12-13', '1560119516aaaa', 'green@swars.com', NULL, NULL, NULL, NULL, NULL, NULL, '2017-03-29 14:12:18'),
	(7, 'APPTS', '2016-12-13', '9066466691', 'appts@swars.com', NULL, NULL, NULL, NULL, NULL, NULL, '2017-03-29 14:12:28'),
	(8, 'US Manifolds', '2017-01-25', '156716', 'us@swars.com', NULL, NULL, NULL, NULL, NULL, NULL, '2017-08-09 07:27:46'),
	(10, 'Perfect xxx', '2017-01-25', '6571455966', 'perfect@swars.com', NULL, NULL, NULL, NULL, NULL, NULL, '2017-03-29 14:12:49');
/*!40000 ALTER TABLE `client` ENABLE KEYS */;

-- Dumping structure for table dib_demo.client_contact
DROP TABLE IF EXISTS `client_contact`;
CREATE TABLE IF NOT EXISTS `client_contact` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) NOT NULL,
  `first_name` varchar(35) NOT NULL,
  `last_name` varchar(35) NOT NULL,
  `position` varchar(50) DEFAULT '0',
  `email` varchar(150) DEFAULT NULL,
  `phone_w` varchar(35) DEFAULT NULL,
  `mobile` varchar(35) DEFAULT NULL,
  `resigned` date DEFAULT NULL,
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `notes` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `Firstname` (`first_name`),
  KEY `Surname` (`last_name`),
  KEY `FK_client_contact` (`client_id`),
  KEY `email` (`email`),
  CONSTRAINT `FK_client_client_contact` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6859 DEFAULT CHARSET=utf8;

-- Dumping data for table dib_demo.client_contact: ~36 rows (approximately)
/*!40000 ALTER TABLE `client_contact` DISABLE KEYS */;
INSERT INTO `client_contact` (`id`, `client_id`, `first_name`, `last_name`, `position`, `email`, `phone_w`, `mobile`, `resigned`, `updated`, `notes`) VALUES
	(2486, 3, 'Grobbie', 'Frey', '0', NULL, '1105106612', NULL, NULL, '2016-11-21 14:16:10', NULL),
	(2487, 4, 'Charles', 'Hamlin', '0', NULL, '3607119537', NULL, NULL, '2016-11-21 14:11:43', NULL),
	(2489, 7, 'Marieta', 'Rassloff', '0', 'peter@tgris.co.za', '8610466687', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(2490, 8, 'Sandra', 'Rohwer', '0', 'Sandra1_Roh@uhun.ac.za', '111212', '1112490112', NULL, '2017-03-05 09:17:36', NULL),
	(2492, 10, 'Patrick', 'Smith', '0', 'Patrick_Smi@wjebo.co.za', '6116455962', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(2545, 4, 'Andre 4', 'Van den Vyver', '0', NULL, '8713751188', NULL, NULL, '2016-11-25 10:28:45', NULL),
	(3616, 3, 'Jan 2', 'Wever', '1', NULL, '8174102582', NULL, NULL, '2016-11-21 14:31:39', NULL),
	(3742, 4, 'Anton', 'van der Maal', '1', 'Anton_van@yrtpresort.com', '3405653735', NULL, NULL, '2016-11-25 10:28:42', NULL),
	(3846, 8, 'Lylian', 'Crouch', '1', 'Lylian_Cro@ygun.ac.za', NULL, NULL, NULL, '2016-11-21 14:44:57', NULL),
	(3938, 3, 'Karien', 'James', '1', 'Karien_Jam@mgarmalat.co.za', '3764103538', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(3960, 3, 'Shamilla', 'Abrahams', '1', NULL, NULL, NULL, NULL, '2016-11-25 10:29:45', NULL),
	(4077, 3, 'Gamieldien', 'A Parker', '1', 'GamieldienAPar@vheathersa.co.za', '1518262416', NULL, NULL, '2016-11-25 10:28:03', NULL),
	(4081, 3, 'Wendy 2', 'Adams', '1', 'waltertopp@zlweb.co.za', '1525084516', NULL, NULL, '2016-11-25 10:29:51', NULL),
	(4097, 7, 'Johan 1', '_', '1', 'desiree@peris.co.za', '1555785316', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(4297, 8, 'Josef', 'Aggenbach', '1', 'sagen@huun.ac.za', '1921808420', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(4338, 4, 'Wendy', 'van Buuren', '1', 'Wendy_van@wplabs.ac.za', '4496390145', NULL, NULL, '2016-11-25 10:28:48', NULL),
	(4732, 3, 'Mia', 'Engelbrecht', '1', 'Mia_Eng@asarmalat.co.za', '21785143', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(4747, 7, 'Anchen', 'Vassen', '1', 'info@pkris.co.za', '7745785378', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(4838, 4, 'Cheryl', 'Abe', '1', NULL, '5412852555', NULL, NULL, '2016-11-25 10:30:02', NULL),
	(4855, 7, 'Abduallah', 'Jappie', '1', NULL, '7943785380', NULL, NULL, '2016-11-21 14:19:22', NULL),
	(5040, 7, 'Brian 2', 'Van der Merwe', '1', 'Brian2_Van@weris.co.za', '78178538', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(5114, 7, 'Roy', 'Newham', '1', 'Roy_New@sjris.co.za', '5917785360', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(5133, 7, 'Bengs', 'Paxton', '1', NULL, '3452702135', NULL, NULL, '2016-11-21 14:19:51', NULL),
	(5246, 3, 'Khosro 2', 'De Jager', '1', 'Khosro2_De@udarmalat.co.za', '6159103562', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(5381, 7, 'Keith', 'Du Toit', '1', 'reception@zxris.co.za', '3906785340', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(5576, 4, 'Armen', 'A Parker', '1', NULL, '1763402318', NULL, NULL, '2016-11-25 10:28:24', NULL),
	(5662, 3, 'Berty', 'Mouton', '1', 'Berty_Mou@ufarmalat.co.za', '692070', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(5855, 8, 'Benjamin', 'Ritter', '1', 'Benja_Ritter@foun.ac.za', '9774808998', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(6331, 4, 'Melanie', 'Abrahams', '1', 'seunalegbeleye@nhmail.com', '6457', NULL, NULL, '2016-11-25 10:28:29', NULL),
	(6422, 3, 'Le Roux', 'Bladergroen', '1', 'LeRoux_Bla@agarmalat.co.za', '8312809184', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(6463, 3, 'Jolene 3', 'Anderson', '1', 'tanya.bergstedt@pbarmalat.co.za', '8879', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(6635, 3, 'Mau', 'Brown', '1', NULL, '1202809113', NULL, NULL, '2016-11-21 14:32:11', 'vurkhyser man'),
	(6636, 7, 'Bernard', 'A Parker', '1', 'Bernard_AP@gnmail.com', NULL, NULL, NULL, '2016-11-25 10:28:22', NULL),
	(6640, 7, 'Christoff', 'Ellis', '1', 'Christoff_Ell@krweb.co.za', '371138', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(6683, 7, 'Janette', 'Immelmann', '1', 'mirandi.pris@boweb.co.za', '1290425113', NULL, NULL, '2016-11-21 14:44:57', NULL),
	(6858, 7, 'Alfred', 'Applewhite', '0', 'Alfred_App@ufris.co.za', '911092', NULL, NULL, '2016-11-21 14:44:57', '');
/*!40000 ALTER TABLE `client_contact` ENABLE KEYS */;

-- Dumping structure for table dib_demo.project
DROP TABLE IF EXISTS `project`;
CREATE TABLE IF NOT EXISTS `project` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `client_id` int(11) NOT NULL,
  `project_leader_id` int(11) unsigned DEFAULT NULL,
  `notes` text,
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `FK_project_client` (`client_id`),
  KEY `FK_project_staff` (`project_leader_id`),
  CONSTRAINT `FK_project_client` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `FK_project_staff` FOREIGN KEY (`project_leader_id`) REFERENCES `staff` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- Dumping data for table dib_demo.project: ~6 rows (approximately)
/*!40000 ALTER TABLE `project` DISABLE KEYS */;
INSERT INTO `project` (`id`, `name`, `client_id`, `project_leader_id`, `notes`, `updated`) VALUES
	(2, 'fishing', 3, 2, '', '2017-01-22 18:45:13'),
	(3, 'swording', 3, NULL, 'as', '2016-12-15 17:12:29'),
	(4, 'mountains', 4, NULL, 'aaff', '2017-01-25 23:31:06'),
	(5, 'rocks', 7, NULL, 'dd', '2016-12-26 11:56:46'),
	(6, 'hoep-hoeps', 4, NULL, NULL, '2016-08-31 13:36:10'),
	(7, 'quilts', 7, NULL, 'aaff', '2016-12-26 12:18:19');
/*!40000 ALTER TABLE `project` ENABLE KEYS */;

-- Dumping structure for table dib_demo.skill
DROP TABLE IF EXISTS `skill`;
CREATE TABLE IF NOT EXISTS `skill` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- Dumping data for table dib_demo.skill: ~7 rows (approximately)
/*!40000 ALTER TABLE `skill` DISABLE KEYS */;
INSERT INTO `skill` (`id`, `name`, `notes`) VALUES
	(1, 'ExtJs', NULL),
	(2, 'Oracle', NULL),
	(3, 'Java', NULL),
	(4, 'MySql', ''),
	(5, 'MsSql', ''),
	(6, 'Php', ''),
	(7, 'NodeJs', '');
/*!40000 ALTER TABLE `skill` ENABLE KEYS */;

-- Dumping structure for table dib_demo.staff
DROP TABLE IF EXISTS `staff`;
CREATE TABLE IF NOT EXISTS `staff` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `staff_code` varchar(10) NOT NULL,
  `email` varchar(200) NOT NULL,
  `mobile` varchar(30) NOT NULL,
  `skype` varchar(40) DEFAULT NULL,
  `position` enum('Employee','Developer','Manager','Project Manager','HR Manager','Sales Manager','Recruitment Director') NOT NULL,
  `join_date` date NOT NULL,
  `pef_login_id` int(10) unsigned NOT NULL,
  `notes` text,
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `first_name_last_name` (`first_name`,`last_name`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `staff_code` (`staff_code`),
  KEY `first_name` (`first_name`),
  KEY `last_name` (`last_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;

-- Dumping data for table dib_demo.staff: ~2 rows (approximately)
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` (`id`, `first_name`, `last_name`, `staff_code`, `email`, `mobile`, `skype`, `position`, `join_date`, `pef_login_id`, `notes`, `updated`) VALUES
	(1, 'Dave', 'Grosvenor', '123444', 'd@aaa.com', 'aaaa', NULL, 'Employee', '2017-03-14', 0, 'ffff\nff\n\nff', '2017-03-03 14:01:05'),
	(2, 'Melony', 'Peace', '45', 'm@aac.com', 'sdfsadf', NULL, 'Developer', '0000-00-00', 0, NULL, '2016-08-31 13:37:55');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;

-- Dumping structure for table dib_demo.staff_project
DROP TABLE IF EXISTS `staff_project`;
CREATE TABLE IF NOT EXISTS `staff_project` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) unsigned NOT NULL,
  `project_id` int(11) unsigned NOT NULL,
  `date_from` date NOT NULL,
  `date_to` date DEFAULT NULL,
  `notes` varchar(50) DEFAULT NULL,
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `FK_staff_project_project` (`project_id`),
  KEY `FK_staff_project_staff` (`staff_id`),
  CONSTRAINT `FK_staff_project_project` FOREIGN KEY (`project_id`) REFERENCES `project` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `FK_staff_project_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;

-- Dumping data for table dib_demo.staff_project: ~7 rows (approximately)
/*!40000 ALTER TABLE `staff_project` DISABLE KEYS */;
INSERT INTO `staff_project` (`id`, `staff_id`, `project_id`, `date_from`, `date_to`, `notes`, `updated`) VALUES
	(1, 1, 4, '2016-11-17', '2016-12-19', 'gggggg', '2016-12-26 12:18:29'),
	(2, 2, 4, '2016-11-18', '2016-12-14', NULL, '2016-11-10 14:46:54'),
	(3, 1, 2, '2016-11-07', '2016-12-15', NULL, '2016-11-21 14:47:13'),
	(4, 1, 3, '2016-11-21', '2016-12-03', NULL, '2016-11-21 14:47:31'),
	(5, 2, 5, '2016-11-21', '2016-11-21', 'n', '2016-12-26 11:56:54'),
	(6, 2, 3, '2017-11-21', NULL, NULL, '2016-11-21 14:48:11'),
	(7, 1, 5, '2017-11-21', NULL, NULL, '2017-01-25 23:40:04');
/*!40000 ALTER TABLE `staff_project` ENABLE KEYS */;

-- Dumping structure for table dib_demo.staff_skill
DROP TABLE IF EXISTS `staff_skill`;
CREATE TABLE IF NOT EXISTS `staff_skill` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) unsigned NOT NULL,
  `skill_id` int(11) unsigned NOT NULL,
  `rating` tinyint(1) unsigned NOT NULL,
  `yrs_of_exp` float unsigned NOT NULL,
  `self_comment` varchar(250) DEFAULT NULL,
  `manager_comment` varchar(250) DEFAULT NULL,
  `file1` varchar(50) DEFAULT NULL,
  `file2` varchar(50) DEFAULT NULL,
  `updated` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id_skill_id` (`staff_id`,`skill_id`),
  KEY `FK_staff_skill_skill` (`skill_id`),
  CONSTRAINT `FK_staff_skill_skill` FOREIGN KEY (`skill_id`) REFERENCES `skill` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `FK_staff_skill_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

-- Dumping data for table dib_demo.staff_skill: ~4 rows (approximately)
/*!40000 ALTER TABLE `staff_skill` DISABLE KEYS */;
INSERT INTO `staff_skill` (`id`, `staff_id`, `skill_id`, `rating`, `yrs_of_exp`, `self_comment`, `manager_comment`, `file1`, `file2`, `updated`) VALUES
	(1, 1, 1, 5, 4, 'brrrrr this is coool!', ':-) next is something in Africa', NULL, NULL, '2016-11-21 14:50:12'),
	(2, 1, 4, 4, 6, 'growing to be going', 'going going come back!', NULL, NULL, '2016-11-21 14:49:40'),
	(3, 2, 7, 3, 2, 'I know js so... ', 'you sure ?', NULL, NULL, '2016-11-21 14:52:03'),
	(4, 2, 2, 1, 1, 'don\'t ask for any prophecies... yet', 'hehe :-)', NULL, NULL, '2016-11-21 14:56:35');
/*!40000 ALTER TABLE `staff_skill` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
