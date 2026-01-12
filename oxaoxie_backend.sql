-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: oxaxie_backend
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.2

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

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','admin','support') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'support',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_users_email_unique` (`email`),
  KEY `admin_users_email_index` (`email`),
  KEY `admin_users_role_is_active_index` (`role`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES ('019ba280-7511-72f5-bc00-eb95aacd7750','admin@demo.com','$2y$12$6WSgiegrt57iSwd3SBDScusUI4vcXqLLIjjGP882uzKXbUn/pEq4.','Admin','support',1,NULL,NULL,'2026-01-09 05:24:46','2026-01-09 05:24:46');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `all_cases`
--

DROP TABLE IF EXISTS `all_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `all_cases` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_type` enum('landlord_tenant','employment','contracts','consumer_rights','family','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_state` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `situation_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','resolved','archived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `resolution_type` enum('won','settled','lost','dropped') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `all_cases_user_id_status_index` (`user_id`,`status`),
  KEY `all_cases_issue_type_index` (`issue_type`),
  KEY `all_cases_created_at_index` (`created_at`),
  KEY `all_cases_resolved_at_index` (`resolved_at`),
  FULLTEXT KEY `all_cases_situation_description_fulltext` (`situation_description`),
  CONSTRAINT `all_cases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `all_cases`
--

LOCK TABLES `all_cases` WRITE;
/*!40000 ALTER TABLE `all_cases` DISABLE KEYS */;
INSERT INTO `all_cases` VALUES ('019ba21d-30db-7128-ad4f-078081de4a19','019ba21b-b7c3-7129-9854-284c95d6e40c','consumer_rights','Alaska','Alaska','US','The Background: Alex rented an apartment for two years. When he moved out on June 1st, he spent two days cleaning, patched the small holes from his picture frames, and took photos of every room. His monthly rent was $1,500, and he had a $1,500 security deposit held by the landlord, Mr. Henderson. The Conflict: It is now July 15th (45 days after move-out). Alex finally receives a check from Mr. Henderson for only $400. Attached is a handwritten note claiming: $600 for \"professional deep cleaning.\" $500 for \"repainting the entire living room due to wall damage.\" Alex feels this is unfair because the apartment was spotless and the \"damage\" was just normal wear and tear from hanging a few photos.','resolved',NULL,NULL,'2026-01-09 03:36:20','2026-01-09 05:17:20',NULL),('019ba299-3490-70d7-925a-c4b1536fb5e4','019ba21b-b7c3-7129-9854-284c95d6e40c','consumer_rights','Alaska','Alaska','US','The Background: Alex rented an apartment for two years. When he moved out on June 1st, he spent two days cleaning, patched the small holes from his picture frames, and took photos of every room. His monthly rent was $1,500, and he had a $1,500 security deposit held by the landlord, Mr. Henderson. The Conflict: It is now July 15th (45 days after move-out). Alex finally receives a check from Mr. Henderson for only $400. Attached is a handwritten note claiming: $600 for \"professional deep cleaning.\" $500 for \"repainting the entire living room due to wall damage.\" Alex feels this is unfair because the apartment was spotless and the \"damage\" was just normal wear and tear from hanging a few photos.','active',NULL,NULL,'2026-01-09 05:51:48','2026-01-09 05:51:48',NULL),('019ba61a-7d25-703c-9895-4b5e4a17e4ac','019ba60f-944b-73e5-871c-30bf0db5291e','employment','Alaska','Alaska','US','The Background: Alex is a lead software engineer at FinTech Corp, a company that recently developed a proprietary algorithm for high-frequency trading. Alex feels passed over for a promotion and decides to quit, but not before taking \"what he’s owed.\"\r\n\r\nThe Incident:\r\n\r\nThe Data Breach: On his last day, Alex downloads the source code for the algorithm onto a personal encrypted thumb drive. He also bcc’s his personal email on several internal strategy memos.\r\n\r\nThe Hand-off: Alex meets a friend, Jordan, at a coffee shop. Alex gives Jordan the thumb drive, telling him, \"There’s a gold mine on here. Find a buyer, and we split the profit 50/50.\"\r\n\r\nThe Arrest: Jordan is nervous and acts suspiciously. A nearby plainclothes officer notices the exchange. Later that night, Jordan is pulled over for a broken taillight. The officer, sensing Jordan\'s extreme nervousness, asks to search the car. Jordan consents, and the officer finds the thumb drive.\r\n\r\nThe Current Situation:\r\n\r\nThe police have seized the thumb drive but haven\'t cracked the encryption yet.\r\n\r\nFinTech Corp has noticed the unauthorized download and has filed a report.\r\n\r\nAlex has been brought in for questioning but hasn\'t admitted to anything yet.','active',NULL,NULL,'2026-01-09 22:11:52','2026-01-09 22:11:52',NULL),('019ba61a-d7f9-70e0-b161-5e8b7587098a','019ba60f-944b-73e5-871c-30bf0db5291e','other','California','California','US','The Background: Alex is a lead software engineer at FinTech Corp, a company that recently developed a proprietary algorithm for high-frequency trading. Alex feels passed over for a promotion and decides to quit, but not before taking \"what he’s owed.\"\r\n\r\nThe Incident:\r\n\r\nThe Data Breach: On his last day, Alex downloads the source code for the algorithm onto a personal encrypted thumb drive. He also bcc’s his personal email on several internal strategy memos.\r\n\r\nThe Hand-off: Alex meets a friend, Jordan, at a coffee shop. Alex gives Jordan the thumb drive, telling him, \"There’s a gold mine on here. Find a buyer, and we split the profit 50/50.\"\r\n\r\nThe Arrest: Jordan is nervous and acts suspiciously. A nearby plainclothes officer notices the exchange. Later that night, Jordan is pulled over for a broken taillight. The officer, sensing Jordan\'s extreme nervousness, asks to search the car. Jordan consents, and the officer finds the thumb drive.\r\n\r\nThe Current Situation:\r\n\r\nThe police have seized the thumb drive but haven\'t cracked the encryption yet.\r\n\r\nFinTech Corp has noticed the unauthorized download and has filed a report.\r\n\r\nAlex has been brought in for questioning but hasn\'t admitted to anything yet.','active',NULL,NULL,'2026-01-09 22:12:15','2026-01-09 22:12:15',NULL),('019ba6ab-3865-7276-ac17-02b9412648d3','019ba60f-944b-73e5-871c-30bf0db5291e','employment','New York','New York','US','Marcus is a freelance graphic designer. He agreed to create a full branding suite for a local coffee shop, \"Daily Grind.\" They discussed a price of $3,000 over coffee and via a few casual text messages.\r\nMarcus delivered the work, but the owner of the shop claims they only agreed to $1,500 and refuses to pay the remainder, citing that \"nothing was ever officially signed.\"\r\n\r\nKey Legal Questions:\r\n\r\nDo the text messages constitute a \"written instrument\" or evidence of a contract?\r\n\r\nHas \"performance\" (the delivery of the work) validated the oral agreement?\r\n\r\nWhat are the local statutes regarding freelance payment protections?','active',NULL,NULL,'2026-01-10 00:49:57','2026-01-10 00:49:57',NULL);
/*!40000 ALTER TABLE `all_cases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
INSERT INTO `cache` VALUES ('advocate-cache-livewire-rate-limiter:16d36dff9abd246c67dfac3e63b993a169af77e6','i:1;',1768035234),('advocate-cache-livewire-rate-limiter:16d36dff9abd246c67dfac3e63b993a169af77e6:timer','i:1768035234;',1768035234),('advocate-cache-livewire-rate-limiter:9a85c7d25d4828b60e211b83318cc879a9769002','i:1;',1767957946),('advocate-cache-livewire-rate-limiter:9a85c7d25d4828b60e211b83318cc879a9769002:timer','i:1767957946;',1767957946);
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `case_documents`
--

DROP TABLE IF EXISTS `case_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_documents` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `all_case_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_feedback_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stored_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size` bigint unsigned NOT NULL,
  `document_type` enum('landlord_tenant','employment','contracts','consumer_rights','family','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'other',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `case_documents_all_case_id_created_at_index` (`all_case_id`,`created_at`),
  KEY `case_documents_user_id_index` (`user_id`),
  KEY `case_documents_response_feedback_id_index` (`response_feedback_id`),
  CONSTRAINT `case_documents_all_case_id_foreign` FOREIGN KEY (`all_case_id`) REFERENCES `all_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `case_documents_response_feedback_id_foreign` FOREIGN KEY (`response_feedback_id`) REFERENCES `response_feedback` (`id`) ON DELETE CASCADE,
  CONSTRAINT `case_documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_documents`
--

LOCK TABLES `case_documents` WRITE;
/*!40000 ALTER TABLE `case_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `case_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `case_outcomes`
--

DROP TABLE IF EXISTS `case_outcomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `case_outcomes` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `all_case_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `outcome_type` enum('won','settled','lost','dropped') COLLATE utf8mb4_unicode_ci NOT NULL,
  `outcome_summary` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `money_saved` text COLLATE utf8mb4_unicode_ci,
  `money_recovered` text COLLATE utf8mb4_unicode_ci,
  `court_avoided` tinyint(1) NOT NULL DEFAULT '0',
  `hired_attorney` tinyint(1) NOT NULL DEFAULT '0',
  `ai_helpfulness_rating` tinyint unsigned DEFAULT NULL,
  `feedback_text` text COLLATE utf8mb4_unicode_ci,
  `would_recommend` tinyint(1) DEFAULT NULL,
  `testimonial_consent` tinyint(1) NOT NULL DEFAULT '0',
  `testimonial_published` tinyint(1) NOT NULL DEFAULT '0',
  `days_to_resolution` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `case_outcomes_all_case_id_unique` (`all_case_id`),
  KEY `case_outcomes_user_id_foreign` (`user_id`),
  KEY `case_outcomes_outcome_type_index` (`outcome_type`),
  KEY `case_outcomes_testimonial_consent_testimonial_published_index` (`testimonial_consent`,`testimonial_published`),
  KEY `case_outcomes_ai_helpfulness_rating_index` (`ai_helpfulness_rating`),
  CONSTRAINT `case_outcomes_all_case_id_foreign` FOREIGN KEY (`all_case_id`) REFERENCES `all_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `case_outcomes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `case_outcomes`
--

LOCK TABLES `case_outcomes` WRITE;
/*!40000 ALTER TABLE `case_outcomes` DISABLE KEYS */;
INSERT INTO `case_outcomes` VALUES ('019ba240-b060-7089-bffb-354b322c85e5','019ba21d-30db-7128-ad4f-078081de4a19','019ba21b-b7c3-7129-9854-284c95d6e40c','won','The Background: Alex rented an apartment for two years. When he moved out on June 1st, he spent two days cleaning, patched the small holes from his picture frames, and took photos of every room. His monthly rent was $1,500, and he had a $1,500 security deposit held by the landlord, Mr. Henderson. The Conflict: It is now July 15th (45 days after move-out). Alex finally receives a check from Mr. Henderson for only $400. Attached is a handwritten note claiming: $600 for \"professional deep cleaning.\" $500 for \"repainting the entire living room due to wall damage.\" Alex feels this is unfair because the apartment was spotless and the \"damage\" was just normal wear and tear from hanging a few photos.',NULL,NULL,1,0,4,NULL,NULL,1,0,0,'2026-01-09 04:15:07','2026-01-09 04:15:07');
/*!40000 ALTER TABLE `case_outcomes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chat_messages`
--

DROP TABLE IF EXISTS `chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `chat_messages` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `all_case_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_feedback_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('user','assistant','system') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `ai_model_used` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_tokens` int DEFAULT NULL,
  `output_tokens` int DEFAULT NULL,
  `cost` decimal(10,6) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `chat_messages_all_case_id_created_at_index` (`all_case_id`,`created_at`),
  KEY `chat_messages_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `chat_messages_response_feedback_id_foreign` (`response_feedback_id`),
  CONSTRAINT `chat_messages_all_case_id_foreign` FOREIGN KEY (`all_case_id`) REFERENCES `all_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `chat_messages_response_feedback_id_foreign` FOREIGN KEY (`response_feedback_id`) REFERENCES `response_feedback` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chat_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chat_messages`
--

LOCK TABLES `chat_messages` WRITE;
/*!40000 ALTER TABLE `chat_messages` DISABLE KEYS */;
INSERT INTO `chat_messages` VALUES ('019ba21d-c3b9-728e-96be-a7146cf29580','019ba21d-30db-7128-ad4f-078081de4a19',NULL,'019ba21b-b7c3-7129-9854-284c95d6e40c','user','hi',NULL,NULL,NULL,NULL,'{\"type\": \"normal\", \"timestamp\": \"2026-01-09T09:36:58.297149Z\", \"feedback_id\": null}','2026-01-09 03:36:58','2026-01-09 03:36:58'),('019ba21d-cc34-70b4-bedd-960364fd217c','019ba21d-30db-7128-ad4f-078081de4a19',NULL,'019ba21b-b7c3-7129-9854-284c95d6e40c','assistant','Hi there! Thanks for reaching out.\n\nI\'m ready to help you understand Alex\'s situation regarding the security deposit in Alaska.\n\nTo recap, Alex is disputing deductions of $600 for \"professional deep cleaning\" and $500 for \"repainting the entire living room due to wall damage\" from his $1,500 security deposit. He believes the apartment was spotless and the \"damage\" was normal wear and tear. It\'s also 45 days after he moved out, and he just received the $400 check.\n\nWhat specific question do you have, or would you like me to start by explaining Alaska\'s laws regarding security deposits and the timeline for their return?','gemini-2.5-flash',614,150,0.000000,'{\"type\": \"normal\", \"timestamp\": \"2026-01-09T09:37:00.468258Z\", \"feedback_id\": null}','2026-01-09 03:37:00','2026-01-09 03:37:00'),('019ba61b-85af-7070-be9d-c6532179722e','019ba61a-d7f9-70e0-b161-5e8b7587098a',NULL,'019ba60f-944b-73e5-871c-30bf0db5291e','user','hi',NULL,NULL,NULL,NULL,'{\"type\": \"normal\", \"timestamp\": \"2026-01-10T04:13:00.206585Z\", \"feedback_id\": null}','2026-01-09 22:13:00','2026-01-09 22:13:00'),('019ba61b-98b5-72c5-a122-a13447a0e1ed','019ba61a-d7f9-70e0-b161-5e8b7587098a',NULL,'019ba60f-944b-73e5-871c-30bf0db5291e','assistant','Hello! Thanks for reaching out.\n\nI\'m ready to assist you with the case details regarding Alex, Jordan, FinTech Corp, and the seized thumb drive.\n\nWhat specific questions do you have, or what aspect of the situation would you like to discuss first? For example, we could look at:\n\n*   Potential charges Alex or Jordan might face.\n*   The legality of the search and seizure of the thumb drive.\n*   FinTech Corp\'s potential legal actions.\n*   The implications of the encryption.\n\nPlease remember that I am a legal assistant providing educational information, not legal advice. For specific legal matters, it\'s always best to consult with a licensed attorney.','gemini-2.5-flash',800,144,0.000000,'{\"type\": \"normal\", \"timestamp\": \"2026-01-10T04:13:05.077470Z\", \"feedback_id\": null}','2026-01-09 22:13:05','2026-01-09 22:13:05'),('019ba6ab-83fd-703f-a378-1e6bd1252b08','019ba6ab-3865-7276-ac17-02b9412648d3',NULL,'019ba60f-944b-73e5-871c-30bf0db5291e','user','Please provide the solution',NULL,NULL,NULL,NULL,'{\"type\": \"normal\", \"timestamp\": \"2026-01-10T06:50:16.957299Z\", \"feedback_id\": null}','2026-01-10 00:50:16','2026-01-10 00:50:16'),('019ba6ab-ad2e-737a-8280-63686c276794','019ba6ab-3865-7276-ac17-02b9412648d3',NULL,'019ba60f-944b-73e5-871c-30bf0db5291e','assistant','Here\'s an analysis of your legal questions regarding Marcus\'s situation in New York, New York:\n\n***\n\n**Disclaimer:** Please remember, this information is for educational purposes only and does not constitute legal advice. For specific legal guidance on your situation, you should always consult with a licensed attorney.\n\n***\n\n### 1. Do the text messages constitute a \"written instrument\" or evidence of a contract?\n\nWhile text messages are not typically considered a formal \"written instrument\" in the traditional sense (like a signed paper document), they can absolutely serve as **strong evidence of a contract** and its terms under New York law.\n\nHere\'s why:\n\n*   **Offer and Acceptance:** If the text messages show a clear offer (e.g., \"I\'ll create the full branding suite for $3,000\") and a clear acceptance (e.g., \"Sounds good, let\'s do it\" or similar affirmative language), they can establish mutual assent.\n*   **Key Terms:** If the texts include essential terms like the parties involved, the scope of work (branding suite), and the price ($3,000), they provide concrete evidence of the agreement.\n*   **Electronic Communications:** New York\'s Electronic Signatures and Records Act (ESRA) generally provides that electronic communications and signatures can be legally binding, as long as the parties intended to be bound by them. While a formal e-signature might not be present, the casual nature of texts doesn\'t automatically negate their evidentiary value if they demonstrate an agreement.\n*   **Statute of Frauds:** For most service contracts that can be performed within one year (like a branding project), New York\'s Statute of Frauds does *not* require the contract to be in writing to be enforceable. Therefore, an oral agreement, supported by text message evidence, can be perfectly valid.\n\n**Conclusion:** The text messages are highly likely to be admissible as evidence of the contract and its terms, even if they don\'t form a single, formal \"written instrument.\"\n\n### 2. Has \"performance\" (the delivery of the work) validated the oral agreement?\n\n**Yes, absolutely.** Marcus\'s full performance of the work (delivering the branding suite) significantly strengthens the enforceability of the agreement, even if it was initially oral or informally documented.\n\nHere\'s the legal principle at play:\n\n*   **Acceptance of Performance:** When Marcus delivered the complete branding suite, and Daily Grind accepted (or at least received and utilized) that work, it serves as powerful evidence that an agreement existed and that Marcus fulfilled his end of the bargain. A party generally cannot accept the benefits of another\'s performance under a contract and then deny the existence of that contract or refuse to pay the agreed-upon price.\n*   **Unjust Enrichment:** If Daily Grind benefits from Marcus\'s work without paying the agreed-upon amount, it could be argued that they are being \"unjustly enriched.\" The law generally prevents one party from unfairly benefiting at the expense of another.\n*   **Disputed Terms:** While Daily Grind disputes the *price*, Marcus\'s performance validates the *existence* of an agreement for him to do the work. The dispute then shifts from \"was there a contract?\" to \"what were the terms of that contract, specifically the price?\" The text messages would be crucial here to prove the $3,000 figure.\n\n**Conclusion:** Marcus\'s delivery and Daily Grind\'s acceptance of the work strongly validate the existence of a contractual agreement, making it difficult for Daily Grind to claim no agreement existed at all.\n\n### 3. What are the local statutes regarding freelance payment protections?\n\nThis is a critical area for Marcus, as New York City has robust protections for freelancers under the **Freelance Isn\'t Free Act (FIFA)**, effective since May 2017. Since Daily Grind is a local coffee shop in New York, New York, FIFA applies directly.\n\nHere are the key provisions of FIFA relevant to Marcus\'s case:\n\n*   **Written Contract Requirement:** For services valued at **$800 or more** (or multiple contracts with the same hiring party totaling $800 or more within a 120-day period), FIFA *requires* a written contract. This contract must include:\n    *   The names and addresses of both parties.\n    *   An itemization of all services to be provided.\n    *   The rate and method of payment.\n    *   The date payment is due.\n    *   The date by which the hiring party must pay or return any property.\n*   **Timely Payment:** Unless otherwise specified in the contract, a freelancer must be paid within **30 days** of completing the work or by the date specified in the contract, whichever is earlier.\n*   **Retaliation Prohibited:** Clients cannot retaliate against freelancers for exercising their rights under FIFA.\n*   **Damages and Penalties for Violations:** If a hiring party violates FIFA, a freelancer can recover significant damages:\n    *   **Statutory Damages:** If a client fails to provide a written contract when required, the freelancer can recover statutory damages of **$250**.\n    *   **Double Damages for Late Payment:** If a client pays late','gemini-2.5-flash',539,1110,0.000000,'{\"type\": \"normal\", \"timestamp\": \"2026-01-10T06:50:27.501845Z\", \"feedback_id\": null}','2026-01-10 00:50:27','2026-01-10 00:50:27');
/*!40000 ALTER TABLE `chat_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `credit_purchases`
--

DROP TABLE IF EXISTS `credit_purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credit_purchases` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subscription_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(8,2) NOT NULL,
  `credits_added` decimal(10,4) NOT NULL,
  `status` enum('pending','completed','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `stripe_payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `applied_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_purchases_subscription_id_foreign` (`subscription_id`),
  KEY `credit_purchases_user_id_status_index` (`user_id`,`status`),
  KEY `credit_purchases_stripe_payment_intent_id_index` (`stripe_payment_intent_id`),
  KEY `credit_purchases_expires_at_index` (`expires_at`),
  CONSTRAINT `credit_purchases_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `credit_purchases_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `credit_purchases`
--

LOCK TABLES `credit_purchases` WRITE;
/*!40000 ALTER TABLE `credit_purchases` DISABLE KEYS */;
INSERT INTO `credit_purchases` VALUES ('019ba21b-b7cf-73b6-b154-e8ac6ad1235d','019ba21b-b7c3-7129-9854-284c95d6e40c','019ba21b-b7cc-7124-befc-3db8f5c61692',0.00,10.0000,'completed',NULL,'2125-01-09 09:34:44','2026-01-09 09:34:44','2026-01-09 03:34:44','2026-01-09 03:34:44'),('019ba60f-9458-70fb-928a-d1bf32735411','019ba60f-944b-73e5-871c-30bf0db5291e','019ba60f-9456-7078-9940-ab64d3b6d8be',0.00,10.0000,'completed',NULL,'2125-01-10 03:59:57','2026-01-10 03:59:57','2026-01-09 21:59:57','2026-01-09 21:59:57');
/*!40000 ALTER TABLE `credit_purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `documents`
--

DROP TABLE IF EXISTS `documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documents` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `all_case_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_type` enum('demand_letter','formal_notice','response_letter','cease_desist','notice_to_cure','complaint_letter','cease_and_desist','custom','uploaded') COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` enum('generated','uploaded') COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_mime_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint unsigned DEFAULT NULL,
  `download_count` int NOT NULL DEFAULT '0',
  `last_downloaded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `documents_all_case_id_source_index` (`all_case_id`,`source`),
  KEY `documents_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `documents_document_type_index` (`document_type`),
  CONSTRAINT `documents_all_case_id_foreign` FOREIGN KEY (`all_case_id`) REFERENCES `all_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `documents`
--

LOCK TABLES `documents` WRITE;
/*!40000 ALTER TABLE `documents` DISABLE KEYS */;
INSERT INTO `documents` VALUES ('019ba252-479f-72ac-bd8f-59c8d0e34f2e','019ba21d-30db-7128-ad4f-078081de4a19','019ba21b-b7c3-7129-9854-284c95d6e40c','Demand Letter','demand_letter','generated','[YOUR NAME]\n[YOUR ADDRESS]\n[YOUR CITY, STATE ZIP]\n\nJanuary 09, 2026\n\n[RECIPIENT NAME]\n[RECIPIENT ADDRESS]\n[RECIPIENT CITY, STATE ZIP]\n\n**Re: Demand for Return of Improperly Withheld Security Deposit for [PROPERTY ADDRESS]**\n\nDear [RECIPIENT NAME]:\n\nThis letter serves as a formal demand for the immediate return of the $1,100 improperly withheld from my security deposit for the property located at [PROPERTY ADDRESS], which I vacated on or about [DATE OF MOVE-OUT].\n\n**Statement of Facts:**\n\n1.  I provided a security deposit of $1,500 for the rental property at [PROPERTY ADDRESS].\n2.  Upon my departure from the property, I ensured the apartment was spotless and in excellent condition, beyond normal wear and tear.\n3.  Approximately 45 days after my move-out date, I received a check for only $400, indicating that $1,100 had been withheld from my original $1,500 security deposit.\n4.  The deductions itemized were $600 for \"professional deep cleaning\" and $500 for \"repainting the entire living room due to wall damage.\"\n5.  I dispute these deductions entirely. The apartment was left in a clean state, and any perceived \"wall damage\" was, in my assessment, consistent with normal wear and tear that occurs over the course of a tenancy, not actual damage requiring repainting at my expense.\n\n**Legal Basis:**\n\nUnder Alaska Statute (AS) 34.03.070, a landlord is required to return a security deposit within 14 days after the tenancy terminates and the tenant vacates if the tenant gives proper notice, or within 30 days if the tenant does not give proper notice. Your return of only a portion of the deposit 45 days after my move-out date constitutes a violation of this statutory timeline.\n\nFurthermore, Alaska law permits deductions from a security deposit only for actual damages to the premises beyond normal wear and tear, or for unpaid rent. Deductions for routine cleaning or for cosmetic issues that fall under normal wear and tear are generally impermissible. My leaving the apartment spotless contradicts the need for \"professional deep cleaning,\" and any minor wall imperfections are considered normal wear and tear, not damage for which a tenant can be charged for full repainting. The deductions for $600 for \"professional deep cleaning\" and $500 for \"repainting the entire living room due to wall damage\" are therefore improper and unlawful under Alaska law.\n\n**Demands:**\n\nTherefore, I demand the following actions be taken immediately:\n\n1.  Return the full $1,100 that was improperly withheld from my security deposit.\n2.  Provide this payment in the form of a certified check or money order, made payable to [YOUR NAME].\n3.  Ensure the payment is received by me within ten (10) days from the date of this letter.\n\n**Consequences:**\n\nPlease be advised that if I do not receive the full $1,100 within the specified ten (10) day period, I will have no alternative but to pursue legal action. This may include filing a claim in small claims court, where I will seek not only the $1,100 wrongfully withheld but also potential statutory penalties of up to twice the amount wrongfully withheld, as permitted by AS 34.03.070(d), along with court costs and any applicable attorney\'s fees.\n\n**Closing:**\n\nI expect a prompt and satisfactory response to this demand. I prefer to resolve this matter amicably and without the need for litigation.\n\nSincerely,\n\n[YOUR NAME]\n\n---\n*Note: This document was generated based on AI assistance and should be reviewed by a licensed attorney before use.*',NULL,NULL,NULL,0,NULL,'2026-01-09 04:34:19','2026-01-09 04:34:19',NULL),('019ba288-882b-72d2-beca-972a6bfa7831','019ba21d-30db-7128-ad4f-078081de4a19','019ba21b-b7c3-7129-9854-284c95d6e40c','Demand Letter','demand_letter','generated','[YOUR NAME]\n[YOUR ADDRESS]\n[YOUR CITY, STATE ZIP]\n\nJanuary 09, 2026\n\n[RECIPIENT NAME]\n[RECIPIENT ADDRESS]\n[RECIPIENT CITY, STATE ZIP]\n\n**Re: Demand for Immediate Return of Wrongfully Withheld Security Deposit for [Alex\'s Former Address]**\n\nDear [RECIPIENT NAME]:\n\nThis letter serves as a formal demand for the immediate return of the $1,100 wrongfully withheld from my security deposit for the rental property located at [Alex\'s Former Address, if known, otherwise state \"the property I recently vacated\"]. The deductions made for \"professional deep cleaning\" and \"repainting the entire living room due to wall damage\" are improper, and the return of the deposit was delayed beyond the legally mandated timeframe under Alaska law.\n\n**Statement of Facts:**\n\n1.  I entered into a lease agreement with you for the property located at [Alex\'s Former Address] and provided a security deposit of $1,500.\n2.  I vacated the premises on or about [INSERT MOVE-OUT DATE, e.g., November 25, 2025], ensuring the apartment was spotless upon my departure.\n3.  As of January 09, 2026, approximately 45 days after my move-out, I received a check for only $400, indicating that $1,100 of my security deposit has been withheld.\n4.  The deductions communicated include $600 for \"professional deep cleaning\" and $500 for \"repainting the entire living',NULL,NULL,NULL,0,NULL,'2026-01-09 05:33:35','2026-01-09 05:33:35',NULL),('019ba61b-f61e-733f-bb8b-6d9bf9e6e2aa','019ba61a-d7f9-70e0-b161-5e8b7587098a','019ba60f-944b-73e5-871c-30bf0db5291e','Demand Letter','demand_letter','generated','[YOUR NAME]\n[YOUR ADDRESS]\n[YOUR CITY, STATE ZIP]\n\nJanuary 10, 2026\n\n[RECIPIENT NAME/ENTITY]\n[RECIPIENT ADDRESS]\n[RECIPIENT CITY, STATE ZIP]\n\n**Re: Demand for Resolution Regarding Matters Involving Alex, Jordan, FinTech Corp, and the Seized Thumb',NULL,NULL,NULL,0,NULL,'2026-01-09 22:13:28','2026-01-09 22:13:28',NULL),('019ba6ac-dd33-7362-a173-8569c7c6b7f7','019ba6ab-3865-7276-ac17-02b9412648d3','019ba60f-944b-73e5-871c-30bf0db5291e','Response Letter','response_letter','generated','[MARCUS\'S NAME]\n[MARCUS\'S ADDRESS]\n[MARCUS\'S CITY, STATE ZIP]\n\nJanuary 10, 2026\n\nDaily Grind\n[DAILY GRIND\'S ADDRESS - Placeholder, as not provided in conversation]\n[DAILY GRIND\'S CITY, STATE ZIP - Placeholder]\n\n**Re: Demand for Payment for Branding Suite Services and Compliance with NYC Freelance Isn\'t Free Act**\n\nDear Daily Grind:\n\nThis letter serves as a formal demand for payment for the full branding suite services rendered, as well as for statutory damages and penalties due to your non-compliance with the New York City Freelance Isn\'t Free Act (\"FIFA\").\n\nAs you are aware, an agreement was established for me to create a full branding suite for your coffee shop, Daily Grind. The agreed-upon price for these services was $3,000.\n\n**Existence and Terms of Agreement:**\nThe agreement and its specific terms, including the scope of work and the $3,000 compensation, were clearly established through text message communications between us. These messages demonstrate a clear offer from my end (\"I\'ll create the full branding suite for $3,000\") and your affirmative acceptance (\"Sounds good, let\'s do it\"). Under New York law, these electronic communications serve as strong, admissible evidence of a binding contract.\n\n**Performance of Services:**\nI have fully performed my obligations under this agreement by delivering the complete branding suite as contracted. You have received and, presumably, are utilizing this work. My full performance of the agreed-upon services further validates the existence and enforceability of our contract.\n\n**Failure to Pay and Unjust Enrichment:**\nDespite the clear agreement and my complete delivery of the branding suite, payment of the agreed $3,000 has not been remitted. Your acceptance of my work without providing the agreed compensation constitutes unjust enrichment, as you are benefiting from my services without fulfilling your contractual obligation.\n\n**Violations of the New York City Freelance Isn\'t Free Act (FIFA):**\nAs a freelance service provider operating in New York, New York, for a local business also located in New York, New York, our agreement is subject to the protections afforded by the NYC Freelance Isn\'t Free Act. Your conduct in this matter is in direct violation of several key provisions of FIFA:\n\n1.  **Failure to Provide a Written Contract:** For services valued at $800 or more, FIFA explicitly requires the hiring party to provide a written contract detailing the names and addresses of both parties, an itemization of services, the rate and method of payment, and the due date for payment. You failed to provide any such written contract.\n2.  **Failure of Timely Payment:** Unless otherwise specified, FIFA mandates that freelancers be paid within 30 days of completing the work. Your failure to pay the agreed-upon amount within this timeframe, or at all, constitutes a violation of the timely payment provision.\n\n**Your Position:**\nIt is my firm position that a valid and enforceable contract for the creation of the branding suite existed, the terms of which were agreed upon at $3,000. I have fully upheld my end of this agreement by delivering the complete work. Daily Grind, however, has breached this contract by failing to remit payment and has furthermore violated the NYC Freelance Isn\'t Free Act by failing to provide a written contract and failing to make timely payment.\n\n**Supporting Facts and Legal Basis:**\n*   The text message exchanges clearly document the offer, acceptance, and the agreed-upon price of $3,000 for the full branding suite.\n*   My delivery of',NULL,NULL,NULL,0,NULL,'2026-01-10 00:51:45','2026-01-10 00:51:45',NULL);
/*!40000 ALTER TABLE `documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feature_flags`
--

DROP TABLE IF EXISTS `feature_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_flags` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `flag_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `enabled_for_users` json DEFAULT NULL,
  `enabled_for_plans` json DEFAULT NULL,
  `rollout_percentage` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_flags_flag_key_unique` (`flag_key`),
  KEY `feature_flags_flag_key_index` (`flag_key`),
  KEY `feature_flags_is_enabled_index` (`is_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feature_flags`
--

LOCK TABLES `feature_flags` WRITE;
/*!40000 ALTER TABLE `feature_flags` DISABLE KEYS */;
/*!40000 ALTER TABLE `feature_flags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `legal_templates`
--

DROP TABLE IF EXISTS `legal_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `legal_templates` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_type` enum('demand_letter','notice_to_cure','complaint_letter','cease_and_desist','custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_type` enum('landlord_tenant','employment','contracts','consumer_rights','family','other','general') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jurisdiction_state` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jurisdiction_country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `required_fields` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `usage_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `legal_templates_document_type_issue_type_index` (`document_type`,`issue_type`),
  KEY `legal_templates_jurisdiction_state_jurisdiction_country_index` (`jurisdiction_state`,`jurisdiction_country`),
  KEY `legal_templates_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `legal_templates`
--

LOCK TABLES `legal_templates` WRITE;
/*!40000 ALTER TABLE `legal_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `legal_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_12_31_060627_create_admin_users_table',1),(5,'2025_12_31_060629_create_feature_flags_table',1),(6,'2025_12_31_060630_create_system_settings_table',1),(7,'2025_12_31_060631_create_legal_templates_table',1),(8,'2025_12_31_060640_create_subscriptions_table',1),(9,'2025_12_31_061141_create_all_cases_table',1),(10,'2025_12_31_061145_create_usage_trackings_table',1),(11,'2025_12_31_061603_create_credit_purchases_table',1),(12,'2025_12_31_064646_create_chat_messages_table',1),(13,'2025_12_31_064751_create_documents_table',1),(14,'2025_12_31_065009_create_response_feedback_table',1),(15,'2025_12_31_065100_create_case_outcomes_table',1),(16,'2025_12_31_095726_create_personal_access_tokens_table',1),(17,'2026_01_02_103352_create_payments_table',1),(18,'2026_01_07_034711_create_case_documents_table',1),(19,'2026_01_08_035110_update_documents_document_type_enum',1),(20,'2026_01_08_062344_add_ai_fields_to_response_feedback_table',1),(21,'2026_01_08_062506_add_response_feedback_id_to_case_documents_table',1),(22,'2026_01_09_042755_add_send_to_chat_to_response_feedback_table',1),(23,'2026_01_09_043123_add_columns_to_chat_messages_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_gateway` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_intent_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'usd',
  `status` enum('pending','succeeded','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` text COLLATE utf8mb4_unicode_ci,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_transaction_id_unique` (`transaction_id`),
  KEY `payments_user_id_status_index` (`user_id`,`status`),
  KEY `payments_transaction_id_index` (`transaction_id`),
  CONSTRAINT `payments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,'019ba21b-b7c3-7129-9854-284c95d6e40c','stripe','b247b1fd-7a77-475a-8d1e-83fab66d945f','pi_3Snc6YL0pUftKQ9X0qpTCr2Z',9.00,'usd','succeeded','{\"plan_name\":\"Pro\",\"plan_type\":\"pro\",\"subscription_plan_id\":2,\"type\":\"online\",\"verified_at\":\"2026-01-09T09:35:40.848000Z\",\"payment_intent_status\":\"succeeded\"}','2026-01-09 03:35:40','2026-01-09 03:35:38','2026-01-09 03:35:40'),(2,'019ba60f-944b-73e5-871c-30bf0db5291e','stripe','d4e763a6-3961-40a1-b336-cc084244910f','pi_3SnuwHL0pUftKQ9X1w5GZTW1',9.00,'usd','succeeded','{\"plan_name\":\"Pro\",\"plan_type\":\"pro\",\"subscription_plan_id\":2,\"type\":\"online\",\"verified_at\":\"2026-01-10T05:42:18.084660Z\",\"payment_intent_status\":\"succeeded\"}','2026-01-09 23:42:18','2026-01-09 23:42:15','2026-01-09 23:42:18'),(3,'019ba60f-944b-73e5-871c-30bf0db5291e','stripe','e88a7c64-0715-48a4-881d-c425bc85034e','pi_3SnvKqL0pUftKQ9X0BgPV5dp',29.00,'usd','pending','{\"plan_name\":\"Pro Plus\",\"plan_type\":\"pro_plus\",\"subscription_plan_id\":3,\"type\":\"online\"}',NULL,'2026-01-10 00:07:39','2026-01-10 00:07:39'),(4,'019ba60f-944b-73e5-871c-30bf0db5291e','stripe','4a4c6903-ffa2-49b6-8947-8ddaa3fb8c5b','pi_3SnvL5L0pUftKQ9X1IUD9MCJ',29.00,'usd','succeeded','{\"plan_name\":\"Pro Plus\",\"plan_type\":\"pro_plus\",\"subscription_plan_id\":3,\"type\":\"online\",\"verified_at\":\"2026-01-10T06:07:57.210073Z\",\"payment_intent_status\":\"succeeded\"}','2026-01-10 00:07:57','2026-01-10 00:07:54','2026-01-10 00:07:57'),(5,'019ba60f-944b-73e5-871c-30bf0db5291e','stripe','a022ce41-107f-4d6e-8aea-e2455381d261','pi_3SnvLaL0pUftKQ9X00EOqIv6',9.00,'usd','succeeded','{\"plan_name\":\"Pro\",\"plan_type\":\"pro\",\"subscription_plan_id\":2,\"type\":\"online\",\"verified_at\":\"2026-01-10T06:08:28.618459Z\",\"payment_intent_status\":\"succeeded\"}','2026-01-10 00:08:28','2026-01-10 00:08:26','2026-01-10 00:08:28'),(6,'019ba60f-944b-73e5-871c-30bf0db5291e','stripe','6e0e6df4-8069-409a-a4ab-ce2de6cf2a8f','pi_3SnwOWL0pUftKQ9X1j0qLON1',9.00,'usd','succeeded','{\"plan_name\":\"Pro\",\"plan_type\":\"pro\",\"subscription_plan_id\":2,\"type\":\"online\",\"verified_at\":\"2026-01-10T07:15:34.552018Z\",\"payment_intent_status\":\"succeeded\"}','2026-01-10 01:15:34','2026-01-10 01:15:32','2026-01-10 01:15:34');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `response_feedback`
--

DROP TABLE IF EXISTS `response_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `response_feedback` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `all_case_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_type` enum('complied','partial_compliance','refused','no_response','counter_offer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_to_chat` tinyint(1) NOT NULL DEFAULT '0',
  `response_date` date NOT NULL,
  `action_taken_date` date DEFAULT NULL,
  `days_to_response` int DEFAULT NULL,
  `ai_analyzed` tinyint(1) NOT NULL DEFAULT '0',
  `ai_analysis` text COLLATE utf8mb4_unicode_ci,
  `ai_next_steps` text COLLATE utf8mb4_unicode_ci,
  `escalation_options` json DEFAULT NULL,
  `urgency_level` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `recommended_deadline` date DEFAULT NULL,
  `status` enum('active','resolved','escalated','closed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `response_feedback_user_id_foreign` (`user_id`),
  KEY `response_feedback_all_case_id_response_date_index` (`all_case_id`,`response_date`),
  KEY `response_feedback_response_type_index` (`response_type`),
  KEY `response_feedback_ai_analyzed_index` (`ai_analyzed`),
  KEY `response_feedback_status_index` (`status`),
  CONSTRAINT `response_feedback_all_case_id_foreign` FOREIGN KEY (`all_case_id`) REFERENCES `all_cases` (`id`) ON DELETE CASCADE,
  CONSTRAINT `response_feedback_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `response_feedback`
--

LOCK TABLES `response_feedback` WRITE;
/*!40000 ALTER TABLE `response_feedback` DISABLE KEYS */;
/*!40000 ALTER TABLE `response_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('MGWaIGSgjRJWeBCMWW9Z6Tl7YRpQmyxk1aSfjCYK','019ba280-7511-72f5-bc00-eb95aacd7750','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiakRMdzJFTXFBeFh6SFpVbUpxTzBUNlg5NllWc1YxWWd0TWdvOFJoZCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjc6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbiI7czo1OiJyb3V0ZSI7czozMDoiZmlsYW1lbnQuYWRtaW4ucGFnZXMuZGFzaGJvYXJkIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MjoibG9naW5fYWRtaW5fNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7czozNjoiMDE5YmEyODAtNzUxMS03MmY1LWJjMDAtZWI5NWFhY2Q3NzUwIjtzOjE5OiJwYXNzd29yZF9oYXNoX2FkbWluIjtzOjYwOiIkMnkkMTIkNldTZ2llZ3J0NTdpU3dkM1NCRFNjdXNVSTR2Y1hxTExJampHUDg4MnV6S1hiVW4vcEVxNC4iO3M6NjoidGFibGVzIjthOjI6e3M6NDA6Ijg2NzQ1ZDU1NDYzN2ZmYzdjYmYzYWExMjc0NDQ1MDQ2X2NvbHVtbnMiO2E6MTA6e2k6MDthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czo5OiJ1c2VyLm5hbWUiO3M6NToibGFiZWwiO3M6NDoiTmFtZSI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjE7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6Imlzc3VlX3R5cGUiO3M6NToibGFiZWwiO3M6MTA6Iklzc3VlIFR5cGUiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aToyO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjEzOiJsb2NhdGlvbl9jaXR5IjtzOjU6ImxhYmVsIjtzOjQ6IkNpdHkiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aTozO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjE0OiJsb2NhdGlvbl9zdGF0ZSI7czo1OiJsYWJlbCI7czo1OiJTdGF0ZSI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjQ7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTY6ImxvY2F0aW9uX2NvdW50cnkiO3M6NToibGFiZWwiO3M6NzoiQ291bnRyeSI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjU7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6Njoic3RhdHVzIjtzOjU6ImxhYmVsIjtzOjY6IlN0YXR1cyI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjY7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTE6InJlc29sdmVkX2F0IjtzOjU6ImxhYmVsIjtzOjExOiJSZXNvbHZlZCBBdCI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjc7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6ImNyZWF0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IkNyZWF0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO31pOjg7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6InVwZGF0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IlVwZGF0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO31pOjk7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6ImRlbGV0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IkRlbGV0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO319czo0MDoiN2Q5ZjUwYzNlZjc3Njk3MGIwYmIxNWIyNGIyNTY2YTVfY29sdW1ucyI7YTo3OntpOjA7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6NDoibmFtZSI7czo1OiJsYWJlbCI7czo0OiJOYW1lIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MTthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czo1OiJlbWFpbCI7czo1OiJsYWJlbCI7czoxMzoiRW1haWwgYWRkcmVzcyI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjI7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTQ6ImFjY291bnRfc3RhdHVzIjtzOjU6ImxhYmVsIjtzOjE0OiJBY2NvdW50IHN0YXR1cyI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjM7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MjM6InN1YnNjcmlwdGlvbnMucGxhbl90aWVyIjtzOjU6ImxhYmVsIjtzOjk6IlBsYW4gdGllciI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjQ7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6ImNyZWF0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IkNyZWF0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO31pOjU7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6InVwZGF0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IlVwZGF0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO31pOjY7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6ImRlbGV0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IkRlbGV0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO319fX0=',1767960503),('NxI2DkV68MKkxQgScS4a6s9Z4VLCxm90OuUtjirh','019ba280-7511-72f5-bc00-eb95aacd7750','127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','YTo2OntzOjY6Il90b2tlbiI7czo0MDoiZDdadXphQVFrakJwQ1dFUlJUZUhCNkRWbFF6ZG44SldUNldHa24zeCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hZG1pbi91c2VycyI7czo1OiJyb3V0ZSI7czozNjoiZmlsYW1lbnQuYWRtaW4ucmVzb3VyY2VzLnVzZXJzLmluZGV4Ijt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo1MjoibG9naW5fYWRtaW5fNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7czozNjoiMDE5YmEyODAtNzUxMS03MmY1LWJjMDAtZWI5NWFhY2Q3NzUwIjtzOjE5OiJwYXNzd29yZF9oYXNoX2FkbWluIjtzOjYwOiIkMnkkMTIkNldTZ2llZ3J0NTdpU3dkM1NCRFNjdXNVSTR2Y1hxTExJampHUDg4MnV6S1hiVW4vcEVxNC4iO3M6NjoidGFibGVzIjthOjI6e3M6NDA6Ijg2NzQ1ZDU1NDYzN2ZmYzdjYmYzYWExMjc0NDQ1MDQ2X2NvbHVtbnMiO2E6MTA6e2k6MDthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czo5OiJ1c2VyLm5hbWUiO3M6NToibGFiZWwiO3M6NDoiTmFtZSI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjE7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6Imlzc3VlX3R5cGUiO3M6NToibGFiZWwiO3M6MTA6Iklzc3VlIFR5cGUiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aToyO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjEzOiJsb2NhdGlvbl9jaXR5IjtzOjU6ImxhYmVsIjtzOjQ6IkNpdHkiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjoxO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjowO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7Tjt9aTozO2E6Nzp7czo0OiJ0eXBlIjtzOjY6ImNvbHVtbiI7czo0OiJuYW1lIjtzOjE0OiJsb2NhdGlvbl9zdGF0ZSI7czo1OiJsYWJlbCI7czo1OiJTdGF0ZSI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjQ7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTY6ImxvY2F0aW9uX2NvdW50cnkiO3M6NToibGFiZWwiO3M6NzoiQ291bnRyeSI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjU7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6Njoic3RhdHVzIjtzOjU6ImxhYmVsIjtzOjY6IlN0YXR1cyI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjY7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTE6InJlc29sdmVkX2F0IjtzOjU6ImxhYmVsIjtzOjExOiJSZXNvbHZlZCBBdCI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjc7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6ImNyZWF0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IkNyZWF0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO31pOjg7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6InVwZGF0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IlVwZGF0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO31pOjk7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6ImRlbGV0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IkRlbGV0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO319czo0MDoiN2Q5ZjUwYzNlZjc3Njk3MGIwYmIxNWIyNGIyNTY2YTVfY29sdW1ucyI7YTo3OntpOjA7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6NDoibmFtZSI7czo1OiJsYWJlbCI7czo0OiJOYW1lIjtzOjg6ImlzSGlkZGVuIjtiOjA7czo5OiJpc1RvZ2dsZWQiO2I6MTtzOjEyOiJpc1RvZ2dsZWFibGUiO2I6MDtzOjI0OiJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiO047fWk6MTthOjc6e3M6NDoidHlwZSI7czo2OiJjb2x1bW4iO3M6NDoibmFtZSI7czo1OiJlbWFpbCI7czo1OiJsYWJlbCI7czoxMzoiRW1haWwgYWRkcmVzcyI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjI7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTQ6ImFjY291bnRfc3RhdHVzIjtzOjU6ImxhYmVsIjtzOjE0OiJBY2NvdW50IHN0YXR1cyI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjM7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MjM6InN1YnNjcmlwdGlvbnMucGxhbl90aWVyIjtzOjU6ImxhYmVsIjtzOjk6IlBsYW4gdGllciI7czo4OiJpc0hpZGRlbiI7YjowO3M6OToiaXNUb2dnbGVkIjtiOjE7czoxMjoiaXNUb2dnbGVhYmxlIjtiOjA7czoyNDoiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjtOO31pOjQ7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6ImNyZWF0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IkNyZWF0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO31pOjU7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6InVwZGF0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IlVwZGF0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO31pOjY7YTo3OntzOjQ6InR5cGUiO3M6NjoiY29sdW1uIjtzOjQ6Im5hbWUiO3M6MTA6ImRlbGV0ZWRfYXQiO3M6NToibGFiZWwiO3M6MTA6IkRlbGV0ZWQgYXQiO3M6ODoiaXNIaWRkZW4iO2I6MDtzOjk6ImlzVG9nZ2xlZCI7YjowO3M6MTI6ImlzVG9nZ2xlYWJsZSI7YjoxO3M6MjQ6ImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI7YjoxO319fX0=',1768037730),('qTo0s2aGxwbuEZlMwNEZhY5hTjdkSYTq2Dsg5TbP',NULL,'127.0.0.1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibzJLRkhjVUtNemI5UFB0YUtQR1I2bnZPQzNCaGlXVEFCdDVTOXlzeSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1768014669);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plan_tier` enum('free','pro','pro_plus') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'free',
  `status` enum('active','cancelled','expired','past_due') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `monthly_price` decimal(8,2) NOT NULL DEFAULT '0.00',
  `current_period_start` datetime DEFAULT NULL,
  `current_period_end` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `stripe_subscription_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stripe_customer_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_stripe_subscription_id_unique` (`stripe_subscription_id`),
  KEY `subscriptions_user_id_status_index` (`user_id`,`status`),
  KEY `subscriptions_stripe_subscription_id_index` (`stripe_subscription_id`),
  KEY `subscriptions_current_period_end_index` (`current_period_end`),
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscriptions`
--

LOCK TABLES `subscriptions` WRITE;
/*!40000 ALTER TABLE `subscriptions` DISABLE KEYS */;
INSERT INTO `subscriptions` VALUES ('019ba21b-b7cc-7124-befc-3db8f5c61692','019ba21b-b7c3-7129-9854-284c95d6e40c','free','cancelled',0.00,'2026-01-09 09:34:44','2125-01-09 09:34:44','2026-01-09 09:35:41',NULL,NULL,'2026-01-09 03:34:44','2026-01-09 03:35:41'),('019ba21c-9600-7203-9822-989b92a9de0b','019ba21b-b7c3-7129-9854-284c95d6e40c','pro','active',9.00,'2026-01-09 09:35:40','2026-02-09 09:35:40',NULL,'pi_3Snc6YL0pUftKQ9X0qpTCr2Z',NULL,'2026-01-09 03:35:41','2026-01-09 03:35:41'),('019ba60f-9456-7078-9940-ab64d3b6d8be','019ba60f-944b-73e5-871c-30bf0db5291e','free','cancelled',0.00,'2026-01-10 03:59:57','2125-01-10 03:59:57','2026-01-10 05:42:18',NULL,NULL,'2026-01-09 21:59:57','2026-01-09 23:42:18'),('019ba66d-47d9-7054-9788-524e6de23439','019ba60f-944b-73e5-871c-30bf0db5291e','pro','cancelled',9.00,'2026-01-10 05:42:19','2026-02-10 05:42:19','2026-01-10 06:07:57','pi_3SnuwHL0pUftKQ9X1w5GZTW1',NULL,'2026-01-09 23:42:18','2026-01-10 00:07:57'),('019ba684-c390-7294-8d64-2032a86ff81a','019ba60f-944b-73e5-871c-30bf0db5291e','pro_plus','cancelled',29.00,'2026-01-10 06:07:56','2026-02-10 06:07:56','2026-01-10 06:08:28','pi_3SnvL5L0pUftKQ9X1IUD9MCJ',NULL,'2026-01-10 00:07:57','2026-01-10 00:08:28'),('019ba685-3e2f-7045-87dc-814a53f5ae27','019ba60f-944b-73e5-871c-30bf0db5291e','free','cancelled',0.00,'2026-01-10 06:37:37',NULL,'2026-01-10 07:15:34',NULL,NULL,'2026-01-10 00:08:28','2026-01-10 01:15:34'),('019ba6c2-ac7f-7207-87a8-4da0b11c53ce','019ba60f-944b-73e5-871c-30bf0db5291e','free','active',0.00,'2026-01-10 07:15:55',NULL,'2026-01-10 07:15:55',NULL,NULL,'2026-01-10 01:15:34','2026-01-10 01:15:55');
/*!40000 ALTER TABLE `subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `system_settings_setting_key_unique` (`setting_key`),
  KEY `system_settings_setting_key_index` (`setting_key`),
  KEY `system_settings_setting_group_index` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usage_trackings`
--

DROP TABLE IF EXISTS `usage_trackings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usage_trackings` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subscription_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_cycle_date` date NOT NULL,
  `messages_used` int NOT NULL DEFAULT '0',
  `documents_generated` int NOT NULL DEFAULT '0',
  `cases_created` int NOT NULL DEFAULT '0',
  `ai_cost_accumulated` decimal(10,4) NOT NULL DEFAULT '0.0000',
  `input_tokens_used` int NOT NULL DEFAULT '0',
  `output_tokens_used` int NOT NULL DEFAULT '0',
  `cost_threshold_reached` tinyint(1) NOT NULL DEFAULT '0',
  `threshold_reached_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usage_trackings_user_id_billing_cycle_date_unique` (`user_id`,`billing_cycle_date`),
  KEY `usage_trackings_subscription_id_foreign` (`subscription_id`),
  KEY `usage_trackings_billing_cycle_date_index` (`billing_cycle_date`),
  KEY `usage_trackings_user_id_cost_threshold_reached_index` (`user_id`,`cost_threshold_reached`),
  CONSTRAINT `usage_trackings_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `usage_trackings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usage_trackings`
--

LOCK TABLES `usage_trackings` WRITE;
/*!40000 ALTER TABLE `usage_trackings` DISABLE KEYS */;
INSERT INTO `usage_trackings` VALUES ('019ba21b-b7d3-72b9-bae3-7e6acbb5fe7b','019ba21b-b7c3-7129-9854-284c95d6e40c','019ba21c-9600-7203-9822-989b92a9de0b','2026-01-09',1,2,2,0.0000,614,150,0,NULL,'2026-01-09 03:34:44','2026-01-09 05:51:48'),('019ba60f-945e-70df-b534-b33f8d50112a','019ba60f-944b-73e5-871c-30bf0db5291e','019ba6c2-ac7f-7207-87a8-4da0b11c53ce','2026-01-10',0,0,0,0.0000,0,0,0,NULL,'2026-01-09 21:59:57','2026-01-10 01:15:34');
/*!40000 ALTER TABLE `usage_trackings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_status` enum('active','suspended','deleted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_email_index` (`email`),
  KEY `users_account_status_index` (`account_status`),
  KEY `users_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('019ba21b-b7c3-7129-9854-284c95d6e40c','Free User','free@gmail.com','$2y$12$7avKfGBllrVkIQkDFqXSKO5A5.c6U8/pNop51AIw0DEGiAd0rAwXO','active',NULL,NULL,'2026-01-09 03:34:44','2026-01-09 03:34:44',NULL),('019ba60f-944b-73e5-871c-30bf0db5291e','Test User','testuser@gmail.com','$2y$12$strRXBzN2bJ0PyNiIG.m1O2mmkHlTBJqp25z8L.IlrzYswPOb/Vaq','active',NULL,NULL,'2026-01-09 21:59:57','2026-01-09 21:59:57',NULL);
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

-- Dump completed on 2026-01-10 16:39:49
