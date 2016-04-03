-- MySQL dump 10.13  Distrib 5.6.19, for osx10.7 (i386)
--
-- Host: localhost    Database: ringsdb
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
INSERT INTO `cycle` (`id`, `code`, `name`, `position`, `is_box`, `is_saga`, `date_creation`, `date_update`) VALUES (1,'Core','Core Set',1,1,0,'2016-03-08 21:35:00','2016-03-19 12:45:27'),(2,'SoM','Shadows of Mirkwood',2,0,0,'2016-01-23 11:43:23','2016-03-19 12:45:27'),(3,'KD','Khazad-dûm',3,1,0,'2016-01-23 11:44:18','2016-03-19 12:45:27'),(4,'DD','Dwarrowdelf',4,0,0,'2016-01-23 11:44:36','2016-03-19 12:45:27'),(5,'HoN','Heirs of Númenor',5,1,0,'2016-01-23 11:45:48','2016-03-19 12:45:27'),(6,'AtS','Against the Shadow',6,0,0,'2016-01-23 11:46:09','2016-03-19 12:45:27'),(7,'VoI','The Voice of Isengard',7,1,0,'2016-01-23 11:46:27','2016-03-19 12:45:27'),(8,'TRM','The Ring-maker',8,0,0,'2016-01-23 11:46:46','2016-03-19 12:45:27'),(9,'TLR','The Lost Realm',9,1,0,'2016-01-23 11:47:02','2016-03-19 12:45:27'),(10,'AA','Angmar Awakened',10,0,0,'2016-01-23 11:47:17','2016-03-19 12:45:27'),(11,'TGH','The Grey Havens',11,1,0,'2016-01-23 11:47:32','2016-03-19 12:45:27'),(12,'DC','Dream-chaser',12,0,0,'2016-01-23 11:47:43','2016-03-19 12:45:27'),(13,'TH','The Hobbit',13,0,1,'2016-01-23 11:48:48','2016-03-19 12:45:27'),(14,'LotR','The Lord of the Rings',14,0,1,'2016-01-23 11:49:13','2016-03-19 12:45:27');
/*!40000 ALTER TABLE `cycle` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `pack`
--

LOCK TABLES `pack` WRITE;
/*!40000 ALTER TABLE `pack` DISABLE KEYS */;
INSERT INTO `pack` (`id`, `cycle_id`, `code`, `name`, `position`, `size`, `date_creation`, `date_update`, `date_release`) VALUES (1,1,'Core','Core Set',1,73,'2016-03-08 21:39:56','2016-03-22 13:26:00','2011-04-20'),(2,2,'HfG','The Hunt for Gollum',1,10,'2016-01-23 11:53:15','2016-03-22 13:26:00','2011-07-21'),(3,2,'CatC','Conflict at the Carrock',2,10,'2016-01-23 11:54:11','2016-03-22 13:26:00','2011-08-10'),(4,2,'JtR','A Journey to Rhosgobel',3,10,'2016-01-23 11:54:47','2016-03-22 13:26:00','2011-09-01'),(5,2,'HoEM','The Hills of Emyn Muil',4,10,'2016-01-23 11:55:43','2016-03-22 13:26:00','2011-09-30'),(6,2,'TDM','The Dead Marshes',5,10,'2016-01-23 11:56:21','2016-03-22 13:26:00','2011-11-02'),(7,2,'RtM','Return to Mirkwood',6,10,'2016-01-23 11:57:12','2016-03-22 13:26:00','2011-11-23'),(8,3,'KD','Khazad-dûm',1,13,'2016-01-23 12:07:03','2016-03-22 13:26:00','2012-01-06'),(9,4,'TRG','The Redhorn Gate',1,10,'2016-01-23 12:09:03','2016-03-22 13:26:00','2012-03-01'),(10,4,'RtR','Road to Rivendell',2,10,'2016-01-23 12:12:56','2016-03-22 13:26:00','2012-03-21'),(11,4,'WitW','The Watcher in the Water',3,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2012-04-25'),(12,4,'TLD','The Long Dark',4,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2012-05-16'),(13,4,'FoS','Foundations of Stone',5,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2012-06-20'),(14,4,'SaF','Shadow and Flame',6,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2012-08-08'),(15,5,'HoN','Heirs of Númenor',1,18,'2016-03-08 21:39:56','2016-03-22 13:26:00','2012-11-26'),(16,6,'TSF','The Steward\'s Fear',1,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2013-05-09'),(17,6,'TDF','The Drúadan Forest',2,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2013-05-31'),(18,6,'EaAD','Encounter at Amon Dîn',3,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2013-07-05'),(19,6,'AoO','Assault on Osgiliath',4,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2013-08-09'),(20,6,'BoG','The Blood of Gondor',5,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2013-10-17'),(21,6,'TMV','The Morgul Vale',6,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2013-11-15'),(22,7,'VoI','The Voice of Isengard',1,15,'2016-03-08 21:39:56','2016-03-22 13:26:00','2014-02-21'),(23,8,'TDT','The Dunland Trap',1,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2014-06-26'),(24,8,'TTT','The Three Trials',2,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2014-07-24'),(25,8,'TiT','Trouble in Tharbad',3,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2014-08-21'),(26,8,'NiE','The Nîn-in-Eilph',4,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2014-10-23'),(27,8,'CS','Celebrimbor\'s Secret',5,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2014-11-13'),(28,8,'TAC','The Antlered Crown',6,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2014-12-23'),(29,9,'TLR','The Lost Realm',1,15,'2016-03-08 21:39:56','2016-03-22 13:26:00','2015-04-03'),(30,10,'WoE','The Wastes of Eriador',1,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2015-07-02'),(31,10,'EfMG','Escape from Mount Gram',2,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2015-07-30'),(32,10,'AtE','Across the Ettenmoors',3,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2015-09-03'),(33,10,'ToR','The Treachery of Rhudaur',4,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2015-09-24'),(34,10,'BoCD','The Battle of Carn Dûm',5,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2015-11-06'),(35,10,'TDR','The Dread Realm',6,10,'2016-03-08 21:39:56','2016-03-22 13:26:00','2015-12-17'),(36,11,'TGH','The Grey Havens',1,15,'2016-03-08 21:39:56','2016-03-22 13:26:00','2016-02-11'),(37,13,'OHaUH','Over Hill and Under Hill',1,22,'2016-03-08 21:39:56','2016-03-22 13:26:00','2012-08-17'),(38,13,'OtD','On the Doorstep',2,24,'2016-03-08 21:39:56','2016-03-22 13:26:00','2013-02-22'),(39,14,'TBR','The Black Riders',1,21,'2016-03-08 21:39:56','2016-03-22 13:26:00','2013-09-20'),(40,14,'TRD','The Road Darkens',2,18,'2016-03-08 21:39:56','2016-03-22 13:26:00','2014-10-03'),(41,14,'ToS','The Treason of Saruman',3,20,'2016-03-08 21:39:56','2016-03-22 13:26:00','2015-04-23'),(42,14,'LoS','The Land of Shadow',4,14,'2016-03-08 21:39:56','2016-03-22 13:26:00','2015-11-19'),(43,12,'FotS','Flight of the Stormcaller',1,10,'2016-03-22 21:12:43','2016-03-22 21:18:43',NULL),(44,12,'TitD','The Thing in the Depths',2,10,'2016-03-22 21:13:36','2016-03-22 21:18:56',NULL),(45,12,'TotD','Temple of the Deceived',3,10,'2016-03-22 21:15:10','2016-03-22 21:18:52',NULL),(46,12,'DR','The Drowned Ruins',4,10,'2016-03-22 21:17:04','2016-03-22 21:18:48',NULL),(47,14,'FotW','The Flame of the West',5,13,'2016-03-22 21:18:02','2016-03-22 21:18:23',NULL);
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
-- Dumping routines for database 'ringsdb'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2016-04-03 20:04:16
