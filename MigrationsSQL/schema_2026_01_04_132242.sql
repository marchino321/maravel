/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: traffic_familyNest
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0+deb12u2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


--
-- Table structure for table `ap_permessi`
--

DROP TABLE IF EXISTS `ap_permessi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ap_permessi` (
  `idPermessoAutoIncrement` int(11) NOT NULL AUTO_INCREMENT,
  `descrizionePermesso` varchar(255) NOT NULL,
  `permessiEffettivi` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `eliminatoPermesso` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`idPermessoAutoIncrement`),
  KEY `eliminatoPermesso` (`eliminatoPermesso`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ap_ricordaLogin`
--

DROP TABLE IF EXISTS `ap_ricordaLogin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ap_ricordaLogin` (
  `token_hash` varchar(64) NOT NULL,
  `utenteIdAutoIncrement` int(11) NOT NULL,
  `scadenza_at` datetime NOT NULL,
  PRIMARY KEY (`token_hash`),
  KEY `utenteIdAutoIncrement` (`utenteIdAutoIncrement`),
  CONSTRAINT `ap_ricordaLogin_ibfk_1` FOREIGN KEY (`utenteIdAutoIncrement`) REFERENCES `tbl_utenti` (`idUtenteAutoIncrement`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `tbl_utenti`
--

DROP TABLE IF EXISTS `tbl_utenti`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tbl_utenti` (
  `idUtenteAutoIncrement` int(11) NOT NULL AUTO_INCREMENT,
  `nomeUtente` varchar(255) NOT NULL,
  `cognomeUtente` varchar(255) NOT NULL,
  `passwordUtente` varchar(255) NOT NULL,
  `emailUtente` varchar(255) NOT NULL,
  `cellulareRecapitoUtente` varchar(255) DEFAULT NULL,
  `registrazioneUtente` timestamp NOT NULL DEFAULT current_timestamp(),
  `permessiUtente` int(11) NOT NULL DEFAULT 1 COMMENT 'riferimento ap_permessi',
  `ultimoLogin` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `eliminatoUtente` int(11) NOT NULL DEFAULT 0 COMMENT '0 in corso, 1 eliminato',
  `ultimoCambioPassword` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `avatar_utente` varchar(255) NOT NULL DEFAULT '/App/public/images/users/default.svg',
  `uuidUser` varchar(255) NOT NULL DEFAULT uuid(),
  `DefaultSetting` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '{"mode":"light","width":"fluid","menuPosition":"fixed","sidebar":{"color":["dark"]},"topbar":{"color":["dark"]},"showRightSidebarOnPageLoad":true}',
  PRIMARY KEY (`idUtenteAutoIncrement`),
  KEY `permessiUtente` (`permessiUtente`),
  KEY `emailUtente` (`emailUtente`),
  KEY `uuidUser` (`uuidUser`),
  CONSTRAINT `tbl_utenti_ibfk_1` FOREIGN KEY (`permessiUtente`) REFERENCES `ap_permessi` (`idPermessoAutoIncrement`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1 COLLATE=latin1_general_cs;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-01-04 13:22:42
