-- MySQL dump 10.17  Distrib 10.3.16-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: agricultura_db
-- ------------------------------------------------------
-- Server version	10.3.16-MariaDB

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
-- Current Database: `agricultura_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `agricultura_db` /*!40100 DEFAULT CHARACTER SET utf32 COLLATE utf32_spanish_ci */;

USE `agricultura_db`;

--
-- Table structure for table `facturas`
--

DROP TABLE IF EXISTS `facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facturas` (
  `codigo_factura` int(11) NOT NULL AUTO_INCREMENT,
  `codigo_trabajo` int(11) NOT NULL,
  `fecha_emision` date NOT NULL,
  `base_imponible` decimal(10,2) NOT NULL,
  `iva` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `estado_pago` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT 'Pendiente',
  PRIMARY KEY (`codigo_factura`),
  UNIQUE KEY `codigo_trabajo` (`codigo_trabajo`),
  CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`codigo_trabajo`) REFERENCES `trabajos` (`codigo_trabajo`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facturas`
--

LOCK TABLES `facturas` WRITE;
/*!40000 ALTER TABLE `facturas` DISABLE KEYS */;
INSERT INTO `facturas` VALUES (1,2,'2026-01-30',110.00,23.10,133.10,'Pagada'),(2,4,'2026-02-23',720.00,151.20,871.20,'Pagada');
/*!40000 ALTER TABLE `facturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maquinas`
--

DROP TABLE IF EXISTS `maquinas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maquinas` (
  `codigo_maquina` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `matricula` varchar(20) COLLATE utf8mb4_spanish_ci NOT NULL,
  `tipo_maquina` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Tractor, Cosechadora, etc.',
  `precio_hora` decimal(10,2) NOT NULL COMMENT 'Tarifa base por hora de uso',
  PRIMARY KEY (`codigo_maquina`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maquinas`
--

LOCK TABLES `maquinas` WRITE;
/*!40000 ALTER TABLE `maquinas` DISABLE KEYS */;
INSERT INTO `maquinas` VALUES (1,'John Deere 6155M','E-4589-BBT','Tractor',55.00),(2,'Claas Lexion 760','E-9988-GGR','Cosechadora',120.00),(3,'Väderstad Rapid 400','E-7722-JKL','Sembradora',45.00),(4,'Amazone UX 5200','E-5566-QRS','Fumigadora',50.00);
/*!40000 ALTER TABLE `maquinas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcelas`
--

DROP TABLE IF EXISTS `parcelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `parcelas` (
  `codigo_parcela` int(11) NOT NULL AUTO_INCREMENT,
  `login_agricultor` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `poligono` varchar(20) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `referencia_catastral` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `hectareas` decimal(10,2) DEFAULT NULL,
  `municipio` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `latitud` decimal(10,6) DEFAULT NULL COMMENT 'Centroide Lat',
  `longitud` decimal(10,6) DEFAULT NULL COMMENT 'Centroide Lon',
  `borde_poligono` longtext COLLATE utf8mb4_spanish_ci DEFAULT NULL COMMENT 'Coordenadas GeoJSON',
  PRIMARY KEY (`codigo_parcela`),
  KEY `login_agricultor` (`login_agricultor`),
  CONSTRAINT `parcelas_ibfk_1` FOREIGN KEY (`login_agricultor`) REFERENCES `usuarios` (`login`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `parcelas`
--

LOCK TABLES `parcelas` WRITE;
/*!40000 ALTER TABLE `parcelas` DISABLE KEYS */;
INSERT INTO `parcelas` VALUES (1,'Alvaro','Oliva1','Z','1234H',10.00,'Oliva de Plasencia',40.112676,-6.081430,'[[40.11262411376118,-6.081851960546241],[40.11252558590731,-6.081337220599795],[40.11262411376118,-6.081106659998783],[40.112932012384455,-6.081423010590866]]'),(2,'Alvaro','Oliva2','F','12345Z',20.00,'Oliva de Plasencia',40.112942,-6.081000,'[[40.11295341729887,-6.081388509418048],[40.11265372941796,-6.081098968198168],[40.11293289077382,-6.080578866377256],[40.113228472136974,-6.080932750090449]]'),(3,'Alvaro','Oliva3','K','12345a',15.00,'Oliva de Plasencia',40.113183,-6.080553,'[[40.11322811210482,-6.080895349235916],[40.11294895196073,-6.080562913020481],[40.11311316394899,-6.08033235241947],[40.11320348037349,-6.080326990545025],[40.1131952697944,-6.080444951782762],[40.113408744528606,-6.0807559405004]]');
/*!40000 ALTER TABLE `parcelas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trabajos`
--

DROP TABLE IF EXISTS `trabajos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `trabajos` (
  `codigo_trabajo` int(11) NOT NULL AUTO_INCREMENT,
  `tipo_trabajo` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `estado` int(11) DEFAULT 0 COMMENT '0=Pendiente, 1=En Curso, 2=Finalizado',
  `fecha_solicitud` date NOT NULL,
  `codigo_parcela` int(11) NOT NULL,
  `codigo_maquina` int(11) DEFAULT NULL,
  `login_maquinista` varchar(50) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `horas_reales` decimal(5,2) DEFAULT 0.00,
  PRIMARY KEY (`codigo_trabajo`),
  KEY `codigo_parcela` (`codigo_parcela`),
  KEY `codigo_maquina` (`codigo_maquina`),
  KEY `login_maquinista` (`login_maquinista`),
  CONSTRAINT `trabajos_ibfk_1` FOREIGN KEY (`codigo_parcela`) REFERENCES `parcelas` (`codigo_parcela`) ON DELETE CASCADE,
  CONSTRAINT `trabajos_ibfk_2` FOREIGN KEY (`codigo_maquina`) REFERENCES `maquinas` (`codigo_maquina`) ON UPDATE CASCADE,
  CONSTRAINT `trabajos_ibfk_3` FOREIGN KEY (`login_maquinista`) REFERENCES `usuarios` (`login`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trabajos`
--

LOCK TABLES `trabajos` WRITE;
/*!40000 ALTER TABLE `trabajos` DISABLE KEYS */;
INSERT INTO `trabajos` VALUES (1,'Arado',0,'2026-01-30',1,3,'maquinista',0.00),(2,'Siembra',4,'2026-01-30',2,1,'maquinista',2.00),(3,'Fumigación',1,'2026-01-30',3,2,'maquinista',0.00),(4,'Arado',4,'2026-02-23',1,2,'maquinista',6.00);
/*!40000 ALTER TABLE `trabajos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `login` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL COMMENT 'Identificador único de acceso',
  `password` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_spanish_ci NOT NULL,
  `apellidos` varchar(100) COLLATE utf8mb4_spanish_ci NOT NULL,
  `dni` varchar(15) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_spanish_ci DEFAULT NULL,
  `tipo` int(11) NOT NULL COMMENT '0=Admin, 1=Maquinista, 2=Agricultor',
  PRIMARY KEY (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES ('admin','admin','Administrador','Principal','00000000A','admin@sistema.com',0),('agricultor','1234','Juan','El Cliente','87654321Z','juan@finca.com',2),('Alvaro','1234','Alvaro','de Vicente','12345678Z','alvaro@agri.com',2),('maquinista','1234','Pedro','El Maquinista','12345678M','pedro@campo.com',1);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-30 16:46:35
