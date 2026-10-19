-- =====================================================================
-- andiswa_business_azure.sql
-- Your andiswa_business database (exported from XAMPP on 14 Oct 2026),
-- converted so it imports into Azure Database for MySQL (MySQL 8.0).
--
-- What was changed compared with the phpMyAdmin export:
--   * Every PRIMARY KEY, index and AUTO_INCREMENT is written INSIDE its
--     CREATE TABLE. The phpMyAdmin export adds them afterwards, which fails
--     on Azure with "Multiple primary key defined" when the server setting
--     sql_generate_invisible_primary_key is ON.
--   * It creates the database itself (CREATE DATABASE ... / USE ...).
--   * quotes has the new "source" column (website / app) used by the
--     website admin and the mobile app's API.
--   * settings has facebook_url and youtube_url rows.
--   * All your existing rows (users, quotes, projects, invoices, chats...)
--     are kept exactly as they were, including the admin password.
--
-- HOW TO RUN IT: see AZURE_SETUP_GUIDE.md, Part 3.
-- Run it ONCE on an empty server. If it stops halfway, delete the
-- andiswa_business database in Azure and run it again.
-- =====================================================================


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

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `andiswa_business` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `andiswa_business`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cancellation_requests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int unsigned NOT NULL,
  `user_id` int unsigned NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Pending','Approved','Declined') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending',
  `admin_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `cancellation_requests_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cancellation_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `cancellation_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `cancellation_requests` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `sender` enum('user','bot') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `session_id` (`session_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=47 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` VALUES (1,'chat-1788162417934-rpf6zp',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 07:46:58'),(2,'chat-1788162420613-xt55x9',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 07:47:00'),(3,'chat-1788162457483-11pfd1',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 07:47:37'),(4,'chat-1788162701218-6nsr49',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 07:51:41'),(5,'chat-1788162735031-4ucnlm',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 07:52:15'),(6,'chat-1788164240240-3ph070',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 08:17:20'),(7,'chat-1788168099497-fk0dko',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:21:39'),(8,'chat-1788168430748-kwcsc7',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:27:10'),(9,'chat-1788168435416-cz6cw5',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:27:15'),(10,'chat-1788168571415-1rbce0',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:29:31'),(11,'chat-1788168616170-sxbsp2',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:30:16'),(12,'chat-1788168622172-r3w3mi',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:30:22'),(13,'chat-1788168628575-hqggu4',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:30:28'),(14,'chat-1788168630543-5fl46b',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:30:30'),(15,'chat-1788168698806-y63bzy',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:31:38'),(16,'chat-1788168721304-y0kh4f',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:32:01'),(17,'chat-1788168756511-lufrd6',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:32:36'),(18,'chat-1788168769883-7trm3p',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:32:49'),(19,'chat-1788168822207-xihwb1',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:33:42'),(20,'chat-1788168997467-fjw61m',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:36:37'),(21,'chat-1788169401605-jy09to',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 09:43:21'),(22,'chat-1788170900035-s0c7ch',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 10:08:20'),(23,'chat-1788170903435-iikb0c',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 10:08:23'),(24,'chat-1788173060057-ymah04',NULL,'bot','Hi! 👋 How can I help you today?','2026-08-31 10:44:20'),(25,'chat-1788251281599-f953e5',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 08:28:02'),(26,'chat-1788251285798-99ac4p',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 08:28:05'),(27,'chat-1788251443490-nh1bmo',1,'bot','Hi! 👋 How can I help you today?','2026-09-01 08:30:43'),(28,'chat-1788251554576-bfxhid',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 08:32:34'),(29,'chat-1788251556976-1dyslz',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 08:32:37'),(30,'chat-1788251804328-mv7ryz',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 08:36:44'),(31,'chat-1788255420428-shr19d',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:37:00'),(32,'chat-1788255429092-fg2cif',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:37:09'),(33,'chat-1788255440083-14kgny',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:37:20'),(34,'chat-1788255442001-glp6re',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:37:22'),(35,'chat-1788255457263-lioos1',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:37:37'),(36,'chat-1788255498780-u53fdc',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:38:18'),(37,'chat-1788255509809-ff9jta',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:38:29'),(38,'chat-1788255522190-n9d9m7',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:38:42'),(39,'chat-1788255561423-o0a93p',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:39:21'),(40,'chat-1788255738901-0opij4',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:42:18'),(41,'chat-1788255745733-c6vljg',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:42:25'),(42,'chat-1788255882295-ig6efv',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:44:42'),(43,'chat-1788256198742-s8gxnj',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:49:58'),(44,'chat-1788256314060-r4g6ak',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:51:54'),(45,'chat-1788256317114-a4149s',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:51:57'),(46,'chat-1788256337360-o79ah3',NULL,'bot','Hi! 👋 How can I help you today?','2026-09-01 09:52:17');
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quote_id` int unsigned DEFAULT NULL,
  `client_id` int unsigned NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `tax_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_amount` decimal(12,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Draft','Sent','Paid','Overdue','Cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `quote_id` (`quote_id`),
  KEY `client_id` (`client_id`,`status`),
  CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
INSERT INTO `invoices` VALUES (1,'INV-20260901-4225',1,2,'Graphic Design',2500.00,15.00,375.00,2875.00,'2026-01-09','Sent','2026-09-01 09:41:03','2026-09-01 09:41:03'),(2,'INV-20260901-7740',1,2,'Graphic Design',2500.00,15.00,375.00,2875.00,'2026-01-09','Sent','2026-09-01 09:41:07','2026-09-01 09:41:07');
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`,`is_read`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,2,'Quote accepted & invoice issued','Your request has been accepted. Invoice INV-20260901-4225 for R2,875.00 is ready in your client portal.',0,'2026-09-01 09:41:03'),(2,2,'Quote accepted & invoice issued','Your request has been accepted. Invoice INV-20260901-7740 for R2,875.00 is ready in your client portal.',0,'2026-09-01 09:41:07');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_updates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int unsigned NOT NULL,
  `update_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `project_id` (`project_id`),
  CONSTRAINT `project_updates_ibfk_1` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `project_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `project_updates` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projects` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned NOT NULL,
  `quote_id` int unsigned DEFAULT NULL,
  `title` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Not Started','In Progress','On Hold','Completed','Cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Not Started',
  `progress` tinyint unsigned NOT NULL DEFAULT '0',
  `start_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_on_services` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `quote_id` (`quote_id`),
  KEY `show_on_services` (`show_on_services`),
  CONSTRAINT `projects_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `projects_ibfk_2` FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` VALUES (1,2,1,'Graphic Design','make it pink','Not Started',0,NULL,NULL,NULL,0,'2026-09-01 09:41:03','2026-09-01 09:41:03');
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int unsigned DEFAULT NULL,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'General Enquiry',
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` enum('website','app') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'website',
  `status` enum('New','Under Review','Accepted','Rejected','Cancelled','Completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'New',
  `admin_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `status` (`status`),
  KEY `email` (`email`),
  KEY `source` (`source`),
  CONSTRAINT `quotes_ibfk_1` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `quotes` DISABLE KEYS */;
INSERT INTO `quotes` VALUES (1,2,'Ondela Skwatsha','ondelaskwatsha65@gmail.com','Graphic Design','make it pink','website','Accepted','','2026-09-01 09:39:19','2026-09-01 09:41:03');
/*!40000 ALTER TABLE `quotes` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'Graphic Design','Professional flyers, posters and marketing artwork.',NULL,1,0,'2026-09-01 08:29:04','2026-09-01 08:29:04'),(2,'Branding & Identity','Logo design, brand identity and business stationery.',NULL,1,1,'2026-09-01 08:29:04','2026-09-01 08:29:04'),(3,'Web & Digital Design','Modern websites and digital visual experiences.',NULL,1,2,'2026-09-01 08:29:04','2026-09-01 08:29:04'),(4,'Social Media Design','Social media graphics and campaign content.',NULL,1,3,'2026-09-01 08:29:04','2026-09-01 08:29:04'),(5,'Business Consultation','Practical consultation for creative business growth.',NULL,1,4,'2026-09-01 08:29:04','2026-09-01 08:29:04');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'studio','Andiswa Business Consultation'),(2,'email','andiswasbusinessconsultations@outlook.com'),(3,'phone','+27 67 790 4428'),(4,'address','2 Belmont Terrace, Port Elizabeth, 6001'),(5,'hours','Monday – Friday, 08:00 – 18:00'),(6,'whatsapp','27677904428'),(28,'tagline','WE DESIGN. YOU GROW.'),(29,'hero_text','Andiswa Business Consultation crafts bold visual identities and digital experiences that make South African businesses impossible to ignore.'),(30,'about_text1','Andiswa Business Consultation is a Port Elizabeth based creative agency founded on the belief that exceptional design is the single most powerful investment any business can make. We blend strategic thinking with visual craft to build brands that resonate, differentiate, and endure.'),(31,'about_text2','From early-stage startups defining their first identity to established businesses evolving for new markets, we bring the same level of care, craft, and creative ambition to every brief.'),(32,'owner_name','Andiswa Phewa'),(33,'owner_title','Creative Director'),(34,'stat1_value','50+'),(35,'stat1_label','Projects Completed'),(36,'stat2_value','40+'),(37,'stat2_label','Happy Clients'),(38,'stat3_value','5+'),(39,'stat3_label','Years Experience'),(40,'stat4_value','98%'),(41,'stat4_label','Client Satisfaction'),(42,'instagram_url',''),(43,'linkedin_url',''),(44,'twitter_url',''),(45,'facebook_url',''),(46,'youtube_url','');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','client') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'client',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'System Administrator','admin@localhost','admin','$2y$10$i6mIqDM2pULuCYXJifrtzu7Gbu8Hw4IINTejG7H21UJikghCr5xE6','admin',0,'2026-09-01 08:29:04'),(2,'Ondela Skwatsha','ondelaskwatsha65@gmail.com','Ondela.Skwatsha','$2y$10$BeWfRinTM3.MTIlMIyQRfO9pL7XkFWgQYnAtxPqsdZnSn61rYnUQa','client',1,'2026-09-01 09:41:03');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

