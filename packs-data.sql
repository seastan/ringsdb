-- MySQL dump 10.13  Distrib 5.6.19, for osx10.7 (i386)
--
-- Host: localhost    Database: ringsdb_v2
-- ------------------------------------------------------
-- Server version	5.6.21

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Dumping data for table `cycle`
--

LOCK TABLES `cycle` WRITE;
/*!40000 ALTER TABLE `cycle` DISABLE KEYS */;
INSERT INTO `cycle` (`id`, `code`, `name`, `position`, `is_box`, `date_creation`, `date_update`, `is_saga`) VALUES (1,'core','Core Set',1,1,'2016-03-08 21:35:00','2016-03-08 21:35:00',0),(2,'som','Shadows of Mirkwood',2,0,'2016-01-23 11:43:23','2016-01-23 11:43:23',0),(3,'kd','Khazad-dûm',3,1,'2016-01-23 11:44:18','2016-01-23 11:44:18',0),(4,'dd','Dwarrowdelf',4,0,'2016-01-23 11:44:36','2016-01-23 11:44:36',0),(5,'hon','Heirs of Númenor',5,1,'2016-01-23 11:45:48','2016-01-23 11:45:48',0),(6,'ats','Against the Shadow',6,0,'2016-01-23 11:46:09','2016-01-23 11:46:09',0),(7,'voi','The Voice of Isengard',7,1,'2016-01-23 11:46:27','2016-01-23 11:46:27',0),(8,'rm','The Ring-maker',8,0,'2016-01-23 11:46:46','2016-01-23 11:46:46',0),(9,'tlr','The Lost Realm',9,1,'2016-01-23 11:47:02','2016-01-23 11:47:02',0),(10,'aa','Angmar Awakened',10,0,'2016-01-23 11:47:17','2016-01-23 11:47:17',0),(11,'gh','The Grey Havens',11,1,'2016-01-23 11:47:32','2016-01-23 11:47:32',0),(12,'dc','Dream-chaser',12,0,'2016-01-23 11:47:43','2016-01-23 11:47:43',0),(13,'h','The Hobbit',13,0,'2016-01-23 11:48:48','2016-03-17 00:44:08',1),(14,'lotr','The Lord of the Rings',14,0,'2016-01-23 11:49:13','2016-03-17 00:44:25',1);
/*!40000 ALTER TABLE `cycle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `pack`
--

LOCK TABLES `pack` WRITE;
/*!40000 ALTER TABLE `pack` DISABLE KEYS */;
INSERT INTO `pack` (`id`, `cycle_id`, `code`, `name`, `position`, `size`, `date_creation`, `date_update`, `date_release`) VALUES (1,1,'core','Core Set',1,73,'2016-03-08 21:39:56','2016-03-12 11:09:34','2011-04-20'),(2,2,'thfg','The Hunt for Gollum',1,10,'2016-01-23 11:53:15','2016-01-23 11:53:15','2011-07-21'),(3,2,'catc','Conflict at the Carrock',2,10,'2016-01-23 11:54:11','2016-01-23 11:54:11','2011-08-10'),(4,2,'ajtr','A Journey to Rhosgobel',3,10,'2016-01-23 11:54:47','2016-01-23 11:54:47','2011-09-01'),(5,2,'thoem','The Hills of Emyn Muil',4,10,'2016-01-23 11:55:43','2016-01-23 11:55:43','2011-09-30'),(6,2,'tdm','The Dead Marshes',5,10,'2016-01-23 11:56:21','2016-01-23 11:56:21','2011-11-02'),(7,2,'rtm','Return to Mirkwood',6,10,'2016-01-23 11:57:12','2016-01-23 11:57:12','2011-11-23'),(8,3,'kd','Khazad-dûm',1,13,'2016-01-23 12:07:03','2016-01-23 12:07:03','2012-01-06'),(9,4,'trg','The Redhorn Gate',1,10,'2016-01-23 12:09:03','2016-01-23 12:09:03','2012-03-01'),(10,4,'rtr','Road to Rivendell',2,10,'2016-01-23 12:12:56','2016-01-23 12:12:56','2012-03-21'),(11,4,'twitw','The Watcher in the Water',3,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2012-04-25'),(12,4,'tld','The Long Dark',4,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2012-05-16'),(13,4,'fos','Foundations of Stone',5,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2012-06-20'),(14,4,'saf','Shadow and Flame',6,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2012-08-08'),(15,5,'hon','Heirs of Númenor',1,18,'2016-03-08 21:39:56','2016-03-12 15:56:01','2012-11-26'),(16,6,'tsf','The Steward\'s Fear',1,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2013-05-09'),(17,6,'tdf','The Drúadan Forest',2,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2013-05-31'),(18,6,'eaad','Encounter at Amon Dîn',3,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2013-07-05'),(19,6,'aoo','Assault on Osgiliath',4,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2013-08-09'),(20,6,'tbog','The Blood of Gondor',5,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2013-10-17'),(21,6,'tmv','The Morgul Vale',6,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2013-11-15'),(22,7,'tvoi','The Voice of Isengard',1,15,'2016-03-08 21:39:56','2016-03-14 23:42:50','2014-02-21'),(23,8,'tdt','The Dunland Trap',1,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2014-06-26'),(24,8,'ttt','The Three Trials',2,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2014-07-24'),(25,8,'tit','Trouble in Tharbad',3,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2014-08-21'),(26,8,'tnie','The Nîn-in-Eilph',4,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2014-10-23'),(27,8,'cs','Celebrimbor\'s Secret',5,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2014-11-13'),(28,8,'tac','The Antlered Crown',6,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2014-12-23'),(29,9,'tlr','The Lost Realm',1,15,'2016-03-08 21:39:56','2016-03-14 23:43:28','2015-04-03'),(30,10,'twoe','The Wastes of Eriador',1,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2015-07-02'),(31,10,'efmg','Escape from Mount Gram',2,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2015-07-30'),(32,10,'ate','Across the Ettenmoors',3,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2015-09-03'),(33,10,'ttor','The Treachery of Rhudaur',4,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2015-09-24'),(34,10,'tbocd','The Battle of Carn Dûm',5,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2015-11-06'),(35,10,'tdr','The Dread Realm',6,10,'2016-03-08 21:39:56','2016-03-08 21:39:56','2015-12-17'),(36,11,'tgh','The Grey Havens',1,15,'2016-03-08 21:39:56','2016-03-15 00:49:47','2016-02-11'),(37,13,'ohauh','Over Hill and Under Hill',1,22,'2016-03-08 21:39:56','2016-03-17 01:07:04','2012-08-17'),(38,13,'otd','On the Doorstep',2,24,'2016-03-08 21:39:56','2016-03-17 01:07:13','2013-02-22'),(39,14,'tbr','The Black Riders',1,16,'2016-03-08 21:39:56','2016-03-17 21:44:27','2013-09-20'),(40,14,'trd','The Road Darkens',2,13,'2016-03-08 21:39:56','2016-03-08 21:39:56','2014-10-03'),(41,14,'ttos','The Treason of Saruman',3,13,'2016-03-08 21:39:56','2016-03-08 21:39:56','2015-04-23'),(42,14,'tlos','The Land of Shadow',4,13,'2016-03-08 21:39:56','2016-03-08 21:39:56','2015-11-19');
/*!40000 ALTER TABLE `pack` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `sphere`
--

LOCK TABLES `sphere` WRITE;
/*!40000 ALTER TABLE `sphere` DISABLE KEYS */;
INSERT INTO `sphere` (`id`, `code`, `name`, `is_primary`, `octgnid`) VALUES (1,'tactics','Tactics',1,NULL),(2,'spirit','Spirit',1,NULL),(3,'leadership','Leadership',1,NULL),(4,'lore','Lore',1,NULL),(5,'neutral','Neutral',1,NULL),(6,'baggins','Baggins',0,NULL),(7,'fellowship','Fellowship',0,NULL);
/*!40000 ALTER TABLE `sphere` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `type`
--

LOCK TABLES `type` WRITE;
/*!40000 ALTER TABLE `type` DISABLE KEYS */;
INSERT INTO `type` (`id`, `code`, `name`) VALUES (1,'hero','Hero'),(2,'ally','Ally'),(3,'attachment','Attachment'),(4,'event','Event'),(5,'treasure','Treasure'),(6,'player-side-quest','Player Side Quest');
/*!40000 ALTER TABLE `type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'ringsdb_v2'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-03-17 23:01:28
