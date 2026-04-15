
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `announcements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `audience` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'all',
  `author_user_id` bigint unsigned NOT NULL,
  `published_on` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_author_user_id_foreign` (`author_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,'≡ƒÄë Eid Mubarak! Office Closure Notice','Holiday','The office will be closed from April 20ΓÇô22 for Eid-ul-Fitr. Wishing everyone a blessed Eid!','all',8,'2025-04-10','2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,'≡ƒôï Q2 Town Hall ΓÇö Save the Date','Event','Q2 Town Hall will be held on April 22, 2025 at 3:00 PM in the Main Conference Room. Attendance is mandatory for all department heads.','all',8,'2025-04-08','2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,'Γ£à New Attendance Policy ΓÇö Effective May 1','Policy','Starting May 1, the shift starts at 11:00 AM with a 10-minute grace period. Late arrivals after 11:10 AM will be marked \\\"Late\\\". Repeated late arrivals (3+) will trigger an HR review.','all',8,'2025-04-05','2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `attendance_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_days` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Absent',
  `late` tinyint(1) NOT NULL DEFAULT '0',
  `overtime_minutes` int unsigned NOT NULL DEFAULT '0',
  `worked_minutes` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_days_user_id_date_unique` (`user_id`,`date`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `attendance_days` WRITE;
/*!40000 ALTER TABLE `attendance_days` DISABLE KEYS */;
INSERT INTO `attendance_days` VALUES (1,7,'2025-04-10','Present',0,0,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,7,'2025-04-09','Present',1,0,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,7,'2025-04-08','Leave',0,0,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(4,7,'2025-04-07','Present',0,33,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(5,7,'2025-04-04','Present',0,0,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(6,7,'2025-04-03','Absent',0,0,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(7,8,'2025-04-10','Present',0,0,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(8,9,'2026-04-14','Present',0,0,0,'2026-04-14 08:16:56','2026-04-14 10:02:02'),(9,9,'2026-04-15','Present',0,0,0,'2026-04-15 01:18:52','2026-04-15 04:16:28');
/*!40000 ALTER TABLE `attendance_days` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `attendance_punches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_punches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `punched_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_punches_user_id_date_index` (`user_id`,`date`),
  KEY `attendance_punches_type_punched_at_index` (`type`,`punched_at`)
) ENGINE=MyISAM AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `attendance_punches` WRITE;
/*!40000 ALTER TABLE `attendance_punches` DISABLE KEYS */;
INSERT INTO `attendance_punches` VALUES (1,7,'2025-04-10','clock_in','2025-04-10 08:58:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,7,'2025-04-09','clock_in','2025-04-09 09:15:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,7,'2025-04-09','break_out','2025-04-09 13:00:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(4,7,'2025-04-09','break_in','2025-04-09 13:30:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(5,7,'2025-04-09','clock_out','2025-04-09 18:00:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(6,7,'2025-04-07','clock_in','2025-04-07 08:55:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(7,7,'2025-04-07','break_out','2025-04-07 13:00:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(8,7,'2025-04-07','break_in','2025-04-07 13:30:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(9,7,'2025-04-07','clock_out','2025-04-07 18:33:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(10,7,'2025-04-04','clock_in','2025-04-04 09:00:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(11,7,'2025-04-04','break_out','2025-04-04 13:00:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(12,7,'2025-04-04','break_in','2025-04-04 13:30:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(13,7,'2025-04-04','clock_out','2025-04-04 17:00:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(14,8,'2025-04-10','clock_in','2025-04-10 08:45:00','2026-04-14 07:03:25','2026-04-14 07:03:25'),(15,9,'2026-04-14','clock_in','2026-04-14 13:16:56','2026-04-14 08:16:56','2026-04-14 08:16:56'),(16,9,'2026-04-14','clock_in','2026-04-14 13:17:03','2026-04-14 08:17:03','2026-04-14 08:17:03'),(17,9,'2026-04-14','clock_out','2026-04-14 15:01:54','2026-04-14 10:01:54','2026-04-14 10:01:54'),(18,9,'2026-04-14','clock_in','2026-04-14 15:01:56','2026-04-14 10:01:56','2026-04-14 10:01:56'),(19,9,'2026-04-14','break_out','2026-04-14 15:01:58','2026-04-14 10:01:58','2026-04-14 10:01:58'),(20,9,'2026-04-14','break_in','2026-04-14 15:02:00','2026-04-14 10:02:00','2026-04-14 10:02:00'),(21,9,'2026-04-14','clock_out','2026-04-14 15:02:02','2026-04-14 10:02:02','2026-04-14 10:02:02'),(22,9,'2026-04-15','clock_in','2026-04-15 06:18:52','2026-04-15 01:18:52','2026-04-15 01:18:52'),(23,9,'2026-04-15','clock_out','2026-04-15 06:18:53','2026-04-15 01:18:53','2026-04-15 01:18:53'),(24,9,'2026-04-15','clock_in','2026-04-15 06:25:18','2026-04-15 01:25:18','2026-04-15 01:25:18'),(25,9,'2026-04-15','break_out','2026-04-15 06:25:22','2026-04-15 01:25:22','2026-04-15 01:25:22'),(26,9,'2026-04-15','clock_out','2026-04-15 06:25:24','2026-04-15 01:25:24','2026-04-15 01:25:24'),(27,9,'2026-04-15','clock_in','2026-04-15 06:27:08','2026-04-15 01:27:08','2026-04-15 01:27:08'),(28,9,'2026-04-15','clock_in','2026-04-15 06:59:32','2026-04-15 01:59:32','2026-04-15 01:59:32'),(29,9,'2026-04-15','clock_out','2026-04-15 07:00:04','2026-04-15 02:00:04','2026-04-15 02:00:04'),(30,9,'2026-04-15','clock_in','2026-04-15 07:00:57','2026-04-15 02:00:57','2026-04-15 02:00:57'),(31,9,'2026-04-15','clock_out','2026-04-15 07:01:00','2026-04-15 02:01:00','2026-04-15 02:01:00'),(32,9,'2026-04-15','clock_in','2026-04-15 07:48:27','2026-04-15 02:48:27','2026-04-15 02:48:27'),(33,9,'2026-04-15','clock_in','2026-04-15 07:49:20','2026-04-15 02:49:20','2026-04-15 02:49:20'),(34,9,'2026-04-15','clock_in','2026-04-15 09:13:46','2026-04-15 04:13:46','2026-04-15 04:13:46'),(35,9,'2026-04-15','clock_in','2026-04-15 09:16:28','2026-04-15 04:16:28','2026-04-15 04:16:28');
/*!40000 ALTER TABLE `attendance_punches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `attendance_regulation_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_regulation_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requested_value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `reviewer_user_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_regulation_requests_code_unique` (`code`),
  KEY `attendance_regulation_requests_reviewer_user_id_foreign` (`reviewer_user_id`),
  KEY `attendance_regulation_requests_user_id_date_index` (`user_id`,`date`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `attendance_regulation_requests` WRITE;
/*!40000 ALTER TABLE `attendance_regulation_requests` DISABLE KEYS */;
INSERT INTO `attendance_regulation_requests` VALUES (1,'REG-001',7,'2025-04-03','Missing Clock In','ΓÇö','11:00','Biometric device issue','Pending',NULL,NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,'REG-002',7,'2025-03-28','Wrong Clock Out Time','17:00','18:30','Client call overrun','Approved',NULL,NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,'REG-003',7,'2025-03-15','Break Adjustment','60 min','30 min','Urgent delivery','Rejected',NULL,NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `attendance_regulation_requests` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `company_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website_link` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `official_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `official_contact_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `office_location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin_page` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `company_settings` WRITE;
/*!40000 ALTER TABLE `company_settings` DISABLE KEYS */;
INSERT INTO `company_settings` VALUES (1,'WorkPulse',NULL,NULL,NULL,NULL,NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `company_settings` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(16) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `head_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `departments_name_unique` (`name`),
  KEY `departments_head_user_id_foreign` (`head_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'Engineering','#2447D0',NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,'Human Resources','#1B7A42',8,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,'Finance','#6B3FA0',NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(4,'Marketing','#A05C00',NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(5,'Product','#C0392B',NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(6,'Operations','#6E6C63',NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(7,'Management','#6B3FA0',9,'2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `employee_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `manager_user_id` bigint unsigned DEFAULT NULL,
  `designation` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_joining` date DEFAULT NULL,
  `probation_end_date` date DEFAULT NULL,
  `employment_type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cnic` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personal_phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `personal_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blood_group` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `next_of_kin_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `next_of_kin_relationship` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `next_of_kin_phone` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `basic_salary` int unsigned DEFAULT NULL,
  `house_allowance` int unsigned DEFAULT NULL,
  `transport_allowance` int unsigned DEFAULT NULL,
  `tax_deduction` int unsigned DEFAULT NULL,
  `bank_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_account_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_iban` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_profiles_user_id_unique` (`user_id`),
  KEY `employee_profiles_department_id_foreign` (`department_id`),
  KEY `employee_profiles_manager_user_id_foreign` (`manager_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `employee_profiles` WRITE;
/*!40000 ALTER TABLE `employee_profiles` DISABLE KEYS */;
INSERT INTO `employee_profiles` VALUES (1,7,1,NULL,'Senior Engineer','2022-01-15','2022-04-15','Permanent','Active','1990-03-15','Male','42301-1234567-8','+92 300 1234567','ahmed.k@workpulse.com','123 Gulberg III, Lahore','O+','Fatima Karim','Spouse','+92 301 7654321',150000,40000,10000,8500,'HBL','****-1234','PK36HBL...','2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,8,2,9,'HR Manager','2021-03-01',NULL,'Permanent','Active','1996-04-10','Female','42301-2345678-9','+92 301 2345678','sara.a@workpulse.com','45 DHA Phase 5, Lahore','B+','Ali Ahmed','Father','+92 333 4567890',120000,30000,8000,5500,'MCB','****-5678','PK36MCB...','2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `employee_profiles` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'company',
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `events_created_by_user_id_foreign` (`created_by_user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `events` WRITE;
/*!40000 ALTER TABLE `events` DISABLE KEYS */;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `holidays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `holidays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'National',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `holidays_date_unique` (`date`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `holidays` WRITE;
/*!40000 ALTER TABLE `holidays` DISABLE KEYS */;
INSERT INTO `holidays` VALUES (1,'2025-01-01','New Year\'s Day','National','2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,'2025-02-05','Kashmir Solidarity Day','National','2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,'2025-03-23','Pakistan Day','National','2026-04-14 07:03:25','2026-04-14 07:03:25'),(4,'2025-04-20','Eid-ul-Fitr','Religious','2026-04-14 07:03:25','2026-04-14 07:03:25'),(5,'2025-04-21','Eid-ul-Fitr (2nd Day)','Religious','2026-04-14 07:03:25','2026-04-14 07:03:25'),(6,'2025-05-01','Labour Day','National','2026-04-14 07:03:25','2026-04-14 07:03:25'),(7,'2025-08-14','Independence Day','National','2026-04-14 07:03:25','2026-04-14 07:03:25'),(8,'2025-11-09','Iqbal Day','National','2026-04-14 07:03:25','2026-04-14 07:03:25'),(9,'2025-12-25','Quaid Day / Christmas','National','2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `holidays` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `leave_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_approvals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `leave_request_id` bigint unsigned NOT NULL,
  `step` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reviewer_user_id` bigint unsigned DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_approvals_reviewer_user_id_foreign` (`reviewer_user_id`),
  KEY `leave_approvals_leave_request_id_step_index` (`leave_request_id`,`step`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `leave_approvals` WRITE;
/*!40000 ALTER TABLE `leave_approvals` DISABLE KEYS */;
INSERT INTO `leave_approvals` VALUES (1,1,'manager',NULL,'Approved',NULL,NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,1,'hr',NULL,'Approved',NULL,NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,2,'manager',NULL,'Approved',NULL,NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(4,2,'hr',NULL,'Pending',NULL,NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `leave_approvals` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `year` smallint unsigned NOT NULL,
  `leave_type_id` bigint unsigned NOT NULL,
  `allocated_days` decimal(6,2) NOT NULL DEFAULT '0.00',
  `used_days` decimal(6,2) NOT NULL DEFAULT '0.00',
  `remaining_days` decimal(6,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_balances_user_id_year_leave_type_id_unique` (`user_id`,`year`,`leave_type_id`),
  KEY `leave_balances_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_balances_year_index` (`year`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
INSERT INTO `leave_balances` VALUES (1,7,2025,1,18.00,0.00,18.00,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,7,2025,2,7.00,0.00,7.00,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,7,2025,4,5.00,0.00,5.00,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(4,7,2025,5,90.00,0.00,90.00,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(5,7,2025,6,7.00,0.00,7.00,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(6,7,2025,7,3.00,0.00,3.00,'2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `leave_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `year` smallint unsigned NOT NULL,
  `leave_type_id` bigint unsigned NOT NULL,
  `quota_days` smallint unsigned NOT NULL DEFAULT '0',
  `pro_rata` tinyint(1) NOT NULL DEFAULT '1',
  `carry_forward_days` smallint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_policies_year_leave_type_id_unique` (`year`,`leave_type_id`),
  KEY `leave_policies_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_policies_year_index` (`year`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `leave_policies` WRITE;
/*!40000 ALTER TABLE `leave_policies` DISABLE KEYS */;
INSERT INTO `leave_policies` VALUES (1,2025,1,18,1,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,2025,2,7,1,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,2025,4,5,1,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(4,2025,5,90,1,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(5,2025,6,7,1,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(6,2025,7,3,1,0,'2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `leave_policies` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `leave_type_id` bigint unsigned NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `days` decimal(6,2) NOT NULL DEFAULT '1.00',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `handover_to` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_requests_code_unique` (`code`),
  KEY `leave_requests_leave_type_id_foreign` (`leave_type_id`),
  KEY `leave_requests_user_id_from_date_to_date_index` (`user_id`,`from_date`,`to_date`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
INSERT INTO `leave_requests` VALUES (1,'LV-001',7,1,'2025-04-08','2025-04-08',1.00,'Personal work','Omar Farooq','Approved','2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,'LV-003',8,1,'2025-04-18','2025-04-20',3.00,'Family event','Nadia Iqbal','Pending','2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `paid` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_types_name_unique` (`name`),
  UNIQUE KEY `leave_types_code_unique` (`code`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
INSERT INTO `leave_types` VALUES (1,'Annual Leave','annual',1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,'Sick Leave','sick',1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,'Unpaid Leave','unpaid',0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(4,'Paternity Leave','paternity',1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(5,'Maternity Leave','maternity',1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(6,'Marriage Leave','marriage',1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(7,'Bereavement Leave','bereavement',1,'2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `leave_types` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_04_14_000100_add_role_and_employee_code_to_users_table',2),(5,'2026_04_14_000110_create_departments_table',2),(6,'2026_04_14_000120_create_employee_profiles_table',2),(7,'2026_04_14_000130_create_reporting_lines_table',2),(8,'2026_04_14_000140_create_announcements_table',2),(9,'2026_04_14_000150_create_holidays_table',2),(10,'2026_04_14_000200_create_attendance_tables',2),(11,'2026_04_14_000300_create_leave_tables',2),(12,'2026_04_14_000400_create_events_and_company_settings_tables',2),(13,'2026_04_14_000500_create_module_policies_and_permissions_tables',2);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `module_policies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `module_policies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_policies_module_key_unique` (`module`,`key`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `module_policies` WRITE;
/*!40000 ALTER TABLE `module_policies` DISABLE KEYS */;
INSERT INTO `module_policies` VALUES (1,'attendance','shift_start','11:00','string','2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,'attendance','grace_minutes','10','int','2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,'attendance','late_trigger_count','3','int','2026-04-14 07:03:25','2026-04-14 07:03:25'),(4,'leave','approval_flow','[\"manager\",\"hr\"]','json','2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `module_policies` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_key_unique` (`key`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'employees.view','View employees','2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,'employees.view_confidential','View salary/bank','2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,'employees.manage','Manage employees','2026-04-14 07:03:25','2026-04-14 07:03:25'),(4,'attendance.punch','Clock in/out and breaks','2026-04-14 07:03:25','2026-04-14 07:03:25'),(5,'attendance.view','View attendance','2026-04-14 07:03:25','2026-04-14 07:03:25'),(6,'attendance.manage','Manage attendance/policies','2026-04-14 07:03:25','2026-04-14 07:03:25'),(7,'leave.apply','Apply for leave','2026-04-14 07:03:25','2026-04-14 07:03:25'),(8,'leave.approve_manager','Approve leave (manager)','2026-04-14 07:03:25','2026-04-14 07:03:25'),(9,'leave.approve_hr','Approve leave (HR)','2026-04-14 07:03:25','2026-04-14 07:03:25'),(10,'leave.manage','Manage leave types/quotas','2026-04-14 07:03:25','2026-04-14 07:03:25'),(11,'announcements.manage','Create announcements','2026-04-14 07:03:25','2026-04-14 07:03:25'),(12,'reports.view','View reports','2026-04-14 07:03:25','2026-04-14 07:03:25'),(13,'company.manage','Manage company settings','2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `reporting_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reporting_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `manager_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `reporting_lines_user_id_unique` (`user_id`),
  KEY `reporting_lines_manager_user_id_foreign` (`manager_user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `reporting_lines` WRITE;
/*!40000 ALTER TABLE `reporting_lines` DISABLE KEYS */;
INSERT INTO `reporting_lines` VALUES (1,7,NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,8,9,'2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `reporting_lines` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_id` bigint unsigned NOT NULL,
  `allowed` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_permissions_role_permission_id_unique` (`role`,`permission_id`),
  KEY `role_permissions_permission_id_foreign` (`permission_id`),
  KEY `role_permissions_role_allowed_index` (`role`,`allowed`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,'hr',1,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(2,'hr',2,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(3,'hr',3,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(4,'hr',4,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(5,'hr',5,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(6,'hr',6,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(7,'hr',7,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(8,'hr',8,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(9,'hr',9,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(10,'hr',10,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(11,'hr',11,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(12,'hr',12,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(13,'hr',13,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(14,'employee',1,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(15,'employee',2,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(16,'employee',3,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(17,'employee',4,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(18,'employee',5,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(19,'employee',6,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(20,'employee',7,1,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(21,'employee',8,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(22,'employee',9,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(23,'employee',10,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(24,'employee',11,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(25,'employee',12,0,'2026-04-14 07:03:25','2026-04-14 07:03:25'),(26,'employee',13,0,'2026-04-14 07:03:25','2026-04-14 07:03:25');
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('liQxBKHwa1hH075LVxTnl3rUjyvrph087zAq8FpC',9,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJyYm1yOFhSN2lrTHdNZ3VyVkpsV3N0dVJ4OGF4aDZsVmNyMXNLNlJPIiwibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiOjksIl9mbGFzaCI6eyJvbGQiOltdLCJuZXciOltdfSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hcGlcL2xlYXZlXC9iYWxhbmNlc1wvQURNLTAwMSIsInJvdXRlIjpudWxsfX0=',1776247079);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'employee',
  `employee_code` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_employee_code_unique` (`employee_code`),
  KEY `users_role_index` (`role`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (7,'Ahmed Karim','employee@workpulse.com',NULL,'$2y$12$PkStMgwJMKY7oSPYyGYns.nPGFUAIR7OWcRAnK7mi6yK0h6UcyHHy',NULL,'2026-04-14 07:03:24','2026-04-14 07:03:24','employee','EMP-001'),(9,'Zainab Hussain','admin@workpulse.com',NULL,'$2y$12$Jdc4ETbMWDNzw9Sl6hCA2eYtfaymPwKQNtXp5Xmpo4uLyO/LPPM4K','1wbjv8kFqWWoX8RGMn0Idm34OVQRYC6xdx1kb9ABUet3XKWuLcLRENnDkhe2','2026-04-14 07:03:25','2026-04-14 07:03:25','admin','ADM-001'),(8,'Sara Ahmed','hr@workpulse.com',NULL,'$2y$12$UDg27PO40QEiDECXE9AOJuoxPATleJqXXaLefj.K8iLqy3oF9cm16',NULL,'2026-04-14 07:03:25','2026-04-14 07:03:25','hr','EMP-002');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

