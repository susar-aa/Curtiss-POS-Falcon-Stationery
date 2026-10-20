-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: falcon_pos
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_branch` (`branch_id`),
  KEY `idx_audit_module` (`module`),
  KEY `idx_audit_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=1677 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
INSERT INTO `audit_logs` VALUES (1,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0','2026-10-12 09:22:07'),(2,1,2,'BRANCH_SWITCH','auth','all','{\"from\":\"Colombo Branch\"}','{\"to\":\"All Branches\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0','2026-10-12 09:22:10'),(3,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0','2026-10-12 09:22:12'),(4,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0','2026-10-12 09:22:16'),(5,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36 Edg/154.0.0.0','2026-10-12 09:22:18'),(6,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 10:49:54'),(7,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 10:50:03'),(21,1,1,'INVENTORY_LOT_CREATED','inventory','1',NULL,'{\"branch_id\":1,\"product_id\":1,\"lot_number\":\"LOT-20261012-5442\",\"quantity\":10,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 11:08:51'),(22,1,1,'JOURNAL_POSTED','accounting','1',NULL,'{\"entry_number\":\"JE-20261012-0001\",\"source_type\":\"opening_balance\",\"source_id\":1,\"total_amount\":1500}','CLI','CLI','2026-10-12 11:08:51'),(39,1,1,'INVENTORY_LOT_CREATED','inventory','2',NULL,'{\"branch_id\":1,\"product_id\":2,\"lot_number\":\"LOT-20261012-5018\",\"quantity\":10,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 11:09:09'),(40,1,1,'JOURNAL_POSTED','accounting','2',NULL,'{\"entry_number\":\"JE-20261012-0001\",\"source_type\":\"opening_balance\",\"source_id\":2,\"total_amount\":1500}','CLI','CLI','2026-10-12 11:09:09'),(61,1,1,'INVENTORY_LOT_CREATED','inventory','3',NULL,'{\"branch_id\":1,\"product_id\":3,\"lot_number\":\"LOT-20261012-3244\",\"quantity\":10,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 11:09:37'),(62,1,1,'JOURNAL_POSTED','accounting','3',NULL,'{\"entry_number\":\"JE-20261012-0001\",\"source_type\":\"opening_balance\",\"source_id\":3,\"total_amount\":1500}','CLI','CLI','2026-10-12 11:09:37'),(73,NULL,NULL,'SUPPLIER_CREATED','suppliers','5',NULL,'{\"supplier_code\":\"SUP-TEST-01\",\"name\":\"Test Vendor Alpha\",\"phone\":\"0112000001\",\"whatsapp\":\"0771000001\",\"address\":\"123 Main St, Kandy\",\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','CLI','CLI','2026-10-12 11:09:46'),(74,NULL,NULL,'SUPPLIER_CREATED','suppliers','6',NULL,'{\"supplier_code\":\"SUP-TEST-02\",\"name\":\"Test Vendor Beta\",\"phone\":\"0112000002\",\"whatsapp\":null,\"address\":\"456 Park Rd, Colombo\",\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','CLI','CLI','2026-10-12 11:09:46'),(75,NULL,NULL,'SUPPLIER_DELETED','suppliers','6','{\"id\":6,\"supplier_code\":\"SUP-TEST-02\",\"name\":\"Test Vendor Beta\",\"phone\":\"0112000002\",\"whatsapp\":null,\"address\":\"456 Park Rd, Colombo\",\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\",\"created_at\":\"2026-10-12 11:09:46\",\"updated_at\":\"2026-10-12 11:09:46\",\"deleted_at\":null}','{\"deleted_at\":\"2026-10-12 07:39:46\"}','CLI','CLI','2026-10-12 11:09:46'),(76,NULL,NULL,'SUPPLIER_UPDATED','suppliers','5','{\"id\":5,\"supplier_code\":\"SUP-TEST-01\",\"name\":\"Test Vendor Alpha\",\"phone\":\"0112000001\",\"whatsapp\":\"0771000001\",\"address\":\"123 Main St, Kandy\",\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\",\"created_at\":\"2026-10-12 11:09:46\",\"updated_at\":\"2026-10-12 11:09:46\",\"deleted_at\":null}','{\"supplier_code\":\"SUP-TEST-01\",\"name\":\"Test Vendor Alpha Updated\",\"phone\":\"0112999999\",\"whatsapp\":\"0771000001\",\"address\":\"Updated Address\",\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','CLI','CLI','2026-10-12 11:09:46'),(78,NULL,NULL,'SUPPLIER_CSV_IMPORT','suppliers','0',NULL,'{\"mode\":\"create_and_update\",\"ignore_blank_cells\":true,\"created\":1,\"updated\":1,\"skipped\":0,\"failed\":0}','CLI','CLI','2026-10-12 11:09:46'),(83,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','4',NULL,'{\"branch_id\":1,\"product_id\":4,\"lot_number\":\"LOT-20261012-8363\",\"quantity\":25,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 11:10:38'),(84,NULL,NULL,'JOURNAL_POSTED','accounting','4',NULL,'{\"entry_number\":\"JE-20261012-0001\",\"source_type\":\"opening_balance\",\"source_id\":4,\"total_amount\":3750}','CLI','CLI','2026-10-12 11:10:38'),(87,NULL,NULL,'PRODUCT_DELETED','products','4','{\"id\":4,\"barcode\":\"BC148654\",\"sku\":\"000504\",\"brand_id\":6,\"category_id\":6,\"name\":\"Section 1 Test Item\",\"supplier_id\":4,\"cost\":\"150.00\",\"selling_price\":\"250.00\",\"opening_stock\":\"25.00\",\"low_stock_threshold\":\"5.00\",\"has_expiry\":0,\"status\":\"active\",\"created_at\":\"2026-10-12 11:10:38\",\"updated_at\":\"2026-10-12 11:10:38\",\"deleted_at\":null,\"brand_name\":\"Sec1 Test Brand\",\"category_name\":\"Sec1 Test Category\",\"supplier_name\":\"Sec1 Test Supplier\",\"branch_stock\":\"25.00\"}','{\"deleted_at\":\"2026-10-12 07:40:38\"}','CLI','CLI','2026-10-12 11:10:38'),(88,NULL,NULL,'BULK_PRODUCT_DEACTIVATED','products',NULL,NULL,'{\"count\":1,\"ids\":[4]}','CLI','CLI','2026-10-12 11:10:38'),(89,NULL,NULL,'BULK_PRODUCT_ACTIVATED','products',NULL,NULL,'{\"count\":1,\"ids\":[4]}','CLI','CLI','2026-10-12 11:10:38'),(90,NULL,NULL,'BULK_PRODUCT_LOW_STOCK_UPDATED','products',NULL,NULL,'{\"count\":1,\"threshold\":15}','CLI','CLI','2026-10-12 11:10:38'),(97,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','7',NULL,'{\"branch_id\":1,\"product_id\":7,\"lot_number\":\"LOT-20261012-7722\",\"quantity\":25,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 11:10:57'),(98,NULL,NULL,'JOURNAL_POSTED','accounting','7',NULL,'{\"entry_number\":\"JE-20261012-0005\",\"source_type\":\"opening_balance\",\"source_id\":7,\"total_amount\":3750}','CLI','CLI','2026-10-12 11:10:57'),(101,NULL,NULL,'PRODUCT_DELETED','products','7','{\"id\":7,\"barcode\":\"BC506951\",\"sku\":\"000177\",\"brand_id\":6,\"category_id\":6,\"name\":\"Section 1 Test Item\",\"supplier_id\":4,\"cost\":\"150.00\",\"selling_price\":\"250.00\",\"opening_stock\":\"25.00\",\"low_stock_threshold\":\"5.00\",\"has_expiry\":0,\"status\":\"active\",\"created_at\":\"2026-10-12 11:10:57\",\"updated_at\":\"2026-10-12 11:10:57\",\"deleted_at\":null,\"brand_name\":\"Sec1 Test Brand\",\"category_name\":\"Sec1 Test Category\",\"supplier_name\":\"Sec1 Test Supplier\",\"branch_stock\":\"25.00\"}','{\"deleted_at\":\"2026-10-12 07:40:57\"}','CLI','CLI','2026-10-12 11:10:57'),(102,NULL,NULL,'BULK_PRODUCT_DEACTIVATED','products',NULL,NULL,'{\"count\":1,\"ids\":[7]}','CLI','CLI','2026-10-12 11:10:57'),(103,NULL,NULL,'BULK_PRODUCT_ACTIVATED','products',NULL,NULL,'{\"count\":1,\"ids\":[7]}','CLI','CLI','2026-10-12 11:10:57'),(104,NULL,NULL,'BULK_PRODUCT_LOW_STOCK_UPDATED','products',NULL,NULL,'{\"count\":1,\"threshold\":15}','CLI','CLI','2026-10-12 11:10:57'),(105,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','8',NULL,'{\"branch_id\":1,\"product_id\":8,\"lot_number\":\"LOT-20261012-8774\",\"quantity\":999,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 11:10:57'),(106,NULL,NULL,'JOURNAL_POSTED','accounting','8',NULL,'{\"entry_number\":\"JE-20261012-0008\",\"source_type\":\"opening_balance\",\"source_id\":8,\"total_amount\":159840}','CLI','CLI','2026-10-12 11:10:57'),(111,NULL,NULL,'PRODUCT_IMPORT_COMMITTED','products',NULL,NULL,'{\"batch_no\":\"IMP-2026OCTMON-074057-332\",\"mode\":\"create_and_update\",\"created\":2,\"updated\":0,\"skipped\":0,\"failed\":0}','CLI','CLI','2026-10-12 11:10:57'),(112,1,1,'BRAND_CREATED','brands','7',NULL,'{\"name\":\"Asus\",\"status\":\"active\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:46:10'),(113,1,1,'CATEGORY_CREATED','categories','7',NULL,'{\"name\":\"Gaming Laptop\",\"status\":\"active\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:46:17'),(114,1,1,'SUPPLIER_CREATED','suppliers','9',NULL,'{\"supplier_code\":\"SUP-0009\",\"name\":\"Chani Importers - Asus Agent\",\"phone\":\"\",\"whatsapp\":null,\"address\":null,\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:46:30'),(128,1,1,'INVENTORY_LOT_CREATED','inventory','10',NULL,'{\"branch_id\":1,\"product_id\":10,\"lot_number\":\"LOT-20261012-9386\",\"quantity\":10,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 11:49:58'),(129,1,1,'JOURNAL_POSTED','accounting','10',NULL,'{\"entry_number\":\"JE-20261012-0010\",\"source_type\":\"opening_balance\",\"source_id\":10,\"total_amount\":1500}','CLI','CLI','2026-10-12 11:49:58'),(150,1,1,'INVENTORY_LOT_CREATED','inventory','11',NULL,'{\"branch_id\":1,\"product_id\":11,\"lot_number\":\"LOT-20261012-2673\",\"quantity\":10,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 11:55:44'),(151,1,1,'JOURNAL_POSTED','accounting','11',NULL,'{\"entry_number\":\"JE-20261012-0010\",\"source_type\":\"opening_balance\",\"source_id\":11,\"total_amount\":1500}','CLI','CLI','2026-10-12 11:55:44'),(159,1,1,'INVENTORY_LOT_CREATED','inventory','12',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-20261012-5363\",\"quantity\":15,\"movement_type\":\"opening_stock\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:56:50'),(160,1,1,'JOURNAL_POSTED','accounting','12',NULL,'{\"entry_number\":\"JE-20261012-0010\",\"source_type\":\"opening_balance\",\"source_id\":12,\"total_amount\":15000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:56:50'),(161,1,1,'PRODUCT_CREATED','products','12',NULL,'{\"sku\":\"10100\",\"barcode\":\"1010\",\"name\":\"Asus Gaming Laptop Tuff\",\"cost\":1000,\"selling_price\":2000,\"opening_stock\":15,\"has_expiry\":0,\"branch_id\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:56:50'),(162,1,1,'INVENTORY_LOT_CREATED','inventory','13',NULL,'{\"branch_id\":1,\"product_id\":13,\"lot_number\":\"LOT-20261012-1721\",\"quantity\":10,\"movement_type\":\"opening_stock\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:58:19'),(163,1,1,'JOURNAL_POSTED','accounting','13',NULL,'{\"entry_number\":\"JE-20261012-0013\",\"source_type\":\"opening_balance\",\"source_id\":13,\"total_amount\":15000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:58:19'),(164,1,1,'PRODUCT_CREATED','products','13',NULL,'{\"sku\":\"10200\",\"barcode\":\"1020\",\"name\":\"Asus Gaming Laptop ROG\",\"cost\":1500,\"selling_price\":2000,\"opening_stock\":10,\"has_expiry\":0,\"branch_id\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:58:19'),(165,1,1,'BRAND_CREATED','brands','12',NULL,'{\"name\":\"Baby Cheramy\",\"status\":\"active\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:58:37'),(166,1,1,'CATEGORY_CREATED','categories','12',NULL,'{\"name\":\"Cream\",\"status\":\"active\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:58:40'),(167,1,1,'SUPPLIER_CREATED','suppliers','12',NULL,'{\"supplier_code\":\"SUP-0010\",\"name\":\"Ceylon Cream\",\"phone\":\"\",\"whatsapp\":null,\"address\":null,\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 11:58:50'),(181,1,1,'INVENTORY_LOT_CREATED','inventory','14',NULL,'{\"branch_id\":1,\"product_id\":14,\"lot_number\":\"LOT-20261012-5946\",\"quantity\":10,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 12:03:47'),(182,1,1,'JOURNAL_POSTED','accounting','14',NULL,'{\"entry_number\":\"JE-20261012-0014\",\"source_type\":\"opening_balance\",\"source_id\":14,\"total_amount\":1500}','CLI','CLI','2026-10-12 12:03:47'),(190,1,1,'INVENTORY_LOT_CREATED','inventory','15',NULL,'{\"branch_id\":1,\"product_id\":15,\"lot_number\":\"LOT-20261012-3953\",\"quantity\":20,\"movement_type\":\"opening_stock\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:05:35'),(191,1,1,'JOURNAL_POSTED','accounting','15',NULL,'{\"entry_number\":\"JE-20261012-0014\",\"source_type\":\"opening_balance\",\"source_id\":15,\"total_amount\":2000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:05:35'),(192,1,1,'PRODUCT_CREATED','products','15',NULL,'{\"sku\":\"10300\",\"barcode\":\"1030\",\"name\":\"Baby Cheramy Cream 10ml Bottle\",\"cost\":100,\"selling_price\":150,\"opening_stock\":20,\"has_expiry\":0,\"branch_id\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:05:35'),(193,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:05:53'),(194,1,2,'BRANCH_SWITCH','auth','all','{\"from\":\"Colombo Branch\"}','{\"to\":\"All Branches\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:05:56'),(195,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:05:58'),(212,1,1,'INVENTORY_LOT_CREATED','inventory','17',NULL,'{\"branch_id\":1,\"product_id\":17,\"lot_number\":\"LOT-20261012-1865\",\"quantity\":10,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 12:47:50'),(221,1,1,'PRODUCT_DELETED','products','15','{\"id\":15,\"barcode\":\"1030\",\"sku\":\"10300\",\"brand_id\":12,\"category_id\":12,\"name\":\"Baby Cheramy Cream 10ml Bottle\",\"supplier_id\":12,\"cost\":\"100.00\",\"selling_price\":\"150.00\",\"opening_stock\":\"20.00\",\"low_stock_threshold\":\"10.00\",\"has_expiry\":0,\"status\":\"active\",\"created_at\":\"2026-10-12 12:05:35\",\"updated_at\":\"2026-10-12 12:05:35\",\"deleted_at\":null,\"brand_name\":\"Baby Cheramy\",\"category_name\":\"Cream\",\"supplier_name\":\"Ceylon Cream\",\"branch_stock\":\"20.00\"}','{\"deleted_at\":\"2026-10-12 09:18:17\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:48:17'),(222,1,1,'INVENTORY_LOT_CREATED','inventory','18',NULL,'{\"branch_id\":1,\"product_id\":18,\"lot_number\":\"LOT-20261012-1052\",\"quantity\":20,\"movement_type\":\"opening_stock\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:49:04'),(223,1,1,'JOURNAL_POSTED','accounting','18',NULL,'{\"entry_number\":\"JE-20261012-0016\",\"source_type\":\"opening_balance\",\"source_id\":18,\"total_amount\":2000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:49:04'),(224,1,1,'PRODUCT_CREATED','products','18',NULL,'{\"sku\":\"10300\",\"barcode\":\"1030\",\"name\":\"Baby Cheramy Cream 10 Ml\",\"cost\":100,\"selling_price\":200,\"opening_stock\":20,\"has_expiry\":1,\"branch_id\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:49:04'),(225,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:51:23'),(226,1,2,'BRANCH_SWITCH','auth','all','{\"from\":\"Colombo Branch\"}','{\"to\":\"All Branches\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:51:26'),(227,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 12:51:28'),(228,1,1,'INVENTORY_LOT_CREATED','inventory','19',NULL,'{\"branch_id\":1,\"product_id\":19,\"lot_number\":\"LOT-20261012-3520\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 13:00:48'),(230,1,1,'INVENTORY_LOT_CREATED','inventory','20',NULL,'{\"branch_id\":1,\"product_id\":20,\"lot_number\":\"LOT-20261012-1113\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 13:00:48'),(231,1,1,'PRODUCT_CREATED','products','20',NULL,'{\"sku\":\"TEST-SKU-B-1791790248_976\",\"barcode\":\"BAR-B-1791790248_976\",\"name\":\"GRN Test Expiry Product 1791790248_976\",\"cost\":200,\"selling_price\":300,\"opening_stock\":0,\"has_expiry\":1,\"branch_id\":1}','CLI','CLI','2026-10-12 13:00:48'),(234,1,1,'INVENTORY_LOT_CREATED','inventory','21',NULL,'{\"branch_id\":1,\"product_id\":19,\"lot_number\":\"LOT-GRN1-19-623\",\"quantity\":10,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-12 13:00:48'),(235,1,1,'INVENTORY_LOT_CREATED','inventory','22',NULL,'{\"branch_id\":1,\"product_id\":20,\"lot_number\":\"LOT-GRN1-20-551\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-12 13:00:48'),(240,1,1,'INVENTORY_LOT_CREATED','inventory','23',NULL,'{\"branch_id\":1,\"product_id\":19,\"lot_number\":\"LOT-GRN2-19-315\",\"quantity\":15,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-12 13:00:48'),(245,1,1,'INVENTORY_LOT_CREATED','inventory','24',NULL,'{\"branch_id\":1,\"product_id\":21,\"lot_number\":\"LOT-20261012-2229\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 13:03:19'),(247,1,1,'INVENTORY_LOT_CREATED','inventory','25',NULL,'{\"branch_id\":1,\"product_id\":22,\"lot_number\":\"LOT-20261012-3327\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-12 13:03:19'),(251,1,1,'INVENTORY_LOT_CREATED','inventory','26',NULL,'{\"branch_id\":1,\"product_id\":21,\"lot_number\":\"LOT-GRN4-21-875\",\"quantity\":10,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-12 13:03:19'),(252,1,1,'INVENTORY_LOT_CREATED','inventory','27',NULL,'{\"branch_id\":1,\"product_id\":22,\"lot_number\":\"LOT-GRN4-22-734\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-12 13:03:19'),(258,1,1,'INVENTORY_LOT_CREATED','inventory','28',NULL,'{\"branch_id\":1,\"product_id\":21,\"lot_number\":\"LOT-GRN5-21-756\",\"quantity\":15,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-12 13:03:20'),(259,1,1,'JOURNAL_POSTED','accounting','22',NULL,'{\"entry_number\":\"JE-20261012-0022\",\"source_type\":\"grn\",\"source_id\":5,\"total_amount\":2100}','CLI','CLI','2026-10-12 13:03:20'),(263,NULL,NULL,'JOURNAL_POSTED','accounting','23',NULL,'{\"entry_number\":\"JE-20261012-0019\",\"source_type\":\"reversal\",\"source_id\":15,\"total_amount\":2000}','CLI','CLI','2026-10-12 13:23:55'),(264,NULL,NULL,'JOURNAL_REVERSED','accounting','15','{\"entry_number\":\"JE-20261012-0014\"}','{\"reversal_journal_id\":23,\"reason\":\"Product #15 moved to Trash Bin\"}','CLI','CLI','2026-10-12 13:23:55'),(273,1,1,'GRN_CREATED','purchasing','7',NULL,'{\"grn_number\":\"GRN-202610-0001\",\"supplier_invoice_no\":\"2029\",\"actual_grn_total\":10000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:26:36'),(274,1,1,'GRN_CREATED','purchasing','8',NULL,'{\"grn_number\":\"GRN-202610-0008\",\"supplier_invoice_no\":\"2029\",\"actual_grn_total\":15000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:28:13'),(275,1,1,'GRN_CREATED','purchasing','9',NULL,'{\"grn_number\":\"GRN-202610-0009\",\"supplier_invoice_no\":\"2026\",\"actual_grn_total\":15000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:28:35'),(276,1,1,'GRN_UPDATED','purchasing','7','{\"id\":7,\"grn_number\":\"GRN-202610-0001\",\"po_id\":null,\"supplier_id\":12,\"branch_id\":1,\"supplier_invoice_no\":\"2029\",\"supplier_invoice_date\":\"2026-10-12\",\"received_date\":\"2026-10-12\",\"actual_grn_total\":\"10000.00\",\"paid_amount\":\"0.00\",\"payment_status\":\"unpaid\",\"supplier_invoice_amount\":\"15000.00\",\"difference_amount\":\"5000.00\",\"difference_reason\":\"Price Variance\",\"notes\":\"\",\"status\":\"Draft\",\"created_by\":1,\"approved_by\":null,\"approved_at\":null,\"created_at\":\"2026-10-12 14:26:36\",\"updated_at\":\"2026-10-12 14:26:36\",\"supplier_name\":\"Ceylon Cream\",\"supplier_code\":\"SUP-0010\",\"supplier_status\":\"active\",\"supplier_phone\":null,\"supplier_whatsapp\":null,\"supplier_address\":null,\"branch_name\":\"Main Branch - Kandy\",\"branch_code\":\"BR-KDY-01\",\"branch_address\":\"No 123, Peradeniya Road, Kandy\",\"branch_phone\":\"+94 81 222 3344\",\"branch_email\":\"kandy@curtisspos.com\",\"creator_name\":\"Super Administrator\",\"approver_name\":null,\"po_number\":null,\"items\":[{\"id\":13,\"grn_id\":7,\"product_id\":13,\"quantity\":\"10.00\",\"unit_cost\":\"1000.00\",\"selling_price\":\"2000.00\",\"expiry_date\":null,\"batch_number\":null,\"line_total\":\"10000.00\",\"lot_id\":null,\"product_name\":\"Asus Gaming Laptop ROG\",\"sku\":\"10200\",\"barcode\":\"1020\",\"has_expiry\":0}]}','{\"actual_grn_total\":10000,\"supplier_invoice_no\":\"2029\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:28:58'),(277,1,1,'INVENTORY_LOT_CREATED','inventory','13',NULL,'{\"branch_id\":1,\"product_id\":13,\"lot_number\":\"LOT-GRN9-13-360\",\"quantity\":10,\"movement_type\":\"purchase_grn\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:29:38'),(278,1,1,'JOURNAL_POSTED','accounting','25',NULL,'{\"entry_number\":\"JE-20261012-0024\",\"source_type\":\"grn\",\"source_id\":9,\"total_amount\":15000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:29:38'),(279,1,1,'GRN_APPROVED','purchasing','9','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:29:38'),(280,1,1,'INVENTORY_LOT_CREATED','inventory','31',NULL,'{\"branch_id\":1,\"product_id\":13,\"lot_number\":\"LOT-GRN8-13-482\",\"quantity\":10,\"movement_type\":\"purchase_grn\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:32:28'),(281,1,1,'JOURNAL_POSTED','accounting','26',NULL,'{\"entry_number\":\"JE-20261012-0026\",\"source_type\":\"grn\",\"source_id\":8,\"total_amount\":15000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:32:28'),(282,1,1,'GRN_APPROVED','purchasing','8','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:32:28'),(283,1,1,'INVENTORY_LOT_CREATED','inventory','32',NULL,'{\"branch_id\":1,\"product_id\":13,\"lot_number\":\"LOT-GRN7-13-886\",\"quantity\":10,\"movement_type\":\"purchase_grn\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:32:29'),(284,1,1,'JOURNAL_POSTED','accounting','27',NULL,'{\"entry_number\":\"JE-20261012-0027\",\"source_type\":\"grn\",\"source_id\":7,\"total_amount\":10000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:32:29'),(285,1,1,'GRN_APPROVED','purchasing','7','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"10000.00\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 14:32:29'),(288,NULL,NULL,'BUSINESS_DAY_STARTED','cash_drawer','2',NULL,'{\"branch_id\":1,\"business_date\":\"2026-10-12\",\"opening_cash\":20000}','CLI','CLI','2026-10-12 14:48:50'),(291,NULL,NULL,'SALES_FINALIZED','pos','3',NULL,'{\"invoice_no\":\"0002\",\"total_amount\":637.5,\"allocated\":637.5,\"change_amount\":0,\"credit_amount\":0}','CLI','CLI','2026-10-12 14:48:50'),(292,1,1,'EMPLOYEE_CREATED','employees','1',NULL,'{\"employee_code\":\"EMP-0001\",\"full_name\":\"Sandaru\",\"job_role\":\"Sales Rep\",\"branch_id\":2,\"basic_salary\":50000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 15:29:47'),(293,1,1,'PAYROLL_PERIOD_CREATED','payroll','1',NULL,'{\"period_code\":\"2026-10\",\"period_name\":\"October 2026\",\"employee_count\":1,\"total_gross\":50000,\"total_net_salary\":46000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 15:31:24'),(297,NULL,NULL,'JOURNAL_POSTED','accounting','29',NULL,'{\"entry_number\":\"JE-20261012-0029\",\"source_type\":\"sale\",\"source_id\":4,\"total_amount\":1250}','CLI','CLI','2026-10-12 16:02:44'),(298,NULL,NULL,'SALES_FINALIZED','pos','4',NULL,'{\"invoice_no\":\"0002\",\"total_amount\":637.5,\"allocated\":637.5,\"change_amount\":0,\"credit_amount\":0}','CLI','CLI','2026-10-12 16:02:44'),(299,1,1,'SESSION_EXPIRED','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 16:46:12'),(300,1,1,'LOGOUT','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 16:46:12'),(301,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 16:46:14'),(302,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 16:46:17'),(303,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 16:46:20'),(304,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 16:46:23'),(762,1,1,'GRN_CREATED','purchasing','10',NULL,'{\"grn_number\":\"GRN-202610-0010\",\"supplier_invoice_no\":\"1456\",\"actual_grn_total\":2000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 17:40:06'),(763,1,1,'INVENTORY_LOT_CREATED','inventory','65',NULL,'{\"branch_id\":1,\"product_id\":18,\"lot_number\":\"LOT-GRN10-18-504\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 17:40:17'),(764,1,1,'JOURNAL_POSTED','accounting','167',NULL,'{\"entry_number\":\"JE-20261012-0028\",\"source_type\":\"grn\",\"source_id\":10,\"total_amount\":2000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 17:40:17'),(765,1,1,'GRN_APPROVED','purchasing','10','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"2000.00\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 17:40:17'),(766,1,1,'SESSION_EXPIRED','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 18:57:06'),(767,1,1,'LOGOUT','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 18:57:06'),(768,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 18:57:08'),(769,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 18:57:10'),(770,1,1,'EMPLOYEE_CREATED','employees','2',NULL,'{\"employee_code\":\"EMP-0002\",\"full_name\":\"Chamath\",\"job_role\":\"Sales Rep\",\"branch_id\":1,\"basic_salary\":50000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:21:34'),(771,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:21:40'),(772,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:21:43'),(773,1,1,'CUSTOMER_CREATED','customers','14',NULL,'{\"customer_code\":\"CUS-00002\",\"name\":\"Buthsara Customer\",\"phone\":\"04711522666\",\"card_barcode\":null,\"credit_limit\":0,\"is_loyalty\":1,\"loyalty_discount_override\":10}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:22:50'),(774,1,1,'CUSTOMER_CREATED','customers','15',NULL,'{\"customer_code\":\"CUS-00015\",\"name\":\"Non Loyality Customer\",\"phone\":\"0761452633\",\"card_barcode\":null,\"credit_limit\":0,\"is_loyalty\":0,\"loyalty_discount_override\":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:23:16'),(775,1,1,'CUSTOMER_CREATED','customers','16',NULL,'{\"customer_code\":\"CUS-00016\",\"name\":\"1000 Credit Customer\",\"phone\":\"01236522266\",\"card_barcode\":null,\"credit_limit\":1000,\"is_loyalty\":0,\"loyalty_discount_override\":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:23:39'),(776,NULL,NULL,'JOURNAL_POSTED','accounting','168',NULL,'{\"entry_number\":\"JE-20261012-0168\",\"source_type\":\"sale\",\"source_id\":9,\"total_amount\":7000}','CLI','CLI','2026-10-12 19:27:32'),(777,NULL,NULL,'SALES_FINALIZED','pos','9',NULL,'{\"invoice_no\":\"0009\",\"total_amount\":4000,\"allocated\":4000,\"change_amount\":0,\"credit_amount\":0}','CLI','CLI','2026-10-12 19:27:32'),(778,NULL,NULL,'JOURNAL_POSTED','accounting','169',NULL,'{\"entry_number\":\"JE-20261012-0168\",\"source_type\":\"sale\",\"source_id\":10,\"total_amount\":7000}','CLI','CLI','2026-10-12 19:27:57'),(779,NULL,NULL,'SALES_FINALIZED','pos','10',NULL,'{\"invoice_no\":\"0009\",\"total_amount\":4000,\"allocated\":4000,\"change_amount\":0,\"credit_amount\":0}','CLI','CLI','2026-10-12 19:27:57'),(780,1,1,'KOKO_PAYMENT_INITIATED','koko_transactions','3',NULL,'{\"koko_order_id\":\"KOKO-B1-261012160042-CCA7D0\",\"amount\":1000,\"channel\":\"qr\",\"phone\":\"\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:30:42'),(781,1,1,'KOKO_PAYMENT_INITIATED','koko_transactions','4',NULL,'{\"koko_order_id\":\"KOKO-B1-261012160900-88F04A\",\"amount\":5000,\"channel\":\"qr\",\"phone\":\"01236522266\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:39:00'),(782,1,1,'KOKO_PAYMENT_INITIATED','koko_transactions','5',NULL,'{\"koko_order_id\":\"KOKO-B1-261012160959-87F7D0\",\"amount\":1000,\"channel\":\"qr\",\"phone\":\"01236522266\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:39:59'),(783,1,1,'JOURNAL_POSTED','accounting','170',NULL,'{\"entry_number\":\"JE-20261012-0168\",\"source_type\":\"sale\",\"source_id\":2,\"total_amount\":15500}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:41:09'),(784,1,1,'SALES_FINALIZED','pos','2',NULL,'{\"invoice_no\":\"0002\",\"total_amount\":9000,\"allocated\":9000,\"change_amount\":0,\"credit_amount\":2500}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:41:09'),(785,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:47:27'),(786,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:47:31'),(787,1,1,'BANK_RECONCILIATION_STARTED','banking','3',NULL,'{\"reconciliation_no\":\"BREC-0001\",\"bank_account_id\":1,\"statement_end_date\":\"2026-10-31\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:51:50'),(788,1,1,'BANK_RECONCILIATION_STARTED','banking','4',NULL,'{\"reconciliation_no\":\"BREC-0004\",\"bank_account_id\":2,\"statement_end_date\":\"2026-10-31\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 19:52:20'),(789,NULL,NULL,'CUSTOMER_CREATED','customers','17',NULL,'{\"customer_code\":\"CUS-00017\",\"name\":\"Test Credit Limit User 9761\",\"phone\":null,\"card_barcode\":null,\"credit_limit\":1000,\"is_loyalty\":0,\"loyalty_discount_override\":null}','CLI','CLI','2026-10-12 20:26:52'),(794,1,1,'CUSTOMER_CREATED','customers','22',NULL,'{\"customer_code\":\"CUS-00017\",\"name\":\"Susara Senarathne\",\"phone\":\"0761407875\",\"card_barcode\":null,\"credit_limit\":0,\"is_loyalty\":0,\"loyalty_discount_override\":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 21:43:58'),(795,1,1,'CUSTOMER_CREATED','customers','23',NULL,'{\"customer_code\":\"CUS-00023\",\"name\":\"Himaya Senarathne\",\"phone\":\"0761407878\",\"card_barcode\":null,\"credit_limit\":0,\"is_loyalty\":0,\"loyalty_discount_override\":null}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 21:44:44'),(796,1,1,'JOURNAL_POSTED','accounting','171',NULL,'{\"entry_number\":\"JE-20261012-0171\",\"source_type\":\"sale\",\"source_id\":7,\"total_amount\":125}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 23:02:17'),(797,1,1,'SALES_FINALIZED','pos','7',NULL,'{\"invoice_no\":\"0005\",\"total_amount\":67,\"allocated\":70,\"change_amount\":3,\"credit_amount\":0}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-12 23:02:17'),(798,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 11:08:25'),(799,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 11:08:26'),(800,1,1,'KOKO_PAYMENT_INITIATED','koko_transactions','6',NULL,'{\"koko_order_id\":\"KOKO-B1-261013075955-10B473\",\"amount\":2000,\"channel\":\"qr\",\"phone\":\"0761407878\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 11:29:55'),(801,1,1,'KOKO_PAYMENT_INITIATED','koko_transactions','7',NULL,'{\"koko_order_id\":\"KOKO-B1-261013080258-7F2EDE\",\"amount\":2000,\"channel\":\"qr\",\"phone\":\"0761407878\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 11:32:58'),(802,1,1,'KOKO_PAYMENT_INITIATED','koko_transactions','8',NULL,'{\"koko_order_id\":\"KOKO-B1-261013080259-4E7E39\",\"amount\":2000,\"channel\":\"qr\",\"phone\":\"0761407878\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 11:32:59'),(803,NULL,NULL,'CUSTOMER_CREATED','customers','24',NULL,'{\"customer_code\":\"TEST-LOYALTY-1791876732\",\"name\":\"VIP Loyalty Tester\",\"phone\":\"0779998877\",\"card_barcode\":null,\"credit_limit\":50000,\"is_loyalty\":1,\"loyalty_discount_override\":12.5}','CLI','CLI','2026-10-13 13:02:12'),(804,NULL,NULL,'CUSTOMER_UPDATED','customers','24','{\"id\":24,\"customer_code\":\"TEST-LOYALTY-1791876732\",\"name\":\"VIP Loyalty Tester\",\"phone\":\"0779998877\",\"whatsapp\":null,\"address\":\"123 Loyalty Lane\",\"card_barcode\":null,\"credit_limit\":\"50000.00\",\"credit_balance\":\"0.00\",\"is_walkin\":0,\"is_loyalty\":1,\"loyalty_discount_override\":\"12.50\",\"status\":\"active\",\"created_at\":\"2026-10-13 13:02:12\",\"updated_at\":\"2026-10-13 13:02:12\",\"deleted_at\":null}','{\"customer_code\":\"TEST-LOYALTY-1791876732\",\"name\":\"VIP Loyalty Tester Updated\",\"phone\":\"0779998877\",\"card_barcode\":null,\"credit_limit\":50000,\"status\":\"active\",\"is_loyalty\":1,\"loyalty_discount_override\":15}','CLI','CLI','2026-10-13 13:02:12'),(806,NULL,NULL,'JOURNAL_POSTED','accounting','172',NULL,'{\"entry_number\":\"JE-20261013-0001\",\"source_type\":\"sale\",\"source_id\":12,\"total_amount\":1250}','CLI','CLI','2026-10-13 13:02:12'),(807,NULL,NULL,'SALES_FINALIZED','pos','12',NULL,'{\"invoice_no\":\"0010\",\"total_amount\":637.5,\"allocated\":637.5,\"change_amount\":0,\"credit_amount\":0}','CLI','CLI','2026-10-13 13:02:12'),(808,1,1,'KOKO_PAYMENT_INITIATED','koko_transactions','9',NULL,'{\"koko_order_id\":\"KOKO-B1-261013094811-2A8427\",\"amount\":2000,\"channel\":\"qr\",\"phone\":\"\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 13:18:11'),(809,1,NULL,'EMPLOYEE_CREATED','employees','3',NULL,'{\"employee_code\":\"TEST-ADV-0350\",\"full_name\":\"Test Advance Employee\",\"job_role\":\"Cashier\",\"branch_id\":1,\"basic_salary\":60000}','CLI','CLI','2026-10-13 14:02:30'),(810,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','2',NULL,'{\"period_code\":\"PR-TEST-0350\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-13 14:02:30'),(811,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','3',NULL,'{\"period_code\":\"PR-TEST-0458\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-13 14:04:18'),(812,1,NULL,'JOURNAL_POSTED','accounting','173',NULL,'{\"entry_number\":\"JE-20261013-0173\",\"source_type\":\"payroll\",\"source_id\":3,\"total_amount\":126500}','CLI','CLI','2026-10-13 14:04:18'),(813,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','3',NULL,'{\"period_code\":\"PR-TEST-0458\",\"total_gross\":\"110000.00\",\"total_net\":101200,\"total_epf\":22000,\"total_etf\":3300,\"journal_id\":173}','CLI','CLI','2026-10-13 14:04:18'),(814,1,NULL,'PAYROLL_PERIOD_CANCELLED','payroll','3','{\"id\":3,\"period_code\":\"PR-TEST-0458\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-31\",\"branch_id\":1,\"status\":\"approved\",\"total_basic\":\"110000.00\",\"total_allowances\":\"0.00\",\"total_commissions\":\"0.00\",\"total_gross\":\"110000.00\",\"total_employee_epf\":\"8800.00\",\"total_employer_epf\":\"13200.00\",\"total_employer_etf\":\"3300.00\",\"total_deductions\":\"8800.00\",\"total_net_salary\":\"101200.00\",\"total_paid\":\"0.00\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-13 14:04:18\",\"notes\":\"Payroll Period for Salary Advance Recovery Test\",\"created_at\":\"2026-10-13 14:04:18\",\"updated_at\":\"2026-10-13 14:04:18\",\"branch_name\":\"Main Branch - Kandy\",\"created_by_name\":\"Super Administrator\",\"approved_by_name\":\"Super Administrator\"}','{\"cancel_reason\":\"Testing payroll cancellation & advance un-recovery\",\"cancelled_by\":1}','CLI','CLI','2026-10-13 14:04:18'),(815,1,NULL,'EMPLOYEE_CREATED','employees','4',NULL,'{\"employee_code\":\"TEST-ADV-0485\",\"full_name\":\"Test Advance Employee 321\",\"job_role\":\"Cashier\",\"branch_id\":1,\"basic_salary\":60000}','CLI','CLI','2026-10-13 14:04:45'),(816,1,NULL,'JOURNAL_POSTED','accounting','174',NULL,'{\"entry_number\":\"JE-20261013-0174\",\"source_type\":\"employee_advance\",\"source_id\":1,\"total_amount\":5000}','CLI','CLI','2026-10-13 14:04:45'),(817,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','1',NULL,'{\"advance_no\":\"ADV-202610-5344\",\"employee_id\":4,\"amount\":5000,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":174}','CLI','CLI','2026-10-13 14:04:45'),(818,1,NULL,'JOURNAL_POSTED','accounting','175',NULL,'{\"entry_number\":\"JE-20261013-0175\",\"source_type\":\"employee_advance\",\"source_id\":2,\"total_amount\":3000}','CLI','CLI','2026-10-13 14:04:45'),(819,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','2',NULL,'{\"advance_no\":\"ADV-202610-5475\",\"employee_id\":4,\"amount\":3000,\"payment_method\":\"Cash in Hand\",\"cr_account_id\":1,\"journal_id\":175}','CLI','CLI','2026-10-13 14:04:45'),(820,1,NULL,'JOURNAL_POSTED','accounting','176',NULL,'{\"entry_number\":\"JE-20261013-0176\",\"source_type\":\"employee_advance\",\"source_id\":3,\"total_amount\":4000}','CLI','CLI','2026-10-13 14:04:45'),(821,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','3',NULL,'{\"advance_no\":\"ADV-202610-3097\",\"employee_id\":4,\"amount\":4000,\"payment_method\":\"Bank Transfer\",\"cr_account_id\":59,\"journal_id\":176}','CLI','CLI','2026-10-13 14:04:45'),(822,1,NULL,'JOURNAL_POSTED','accounting','177',NULL,'{\"entry_number\":\"JE-20261013-0177\",\"source_type\":\"employee_advance\",\"source_id\":4,\"total_amount\":1500}','CLI','CLI','2026-10-13 14:04:45'),(823,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','4',NULL,'{\"advance_no\":\"ADV-202610-8693\",\"employee_id\":4,\"amount\":1500,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":177}','CLI','CLI','2026-10-13 14:04:45'),(824,1,NULL,'EMPLOYEE_ADVANCE_CANCELLED','payroll','4','{\"id\":4,\"advance_no\":\"ADV-202610-8693\",\"employee_id\":4,\"branch_id\":1,\"amount\":\"1500.00\",\"recovered_amount\":\"0.00\",\"remaining_amount\":\"1500.00\",\"disbursement_date\":\"2026-10-13\",\"payment_method\":\"Cash Drawer\",\"bank_account_id\":null,\"source_account_id\":49,\"reference_no\":\"REF-TO-CANCEL\",\"recovery_period_id\":null,\"status\":\"active\",\"notes\":\"Accidental disbursement to be cancelled\",\"cancelled_at\":null,\"cancelled_by\":null,\"cancel_reason\":null,\"created_by\":1,\"created_at\":\"2026-10-13 14:04:45\",\"updated_at\":\"2026-10-13 14:04:45\",\"employee_name\":\"Test Advance Employee 321\",\"employee_code\":\"TEST-ADV-0485\"}','{\"advance_no\":\"ADV-202610-8693\",\"cancel_reason\":\"Entered wrong amount by mistake\",\"cancelled_by\":1}','CLI','CLI','2026-10-13 14:04:45'),(825,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','4',NULL,'{\"period_code\":\"PR-TEST-0485\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"employee_count\":3,\"total_gross\":170000,\"total_net_salary\":144400}','CLI','CLI','2026-10-13 14:04:45'),(826,1,NULL,'JOURNAL_POSTED','accounting','178',NULL,'{\"entry_number\":\"JE-20261013-0178\",\"source_type\":\"payroll\",\"source_id\":4,\"total_amount\":195500}','CLI','CLI','2026-10-13 14:04:45'),(827,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','4',NULL,'{\"period_code\":\"PR-TEST-0485\",\"total_gross\":\"170000.00\",\"total_net\":144400,\"total_epf\":34000,\"total_etf\":5100,\"journal_id\":178}','CLI','CLI','2026-10-13 14:04:45'),(828,1,NULL,'PAYROLL_PERIOD_CANCELLED','payroll','4','{\"id\":4,\"period_code\":\"PR-TEST-0485\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-31\",\"branch_id\":1,\"status\":\"approved\",\"total_basic\":\"170000.00\",\"total_allowances\":\"0.00\",\"total_commissions\":\"0.00\",\"total_gross\":\"170000.00\",\"total_employee_epf\":\"13600.00\",\"total_employer_epf\":\"20400.00\",\"total_employer_etf\":\"5100.00\",\"total_deductions\":\"25600.00\",\"total_net_salary\":\"144400.00\",\"total_paid\":\"0.00\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-13 14:04:45\",\"notes\":\"Payroll Period for Salary Advance Recovery Test\",\"created_at\":\"2026-10-13 14:04:45\",\"updated_at\":\"2026-10-13 14:04:45\",\"branch_name\":\"Main Branch - Kandy\",\"created_by_name\":\"Super Administrator\",\"approved_by_name\":\"Super Administrator\"}','{\"cancel_reason\":\"Testing payroll cancellation & advance un-recovery\",\"cancelled_by\":1}','CLI','CLI','2026-10-13 14:04:45'),(829,1,NULL,'EMPLOYEE_CREATED','employees','5',NULL,'{\"employee_code\":\"TEST-ADV-0675\",\"full_name\":\"Test Advance Employee 239\",\"job_role\":\"Cashier\",\"branch_id\":1,\"basic_salary\":60000}','CLI','CLI','2026-10-13 14:07:55'),(830,1,NULL,'JOURNAL_POSTED','accounting','179',NULL,'{\"entry_number\":\"JE-20261013-0179\",\"source_type\":\"employee_advance\",\"source_id\":5,\"total_amount\":5000}','CLI','CLI','2026-10-13 14:07:55'),(831,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','5',NULL,'{\"advance_no\":\"ADV-202610-1856\",\"employee_id\":5,\"amount\":5000,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":179}','CLI','CLI','2026-10-13 14:07:55'),(832,1,NULL,'JOURNAL_POSTED','accounting','180',NULL,'{\"entry_number\":\"JE-20261013-0180\",\"source_type\":\"employee_advance\",\"source_id\":6,\"total_amount\":3000}','CLI','CLI','2026-10-13 14:07:55'),(833,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','6',NULL,'{\"advance_no\":\"ADV-202610-8423\",\"employee_id\":5,\"amount\":3000,\"payment_method\":\"Cash in Hand\",\"cr_account_id\":1,\"journal_id\":180}','CLI','CLI','2026-10-13 14:07:55'),(834,1,NULL,'JOURNAL_POSTED','accounting','181',NULL,'{\"entry_number\":\"JE-20261013-0181\",\"source_type\":\"employee_advance\",\"source_id\":7,\"total_amount\":4000}','CLI','CLI','2026-10-13 14:07:55'),(835,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','7',NULL,'{\"advance_no\":\"ADV-202610-3647\",\"employee_id\":5,\"amount\":4000,\"payment_method\":\"Bank Transfer\",\"cr_account_id\":59,\"journal_id\":181}','CLI','CLI','2026-10-13 14:07:55'),(836,1,NULL,'JOURNAL_POSTED','accounting','182',NULL,'{\"entry_number\":\"JE-20261013-0182\",\"source_type\":\"employee_advance\",\"source_id\":8,\"total_amount\":1500}','CLI','CLI','2026-10-13 14:07:55'),(837,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','8',NULL,'{\"advance_no\":\"ADV-202610-7452\",\"employee_id\":5,\"amount\":1500,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":182}','CLI','CLI','2026-10-13 14:07:55'),(838,1,NULL,'JOURNAL_POSTED','accounting','183',NULL,'{\"entry_number\":\"JE-20261013-0183\",\"source_type\":\"reversal\",\"source_id\":182,\"total_amount\":1500}','CLI','CLI','2026-10-13 14:07:55'),(839,1,NULL,'JOURNAL_REVERSED','accounting','182','{\"entry_number\":\"JE-20261013-0182\"}','{\"reversal_journal_id\":183,\"reason\":\"Cancelled salary advance #ADV-202610-7452: Entered wrong amount by mistake\"}','CLI','CLI','2026-10-13 14:07:55'),(840,1,NULL,'EMPLOYEE_ADVANCE_CANCELLED','payroll','8','{\"id\":8,\"advance_no\":\"ADV-202610-7452\",\"employee_id\":5,\"branch_id\":1,\"amount\":\"1500.00\",\"recovered_amount\":\"0.00\",\"remaining_amount\":\"1500.00\",\"disbursement_date\":\"2026-10-13\",\"payment_method\":\"Cash Drawer\",\"bank_account_id\":null,\"source_account_id\":49,\"reference_no\":\"REF-TO-CANCEL\",\"recovery_period_id\":null,\"status\":\"active\",\"notes\":\"Accidental disbursement to be cancelled\",\"cancelled_at\":null,\"cancelled_by\":null,\"cancel_reason\":null,\"created_by\":1,\"created_at\":\"2026-10-13 14:07:55\",\"updated_at\":\"2026-10-13 14:07:55\",\"employee_name\":\"Test Advance Employee 239\",\"employee_code\":\"TEST-ADV-0675\"}','{\"advance_no\":\"ADV-202610-7452\",\"cancel_reason\":\"Entered wrong amount by mistake\",\"cancelled_by\":1}','CLI','CLI','2026-10-13 14:07:55'),(841,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','5',NULL,'{\"period_code\":\"PR-TEST-0675\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"employee_count\":3,\"total_gross\":170000,\"total_net_salary\":144400}','CLI','CLI','2026-10-13 14:07:55'),(842,1,NULL,'JOURNAL_POSTED','accounting','184',NULL,'{\"entry_number\":\"JE-20261013-0184\",\"source_type\":\"payroll\",\"source_id\":5,\"total_amount\":195500}','CLI','CLI','2026-10-13 14:07:55'),(843,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','5',NULL,'{\"period_code\":\"PR-TEST-0675\",\"total_gross\":\"170000.00\",\"total_net\":144400,\"total_epf\":34000,\"total_etf\":5100,\"journal_id\":184}','CLI','CLI','2026-10-13 14:07:55'),(844,1,NULL,'JOURNAL_POSTED','accounting','185',NULL,'{\"entry_number\":\"JE-20261013-0185\",\"source_type\":\"reversal\",\"source_id\":184,\"total_amount\":195500}','CLI','CLI','2026-10-13 14:07:55'),(845,1,NULL,'JOURNAL_REVERSED','accounting','184','{\"entry_number\":\"JE-20261013-0184\"}','{\"reversal_journal_id\":185,\"reason\":\"Payroll Period Cancelled: Testing payroll cancellation & advance un-recovery\"}','CLI','CLI','2026-10-13 14:07:55'),(846,1,NULL,'PAYROLL_PERIOD_CANCELLED','payroll','5','{\"id\":5,\"period_code\":\"PR-TEST-0675\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-31\",\"branch_id\":1,\"status\":\"approved\",\"total_basic\":\"170000.00\",\"total_allowances\":\"0.00\",\"total_commissions\":\"0.00\",\"total_gross\":\"170000.00\",\"total_employee_epf\":\"13600.00\",\"total_employer_epf\":\"20400.00\",\"total_employer_etf\":\"5100.00\",\"total_deductions\":\"25600.00\",\"total_net_salary\":\"144400.00\",\"total_paid\":\"0.00\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-13 14:07:55\",\"notes\":\"Payroll Period for Salary Advance Recovery Test\",\"created_at\":\"2026-10-13 14:07:55\",\"updated_at\":\"2026-10-13 14:07:55\",\"branch_name\":\"Main Branch - Kandy\",\"created_by_name\":\"Super Administrator\",\"approved_by_name\":\"Super Administrator\"}','{\"cancel_reason\":\"Testing payroll cancellation & advance un-recovery\",\"cancelled_by\":1}','CLI','CLI','2026-10-13 14:07:55'),(847,1,NULL,'EMPLOYEE_CREATED','employees','6',NULL,'{\"employee_code\":\"TEST-ADV-0703\",\"full_name\":\"Test Advance Employee 715\",\"job_role\":\"Cashier\",\"branch_id\":1,\"basic_salary\":60000}','CLI','CLI','2026-10-13 14:08:23'),(848,1,NULL,'JOURNAL_POSTED','accounting','186',NULL,'{\"entry_number\":\"JE-20261013-0186\",\"source_type\":\"employee_advance\",\"source_id\":9,\"total_amount\":5000}','CLI','CLI','2026-10-13 14:08:23'),(849,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','9',NULL,'{\"advance_no\":\"ADV-202610-1604\",\"employee_id\":6,\"amount\":5000,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":186}','CLI','CLI','2026-10-13 14:08:23'),(850,1,NULL,'JOURNAL_POSTED','accounting','187',NULL,'{\"entry_number\":\"JE-20261013-0187\",\"source_type\":\"employee_advance\",\"source_id\":10,\"total_amount\":3000}','CLI','CLI','2026-10-13 14:08:23'),(851,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','10',NULL,'{\"advance_no\":\"ADV-202610-3318\",\"employee_id\":6,\"amount\":3000,\"payment_method\":\"Cash in Hand\",\"cr_account_id\":1,\"journal_id\":187}','CLI','CLI','2026-10-13 14:08:23'),(852,1,NULL,'JOURNAL_POSTED','accounting','188',NULL,'{\"entry_number\":\"JE-20261013-0188\",\"source_type\":\"employee_advance\",\"source_id\":11,\"total_amount\":4000}','CLI','CLI','2026-10-13 14:08:23'),(853,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','11',NULL,'{\"advance_no\":\"ADV-202610-9638\",\"employee_id\":6,\"amount\":4000,\"payment_method\":\"Bank Transfer\",\"cr_account_id\":59,\"journal_id\":188}','CLI','CLI','2026-10-13 14:08:23'),(854,1,NULL,'JOURNAL_POSTED','accounting','189',NULL,'{\"entry_number\":\"JE-20261013-0189\",\"source_type\":\"employee_advance\",\"source_id\":12,\"total_amount\":1500}','CLI','CLI','2026-10-13 14:08:23'),(855,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','12',NULL,'{\"advance_no\":\"ADV-202610-2565\",\"employee_id\":6,\"amount\":1500,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":189}','CLI','CLI','2026-10-13 14:08:23'),(856,1,NULL,'JOURNAL_POSTED','accounting','190',NULL,'{\"entry_number\":\"JE-20261013-0190\",\"source_type\":\"reversal\",\"source_id\":189,\"total_amount\":1500}','CLI','CLI','2026-10-13 14:08:23'),(857,1,NULL,'JOURNAL_REVERSED','accounting','189','{\"entry_number\":\"JE-20261013-0189\"}','{\"reversal_journal_id\":190,\"reason\":\"Cancelled salary advance #ADV-202610-2565: Entered wrong amount by mistake\"}','CLI','CLI','2026-10-13 14:08:23'),(858,1,NULL,'EMPLOYEE_ADVANCE_CANCELLED','payroll','12','{\"id\":12,\"advance_no\":\"ADV-202610-2565\",\"employee_id\":6,\"branch_id\":1,\"amount\":\"1500.00\",\"recovered_amount\":\"0.00\",\"remaining_amount\":\"1500.00\",\"disbursement_date\":\"2026-10-13\",\"payment_method\":\"Cash Drawer\",\"bank_account_id\":null,\"source_account_id\":49,\"reference_no\":\"REF-TO-CANCEL\",\"recovery_period_id\":null,\"status\":\"active\",\"notes\":\"Accidental disbursement to be cancelled\",\"cancelled_at\":null,\"cancelled_by\":null,\"cancel_reason\":null,\"created_by\":1,\"created_at\":\"2026-10-13 14:08:23\",\"updated_at\":\"2026-10-13 14:08:23\",\"employee_name\":\"Test Advance Employee 715\",\"employee_code\":\"TEST-ADV-0703\"}','{\"advance_no\":\"ADV-202610-2565\",\"cancel_reason\":\"Entered wrong amount by mistake\",\"cancelled_by\":1}','CLI','CLI','2026-10-13 14:08:23'),(859,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','6',NULL,'{\"period_code\":\"PR-TEST-0703\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"employee_count\":3,\"total_gross\":170000,\"total_net_salary\":144400}','CLI','CLI','2026-10-13 14:08:23'),(860,1,NULL,'JOURNAL_POSTED','accounting','191',NULL,'{\"entry_number\":\"JE-20261013-0191\",\"source_type\":\"payroll\",\"source_id\":6,\"total_amount\":195500}','CLI','CLI','2026-10-13 14:08:23'),(861,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','6',NULL,'{\"period_code\":\"PR-TEST-0703\",\"total_gross\":\"170000.00\",\"total_net\":144400,\"total_epf\":34000,\"total_etf\":5100,\"journal_id\":191}','CLI','CLI','2026-10-13 14:08:23'),(862,1,NULL,'JOURNAL_POSTED','accounting','192',NULL,'{\"entry_number\":\"JE-20261013-0192\",\"source_type\":\"reversal\",\"source_id\":191,\"total_amount\":195500}','CLI','CLI','2026-10-13 14:08:23'),(863,1,NULL,'JOURNAL_REVERSED','accounting','191','{\"entry_number\":\"JE-20261013-0191\"}','{\"reversal_journal_id\":192,\"reason\":\"Payroll Period Cancelled: Testing payroll cancellation & advance un-recovery\"}','CLI','CLI','2026-10-13 14:08:23'),(864,1,NULL,'PAYROLL_PERIOD_CANCELLED','payroll','6','{\"id\":6,\"period_code\":\"PR-TEST-0703\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-31\",\"branch_id\":1,\"status\":\"approved\",\"total_basic\":\"170000.00\",\"total_allowances\":\"0.00\",\"total_commissions\":\"0.00\",\"total_gross\":\"170000.00\",\"total_employee_epf\":\"13600.00\",\"total_employer_epf\":\"20400.00\",\"total_employer_etf\":\"5100.00\",\"total_deductions\":\"25600.00\",\"total_net_salary\":\"144400.00\",\"total_paid\":\"0.00\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-13 14:08:23\",\"notes\":\"Payroll Period for Salary Advance Recovery Test\",\"created_at\":\"2026-10-13 14:08:23\",\"updated_at\":\"2026-10-13 14:08:23\",\"branch_name\":\"Main Branch - Kandy\",\"created_by_name\":\"Super Administrator\",\"approved_by_name\":\"Super Administrator\"}','{\"cancel_reason\":\"Testing payroll cancellation & advance un-recovery\",\"cancelled_by\":1}','CLI','CLI','2026-10-13 14:08:23'),(865,1,NULL,'EMPLOYEE_CREATED','employees','7',NULL,'{\"employee_code\":\"EMP-0003\",\"full_name\":\"Kamal Perera\",\"job_role\":\"Sales Rep\",\"branch_id\":1,\"basic_salary\":50000}','CLI','CLI','2026-10-13 14:08:29'),(866,1,NULL,'EMPLOYEE_STATUS_TOGGLED','employees','7','{\"status\":\"active\"}','{\"status\":\"inactive\"}','CLI','CLI','2026-10-13 14:08:29'),(867,1,NULL,'EMPLOYEE_STATUS_TOGGLED','employees','7','{\"status\":\"inactive\"}','{\"status\":\"active\"}','CLI','CLI','2026-10-13 14:08:29'),(868,1,NULL,'SALES_HOLD_INVOICE','pos','14',NULL,'{\"invoice_no\":\"0011\",\"total_amount\":2000,\"items_count\":1}','CLI','CLI','2026-10-13 14:08:29'),(869,1,NULL,'JOURNAL_POSTED','accounting','193',NULL,'{\"entry_number\":\"JE-20261013-0193\",\"source_type\":\"employee_advance\",\"source_id\":13,\"total_amount\":5000}','CLI','CLI','2026-10-13 14:08:29'),(870,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','13',NULL,'{\"advance_no\":\"ADV-202610-4627\",\"employee_id\":7,\"amount\":5000,\"payment_method\":\"Cash in Hand\",\"cr_account_id\":1,\"journal_id\":193}','CLI','CLI','2026-10-13 14:08:29'),(871,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','7',NULL,'{\"period_code\":\"2026-10\",\"period_name\":\"October 2026\",\"employee_count\":3,\"total_gross\":160000,\"total_net_salary\":142200}','CLI','CLI','2026-10-13 14:08:29'),(872,1,NULL,'PAYROLL_ENTRY_UPDATED','payroll','16','{\"id\":16,\"payroll_period_id\":7,\"employee_id\":7,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"5000.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"9000.00\",\"net_salary\":\"41000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 14:08:29\",\"updated_at\":\"2026-10-13 14:08:29\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":56000,\"net_salary\":47000}','CLI','CLI','2026-10-13 14:08:29'),(873,1,NULL,'JOURNAL_POSTED','accounting','194',NULL,'{\"entry_number\":\"JE-20261013-0194\",\"source_type\":\"payroll\",\"source_id\":7,\"total_amount\":190000}','CLI','CLI','2026-10-13 14:08:29'),(874,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','7',NULL,'{\"period_code\":\"2026-10\",\"total_gross\":\"166000.00\",\"total_net\":148200,\"total_epf\":32000,\"total_etf\":4800,\"journal_id\":194}','CLI','CLI','2026-10-13 14:08:29'),(875,1,NULL,'JOURNAL_POSTED','accounting','195',NULL,'{\"entry_number\":\"JE-20261013-0195\",\"source_type\":\"payroll_payment\",\"source_id\":1,\"total_amount\":47000}','CLI','CLI','2026-10-13 14:08:29'),(876,1,NULL,'PAYROLL_PAYMENT_RECORDED','payroll','1',NULL,'{\"payment_no\":\"PAY-SAL-202610-8469\",\"employee_id\":7,\"amount\":47000,\"method\":\"Cash\"}','CLI','CLI','2026-10-13 14:08:29'),(877,1,NULL,'JOURNAL_POSTED','accounting','196',NULL,'{\"entry_number\":\"JE-20261013-0196\",\"source_type\":\"epf_etf_remittance\",\"source_id\":1,\"total_amount\":10000}','CLI','CLI','2026-10-13 14:08:29'),(878,1,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','1',NULL,'{\"remittance_no\":\"REMIT-202610-5392\",\"period_id\":7,\"total_amount\":10000}','CLI','CLI','2026-10-13 14:08:29'),(879,1,NULL,'JOURNAL_POSTED','accounting','197',NULL,'{\"entry_number\":\"JE-20261013-0197\",\"source_type\":\"epf_etf_remittance\",\"source_id\":2,\"total_amount\":1500}','CLI','CLI','2026-10-13 14:08:29'),(880,1,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','2',NULL,'{\"remittance_no\":\"REMIT-202610-2642\",\"period_id\":7,\"total_amount\":1500}','CLI','CLI','2026-10-13 14:08:29'),(881,1,NULL,'EMPLOYEE_SOFT_DELETED','employees','7','{\"id\":7,\"employee_code\":\"EMP-0003\",\"full_name\":\"Kamal Perera\",\"phone\":\"0771234567\",\"whatsapp\":\"0771234567\",\"address\":\"123 Kandy Road, Kandy\",\"nic\":\"951234567V\",\"job_role\":\"Sales Rep\",\"is_sales_rep\":1,\"branch_id\":1,\"branch_name\":\"Main Branch - Kandy\",\"joining_date\":\"2026-10-13\",\"basic_salary\":\"50000.00\",\"status\":\"active\",\"bank_name\":\"Commercial Bank\",\"bank_account_name\":null,\"bank_account_no\":\"1234567890\",\"notes\":null,\"created_at\":\"2026-10-13 14:08:29\",\"updated_at\":\"2026-10-13 14:08:29\",\"deleted_at\":null}','{\"deleted_at\":\"2026-10-13 10:38:29\"}','CLI','CLI','2026-10-13 14:08:29'),(882,1,NULL,'EMPLOYEE_RESTORED','employees','7',NULL,'{\"restored_at\":\"2026-10-13 10:38:29\"}','CLI','CLI','2026-10-13 14:08:29'),(883,1,1,'SESSION_EXPIRED','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 19:10:00'),(884,1,1,'LOGOUT','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 19:10:00'),(885,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 19:45:50'),(886,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 19:45:51'),(887,1,1,'JOURNAL_POSTED','accounting','198',NULL,'{\"entry_number\":\"JE-20261013-0195\",\"source_type\":\"employee_advance\",\"source_id\":15,\"total_amount\":1000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 19:48:19'),(888,1,1,'EMPLOYEE_ADVANCE_DISBURSED','payroll','15',NULL,'{\"advance_no\":\"ADV-202610-8482\",\"employee_id\":1,\"amount\":1000,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":198}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 19:48:19'),(889,1,1,'PAYROLL_PERIOD_CREATED','payroll','8',NULL,'{\"period_code\":\"2026-10\",\"period_name\":\"October 2026\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 19:48:49'),(890,1,1,'PAYROLL_PERIOD_CREATED','payroll','9',NULL,'{\"period_code\":\"2026-10\",\"period_name\":\"October 2026\",\"employee_count\":1,\"total_gross\":50000,\"total_net_salary\":45000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 19:49:03'),(891,1,NULL,'EMPLOYEE_CREATED','employees','8',NULL,'{\"employee_code\":\"TEST-ADV-2402\",\"full_name\":\"Test Advance Employee 337\",\"job_role\":\"Cashier\",\"branch_id\":1,\"basic_salary\":60000}','CLI','CLI','2026-10-13 20:10:02'),(892,1,NULL,'JOURNAL_POSTED','accounting','199',NULL,'{\"entry_number\":\"JE-20261013-0199\",\"source_type\":\"employee_advance\",\"source_id\":16,\"total_amount\":5000}','CLI','CLI','2026-10-13 20:10:02'),(893,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','16',NULL,'{\"advance_no\":\"ADV-202610-9301\",\"employee_id\":8,\"amount\":5000,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":199}','CLI','CLI','2026-10-13 20:10:02'),(894,1,NULL,'JOURNAL_POSTED','accounting','200',NULL,'{\"entry_number\":\"JE-20261013-0200\",\"source_type\":\"employee_advance\",\"source_id\":17,\"total_amount\":3000}','CLI','CLI','2026-10-13 20:10:02'),(895,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','17',NULL,'{\"advance_no\":\"ADV-202610-3220\",\"employee_id\":8,\"amount\":3000,\"payment_method\":\"Cash in Hand\",\"cr_account_id\":1,\"journal_id\":200}','CLI','CLI','2026-10-13 20:10:02'),(896,1,NULL,'JOURNAL_POSTED','accounting','201',NULL,'{\"entry_number\":\"JE-20261013-0201\",\"source_type\":\"employee_advance\",\"source_id\":18,\"total_amount\":4000}','CLI','CLI','2026-10-13 20:10:02'),(897,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','18',NULL,'{\"advance_no\":\"ADV-202610-4116\",\"employee_id\":8,\"amount\":4000,\"payment_method\":\"Bank Transfer\",\"cr_account_id\":59,\"journal_id\":201}','CLI','CLI','2026-10-13 20:10:02'),(898,1,NULL,'JOURNAL_POSTED','accounting','202',NULL,'{\"entry_number\":\"JE-20261013-0202\",\"source_type\":\"employee_advance\",\"source_id\":19,\"total_amount\":1500}','CLI','CLI','2026-10-13 20:10:02'),(899,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','19',NULL,'{\"advance_no\":\"ADV-202610-9554\",\"employee_id\":8,\"amount\":1500,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":202}','CLI','CLI','2026-10-13 20:10:02'),(900,1,NULL,'JOURNAL_POSTED','accounting','203',NULL,'{\"entry_number\":\"JE-20261013-0203\",\"source_type\":\"reversal\",\"source_id\":202,\"total_amount\":1500}','CLI','CLI','2026-10-13 20:10:02'),(901,1,NULL,'JOURNAL_REVERSED','accounting','202','{\"entry_number\":\"JE-20261013-0202\"}','{\"reversal_journal_id\":203,\"reason\":\"Cancelled salary advance #ADV-202610-9554: Entered wrong amount by mistake\"}','CLI','CLI','2026-10-13 20:10:02'),(902,1,NULL,'EMPLOYEE_ADVANCE_CANCELLED','payroll','19','{\"id\":19,\"advance_no\":\"ADV-202610-9554\",\"employee_id\":8,\"branch_id\":1,\"amount\":\"1500.00\",\"recovered_amount\":\"0.00\",\"remaining_amount\":\"1500.00\",\"disbursement_date\":\"2026-10-13\",\"payment_method\":\"Cash Drawer\",\"bank_account_id\":null,\"source_account_id\":49,\"reference_no\":\"REF-TO-CANCEL\",\"recovery_period_id\":null,\"status\":\"active\",\"notes\":\"Accidental disbursement to be cancelled\",\"cancelled_at\":null,\"cancelled_by\":null,\"cancel_reason\":null,\"created_by\":1,\"created_at\":\"2026-10-13 20:10:02\",\"updated_at\":\"2026-10-13 20:10:02\",\"employee_name\":\"Test Advance Employee 337\",\"employee_code\":\"TEST-ADV-2402\"}','{\"advance_no\":\"ADV-202610-9554\",\"cancel_reason\":\"Entered wrong amount by mistake\",\"cancelled_by\":1}','CLI','CLI','2026-10-13 20:10:02'),(903,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','10',NULL,'{\"period_code\":\"PR-TEST-2402\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"employee_count\":3,\"total_gross\":170000,\"total_net_salary\":144400}','CLI','CLI','2026-10-13 20:10:02'),(904,1,NULL,'JOURNAL_POSTED','accounting','204',NULL,'{\"entry_number\":\"JE-20261013-0204\",\"source_type\":\"payroll\",\"source_id\":10,\"total_amount\":195500}','CLI','CLI','2026-10-13 20:10:02'),(905,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','10',NULL,'{\"period_code\":\"PR-TEST-2402\",\"total_gross\":\"170000.00\",\"total_net\":144400,\"total_epf\":34000,\"total_etf\":5100,\"journal_id\":204}','CLI','CLI','2026-10-13 20:10:02'),(906,1,NULL,'JOURNAL_POSTED','accounting','205',NULL,'{\"entry_number\":\"JE-20261013-0205\",\"source_type\":\"reversal\",\"source_id\":204,\"total_amount\":195500}','CLI','CLI','2026-10-13 20:10:02'),(907,1,NULL,'JOURNAL_REVERSED','accounting','204','{\"entry_number\":\"JE-20261013-0204\"}','{\"reversal_journal_id\":205,\"reason\":\"Payroll Period Cancelled: Testing payroll cancellation & advance un-recovery\"}','CLI','CLI','2026-10-13 20:10:02'),(908,1,NULL,'PAYROLL_PERIOD_CANCELLED','payroll','10','{\"id\":10,\"period_code\":\"PR-TEST-2402\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-31\",\"branch_id\":1,\"status\":\"approved\",\"total_basic\":\"170000.00\",\"total_allowances\":\"0.00\",\"total_commissions\":\"0.00\",\"total_gross\":\"170000.00\",\"total_employee_epf\":\"13600.00\",\"total_employer_epf\":\"20400.00\",\"total_employer_etf\":\"5100.00\",\"total_deductions\":\"25600.00\",\"total_net_salary\":\"144400.00\",\"total_paid\":\"0.00\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-13 20:10:02\",\"notes\":\"Payroll Period for Salary Advance Recovery Test\",\"created_at\":\"2026-10-13 20:10:02\",\"updated_at\":\"2026-10-13 20:10:02\",\"branch_name\":\"Main Branch - Kandy\",\"created_by_name\":\"Super Administrator\",\"approved_by_name\":\"Super Administrator\"}','{\"cancel_reason\":\"Testing payroll cancellation & advance un-recovery\",\"cancelled_by\":1}','CLI','CLI','2026-10-13 20:10:02'),(909,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','11',NULL,'{\"period_code\":\"TEST-UPG-202610-656\",\"period_name\":\"Upgrade Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-13 20:10:24'),(910,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','24','{\"id\":24,\"payroll_period_id\":11,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4000.00\",\"net_salary\":\"46000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:10:24\",\"updated_at\":\"2026-10-13 20:10:24\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":65000,\"net_salary\":61000}','CLI','CLI','2026-10-13 20:10:24'),(911,1,1,'JOURNAL_POSTED','accounting','206',NULL,'{\"entry_number\":\"JE-20261013-0206\",\"source_type\":\"employee_advance\",\"source_id\":20,\"total_amount\":1000.01}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 20:11:20'),(912,1,1,'EMPLOYEE_ADVANCE_DISBURSED','payroll','20',NULL,'{\"advance_no\":\"ADV-202610-2965\",\"employee_id\":2,\"amount\":1000.01,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":206}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 20:11:20'),(913,1,1,'PAYROLL_PERIOD_CREATED','payroll','12',NULL,'{\"period_code\":\"2026-10\",\"period_name\":\"October 2026\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":100199.98999999999}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 20:12:20'),(914,1,1,'PAYROLL_ENTRY_UPDATED','payroll','26','{\"id\":26,\"payroll_period_id\":12,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"0.00\",\"total_deductions\":\"5000.01\",\"net_salary\":\"44999.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 20:12:20\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70001,\"net_salary\":65000.99}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 20:15:23'),(915,1,1,'PAYROLL_ENTRY_UPDATED','payroll','27','{\"id\":27,\"payroll_period_id\":12,\"employee_id\":3,\"branch_id\":1,\"basic_salary\":\"60000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"60000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4800.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4800.00\",\"net_salary\":\"55200.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"7200.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1800.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 20:12:20\",\"period_status\":\"draft\"}','{\"basic_salary\":60000,\"gross_salary\":65000,\"net_salary\":60200}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 20:15:50'),(916,1,1,'PAYROLL_ENTRY_UPDATED','payroll','27','{\"id\":27,\"payroll_period_id\":12,\"employee_id\":3,\"branch_id\":1,\"basic_salary\":\"60000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"5000.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"65000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4800.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4800.00\",\"net_salary\":\"60200.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"7200.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1800.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 20:15:50\",\"period_status\":\"draft\"}','{\"basic_salary\":60000,\"gross_salary\":60000,\"net_salary\":55200}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 20:16:25'),(917,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','13',NULL,'{\"period_code\":\"TEST-EPF-202610-669\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":100199.98999999999}','CLI','CLI','2026-10-13 20:49:00'),(918,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','28','{\"id\":28,\"payroll_period_id\":13,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"0.00\",\"total_deductions\":\"5000.01\",\"net_salary\":\"44999.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:49:00\",\"updated_at\":\"2026-10-13 20:49:00\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":59899.99}','CLI','CLI','2026-10-13 20:49:00'),(919,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','14',NULL,'{\"period_code\":\"TEST-EPF-202610-507\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":100199.98999999999}','CLI','CLI','2026-10-13 20:49:29'),(920,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','30','{\"id\":30,\"payroll_period_id\":14,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"0.00\",\"total_deductions\":\"5000.01\",\"net_salary\":\"44999.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:49:29\",\"updated_at\":\"2026-10-13 20:49:29\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":59899.99}','CLI','CLI','2026-10-13 20:49:29'),(921,NULL,NULL,'JOURNAL_POSTED','accounting','207',NULL,'{\"entry_number\":\"JE-20261013-0207\",\"source_type\":\"payroll\",\"source_id\":14,\"total_amount\":149500}','CLI','CLI','2026-10-13 20:49:29'),(922,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','14',NULL,'{\"period_code\":\"TEST-EPF-202610-507\",\"total_gross\":\"130000.00\",\"total_net\":115099.98999999999,\"total_epf\":26000,\"total_etf\":3900,\"journal_id\":207}','CLI','CLI','2026-10-13 20:49:29'),(923,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','15',NULL,'{\"period_code\":\"TEST-EPF-202610-336\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-13 20:49:55'),(924,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','32','{\"id\":32,\"payroll_period_id\":15,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4000.00\",\"net_salary\":\"46000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:49:55\",\"updated_at\":\"2026-10-13 20:49:55\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":60900}','CLI','CLI','2026-10-13 20:49:55'),(925,NULL,NULL,'JOURNAL_POSTED','accounting','208',NULL,'{\"entry_number\":\"JE-20261013-0208\",\"source_type\":\"payroll\",\"source_id\":15,\"total_amount\":149500}','CLI','CLI','2026-10-13 20:49:55'),(926,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','15',NULL,'{\"period_code\":\"TEST-EPF-202610-336\",\"total_gross\":\"130000.00\",\"total_net\":116100,\"total_epf\":26000,\"total_etf\":3900,\"journal_id\":208}','CLI','CLI','2026-10-13 20:49:55'),(927,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','16',NULL,'{\"period_code\":\"TEST-EPF-202610-153\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-13 20:51:23'),(928,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','34','{\"id\":34,\"payroll_period_id\":16,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4000.00\",\"net_salary\":\"46000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:51:23\",\"updated_at\":\"2026-10-13 20:51:23\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":60900}','CLI','CLI','2026-10-13 20:51:23'),(929,NULL,NULL,'JOURNAL_POSTED','accounting','209',NULL,'{\"entry_number\":\"JE-20261013-0209\",\"source_type\":\"payroll\",\"source_id\":16,\"total_amount\":149500}','CLI','CLI','2026-10-13 20:51:23'),(930,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','16',NULL,'{\"period_code\":\"TEST-EPF-202610-153\",\"total_gross\":\"130000.00\",\"total_net\":116100,\"total_epf\":26000,\"total_etf\":3900,\"journal_id\":209}','CLI','CLI','2026-10-13 20:51:23'),(931,NULL,NULL,'JOURNAL_POSTED','accounting','210',NULL,'{\"entry_number\":\"JE-20261013-0210\",\"source_type\":\"payroll_payment\",\"source_id\":2,\"total_amount\":60900}','CLI','CLI','2026-10-13 20:51:23'),(932,NULL,NULL,'PAYROLL_PAYMENT_RECORDED','payroll','2',NULL,'{\"payment_no\":\"PAY-SAL-202610-8902\",\"employee_id\":2,\"amount\":60900,\"splits\":2}','CLI','CLI','2026-10-13 20:51:23'),(933,NULL,NULL,'JOURNAL_POSTED','accounting','211',NULL,'{\"entry_number\":\"JE-20261013-0211\",\"source_type\":\"epf_etf_remittance\",\"source_id\":3,\"total_amount\":16100}','CLI','CLI','2026-10-13 20:51:23'),(934,NULL,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','3',NULL,'{\"remittance_no\":\"REMIT-202610-8477\",\"period_id\":16,\"total_amount\":16100}','CLI','CLI','2026-10-13 20:51:23'),(935,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','17',NULL,'{\"period_code\":\"TEST-EPF-202610-184\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-13 20:51:44'),(936,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','36','{\"id\":36,\"payroll_period_id\":17,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4000.00\",\"net_salary\":\"46000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:51:44\",\"updated_at\":\"2026-10-13 20:51:44\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":60900}','CLI','CLI','2026-10-13 20:51:44'),(937,NULL,NULL,'JOURNAL_POSTED','accounting','212',NULL,'{\"entry_number\":\"JE-20261013-0212\",\"source_type\":\"payroll\",\"source_id\":17,\"total_amount\":149500}','CLI','CLI','2026-10-13 20:51:44'),(938,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','17',NULL,'{\"period_code\":\"TEST-EPF-202610-184\",\"total_gross\":\"130000.00\",\"total_net\":116100,\"total_epf\":26000,\"total_etf\":3900,\"journal_id\":212}','CLI','CLI','2026-10-13 20:51:44'),(939,NULL,NULL,'JOURNAL_POSTED','accounting','213',NULL,'{\"entry_number\":\"JE-20261013-0213\",\"source_type\":\"payroll_payment\",\"source_id\":4,\"total_amount\":60900}','CLI','CLI','2026-10-13 20:51:44'),(940,NULL,NULL,'PAYROLL_PAYMENT_RECORDED','payroll','4',NULL,'{\"payment_no\":\"PAY-SAL-202610-7488\",\"employee_id\":2,\"amount\":60900,\"splits\":2}','CLI','CLI','2026-10-13 20:51:44'),(941,NULL,NULL,'JOURNAL_POSTED','accounting','214',NULL,'{\"entry_number\":\"JE-20261013-0214\",\"source_type\":\"epf_etf_remittance\",\"source_id\":4,\"total_amount\":16100}','CLI','CLI','2026-10-13 20:51:44'),(942,NULL,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','4',NULL,'{\"remittance_no\":\"REMIT-202610-9901\",\"period_id\":17,\"total_amount\":16100}','CLI','CLI','2026-10-13 20:51:44'),(943,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','18',NULL,'{\"period_code\":\"TEST-EPF-202610-527\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-13 20:52:00'),(944,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','38','{\"id\":38,\"payroll_period_id\":18,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4000.00\",\"net_salary\":\"46000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:52:00\",\"updated_at\":\"2026-10-13 20:52:00\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":60900}','CLI','CLI','2026-10-13 20:52:00'),(945,NULL,NULL,'JOURNAL_POSTED','accounting','215',NULL,'{\"entry_number\":\"JE-20261013-0215\",\"source_type\":\"payroll\",\"source_id\":18,\"total_amount\":149500}','CLI','CLI','2026-10-13 20:52:00'),(946,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','18',NULL,'{\"period_code\":\"TEST-EPF-202610-527\",\"total_gross\":\"130000.00\",\"total_net\":116100,\"total_epf\":26000,\"total_etf\":3900,\"journal_id\":215}','CLI','CLI','2026-10-13 20:52:00'),(947,NULL,NULL,'JOURNAL_POSTED','accounting','216',NULL,'{\"entry_number\":\"JE-20261013-0216\",\"source_type\":\"payroll_payment\",\"source_id\":6,\"total_amount\":60900}','CLI','CLI','2026-10-13 20:52:00'),(948,NULL,NULL,'PAYROLL_PAYMENT_RECORDED','payroll','6',NULL,'{\"payment_no\":\"PAY-SAL-202610-6334\",\"employee_id\":2,\"amount\":60900,\"splits\":2}','CLI','CLI','2026-10-13 20:52:00'),(949,NULL,NULL,'JOURNAL_POSTED','accounting','217',NULL,'{\"entry_number\":\"JE-20261013-0217\",\"source_type\":\"epf_etf_remittance\",\"source_id\":5,\"total_amount\":16100}','CLI','CLI','2026-10-13 20:52:00'),(950,NULL,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','5',NULL,'{\"remittance_no\":\"REMIT-202610-6155\",\"period_id\":18,\"total_amount\":16100}','CLI','CLI','2026-10-13 20:52:00'),(951,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','19',NULL,'{\"period_code\":\"TEST-EPF-202610-730\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-13 20:52:11'),(952,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','40','{\"id\":40,\"payroll_period_id\":19,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4000.00\",\"net_salary\":\"46000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:52:11\",\"updated_at\":\"2026-10-13 20:52:11\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":60900}','CLI','CLI','2026-10-13 20:52:11'),(953,NULL,NULL,'JOURNAL_POSTED','accounting','218',NULL,'{\"entry_number\":\"JE-20261013-0218\",\"source_type\":\"payroll\",\"source_id\":19,\"total_amount\":149500}','CLI','CLI','2026-10-13 20:52:11'),(954,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','19',NULL,'{\"period_code\":\"TEST-EPF-202610-730\",\"total_gross\":\"130000.00\",\"total_net\":116100,\"total_epf\":26000,\"total_etf\":3900,\"journal_id\":218}','CLI','CLI','2026-10-13 20:52:11'),(955,NULL,NULL,'JOURNAL_POSTED','accounting','219',NULL,'{\"entry_number\":\"JE-20261013-0219\",\"source_type\":\"payroll_payment\",\"source_id\":8,\"total_amount\":60900}','CLI','CLI','2026-10-13 20:52:11'),(956,NULL,NULL,'PAYROLL_PAYMENT_RECORDED','payroll','8',NULL,'{\"payment_no\":\"PAY-SAL-202610-7232\",\"employee_id\":2,\"amount\":60900,\"splits\":2}','CLI','CLI','2026-10-13 20:52:11'),(957,NULL,NULL,'JOURNAL_POSTED','accounting','220',NULL,'{\"entry_number\":\"JE-20261013-0220\",\"source_type\":\"epf_etf_remittance\",\"source_id\":6,\"total_amount\":16100}','CLI','CLI','2026-10-13 20:52:11'),(958,NULL,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','6',NULL,'{\"remittance_no\":\"REMIT-202610-2117\",\"period_id\":19,\"total_amount\":16100}','CLI','CLI','2026-10-13 20:52:11'),(959,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','20',NULL,'{\"period_code\":\"TEST-EPF-202610-330\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-13 20:53:36'),(960,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','42','{\"id\":42,\"payroll_period_id\":20,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4000.00\",\"net_salary\":\"46000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:53:36\",\"updated_at\":\"2026-10-13 20:53:36\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":60900}','CLI','CLI','2026-10-13 20:53:36'),(961,NULL,NULL,'JOURNAL_POSTED','accounting','221',NULL,'{\"entry_number\":\"JE-20261013-0221\",\"source_type\":\"payroll\",\"source_id\":20,\"total_amount\":149500}','CLI','CLI','2026-10-13 20:53:36'),(962,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','20',NULL,'{\"period_code\":\"TEST-EPF-202610-330\",\"total_gross\":\"130000.00\",\"total_net\":116100,\"total_epf\":26000,\"total_etf\":3900,\"journal_id\":221}','CLI','CLI','2026-10-13 20:53:36'),(963,NULL,NULL,'JOURNAL_POSTED','accounting','222',NULL,'{\"entry_number\":\"JE-20261013-0222\",\"source_type\":\"payroll_payment\",\"source_id\":10,\"total_amount\":60900}','CLI','CLI','2026-10-13 20:53:36'),(964,NULL,NULL,'PAYROLL_PAYMENT_RECORDED','payroll','10',NULL,'{\"payment_no\":\"PAY-SAL-202610-2469\",\"employee_id\":2,\"amount\":60900,\"splits\":2}','CLI','CLI','2026-10-13 20:53:36'),(965,NULL,NULL,'JOURNAL_POSTED','accounting','223',NULL,'{\"entry_number\":\"JE-20261013-0223\",\"source_type\":\"epf_etf_remittance\",\"source_id\":7,\"total_amount\":16100}','CLI','CLI','2026-10-13 20:53:36'),(966,NULL,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','7',NULL,'{\"remittance_no\":\"REMIT-202610-2055\",\"period_id\":20,\"total_amount\":16100}','CLI','CLI','2026-10-13 20:53:36'),(967,1,1,'PAYROLL_ENTRY_UPDATED','payroll','26','{\"id\":26,\"payroll_period_id\":12,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"20001.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"70001.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"0.00\",\"total_deductions\":\"5000.01\",\"net_salary\":\"65000.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 20:15:23\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70001,\"net_salary\":63400.91}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 20:57:12'),(968,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','21',NULL,'{\"period_code\":\"TEST-EPF-202610-782\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-13 21:12:57'),(969,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','44','{\"id\":44,\"payroll_period_id\":21,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4000.00\",\"net_salary\":\"46000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 21:12:57\",\"updated_at\":\"2026-10-13 21:12:57\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":60900}','CLI','CLI','2026-10-13 21:12:57'),(970,NULL,NULL,'JOURNAL_POSTED','accounting','224',NULL,'{\"entry_number\":\"JE-20261013-0224\",\"source_type\":\"payroll\",\"source_id\":21,\"total_amount\":149500}','CLI','CLI','2026-10-13 21:12:57'),(971,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','21',NULL,'{\"period_code\":\"TEST-EPF-202610-782\",\"total_gross\":\"130000.00\",\"total_net\":116100,\"total_epf\":26000,\"total_etf\":3900,\"journal_id\":224}','CLI','CLI','2026-10-13 21:12:57'),(972,NULL,NULL,'JOURNAL_POSTED','accounting','225',NULL,'{\"entry_number\":\"JE-20261013-0225\",\"source_type\":\"payroll_payment\",\"source_id\":12,\"total_amount\":60900}','CLI','CLI','2026-10-13 21:12:57'),(973,NULL,NULL,'PAYROLL_PAYMENT_RECORDED','payroll','12',NULL,'{\"payment_no\":\"PAY-SAL-202610-7399\",\"employee_id\":2,\"amount\":60900,\"splits\":2}','CLI','CLI','2026-10-13 21:12:57'),(974,NULL,NULL,'JOURNAL_POSTED','accounting','226',NULL,'{\"entry_number\":\"JE-20261013-0226\",\"source_type\":\"epf_etf_remittance\",\"source_id\":8,\"total_amount\":16100}','CLI','CLI','2026-10-13 21:12:57'),(975,NULL,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','8',NULL,'{\"remittance_no\":\"REMIT-202610-5814\",\"period_id\":21,\"total_amount\":16100}','CLI','CLI','2026-10-13 21:12:57'),(976,1,1,'PAYROLL_ENTRY_UPDATED','payroll','26','{\"id\":26,\"payroll_period_id\":12,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"20001.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"70001.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"5600.08\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"0.00\",\"total_deductions\":\"6600.09\",\"net_salary\":\"63400.91\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"8400.12\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"2100.03\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 20:57:12\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":63399.99}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 21:46:26'),(977,1,1,'PAYROLL_ENTRY_UPDATED','payroll','26','{\"id\":26,\"payroll_period_id\":12,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"20000.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"70000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"5600.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"0.00\",\"total_deductions\":\"6600.01\",\"net_salary\":\"63399.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"8400.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"2100.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 21:46:26\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":63399.99}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 21:46:33'),(978,1,1,'PAYROLL_ENTRY_UPDATED','payroll','26','{\"id\":26,\"payroll_period_id\":12,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"20000.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"70000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"5600.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"0.00\",\"total_deductions\":\"6600.01\",\"net_salary\":\"63399.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"8400.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"2100.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 21:46:33\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":63399.99}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 21:46:36'),(979,1,1,'PAYROLL_ENTRY_UPDATED','payroll','27','{\"id\":27,\"payroll_period_id\":12,\"employee_id\":3,\"branch_id\":1,\"basic_salary\":\"60000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"60000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4800.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4800.00\",\"net_salary\":\"55200.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"7200.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1800.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 20:16:25\",\"period_status\":\"draft\"}','{\"basic_salary\":60000,\"gross_salary\":60000,\"net_salary\":55200}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 21:46:37'),(980,1,1,'PAYROLL_ENTRY_UPDATED','payroll','26','{\"id\":26,\"payroll_period_id\":12,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"20000.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"70000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"5600.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"0.00\",\"total_deductions\":\"6600.01\",\"net_salary\":\"63399.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"8400.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"2100.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 21:46:36\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":63399.99}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 21:46:37'),(981,1,1,'PAYROLL_ENTRY_UPDATED','payroll','26','{\"id\":26,\"payroll_period_id\":12,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"20000.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"70000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"5600.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"0.00\",\"total_deductions\":\"6600.01\",\"net_salary\":\"63399.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"8400.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"2100.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 21:46:37\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000.01,\"net_salary\":63400}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 21:46:40'),(982,1,1,'PAYROLL_ENTRY_UPDATED','payroll','26','{\"id\":26,\"payroll_period_id\":12,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"20000.01\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"70000.01\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"5600.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"0.00\",\"total_deductions\":\"6600.01\",\"net_salary\":\"63400.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"8400.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"2100.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 21:46:40\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":50000,\"net_salary\":44999.99}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-13 21:46:51'),(983,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','22',NULL,'{\"period_code\":\"TEST-EPF-202610-559\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-13 21:59:23'),(984,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','46','{\"id\":46,\"payroll_period_id\":22,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4000.00\",\"net_salary\":\"46000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 21:59:23\",\"updated_at\":\"2026-10-13 21:59:23\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":60900}','CLI','CLI','2026-10-13 21:59:23'),(985,NULL,NULL,'JOURNAL_POSTED','accounting','227',NULL,'{\"entry_number\":\"JE-20261013-0227\",\"source_type\":\"payroll\",\"source_id\":22,\"total_amount\":149500}','CLI','CLI','2026-10-13 21:59:23'),(986,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','22',NULL,'{\"period_code\":\"TEST-EPF-202610-559\",\"total_gross\":\"130000.00\",\"total_net\":116100,\"total_epf\":26000,\"total_etf\":3900,\"journal_id\":227}','CLI','CLI','2026-10-13 21:59:23'),(987,NULL,NULL,'JOURNAL_POSTED','accounting','228',NULL,'{\"entry_number\":\"JE-20261013-0228\",\"source_type\":\"payroll_payment\",\"source_id\":14,\"total_amount\":60900}','CLI','CLI','2026-10-13 21:59:23'),(988,NULL,NULL,'PAYROLL_PAYMENT_RECORDED','payroll','14',NULL,'{\"payment_no\":\"PAY-SAL-202610-1316\",\"employee_id\":2,\"amount\":60900,\"splits\":2}','CLI','CLI','2026-10-13 21:59:23'),(989,NULL,NULL,'JOURNAL_POSTED','accounting','229',NULL,'{\"entry_number\":\"JE-20261013-0229\",\"source_type\":\"epf_etf_remittance\",\"source_id\":9,\"total_amount\":16100}','CLI','CLI','2026-10-13 21:59:23'),(990,NULL,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','9',NULL,'{\"remittance_no\":\"REMIT-202610-7448\",\"period_id\":22,\"total_amount\":16100}','CLI','CLI','2026-10-13 21:59:23'),(991,1,NULL,'EMPLOYEE_CREATED','employees','9',NULL,'{\"employee_code\":\"TEST-ADV-8966\",\"full_name\":\"Test Advance Employee 706\",\"job_role\":\"Cashier\",\"branch_id\":1,\"basic_salary\":60000}','CLI','CLI','2026-10-13 21:59:26'),(992,1,NULL,'JOURNAL_POSTED','accounting','230',NULL,'{\"entry_number\":\"JE-20261013-0230\",\"source_type\":\"employee_advance\",\"source_id\":21,\"total_amount\":5000}','CLI','CLI','2026-10-13 21:59:26'),(993,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','21',NULL,'{\"advance_no\":\"ADV-202610-4073\",\"employee_id\":9,\"amount\":5000,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":230}','CLI','CLI','2026-10-13 21:59:26'),(994,1,NULL,'JOURNAL_POSTED','accounting','231',NULL,'{\"entry_number\":\"JE-20261013-0231\",\"source_type\":\"employee_advance\",\"source_id\":22,\"total_amount\":3000}','CLI','CLI','2026-10-13 21:59:26'),(995,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','22',NULL,'{\"advance_no\":\"ADV-202610-2996\",\"employee_id\":9,\"amount\":3000,\"payment_method\":\"Cash in Hand\",\"cr_account_id\":1,\"journal_id\":231}','CLI','CLI','2026-10-13 21:59:26'),(996,1,NULL,'JOURNAL_POSTED','accounting','232',NULL,'{\"entry_number\":\"JE-20261013-0232\",\"source_type\":\"employee_advance\",\"source_id\":23,\"total_amount\":4000}','CLI','CLI','2026-10-13 21:59:26'),(997,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','23',NULL,'{\"advance_no\":\"ADV-202610-5953\",\"employee_id\":9,\"amount\":4000,\"payment_method\":\"Bank Transfer\",\"cr_account_id\":59,\"journal_id\":232}','CLI','CLI','2026-10-13 21:59:26'),(998,1,NULL,'JOURNAL_POSTED','accounting','233',NULL,'{\"entry_number\":\"JE-20261013-0233\",\"source_type\":\"employee_advance\",\"source_id\":24,\"total_amount\":1500}','CLI','CLI','2026-10-13 21:59:26'),(999,1,NULL,'EMPLOYEE_ADVANCE_DISBURSED','payroll','24',NULL,'{\"advance_no\":\"ADV-202610-8455\",\"employee_id\":9,\"amount\":1500,\"payment_method\":\"Cash Drawer\",\"cr_account_id\":49,\"journal_id\":233}','CLI','CLI','2026-10-13 21:59:26'),(1000,1,NULL,'JOURNAL_POSTED','accounting','234',NULL,'{\"entry_number\":\"JE-20261013-0234\",\"source_type\":\"reversal\",\"source_id\":233,\"total_amount\":1500}','CLI','CLI','2026-10-13 21:59:26'),(1001,1,NULL,'JOURNAL_REVERSED','accounting','233','{\"entry_number\":\"JE-20261013-0233\"}','{\"reversal_journal_id\":234,\"reason\":\"Cancelled salary advance #ADV-202610-8455: Entered wrong amount by mistake\"}','CLI','CLI','2026-10-13 21:59:26'),(1002,1,NULL,'EMPLOYEE_ADVANCE_CANCELLED','payroll','24','{\"id\":24,\"advance_no\":\"ADV-202610-8455\",\"employee_id\":9,\"branch_id\":1,\"amount\":\"1500.00\",\"recovered_amount\":\"0.00\",\"remaining_amount\":\"1500.00\",\"disbursement_date\":\"2026-10-13\",\"payment_method\":\"Cash Drawer\",\"bank_account_id\":null,\"source_account_id\":49,\"reference_no\":\"REF-TO-CANCEL\",\"recovery_period_id\":null,\"status\":\"active\",\"notes\":\"Accidental disbursement to be cancelled\",\"cancelled_at\":null,\"cancelled_by\":null,\"cancel_reason\":null,\"created_by\":1,\"created_at\":\"2026-10-13 21:59:26\",\"updated_at\":\"2026-10-13 21:59:26\",\"employee_name\":\"Test Advance Employee 706\",\"employee_code\":\"TEST-ADV-8966\"}','{\"advance_no\":\"ADV-202610-8455\",\"cancel_reason\":\"Entered wrong amount by mistake\",\"cancelled_by\":1}','CLI','CLI','2026-10-13 21:59:26'),(1003,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','23',NULL,'{\"period_code\":\"PR-TEST-8966\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"employee_count\":3,\"total_gross\":170000,\"total_net_salary\":144400}','CLI','CLI','2026-10-13 21:59:26'),(1004,1,NULL,'JOURNAL_POSTED','accounting','235',NULL,'{\"entry_number\":\"JE-20261013-0235\",\"source_type\":\"payroll\",\"source_id\":23,\"total_amount\":195500}','CLI','CLI','2026-10-13 21:59:26'),(1005,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','23',NULL,'{\"period_code\":\"PR-TEST-8966\",\"total_gross\":\"170000.00\",\"total_net\":144400,\"total_epf\":34000,\"total_etf\":5100,\"journal_id\":235}','CLI','CLI','2026-10-13 21:59:26'),(1006,1,NULL,'JOURNAL_POSTED','accounting','236',NULL,'{\"entry_number\":\"JE-20261013-0236\",\"source_type\":\"reversal\",\"source_id\":235,\"total_amount\":195500}','CLI','CLI','2026-10-13 21:59:26'),(1007,1,NULL,'JOURNAL_REVERSED','accounting','235','{\"entry_number\":\"JE-20261013-0235\"}','{\"reversal_journal_id\":236,\"reason\":\"Payroll Period Cancelled: Testing payroll cancellation & advance un-recovery\"}','CLI','CLI','2026-10-13 21:59:26'),(1008,1,NULL,'PAYROLL_PERIOD_CANCELLED','payroll','23','{\"id\":23,\"period_code\":\"PR-TEST-8966\",\"period_name\":\"Test Advance Recovery Period Oct 2026\",\"start_date\":\"2026-10-01\",\"end_date\":\"2026-10-31\",\"branch_id\":1,\"status\":\"approved\",\"total_basic\":\"170000.00\",\"total_allowances\":\"0.00\",\"total_commissions\":\"0.00\",\"total_gross\":\"170000.00\",\"total_employee_epf\":\"13600.00\",\"total_employer_epf\":\"20400.00\",\"total_employer_etf\":\"5100.00\",\"total_deductions\":\"25600.00\",\"total_net_salary\":\"144400.00\",\"total_paid\":\"0.00\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-13 21:59:26\",\"notes\":\"Payroll Period for Salary Advance Recovery Test\",\"created_at\":\"2026-10-13 21:59:26\",\"updated_at\":\"2026-10-13 21:59:26\",\"branch_name\":\"Main Branch - Kandy\",\"created_by_name\":\"Super Administrator\",\"approved_by_name\":\"Super Administrator\"}','{\"cancel_reason\":\"Testing payroll cancellation & advance un-recovery\",\"cancelled_by\":1}','CLI','CLI','2026-10-13 21:59:26'),(1009,1,1,'SESSION_EXPIRED','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:04:38'),(1010,1,1,'LOGOUT','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:04:38'),(1011,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:04:41'),(1012,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:04:42'),(1013,1,1,'PAYROLL_ENTRY_UPDATED','payroll','26','{\"id\":26,\"payroll_period_id\":12,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"0.00\",\"total_deductions\":\"5000.01\",\"net_salary\":\"44999.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 21:46:51\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":50000,\"net_salary\":44000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:08:20'),(1014,1,1,'PAYROLL_ENTRY_UPDATED','payroll','27','{\"id\":27,\"payroll_period_id\":12,\"employee_id\":3,\"branch_id\":1,\"basic_salary\":\"60000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"60000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4800.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4800.00\",\"net_salary\":\"55200.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"7200.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1800.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-13 21:46:37\",\"period_status\":\"draft\"}','{\"basic_salary\":60000,\"gross_salary\":60000,\"net_salary\":54999.99}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:08:22'),(1015,1,1,'PAYROLL_ENTRY_UPDATED','payroll','27','{\"id\":27,\"payroll_period_id\":12,\"employee_id\":3,\"branch_id\":1,\"basic_salary\":\"60000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"60000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4800.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"200.01\",\"total_deductions\":\"5000.01\",\"net_salary\":\"54999.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"7200.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1800.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-14 01:08:22\",\"period_status\":\"draft\"}','{\"basic_salary\":60000,\"gross_salary\":60000,\"net_salary\":54999.99}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:08:32'),(1016,1,1,'PAYROLL_ENTRY_UPDATED','payroll','27','{\"id\":27,\"payroll_period_id\":12,\"employee_id\":3,\"branch_id\":1,\"basic_salary\":\"60000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"60000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4800.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"200.01\",\"total_deductions\":\"5000.01\",\"net_salary\":\"54999.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"7200.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1800.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-14 01:08:32\",\"period_status\":\"draft\"}','{\"basic_salary\":60000,\"gross_salary\":60000,\"net_salary\":54999.99}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:08:34'),(1017,1,1,'PAYROLL_ENTRY_UPDATED','payroll','27','{\"id\":27,\"payroll_period_id\":12,\"employee_id\":3,\"branch_id\":1,\"basic_salary\":\"60000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"60000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4800.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"200.01\",\"total_deductions\":\"5000.01\",\"net_salary\":\"54999.99\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"7200.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1800.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-14 01:08:34\",\"period_status\":\"draft\"}','{\"basic_salary\":60000,\"gross_salary\":60000,\"net_salary\":55000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:08:36'),(1018,1,1,'PAYROLL_ENTRY_UPDATED','payroll','26','{\"id\":26,\"payroll_period_id\":12,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"999.99\",\"total_deductions\":\"6000.00\",\"net_salary\":\"44000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-14 01:08:20\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":51000,\"net_salary\":44920}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:09:02'),(1019,1,1,'PAYROLL_ENTRY_UPDATED','payroll','26','{\"id\":26,\"payroll_period_id\":12,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"1000.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"51000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4080.00\",\"salary_advances_deducted\":\"1000.01\",\"other_deductions\":\"999.99\",\"total_deductions\":\"6080.00\",\"net_salary\":\"44920.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6120.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1530.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-14 01:09:02\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":51000,\"net_salary\":45900}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:09:34'),(1020,1,1,'PAYROLL_ENTRY_UPDATED','payroll','27','{\"id\":27,\"payroll_period_id\":12,\"employee_id\":3,\"branch_id\":1,\"basic_salary\":\"60000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"60000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4800.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"200.00\",\"total_deductions\":\"5000.00\",\"net_salary\":\"55000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"7200.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1800.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-13 20:12:20\",\"updated_at\":\"2026-10-14 01:08:36\",\"period_status\":\"draft\"}','{\"basic_salary\":60000,\"gross_salary\":60000,\"net_salary\":54100}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:09:44'),(1021,1,1,'JOURNAL_POSTED','accounting','237',NULL,'{\"entry_number\":\"JE-20261013-0237\",\"source_type\":\"payroll\",\"source_id\":12,\"total_amount\":127650}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:10:23'),(1022,1,1,'PAYROLL_PERIOD_APPROVED','payroll','12',NULL,'{\"period_code\":\"2026-10\",\"total_gross\":\"111000.00\",\"total_net\":100000,\"total_epf\":22200,\"total_etf\":3330,\"journal_id\":237}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:10:23'),(1023,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','24',NULL,'{\"period_code\":\"TEST-PAY-1791921034\",\"period_name\":\"CLI Period Payment Test\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-14 01:20:34'),(1024,1,NULL,'JOURNAL_POSTED','accounting','238',NULL,'{\"entry_number\":\"JE-20261013-0238\",\"source_type\":\"payroll\",\"source_id\":24,\"total_amount\":126500}','CLI','CLI','2026-10-14 01:20:34'),(1025,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','24',NULL,'{\"period_code\":\"TEST-PAY-1791921034\",\"total_gross\":\"110000.00\",\"total_net\":101200,\"total_epf\":22000,\"total_etf\":3300,\"journal_id\":238}','CLI','CLI','2026-10-14 01:20:34'),(1026,1,NULL,'JOURNAL_POSTED','accounting','239',NULL,'{\"entry_number\":\"JE-20261013-0239\",\"source_type\":\"payroll_payment\",\"source_id\":16,\"total_amount\":101200}','CLI','CLI','2026-10-14 01:20:34'),(1027,1,NULL,'PAYROLL_PERIOD_PAYMENT_RECORDED','payroll','24',NULL,'{\"payment_no\":\"PAY-SAL-202610-7451\",\"period_id\":24,\"amount\":101200,\"splits\":2}','CLI','CLI','2026-10-14 01:20:34'),(1028,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','25',NULL,'{\"period_code\":\"TEST-PAY-1791921125\",\"period_name\":\"CLI Period Payment Test\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-14 01:22:05'),(1029,1,NULL,'JOURNAL_POSTED','accounting','240',NULL,'{\"entry_number\":\"JE-20261013-0240\",\"source_type\":\"payroll\",\"source_id\":25,\"total_amount\":126500}','CLI','CLI','2026-10-14 01:22:05'),(1030,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','25',NULL,'{\"period_code\":\"TEST-PAY-1791921125\",\"total_gross\":\"110000.00\",\"total_net\":101200,\"total_epf\":22000,\"total_etf\":3300,\"journal_id\":240}','CLI','CLI','2026-10-14 01:22:05'),(1031,1,NULL,'JOURNAL_POSTED','accounting','241',NULL,'{\"entry_number\":\"JE-20261013-0241\",\"source_type\":\"payroll_payment\",\"source_id\":18,\"total_amount\":101200}','CLI','CLI','2026-10-14 01:22:05'),(1032,1,NULL,'PAYROLL_PERIOD_PAYMENT_RECORDED','payroll','25',NULL,'{\"payment_no\":\"PAY-SAL-202610-5368\",\"period_id\":25,\"amount\":101200,\"splits\":2}','CLI','CLI','2026-10-14 01:22:05'),(1033,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','26',NULL,'{\"period_code\":\"TEST-PAY-1791921144\",\"period_name\":\"CLI Period Payment Test\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-14 01:22:24'),(1034,1,NULL,'JOURNAL_POSTED','accounting','242',NULL,'{\"entry_number\":\"JE-20261013-0238\",\"source_type\":\"payroll\",\"source_id\":26,\"total_amount\":126500}','CLI','CLI','2026-10-14 01:22:25'),(1035,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','26',NULL,'{\"period_code\":\"TEST-PAY-1791921144\",\"total_gross\":\"110000.00\",\"total_net\":101200,\"total_epf\":22000,\"total_etf\":3300,\"journal_id\":242}','CLI','CLI','2026-10-14 01:22:25'),(1036,1,NULL,'JOURNAL_POSTED','accounting','243',NULL,'{\"entry_number\":\"JE-20261013-0243\",\"source_type\":\"payroll_payment\",\"source_id\":20,\"total_amount\":101200}','CLI','CLI','2026-10-14 01:22:25'),(1037,1,NULL,'PAYROLL_PERIOD_PAYMENT_RECORDED','payroll','26',NULL,'{\"payment_no\":\"PAY-SAL-202610-4785\",\"period_id\":26,\"amount\":101200,\"splits\":2}','CLI','CLI','2026-10-14 01:22:25'),(1038,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','27',NULL,'{\"period_code\":\"TEST-EPF-202610-299\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-14 01:22:35'),(1039,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','57','{\"id\":57,\"payroll_period_id\":27,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4000.00\",\"net_salary\":\"46000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-14 01:22:35\",\"updated_at\":\"2026-10-14 01:22:35\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":60900}','CLI','CLI','2026-10-14 01:22:35'),(1040,NULL,NULL,'JOURNAL_POSTED','accounting','244',NULL,'{\"entry_number\":\"JE-20261013-0238\",\"source_type\":\"payroll\",\"source_id\":27,\"total_amount\":149500}','CLI','CLI','2026-10-14 01:22:35'),(1041,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','27',NULL,'{\"period_code\":\"TEST-EPF-202610-299\",\"total_gross\":\"130000.00\",\"total_net\":116100,\"total_epf\":26000,\"total_etf\":3900,\"journal_id\":244}','CLI','CLI','2026-10-14 01:22:35'),(1042,NULL,NULL,'JOURNAL_POSTED','accounting','245',NULL,'{\"entry_number\":\"JE-20261013-0245\",\"source_type\":\"payroll_payment\",\"source_id\":22,\"total_amount\":60900}','CLI','CLI','2026-10-14 01:22:35'),(1043,NULL,NULL,'PAYROLL_PAYMENT_RECORDED','payroll','22',NULL,'{\"payment_no\":\"PAY-SAL-202610-2600\",\"employee_id\":2,\"amount\":60900,\"splits\":2}','CLI','CLI','2026-10-14 01:22:35'),(1044,NULL,NULL,'JOURNAL_POSTED','accounting','246',NULL,'{\"entry_number\":\"JE-20261013-0246\",\"source_type\":\"epf_etf_remittance\",\"source_id\":10,\"total_amount\":16100}','CLI','CLI','2026-10-14 01:22:35'),(1045,NULL,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','10',NULL,'{\"remittance_no\":\"REMIT-202610-5013\",\"period_id\":27,\"total_amount\":16100}','CLI','CLI','2026-10-14 01:22:35'),(1046,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','28',NULL,'{\"period_code\":\"TEST-PAY-1791921315\",\"period_name\":\"CLI Period Payment Test\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-14 01:25:15'),(1047,1,NULL,'JOURNAL_POSTED','accounting','247',NULL,'{\"entry_number\":\"JE-20261013-0247\",\"source_type\":\"payroll\",\"source_id\":28,\"total_amount\":126500}','CLI','CLI','2026-10-14 01:25:15'),(1048,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','28',NULL,'{\"period_code\":\"TEST-PAY-1791921315\",\"total_gross\":\"110000.00\",\"total_net\":101200,\"total_epf\":22000,\"total_etf\":3300,\"journal_id\":247}','CLI','CLI','2026-10-14 01:25:15'),(1049,1,NULL,'JOURNAL_POSTED','accounting','248',NULL,'{\"entry_number\":\"JE-20261013-0248\",\"source_type\":\"payroll_payment\",\"source_id\":24,\"total_amount\":101200}','CLI','CLI','2026-10-14 01:25:15'),(1050,1,NULL,'PAYROLL_PERIOD_PAYMENT_RECORDED','payroll','28',NULL,'{\"payment_no\":\"PAY-SAL-202610-9192\",\"period_id\":28,\"amount\":101200,\"splits\":2}','CLI','CLI','2026-10-14 01:25:15'),(1051,1,1,'JOURNAL_POSTED','accounting','249',NULL,'{\"entry_number\":\"JE-20261013-0247\",\"source_type\":\"payroll_payment\",\"source_id\":26,\"total_amount\":100000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:31:18'),(1052,1,1,'PAYROLL_PERIOD_PAYMENT_RECORDED','payroll','12',NULL,'{\"payment_no\":\"PAY-SAL-202610-7632\",\"period_id\":12,\"amount\":100000,\"splits\":7}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:31:18'),(1053,1,1,'BANK_RECONCILIATION_COMPLETED','banking','3',NULL,'{\"reconciliation_no\":\"BREC-0001\",\"bank_account_id\":1,\"cleared_balance\":\"500.00\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:33:36'),(1054,1,1,'BANK_RECONCILIATION_STARTED','banking','5',NULL,'{\"reconciliation_no\":\"BREC-0005\",\"bank_account_id\":1,\"statement_end_date\":\"2026-10-31\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:33:45'),(1055,1,1,'PAYROLL_PERIOD_CREATED','payroll','29',NULL,'{\"period_code\":\"2026-101\",\"period_name\":\"October 2026\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:36:47'),(1056,1,1,'JOURNAL_POSTED','accounting','250',NULL,'{\"entry_number\":\"JE-20261013-0250\",\"source_type\":\"payroll\",\"source_id\":29,\"total_amount\":126500}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:36:52'),(1057,1,1,'PAYROLL_PERIOD_APPROVED','payroll','29',NULL,'{\"period_code\":\"2026-101\",\"total_gross\":\"110000.00\",\"total_net\":101200,\"total_epf\":22000,\"total_etf\":3300,\"journal_id\":250}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:36:52'),(1058,1,1,'JOURNAL_POSTED','accounting','251',NULL,'{\"entry_number\":\"JE-20261013-0251\",\"source_type\":\"epf_etf_remittance\",\"source_id\":11,\"total_amount\":25300}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:39:25'),(1059,1,1,'EPF_ETF_REMITTANCE_RECORDED','payroll','11',NULL,'{\"remittance_no\":\"REMIT-202610-7249\",\"period_id\":29,\"total_amount\":25300}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 01:39:25'),(1060,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','30',NULL,'{\"period_code\":\"CLI-202611\",\"period_name\":\"CLI Remit Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-14 01:55:55'),(1061,NULL,NULL,'JOURNAL_POSTED','accounting','252',NULL,'{\"entry_number\":\"JE-20261013-0252\",\"source_type\":\"payroll\",\"source_id\":30,\"total_amount\":126500}','CLI','CLI','2026-10-14 01:55:55'),(1062,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','30',NULL,'{\"period_code\":\"CLI-202611\",\"total_gross\":\"110000.00\",\"total_net\":101200,\"total_epf\":22000,\"total_etf\":3300,\"journal_id\":252}','CLI','CLI','2026-10-14 01:55:55'),(1063,NULL,NULL,'JOURNAL_POSTED','accounting','253',NULL,'{\"entry_number\":\"JE-20261013-0253\",\"source_type\":\"payroll_payment\",\"source_id\":33,\"total_amount\":101200}','CLI','CLI','2026-10-14 01:55:55'),(1064,NULL,NULL,'PAYROLL_PERIOD_PAYMENT_RECORDED','payroll','30',NULL,'{\"payment_no\":\"PAY-SAL-202610-9980\",\"period_id\":30,\"amount\":101200,\"splits\":1}','CLI','CLI','2026-10-14 01:55:55'),(1065,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','31',NULL,'{\"period_code\":\"CLI-R-1791923201\",\"period_name\":\"CLI Remit Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-14 01:56:41'),(1066,NULL,NULL,'JOURNAL_POSTED','accounting','254',NULL,'{\"entry_number\":\"JE-20261013-0254\",\"source_type\":\"payroll\",\"source_id\":31,\"total_amount\":126500}','CLI','CLI','2026-10-14 01:56:41'),(1067,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','31',NULL,'{\"period_code\":\"CLI-R-1791923201\",\"total_gross\":\"110000.00\",\"total_net\":101200,\"total_epf\":22000,\"total_etf\":3300,\"journal_id\":254}','CLI','CLI','2026-10-14 01:56:41'),(1068,NULL,NULL,'JOURNAL_POSTED','accounting','255',NULL,'{\"entry_number\":\"JE-20261013-0255\",\"source_type\":\"payroll_payment\",\"source_id\":34,\"total_amount\":101200}','CLI','CLI','2026-10-14 01:56:41'),(1069,NULL,NULL,'PAYROLL_PERIOD_PAYMENT_RECORDED','payroll','31',NULL,'{\"payment_no\":\"PAY-SAL-202610-2188\",\"period_id\":31,\"amount\":101200,\"splits\":1}','CLI','CLI','2026-10-14 01:56:41'),(1070,NULL,NULL,'JOURNAL_POSTED','accounting','256',NULL,'{\"entry_number\":\"JE-20261013-0256\",\"source_type\":\"epf_etf_remittance\",\"source_id\":12,\"total_amount\":25300}','CLI','CLI','2026-10-14 01:56:41'),(1071,NULL,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','12',NULL,'{\"remittance_no\":\"REMIT-202610-9641\",\"period_id\":31,\"total_amount\":25300}','CLI','CLI','2026-10-14 01:56:41'),(1072,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','32',NULL,'{\"period_code\":\"TEST-PAY-1791923209\",\"period_name\":\"CLI Period Payment Test\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-14 01:56:49'),(1073,1,NULL,'JOURNAL_POSTED','accounting','257',NULL,'{\"entry_number\":\"JE-20261013-0252\",\"source_type\":\"payroll\",\"source_id\":32,\"total_amount\":126500}','CLI','CLI','2026-10-14 01:56:49'),(1074,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','32',NULL,'{\"period_code\":\"TEST-PAY-1791923209\",\"total_gross\":\"110000.00\",\"total_net\":101200,\"total_epf\":22000,\"total_etf\":3300,\"journal_id\":257}','CLI','CLI','2026-10-14 01:56:49'),(1075,1,NULL,'JOURNAL_POSTED','accounting','258',NULL,'{\"entry_number\":\"JE-20261013-0258\",\"source_type\":\"payroll_payment\",\"source_id\":35,\"total_amount\":101200}','CLI','CLI','2026-10-14 01:56:49'),(1076,1,NULL,'PAYROLL_PERIOD_PAYMENT_RECORDED','payroll','32',NULL,'{\"payment_no\":\"PAY-SAL-202610-3693\",\"period_id\":32,\"amount\":101200,\"splits\":2}','CLI','CLI','2026-10-14 01:56:49'),(1077,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','33',NULL,'{\"period_code\":\"TEST-EPF-202610-976\",\"period_name\":\"Sri Lanka Labor Law Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-14 01:56:50'),(1078,NULL,NULL,'PAYROLL_ENTRY_UPDATED','payroll','69','{\"id\":69,\"payroll_period_id\":33,\"employee_id\":2,\"branch_id\":1,\"basic_salary\":\"50000.00\",\"allowances\":\"0.00\",\"bonuses\":\"0.00\",\"commissions\":\"0.00\",\"overtime_amount\":\"0.00\",\"gross_salary\":\"50000.00\",\"employee_epf_rate\":\"8.00\",\"employee_epf_amount\":\"4000.00\",\"salary_advances_deducted\":\"0.00\",\"other_deductions\":\"0.00\",\"total_deductions\":\"4000.00\",\"net_salary\":\"46000.00\",\"employer_epf_rate\":\"12.00\",\"employer_epf_amount\":\"6000.00\",\"employer_etf_rate\":\"3.00\",\"employer_etf_amount\":\"1500.00\",\"paid_amount\":\"0.00\",\"status\":\"draft\",\"notes\":null,\"created_at\":\"2026-10-14 01:56:50\",\"updated_at\":\"2026-10-14 01:56:50\",\"period_status\":\"draft\"}','{\"basic_salary\":50000,\"gross_salary\":70000,\"net_salary\":60900}','CLI','CLI','2026-10-14 01:56:50'),(1079,NULL,NULL,'JOURNAL_POSTED','accounting','259',NULL,'{\"entry_number\":\"JE-20261013-0252\",\"source_type\":\"payroll\",\"source_id\":33,\"total_amount\":149500}','CLI','CLI','2026-10-14 01:56:50'),(1080,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','33',NULL,'{\"period_code\":\"TEST-EPF-202610-976\",\"total_gross\":\"130000.00\",\"total_net\":116100,\"total_epf\":26000,\"total_etf\":3900,\"journal_id\":259}','CLI','CLI','2026-10-14 01:56:50'),(1081,NULL,NULL,'JOURNAL_POSTED','accounting','260',NULL,'{\"entry_number\":\"JE-20261013-0260\",\"source_type\":\"payroll_payment\",\"source_id\":37,\"total_amount\":60900}','CLI','CLI','2026-10-14 01:56:50'),(1082,NULL,NULL,'PAYROLL_PAYMENT_RECORDED','payroll','37',NULL,'{\"payment_no\":\"PAY-SAL-202610-8635\",\"employee_id\":2,\"amount\":60900,\"splits\":2}','CLI','CLI','2026-10-14 01:56:50'),(1083,NULL,NULL,'JOURNAL_POSTED','accounting','261',NULL,'{\"entry_number\":\"JE-20261013-0261\",\"source_type\":\"epf_etf_remittance\",\"source_id\":13,\"total_amount\":16100}','CLI','CLI','2026-10-14 01:56:50'),(1084,NULL,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','13',NULL,'{\"remittance_no\":\"REMIT-202610-2253\",\"period_id\":33,\"total_amount\":16100}','CLI','CLI','2026-10-14 01:56:50'),(1085,NULL,NULL,'print_barcode_labels','inventory','0','[]','{\"product_id\":12,\"custom_price\":9999,\"total_labels\":50,\"branch_id\":1}','CLI','CLI','2026-10-14 02:08:11'),(1086,1,NULL,'PAYROLL_PERIOD_CREATED','payroll','34',NULL,'{\"period_code\":\"TEST-PAY-1791923899\",\"period_name\":\"CLI Period Payment Test\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-14 02:08:19'),(1087,1,NULL,'JOURNAL_POSTED','accounting','262',NULL,'{\"entry_number\":\"JE-20261013-0262\",\"source_type\":\"payroll\",\"source_id\":34,\"total_amount\":126500}','CLI','CLI','2026-10-14 02:08:19'),(1088,1,NULL,'PAYROLL_PERIOD_APPROVED','payroll','34',NULL,'{\"period_code\":\"TEST-PAY-1791923899\",\"total_gross\":\"110000.00\",\"total_net\":101200,\"total_epf\":22000,\"total_etf\":3300,\"journal_id\":262}','CLI','CLI','2026-10-14 02:08:19'),(1089,1,NULL,'JOURNAL_POSTED','accounting','263',NULL,'{\"entry_number\":\"JE-20261013-0263\",\"source_type\":\"payroll_payment\",\"source_id\":39,\"total_amount\":101200}','CLI','CLI','2026-10-14 02:08:19'),(1090,1,NULL,'PAYROLL_PERIOD_PAYMENT_RECORDED','payroll','34',NULL,'{\"payment_no\":\"PAY-SAL-202610-4198\",\"period_id\":34,\"amount\":101200,\"splits\":2}','CLI','CLI','2026-10-14 02:08:19'),(1091,NULL,NULL,'PAYROLL_PERIOD_CREATED','payroll','35',NULL,'{\"period_code\":\"CLI-R-1791923899\",\"period_name\":\"CLI Remit Test Period\",\"employee_count\":2,\"total_gross\":110000,\"total_net_salary\":101200}','CLI','CLI','2026-10-14 02:08:19'),(1092,NULL,NULL,'JOURNAL_POSTED','accounting','264',NULL,'{\"entry_number\":\"JE-20261013-0262\",\"source_type\":\"payroll\",\"source_id\":35,\"total_amount\":126500}','CLI','CLI','2026-10-14 02:08:19'),(1093,NULL,NULL,'PAYROLL_PERIOD_APPROVED','payroll','35',NULL,'{\"period_code\":\"CLI-R-1791923899\",\"total_gross\":\"110000.00\",\"total_net\":101200,\"total_epf\":22000,\"total_etf\":3300,\"journal_id\":264}','CLI','CLI','2026-10-14 02:08:19'),(1094,NULL,NULL,'JOURNAL_POSTED','accounting','265',NULL,'{\"entry_number\":\"JE-20261013-0265\",\"source_type\":\"payroll_payment\",\"source_id\":41,\"total_amount\":101200}','CLI','CLI','2026-10-14 02:08:19'),(1095,NULL,NULL,'PAYROLL_PERIOD_PAYMENT_RECORDED','payroll','35',NULL,'{\"payment_no\":\"PAY-SAL-202610-8058\",\"period_id\":35,\"amount\":101200,\"splits\":1}','CLI','CLI','2026-10-14 02:08:19'),(1096,NULL,NULL,'JOURNAL_POSTED','accounting','266',NULL,'{\"entry_number\":\"JE-20261013-0266\",\"source_type\":\"epf_etf_remittance\",\"source_id\":14,\"total_amount\":25300}','CLI','CLI','2026-10-14 02:08:19'),(1097,NULL,NULL,'EPF_ETF_REMITTANCE_RECORDED','payroll','14',NULL,'{\"remittance_no\":\"REMIT-202610-7248\",\"period_id\":35,\"total_amount\":25300}','CLI','CLI','2026-10-14 02:08:19'),(1098,1,1,'print_barcode_labels','inventory','0','[]','{\"total_labels\":10,\"items_count\":1,\"branch_id\":1,\"timestamp\":\"2026-10-13 22:42:19\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 02:12:19'),(1099,1,1,'print_barcode_labels','inventory','0','[]','{\"total_labels\":209,\"items_count\":2,\"branch_id\":1,\"timestamp\":\"2026-10-13 22:43:04\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 02:13:04'),(1100,1,1,'print_barcode_labels','inventory','0','[]','{\"total_labels\":4,\"items_count\":2,\"branch_id\":1,\"timestamp\":\"2026-10-13 22:43:24\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 02:13:24'),(1101,1,1,'update_label_settings','inventory','0','[]','{\"label_printer_model\":\"TSC TE244\",\"label_width_mm\":\"25\",\"label_height_mm\":\"10\",\"labels_per_row\":\"2\",\"label_column_gap_mm\":\"2\",\"label_row_gap_mm\":\"2\",\"label_margin_top_mm\":\"0\",\"label_margin_left_mm\":\"0\",\"label_rotation_deg\":\"0\",\"label_shop_name_custom\":\"Kandy Kids\",\"label_price_prefix\":\"Rs.\",\"label_show_shop_name\":\"1\",\"label_show_product_code\":\"1\",\"label_show_price\":\"1\",\"label_barcode_height_mm\":\"12\",\"label_font_size_name_pt\":\"8\",\"label_font_size_price_pt\":\"11\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 02:15:31'),(1102,1,1,'update_label_settings','inventory','0','[]','{\"label_printer_model\":\"TSC TE244\",\"label_width_mm\":\"25\",\"label_height_mm\":\"40\",\"labels_per_row\":\"2\",\"label_column_gap_mm\":\"2\",\"label_row_gap_mm\":\"2\",\"label_margin_top_mm\":\"0\",\"label_margin_left_mm\":\"0\",\"label_rotation_deg\":\"0\",\"label_shop_name_custom\":\"Kandy Kids\",\"label_price_prefix\":\"Rs.\",\"label_show_shop_name\":\"1\",\"label_show_product_code\":\"1\",\"label_show_price\":\"1\",\"label_barcode_height_mm\":\"12\",\"label_font_size_name_pt\":\"8\",\"label_font_size_price_pt\":\"11\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 02:15:42'),(1103,NULL,NULL,'print_barcode_labels','inventory','0','[]','{\"product_id\":12,\"custom_price\":9999,\"total_labels\":50,\"branch_id\":1}','CLI','CLI','2026-10-14 02:24:56'),(1104,NULL,NULL,'print_barcode_labels','inventory','0','[]','{\"product_id\":12,\"custom_price\":9999,\"total_labels\":50,\"branch_id\":1}','CLI','CLI','2026-10-14 02:29:36'),(1105,NULL,NULL,'print_barcode_labels','inventory','0','[]','{\"product_id\":12,\"custom_price\":9999,\"total_labels\":50,\"branch_id\":1}','CLI','CLI','2026-10-14 02:33:33'),(1106,1,1,'print_barcode_labels','inventory','0','[]','{\"total_labels\":10,\"items_count\":1,\"branch_id\":1,\"timestamp\":\"2026-10-13 23:04:26\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 02:34:26'),(1107,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 11:07:22'),(1108,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 11:07:24'),(1109,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 11:07:26'),(1110,NULL,NULL,'STOCK_TRANSFER_CREATED','inventory','1',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:48:11'),(1111,NULL,NULL,'STOCK_TRANSFER_UPDATED','inventory','1',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:48:11'),(1112,NULL,NULL,'STOCK_TRANSFER_SENT','inventory','1',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:48:11'),(1113,NULL,NULL,'STOCK_TRANSFER_RECEIVED','inventory','1',NULL,'{\"transfer_number\":\"TRF-0001\",\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:48:11'),(1114,NULL,NULL,'STOCK_TRANSFER_CREATED','inventory','2',NULL,'{\"transfer_number\":\"TRF-0002\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:48:35'),(1115,NULL,NULL,'STOCK_TRANSFER_UPDATED','inventory','2',NULL,'{\"transfer_number\":\"TRF-0002\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:48:35'),(1116,NULL,NULL,'STOCK_TRANSFER_SENT','inventory','2',NULL,'{\"transfer_number\":\"TRF-0002\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:48:35'),(1117,NULL,NULL,'STOCK_TRANSFER_RECEIVED','inventory','2',NULL,'{\"transfer_number\":\"TRF-0002\",\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:48:35'),(1118,NULL,NULL,'STOCK_TRANSFER_CREATED','inventory','3',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:49:28'),(1119,NULL,NULL,'STOCK_TRANSFER_UPDATED','inventory','3',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:49:28'),(1120,NULL,NULL,'STOCK_TRANSFER_SENT','inventory','3',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:49:28'),(1121,NULL,NULL,'STOCK_TRANSFER_RECEIVED','inventory','3',NULL,'{\"transfer_number\":\"TRF-0001\",\"to_branch_id\":2}','CLI','CLI','2026-10-14 11:49:28'),(1122,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:03:59'),(1123,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:04:04'),(1124,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:07:19'),(1125,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:07:21'),(1126,NULL,NULL,'STOCK_TRANSFER_CREATED','inventory','4',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:19:15'),(1127,NULL,NULL,'STOCK_TRANSFER_UPDATED','inventory','4',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:19:15'),(1128,NULL,NULL,'STOCK_TRANSFER_SENT','inventory','4',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:19:15'),(1129,NULL,NULL,'STOCK_TRANSFER_RECEIVED','inventory','4',NULL,'{\"transfer_number\":\"TRF-0001\",\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:19:15'),(1130,NULL,NULL,'STOCK_TRANSFER_CREATED','inventory','5',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:19:43'),(1131,NULL,NULL,'STOCK_TRANSFER_CREATED','inventory','6',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:19:56'),(1132,NULL,NULL,'STOCK_TRANSFER_UPDATED','inventory','6',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:19:56'),(1133,NULL,NULL,'STOCK_TRANSFER_SENT','inventory','6',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:19:56'),(1134,NULL,NULL,'STOCK_TRANSFER_RECEIVED','inventory','6',NULL,'{\"transfer_number\":\"TRF-0001\",\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:19:56'),(1135,NULL,NULL,'STOCK_TRANSFER_CREATED','inventory','7',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:26:20'),(1136,NULL,NULL,'STOCK_TRANSFER_UPDATED','inventory','7',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:26:20'),(1137,NULL,NULL,'STOCK_TRANSFER_SENT','inventory','7',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:26:20'),(1138,NULL,NULL,'STOCK_TRANSFER_RECEIVED','inventory','7',NULL,'{\"transfer_number\":\"TRF-0001\",\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:26:20'),(1139,1,1,'STOCK_TRANSFER_CREATED','inventory','8',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:31:21'),(1140,1,1,'STOCK_TRANSFER_SENT','inventory','8',NULL,'{\"transfer_number\":\"TRF-0001\",\"from_branch_id\":1,\"to_branch_id\":2}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:31:21'),(1141,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:31:38'),(1142,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:31:41'),(1143,NULL,NULL,'STOCK_TRANSFER_CREATED','inventory','9',NULL,'{\"transfer_number\":\"TRF-0009\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:36:51'),(1144,NULL,NULL,'STOCK_TRANSFER_UPDATED','inventory','9',NULL,'{\"transfer_number\":\"TRF-0009\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:36:51'),(1145,NULL,NULL,'STOCK_TRANSFER_SENT','inventory','9',NULL,'{\"transfer_number\":\"TRF-0009\",\"from_branch_id\":1,\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:36:51'),(1146,NULL,NULL,'STOCK_TRANSFER_RECEIVED','inventory','9',NULL,'{\"transfer_number\":\"TRF-0009\",\"to_branch_id\":2}','CLI','CLI','2026-10-14 12:36:51'),(1147,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:37:55'),(1148,1,2,'STOCK_TRANSFER_RECEIVED','inventory','8',NULL,'{\"transfer_number\":\"TRF-0001\",\"to_branch_id\":2}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:40:38'),(1149,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:40:46'),(1150,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:40:57'),(1151,1,2,'BRANCH_SWITCH','auth','all','{\"from\":\"Colombo Branch\"}','{\"to\":\"All Branches\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:41:00'),(1152,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Colombo Branch\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:41:17'),(1153,1,2,'BRANCH_SWITCH','auth','all','{\"from\":\"Colombo Branch\"}','{\"to\":\"All Branches\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:41:22'),(1154,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Colombo Branch\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 12:41:26'),(1155,1,NULL,'JOURNAL_POSTED','accounting','267',NULL,'{\"entry_number\":\"JE-20261014-0001\",\"source_type\":\"customer_collection\",\"source_id\":12,\"total_amount\":1500}','CLI','CLI','2026-10-14 13:13:46'),(1156,1,NULL,'JOURNAL_POSTED','accounting','268',NULL,'{\"entry_number\":\"JE-20261014-0268\",\"source_type\":\"customer_collection\",\"source_id\":13,\"total_amount\":1000}','CLI','CLI','2026-10-14 13:13:46'),(1157,1,NULL,'JOURNAL_POSTED','accounting','269',NULL,'{\"entry_number\":\"JE-20261014-0269\",\"source_type\":\"customer_collection\",\"source_id\":14,\"total_amount\":500}','CLI','CLI','2026-10-14 13:13:46'),(1158,1,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','29',NULL,'{\"payment_no\":\"CPAY-0001\",\"customer_name\":\"Split Test Customer\",\"previous_balance\":3000,\"new_balance\":0,\"amount_paid\":3000,\"splits_count\":3}','CLI','CLI','2026-10-14 13:13:46'),(1159,1,NULL,'JOURNAL_POSTED','accounting','270',NULL,'{\"entry_number\":\"JE-20261014-0270\",\"source_type\":\"customer_collection\",\"source_id\":15,\"total_amount\":1500}','CLI','CLI','2026-10-14 13:14:14'),(1160,1,NULL,'JOURNAL_POSTED','accounting','271',NULL,'{\"entry_number\":\"JE-20261014-0271\",\"source_type\":\"customer_collection\",\"source_id\":16,\"total_amount\":1000}','CLI','CLI','2026-10-14 13:14:14'),(1161,1,NULL,'JOURNAL_POSTED','accounting','272',NULL,'{\"entry_number\":\"JE-20261014-0272\",\"source_type\":\"customer_collection\",\"source_id\":17,\"total_amount\":500}','CLI','CLI','2026-10-14 13:14:14'),(1162,1,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','30',NULL,'{\"payment_no\":\"CPAY-0002\",\"customer_name\":\"Split Test Customer\",\"previous_balance\":3000,\"new_balance\":0,\"amount_paid\":3000,\"splits_count\":3}','CLI','CLI','2026-10-14 13:14:14'),(1163,1,NULL,'JOURNAL_POSTED','accounting','273',NULL,'{\"entry_number\":\"JE-20261014-0273\",\"source_type\":\"customer_collection\",\"source_id\":18,\"total_amount\":1500}','CLI','CLI','2026-10-14 13:14:57'),(1164,1,NULL,'JOURNAL_POSTED','accounting','274',NULL,'{\"entry_number\":\"JE-20261014-0274\",\"source_type\":\"customer_collection\",\"source_id\":19,\"total_amount\":1000}','CLI','CLI','2026-10-14 13:14:57'),(1165,1,NULL,'JOURNAL_POSTED','accounting','275',NULL,'{\"entry_number\":\"JE-20261014-0275\",\"source_type\":\"customer_collection\",\"source_id\":20,\"total_amount\":500}','CLI','CLI','2026-10-14 13:14:57'),(1166,1,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','31',NULL,'{\"payment_no\":\"CPAY-0001\",\"customer_name\":\"Split Test Customer\",\"previous_balance\":3000,\"new_balance\":0,\"amount_paid\":3000,\"splits_count\":3}','CLI','CLI','2026-10-14 13:14:57'),(1167,NULL,NULL,'JOURNAL_POSTED','accounting','276',NULL,'{\"entry_number\":\"JE-20261014-0276\",\"source_type\":\"customer_collection\",\"source_id\":21,\"total_amount\":1000}','CLI','CLI','2026-10-14 13:15:14'),(1168,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','32',NULL,'{\"payment_no\":\"CPAY-0001\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":15000,\"new_balance\":14000,\"amount_paid\":1000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:15:14'),(1169,NULL,NULL,'JOURNAL_POSTED','accounting','277',NULL,'{\"entry_number\":\"JE-20261014-0277\",\"source_type\":\"customer_collection\",\"source_id\":22,\"total_amount\":1000}','CLI','CLI','2026-10-14 13:15:14'),(1170,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','32',NULL,'{\"payment_no\":\"CPAY-0002\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":14000,\"new_balance\":13000,\"amount_paid\":1000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:15:14'),(1171,NULL,NULL,'JOURNAL_POSTED','accounting','278',NULL,'{\"entry_number\":\"JE-20261014-0278\",\"source_type\":\"customer_collection\",\"source_id\":23,\"total_amount\":3000}','CLI','CLI','2026-10-14 13:15:14'),(1172,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','33',NULL,'{\"payment_no\":\"CPAY-0003\",\"customer_name\":\"Sec7 Test Inactive Customer\",\"previous_balance\":8000,\"new_balance\":5000,\"amount_paid\":3000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:15:14'),(1173,NULL,NULL,'JOURNAL_POSTED','accounting','279',NULL,'{\"entry_number\":\"JE-20261014-0279\",\"source_type\":\"customer_collection\",\"source_id\":24,\"total_amount\":4000}','CLI','CLI','2026-10-14 13:15:14'),(1174,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','32',NULL,'{\"payment_no\":\"CPAY-0004\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":13000,\"new_balance\":9000,\"amount_paid\":4000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:15:14'),(1175,NULL,NULL,'JOURNAL_POSTED','accounting','280',NULL,'{\"entry_number\":\"JE-20261014-0280\",\"source_type\":\"customer_collection\",\"source_id\":25,\"total_amount\":2000}','CLI','CLI','2026-10-14 13:15:14'),(1176,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','32',NULL,'{\"payment_no\":\"CPAY-0005\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":9000,\"new_balance\":7000,\"amount_paid\":2000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:15:14'),(1177,NULL,NULL,'JOURNAL_POSTED','accounting','281',NULL,'{\"entry_number\":\"JE-20261014-0281\",\"source_type\":\"customer_collection\",\"source_id\":26,\"total_amount\":3000}','CLI','CLI','2026-10-14 13:15:14'),(1178,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','32',NULL,'{\"payment_no\":\"CPAY-0006\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":7000,\"new_balance\":4000,\"amount_paid\":3000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:15:14'),(1179,NULL,NULL,'JOURNAL_POSTED','accounting','282',NULL,'{\"entry_number\":\"JE-20261014-0282\",\"source_type\":\"customer_collection\",\"source_id\":27,\"total_amount\":4000}','CLI','CLI','2026-10-14 13:15:14'),(1180,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','32',NULL,'{\"payment_no\":\"CPAY-0007\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":4000,\"new_balance\":0,\"amount_paid\":4000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:15:14'),(1181,NULL,NULL,'COA_ACCOUNT_CREATED','accounting','65',NULL,'{\"code\":\"1026\",\"name\":\"Sec7 Test Bank - Sec7 Test Account (S7-ACC-TEST-99)\",\"type\":\"asset\"}','CLI','CLI','2026-10-14 13:15:14'),(1182,NULL,NULL,'BANK_ACCOUNT_CREATED','banking','5',NULL,'{\"bank_name\":\"Sec7 Test Bank\",\"account_number\":\"S7-ACC-TEST-99\",\"opening_balance\":0}','CLI','CLI','2026-10-14 13:15:14'),(1183,1,NULL,'JOURNAL_POSTED','accounting','283',NULL,'{\"entry_number\":\"JE-20261014-0283\",\"source_type\":\"customer_collection\",\"source_id\":28,\"total_amount\":1500}','CLI','CLI','2026-10-14 13:20:14'),(1184,1,NULL,'JOURNAL_POSTED','accounting','284',NULL,'{\"entry_number\":\"JE-20261014-0284\",\"source_type\":\"customer_collection\",\"source_id\":29,\"total_amount\":1000}','CLI','CLI','2026-10-14 13:20:14'),(1185,1,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','36',NULL,'{\"payment_no\":\"CPAY-0008\",\"customer_name\":\"Split Test Customer\",\"previous_balance\":3000,\"new_balance\":0,\"amount_paid\":3000,\"splits_count\":3}','CLI','CLI','2026-10-14 13:20:14'),(1186,NULL,NULL,'JOURNAL_POSTED','accounting','285',NULL,'{\"entry_number\":\"JE-20261014-0285\",\"source_type\":\"customer_collection\",\"source_id\":31,\"total_amount\":3000}','CLI','CLI','2026-10-14 13:20:20'),(1187,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','38',NULL,'{\"payment_no\":\"CPAY-0001\",\"customer_name\":\"Sec7 Test Inactive Customer\",\"previous_balance\":8000,\"new_balance\":5000,\"amount_paid\":3000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:20:20'),(1188,NULL,NULL,'JOURNAL_POSTED','accounting','286',NULL,'{\"entry_number\":\"JE-20261014-0286\",\"source_type\":\"customer_collection\",\"source_id\":32,\"total_amount\":4000}','CLI','CLI','2026-10-14 13:20:20'),(1189,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','37',NULL,'{\"payment_no\":\"CPAY-0002\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":15000,\"new_balance\":11000,\"amount_paid\":4000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:20:20'),(1190,NULL,NULL,'JOURNAL_POSTED','accounting','287',NULL,'{\"entry_number\":\"JE-20261014-0287\",\"source_type\":\"customer_collection\",\"source_id\":33,\"total_amount\":2000}','CLI','CLI','2026-10-14 13:20:20'),(1191,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','37',NULL,'{\"payment_no\":\"CPAY-0003\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":11000,\"new_balance\":9000,\"amount_paid\":2000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:20:20'),(1192,NULL,NULL,'JOURNAL_POSTED','accounting','288',NULL,'{\"entry_number\":\"JE-20261014-0288\",\"source_type\":\"customer_collection\",\"source_id\":34,\"total_amount\":3000}','CLI','CLI','2026-10-14 13:20:20'),(1193,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','37',NULL,'{\"payment_no\":\"CPAY-0004\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":9000,\"new_balance\":6000,\"amount_paid\":3000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:20:20'),(1194,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','37',NULL,'{\"payment_no\":\"CPAY-0005\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":6000,\"new_balance\":2000,\"amount_paid\":4000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:20:20'),(1195,NULL,NULL,'CUSTOMER_CHEQUE_RETURNED','customer_cheques','8',NULL,'{\"cheque_no\":\"S7-CHQ-1001\",\"customer_name\":\"Sec7 Test Active Customer\",\"amount\":4000,\"previous_balance\":2000,\"new_balance\":6000,\"reason\":\"Insufficient funds in customer account\"}','CLI','CLI','2026-10-14 13:20:20'),(1196,NULL,NULL,'COA_ACCOUNT_CREATED','accounting','66',NULL,'{\"code\":\"1027\",\"name\":\"Sec7 Test Bank - Sec7 Test Account (S7-ACC-TEST-99)\",\"type\":\"asset\"}','CLI','CLI','2026-10-14 13:20:20'),(1197,NULL,NULL,'BANK_ACCOUNT_CREATED','banking','6',NULL,'{\"bank_name\":\"Sec7 Test Bank\",\"account_number\":\"S7-ACC-TEST-99\",\"opening_balance\":0}','CLI','CLI','2026-10-14 13:20:20'),(1198,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','37',NULL,'{\"payment_no\":\"CPAY-0006\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":6000,\"new_balance\":3000,\"amount_paid\":3000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:20:20'),(1199,NULL,NULL,'JOURNAL_POSTED','accounting','289',NULL,'{\"entry_number\":\"JE-20261014-0289\",\"source_type\":\"customer_cheque_return\",\"source_id\":10,\"total_amount\":3000}','CLI','CLI','2026-10-14 13:20:20'),(1200,NULL,NULL,'CUSTOMER_CHEQUE_RETURNED','customer_cheques','10',NULL,'{\"cheque_no\":\"S7-CHQ-BANKED-1002\",\"customer_name\":\"Sec7 Test Active Customer\",\"amount\":3000,\"previous_balance\":3000,\"new_balance\":6000,\"reason\":\"Cleared cheque bounced post-clearance\"}','CLI','CLI','2026-10-14 13:20:20'),(1201,NULL,NULL,'JOURNAL_POSTED','accounting','290',NULL,'{\"entry_number\":\"JE-20261014-0289\",\"source_type\":\"customer_collection\",\"source_id\":39,\"total_amount\":3000}','CLI','CLI','2026-10-14 13:21:18'),(1202,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','41',NULL,'{\"payment_no\":\"CPAY-0001\",\"customer_name\":\"Sec7 Test Inactive Customer\",\"previous_balance\":8000,\"new_balance\":5000,\"amount_paid\":3000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:21:18'),(1203,NULL,NULL,'JOURNAL_POSTED','accounting','291',NULL,'{\"entry_number\":\"JE-20261014-0291\",\"source_type\":\"customer_collection\",\"source_id\":40,\"total_amount\":4000}','CLI','CLI','2026-10-14 13:21:18'),(1204,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','40',NULL,'{\"payment_no\":\"CPAY-0002\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":15000,\"new_balance\":11000,\"amount_paid\":4000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:21:18'),(1205,NULL,NULL,'JOURNAL_POSTED','accounting','292',NULL,'{\"entry_number\":\"JE-20261014-0292\",\"source_type\":\"customer_collection\",\"source_id\":41,\"total_amount\":2000}','CLI','CLI','2026-10-14 13:21:18'),(1206,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','40',NULL,'{\"payment_no\":\"CPAY-0003\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":11000,\"new_balance\":9000,\"amount_paid\":2000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:21:18'),(1207,NULL,NULL,'JOURNAL_POSTED','accounting','293',NULL,'{\"entry_number\":\"JE-20261014-0293\",\"source_type\":\"customer_collection\",\"source_id\":42,\"total_amount\":3000}','CLI','CLI','2026-10-14 13:21:18'),(1208,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','40',NULL,'{\"payment_no\":\"CPAY-0004\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":9000,\"new_balance\":6000,\"amount_paid\":3000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:21:18'),(1209,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','40',NULL,'{\"payment_no\":\"CPAY-0005\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":6000,\"new_balance\":2000,\"amount_paid\":4000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:21:18'),(1210,NULL,NULL,'CUSTOMER_CHEQUE_RETURNED','customer_cheques','11',NULL,'{\"cheque_no\":\"S7-CHQ-1001\",\"customer_name\":\"Sec7 Test Active Customer\",\"amount\":4000,\"previous_balance\":2000,\"new_balance\":6000,\"reason\":\"Insufficient funds in customer account\"}','CLI','CLI','2026-10-14 13:21:18'),(1211,NULL,NULL,'COA_ACCOUNT_CREATED','accounting','67',NULL,'{\"code\":\"1028\",\"name\":\"Sec7 Test Bank - Sec7 Test Account (S7-ACC-TEST-99)\",\"type\":\"asset\"}','CLI','CLI','2026-10-14 13:21:18'),(1212,NULL,NULL,'BANK_ACCOUNT_CREATED','banking','7',NULL,'{\"bank_name\":\"Sec7 Test Bank\",\"account_number\":\"S7-ACC-TEST-99\",\"opening_balance\":0}','CLI','CLI','2026-10-14 13:21:18'),(1213,NULL,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','40',NULL,'{\"payment_no\":\"CPAY-0006\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":6000,\"new_balance\":3000,\"amount_paid\":3000,\"splits_count\":1}','CLI','CLI','2026-10-14 13:21:18'),(1214,NULL,NULL,'JOURNAL_POSTED','accounting','294',NULL,'{\"entry_number\":\"JE-20261014-0294\",\"source_type\":\"customer_cheque_return\",\"source_id\":13,\"total_amount\":3000}','CLI','CLI','2026-10-14 13:21:18'),(1215,NULL,NULL,'CUSTOMER_CHEQUE_RETURNED','customer_cheques','13',NULL,'{\"cheque_no\":\"S7-CHQ-BANKED-1002\",\"customer_name\":\"Sec7 Test Active Customer\",\"amount\":3000,\"previous_balance\":3000,\"new_balance\":6000,\"reason\":\"Cleared cheque bounced post-clearance\"}','CLI','CLI','2026-10-14 13:21:18'),(1216,1,NULL,'JOURNAL_POSTED','accounting','295',NULL,'{\"entry_number\":\"JE-20261014-0295\",\"source_type\":\"customer_collection\",\"source_id\":47,\"total_amount\":1500}','CLI','CLI','2026-10-14 13:21:54'),(1217,1,NULL,'JOURNAL_POSTED','accounting','296',NULL,'{\"entry_number\":\"JE-20261014-0296\",\"source_type\":\"customer_collection\",\"source_id\":48,\"total_amount\":1000}','CLI','CLI','2026-10-14 13:21:54'),(1218,1,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','43',NULL,'{\"payment_no\":\"CPAY-0007\",\"customer_name\":\"Split Test Customer\",\"previous_balance\":3000,\"new_balance\":0,\"amount_paid\":3000,\"splits_count\":3}','CLI','CLI','2026-10-14 13:21:54'),(1219,1,2,'JOURNAL_POSTED','accounting','297',NULL,'{\"entry_number\":\"JE-20261014-0297\",\"source_type\":\"customer_collection\",\"source_id\":50,\"total_amount\":6000}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 13:52:53'),(1220,1,2,'CUSTOMER_PAYMENT_PROCESSED','customers','40',NULL,'{\"payment_no\":\"CPAY-0007\",\"customer_name\":\"Sec7 Test Active Customer\",\"previous_balance\":6000,\"new_balance\":0,\"amount_paid\":6000,\"splits_count\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 13:52:53'),(1221,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 13:55:52'),(1222,1,1,'SESSION_EXPIRED','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 15:10:21'),(1223,1,1,'LOGOUT','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 15:10:21'),(1224,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 15:10:22'),(1225,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 15:10:23'),(1226,1,NULL,'JOURNAL_POSTED','accounting','298',NULL,'{\"entry_number\":\"JE-20261014-0298\",\"source_type\":\"customer_collection\",\"source_id\":51,\"total_amount\":1500}','CLI','CLI','2026-10-14 15:18:45'),(1227,1,NULL,'JOURNAL_POSTED','accounting','299',NULL,'{\"entry_number\":\"JE-20261014-0299\",\"source_type\":\"customer_collection\",\"source_id\":52,\"total_amount\":1000}','CLI','CLI','2026-10-14 15:18:46'),(1228,1,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','44',NULL,'{\"payment_no\":\"CPAY-0008\",\"customer_name\":\"Split Test Customer\",\"previous_balance\":3000,\"new_balance\":0,\"amount_paid\":3000,\"splits_count\":3}','CLI','CLI','2026-10-14 15:18:46'),(1229,1,NULL,'JOURNAL_POSTED','accounting','300',NULL,'{\"entry_number\":\"JE-20261014-0300\",\"source_type\":\"customer_collection\",\"source_id\":54,\"total_amount\":1200}','CLI','CLI','2026-10-14 15:18:46'),(1230,1,NULL,'JOURNAL_POSTED','accounting','301',NULL,'{\"entry_number\":\"JE-20261014-0301\",\"source_type\":\"customer_collection\",\"source_id\":55,\"total_amount\":800}','CLI','CLI','2026-10-14 15:18:46'),(1231,1,NULL,'CUSTOMER_PAYMENT_PROCESSED','customers','45',NULL,'{\"payment_no\":\"CPAY-0008\",\"customer_name\":\"Partial Test Customer\",\"previous_balance\":5000,\"new_balance\":3000,\"amount_paid\":2000,\"splits_count\":2}','CLI','CLI','2026-10-14 15:18:46'),(1232,1,1,'CUSTOMER_STATUS_TOGGLED','customers','41','{\"status\":\"inactive\"}','{\"status\":\"active\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 15:27:20'),(1233,1,1,'JOURNAL_POSTED','accounting','302',NULL,'{\"entry_number\":\"JE-20261014-0302\",\"source_type\":\"customer_collection\",\"source_id\":56,\"total_amount\":500}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 15:28:33'),(1234,1,1,'JOURNAL_POSTED','accounting','303',NULL,'{\"entry_number\":\"JE-20261014-0303\",\"source_type\":\"customer_collection\",\"source_id\":57,\"total_amount\":500}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 15:28:33'),(1235,1,1,'CUSTOMER_PAYMENT_PROCESSED','customers','41',NULL,'{\"payment_no\":\"CPAY-0008\",\"customer_name\":\"Sec7 Test Inactive Customer\",\"previous_balance\":5000,\"new_balance\":4000,\"amount_paid\":1000,\"splits_count\":2}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 15:28:33'),(1236,1,NULL,'RETURN_CREATED','returns','3',NULL,'{\"return_no\":\"RET-0001\",\"sale_id\":33,\"return_type\":\"return_only\",\"total_return_amount\":1500,\"total_exchange_amount\":0,\"net_difference\":-1500}','CLI','CLI','2026-10-14 15:56:10'),(1237,1,NULL,'RETURN_CREATED','returns','4',NULL,'{\"return_no\":\"RET-0004\",\"sale_id\":34,\"return_type\":\"return_only\",\"total_return_amount\":1500,\"total_exchange_amount\":0,\"net_difference\":-1500}','CLI','CLI','2026-10-14 15:57:46'),(1238,1,NULL,'JOURNAL_POSTED','accounting','304',NULL,'{\"entry_number\":\"JE-20261014-0304\",\"source_type\":\"sales_return\",\"source_id\":4,\"total_amount\":2500}','CLI','CLI','2026-10-14 15:58:10'),(1239,1,NULL,'JOURNAL_POSTED','accounting','305',NULL,'{\"entry_number\":\"JE-20261014-0305\",\"source_type\":\"sales_return\",\"source_id\":5,\"total_amount\":2500}','CLI','CLI','2026-10-14 15:58:15'),(1240,1,NULL,'RETURN_CREATED','returns','5',NULL,'{\"return_no\":\"RET-0005\",\"sale_id\":35,\"return_type\":\"return_only\",\"total_return_amount\":1500,\"total_exchange_amount\":0,\"net_difference\":-1500}','CLI','CLI','2026-10-14 15:58:15'),(1241,1,NULL,'REFUND_ISSUED','returns','6',NULL,'{\"return_no\":\"RET-0006\",\"refund_method\":\"cash_drawer\",\"amount\":1500}','CLI','CLI','2026-10-14 15:58:15'),(1242,1,NULL,'JOURNAL_POSTED','accounting','306',NULL,'{\"entry_number\":\"JE-20261014-0306\",\"source_type\":\"sales_return\",\"source_id\":6,\"total_amount\":2500}','CLI','CLI','2026-10-14 15:58:15'),(1243,1,NULL,'RETURN_CREATED','returns','6',NULL,'{\"return_no\":\"RET-0006\",\"sale_id\":null,\"return_type\":\"return_only\",\"total_return_amount\":1500,\"total_exchange_amount\":0,\"net_difference\":-1500}','CLI','CLI','2026-10-14 15:58:15'),(1244,1,NULL,'RETURN_NO_INVOICE','returns','6',NULL,'{\"return_no\":\"RET-0006\",\"total_return_amount\":1500}','CLI','CLI','2026-10-14 15:58:15'),(1245,1,NULL,'JOURNAL_POSTED','accounting','307',NULL,'{\"entry_number\":\"JE-20261014-0304\",\"source_type\":\"sales_return\",\"source_id\":7,\"total_amount\":2500}','CLI','CLI','2026-10-14 16:29:45'),(1246,1,NULL,'RETURN_CREATED','returns','7',NULL,'{\"return_no\":\"RET-0001\",\"sale_id\":36,\"return_type\":\"return_only\",\"total_return_amount\":1500,\"total_exchange_amount\":0,\"net_difference\":-1500}','CLI','CLI','2026-10-14 16:29:45'),(1247,1,NULL,'REFUND_ISSUED','returns','8',NULL,'{\"return_no\":\"RET-0008\",\"refund_method\":\"cash_drawer\",\"amount\":1500}','CLI','CLI','2026-10-14 16:29:45'),(1248,1,NULL,'JOURNAL_POSTED','accounting','308',NULL,'{\"entry_number\":\"JE-20261014-0308\",\"source_type\":\"sales_return\",\"source_id\":8,\"total_amount\":2500}','CLI','CLI','2026-10-14 16:29:45'),(1249,1,NULL,'RETURN_CREATED','returns','8',NULL,'{\"return_no\":\"RET-0008\",\"sale_id\":null,\"return_type\":\"return_only\",\"total_return_amount\":1500,\"total_exchange_amount\":0,\"net_difference\":-1500}','CLI','CLI','2026-10-14 16:29:45'),(1250,1,NULL,'RETURN_NO_INVOICE','returns','8',NULL,'{\"return_no\":\"RET-0008\",\"total_return_amount\":1500}','CLI','CLI','2026-10-14 16:29:45'),(1251,1,NULL,'JOURNAL_POSTED','accounting','309',NULL,'{\"entry_number\":\"JE-20261014-0304\",\"source_type\":\"sales_return\",\"source_id\":9,\"total_amount\":3500}','CLI','CLI','2026-10-14 16:34:38'),(1252,1,NULL,'RETURN_CREATED','returns','9',NULL,'{\"return_no\":\"RET-0001\",\"sale_id\":2,\"return_type\":\"return_only\",\"total_return_amount\":2000,\"total_exchange_amount\":0,\"net_difference\":-2000}','CLI','CLI','2026-10-14 16:34:38'),(1253,1,NULL,'JOURNAL_POSTED','accounting','310',NULL,'{\"entry_number\":\"JE-20261014-0304\",\"source_type\":\"sales_return\",\"source_id\":10,\"total_amount\":2500}','CLI','CLI','2026-10-14 16:38:10'),(1254,1,NULL,'RETURN_CREATED','returns','10',NULL,'{\"return_no\":\"RET-0001\",\"sale_id\":37,\"return_type\":\"return_only\",\"total_return_amount\":1500,\"total_exchange_amount\":0,\"net_difference\":-1500}','CLI','CLI','2026-10-14 16:38:10'),(1255,1,NULL,'REFUND_ISSUED','returns','11',NULL,'{\"return_no\":\"RET-0011\",\"refund_method\":\"cash_drawer\",\"amount\":1500}','CLI','CLI','2026-10-14 16:38:10'),(1256,1,NULL,'JOURNAL_POSTED','accounting','311',NULL,'{\"entry_number\":\"JE-20261014-0311\",\"source_type\":\"sales_return\",\"source_id\":11,\"total_amount\":2500}','CLI','CLI','2026-10-14 16:38:10'),(1257,1,NULL,'RETURN_CREATED','returns','11',NULL,'{\"return_no\":\"RET-0011\",\"sale_id\":null,\"return_type\":\"return_only\",\"total_return_amount\":1500,\"total_exchange_amount\":0,\"net_difference\":-1500}','CLI','CLI','2026-10-14 16:38:10'),(1258,1,NULL,'RETURN_NO_INVOICE','returns','11',NULL,'{\"return_no\":\"RET-0011\",\"total_return_amount\":1500}','CLI','CLI','2026-10-14 16:38:10'),(1259,1,1,'DAMAGED_STOCK_LOGGED','returns','12',NULL,'{\"return_no\":\"RET-0001\",\"product_id\":18,\"quantity\":2,\"reason\":\"Customer Return\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 16:39:02'),(1260,1,1,'REFUND_ISSUED','returns','12',NULL,'{\"return_no\":\"RET-0001\",\"refund_method\":\"cash_drawer\",\"amount\":2400}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 16:39:02'),(1261,1,1,'JOURNAL_POSTED','accounting','312',NULL,'{\"entry_number\":\"JE-20261014-0304\",\"source_type\":\"sales_return\",\"source_id\":12,\"total_amount\":4100}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 16:39:02'),(1262,1,1,'RETURN_CREATED','returns','12',NULL,'{\"return_no\":\"RET-0001\",\"sale_id\":2,\"return_type\":\"return_only\",\"total_return_amount\":2400,\"total_exchange_amount\":0,\"net_difference\":-2400}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 16:39:02'),(1263,1,1,'JOURNAL_POSTED','accounting','313',NULL,'{\"entry_number\":\"JE-20261014-0313\",\"source_type\":\"sales_return\",\"source_id\":13,\"total_amount\":3800}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 16:39:59'),(1264,1,1,'RETURN_CREATED','returns','13',NULL,'{\"return_no\":\"RET-0013\",\"sale_id\":2,\"return_type\":\"return_only\",\"total_return_amount\":2200,\"total_exchange_amount\":0,\"net_difference\":-2200}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 16:39:59'),(1265,1,1,'DAMAGED_STOCK_LOGGED','returns','14',NULL,'{\"return_no\":\"RET-0014\",\"product_id\":18,\"quantity\":1,\"reason\":\"Customer Return\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 16:40:29'),(1266,1,1,'JOURNAL_POSTED','accounting','314',NULL,'{\"entry_number\":\"JE-20261014-0314\",\"source_type\":\"sales_return\",\"source_id\":14,\"total_amount\":3800}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 16:40:29'),(1267,1,1,'RETURN_CREATED','returns','14',NULL,'{\"return_no\":\"RET-0014\",\"sale_id\":2,\"return_type\":\"return_only\",\"total_return_amount\":2200,\"total_exchange_amount\":0,\"net_difference\":-2200}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 16:40:29'),(1268,1,1,'SESSION_EXPIRED','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 19:04:31'),(1269,1,1,'LOGOUT','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 19:04:31'),(1270,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 19:04:33'),(1271,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 19:04:35'),(1272,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 19:04:40'),(1273,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 19:09:59'),(1274,1,2,'STOCK_TRANSFER_CREATED','inventory','10',NULL,'{\"transfer_number\":\"TRF-0009\",\"from_branch_id\":2,\"to_branch_id\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 19:10:14'),(1275,1,2,'STOCK_TRANSFER_SENT','inventory','10',NULL,'{\"transfer_number\":\"TRF-0009\",\"from_branch_id\":2,\"to_branch_id\":1}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 19:10:14'),(1276,1,2,'SESSION_EXPIRED','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 22:03:43'),(1277,1,2,'LOGOUT','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 22:03:43'),(1278,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 22:24:15'),(1279,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-14 22:24:16'),(1280,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 12:11:56'),(1281,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Colombo Branch\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 12:11:58'),(1282,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 12:12:02'),(1283,1,1,'SESSION_EXPIRED','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 13:46:37'),(1284,1,1,'LOGOUT','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 13:46:37'),(1285,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 13:46:38'),(1286,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 13:46:40'),(1300,1,1,'INVENTORY_LOT_CREATED','inventory','82',NULL,'{\"branch_id\":1,\"product_id\":28,\"lot_number\":\"LOT-20261015-1930\",\"quantity\":10,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 13:58:23'),(1301,1,1,'JOURNAL_POSTED','accounting','315',NULL,'{\"entry_number\":\"JE-20261015-0001\",\"source_type\":\"opening_balance\",\"source_id\":28,\"total_amount\":1500}','CLI','CLI','2026-10-15 13:58:23'),(1305,1,1,'JOURNAL_POSTED','accounting','316',NULL,'{\"entry_number\":\"JE-20261015-0316\",\"source_type\":\"reversal\",\"source_id\":315,\"total_amount\":1500}','CLI','CLI','2026-10-15 13:58:23'),(1306,1,1,'JOURNAL_REVERSED','accounting','315','{\"entry_number\":\"JE-20261015-0001\"}','{\"reversal_journal_id\":316,\"reason\":\"Product #28 (TestProduct 1792052903) moved to Trash Bin\"}','CLI','CLI','2026-10-15 13:58:23'),(1308,1,1,'JOURNAL_POSTED','accounting','317',NULL,'{\"entry_number\":\"JE-20261015-0317\",\"source_type\":\"reversal\",\"source_id\":316,\"total_amount\":1500}','CLI','CLI','2026-10-15 13:58:23'),(1309,1,1,'JOURNAL_REVERSED','accounting','316','{\"entry_number\":\"JE-20261015-0316\"}','{\"reversal_journal_id\":317,\"reason\":\"Product #28 restored from Trash Bin\"}','CLI','CLI','2026-10-15 13:58:23'),(1316,1,1,'INVENTORY_LOT_CREATED','inventory','83',NULL,'{\"branch_id\":1,\"product_id\":29,\"lot_number\":\"LOT-20261015-3205\",\"quantity\":20,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 13:58:59'),(1317,1,1,'JOURNAL_POSTED','accounting','318',NULL,'{\"entry_number\":\"JE-20261015-0318\",\"source_type\":\"opening_balance\",\"source_id\":29,\"total_amount\":18648}','CLI','CLI','2026-10-15 13:58:59'),(1319,NULL,NULL,'PO_CREATED','purchasing','1',NULL,'{\"po_number\":\"PO-202610-0001\",\"supplier_id\":9,\"net_amount\":12000}','CLI','CLI','2026-10-15 14:14:27'),(1320,NULL,NULL,'PO_APPROVED','purchasing','1','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:14:27'),(1321,NULL,NULL,'PO_DELETED','purchasing','1','{\"id\":1,\"po_number\":\"PO-202610-0001\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:14:27\",\"created_at\":\"2026-10-15 14:14:27\",\"updated_at\":\"2026-10-15 14:14:27\",\"deleted_at\":null,\"supplier_name\":\"Chani Importers - Asus Agent\",\"supplier_code\":\"SUP-0009\",\"supplier_status\":\"active\",\"supplier_phone\":null,\"supplier_whatsapp\":null,\"supplier_address\":null,\"branch_name\":\"Main Branch - Kandy\",\"branch_code\":\"BR-KDY-01\",\"branch_address\":\"No 123, Peradeniya Road, Kandy\",\"branch_phone\":\"+94 81 222 3344\",\"branch_email\":\"kandy@curtisspos.com\",\"creator_name\":\"Super Administrator\",\"approver_name\":\"Super Administrator\",\"items\":[{\"id\":15,\"po_id\":1,\"product_id\":12,\"quantity\":\"10.00\",\"received_quantity\":\"0.00\",\"unit_cost\":\"1250.00\",\"selling_price\":\"1800.00\",\"line_total\":\"12500.00\",\"product_name\":\"Asus Gaming Laptop Tuff\",\"sku\":\"10100\",\"barcode\":\"1010\",\"has_expiry\":0,\"product_status\":\"active\"}]}','{\"deleted_at\":\"2026-10-15 10:44:27\"}','CLI','CLI','2026-10-15 14:14:27'),(1322,NULL,NULL,'PO_RESTORED','purchasing','1','{\"id\":1,\"po_number\":\"PO-202610-0001\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:14:27\",\"created_at\":\"2026-10-15 14:14:27\",\"updated_at\":\"2026-10-15 14:14:27\",\"deleted_at\":\"2026-10-15 14:14:27\"}','{\"deleted_at\":null}','CLI','CLI','2026-10-15 14:14:27'),(1323,NULL,NULL,'PO_CREATED','purchasing','2',NULL,'{\"po_number\":\"PO-202610-0002\",\"supplier_id\":9,\"net_amount\":12000}','CLI','CLI','2026-10-15 14:14:44'),(1324,NULL,NULL,'PO_APPROVED','purchasing','2','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:14:45'),(1325,NULL,NULL,'PO_DELETED','purchasing','2','{\"id\":2,\"po_number\":\"PO-202610-0002\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:14:45\",\"created_at\":\"2026-10-15 14:14:44\",\"updated_at\":\"2026-10-15 14:14:45\",\"deleted_at\":null,\"supplier_name\":\"Chani Importers - Asus Agent\",\"supplier_code\":\"SUP-0009\",\"supplier_status\":\"active\",\"supplier_phone\":null,\"supplier_whatsapp\":null,\"supplier_address\":null,\"branch_name\":\"Main Branch - Kandy\",\"branch_code\":\"BR-KDY-01\",\"branch_address\":\"No 123, Peradeniya Road, Kandy\",\"branch_phone\":\"+94 81 222 3344\",\"branch_email\":\"kandy@curtisspos.com\",\"creator_name\":\"Super Administrator\",\"approver_name\":\"Super Administrator\",\"items\":[{\"id\":16,\"po_id\":2,\"product_id\":12,\"quantity\":\"10.00\",\"received_quantity\":\"0.00\",\"unit_cost\":\"1250.00\",\"selling_price\":\"1800.00\",\"line_total\":\"12500.00\",\"product_name\":\"Asus Gaming Laptop Tuff\",\"sku\":\"10100\",\"barcode\":\"1010\",\"has_expiry\":0,\"product_status\":\"active\"}]}','{\"deleted_at\":\"2026-10-15 10:44:45\"}','CLI','CLI','2026-10-15 14:14:45'),(1326,NULL,NULL,'PO_RESTORED','purchasing','2','{\"id\":2,\"po_number\":\"PO-202610-0002\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:14:45\",\"created_at\":\"2026-10-15 14:14:44\",\"updated_at\":\"2026-10-15 14:14:45\",\"deleted_at\":\"2026-10-15 14:14:45\"}','{\"deleted_at\":null}','CLI','CLI','2026-10-15 14:14:45'),(1327,1,1,'BRAND_CREATED','brands','22',NULL,'{\"name\":\"OptBrand_1792053892\",\"status\":\"active\"}','CLI','CLI','2026-10-15 14:14:52'),(1328,1,1,'CATEGORY_CREATED','categories','22',NULL,'{\"name\":\"OptCat_1792053892\",\"status\":\"active\"}','CLI','CLI','2026-10-15 14:14:52'),(1329,1,1,'SUPPLIER_CREATED','suppliers','15',NULL,'{\"supplier_code\":\"SUP-0013\",\"name\":\"OptSupp_1792053892\",\"phone\":\"0779998888\",\"whatsapp\":null,\"address\":null,\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','CLI','CLI','2026-10-15 14:14:52'),(1330,1,1,'INVENTORY_LOT_CREATED','inventory','84',NULL,'{\"branch_id\":1,\"product_id\":30,\"lot_number\":\"LOT-20261015-7965\",\"quantity\":20,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:14:52'),(1331,1,1,'JOURNAL_POSTED','accounting','319',NULL,'{\"entry_number\":\"JE-20261015-0318\",\"source_type\":\"opening_balance\",\"source_id\":30,\"total_amount\":18648}','CLI','CLI','2026-10-15 14:14:52'),(1333,NULL,NULL,'PO_CREATED','purchasing','3',NULL,'{\"po_number\":\"PO-202610-0002\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:14:58'),(1334,NULL,NULL,'PO_APPROVED','purchasing','3','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:14:58'),(1335,NULL,NULL,'PO_CREATED','purchasing','4',NULL,'{\"po_number\":\"PO-202610-0004\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:14:58'),(1336,NULL,NULL,'PO_CANCELLED','purchasing','4','{\"status\":\"Draft\"}','{\"status\":\"Cancelled\"}','CLI','CLI','2026-10-15 14:14:58'),(1337,NULL,NULL,'GRN_CREATED','purchasing','11',NULL,'{\"grn_number\":\"GRN-202610-0011\",\"supplier_invoice_no\":\"TEST-INV-8899\",\"actual_grn_total\":1300}','CLI','CLI','2026-10-15 14:14:58'),(1338,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','85',NULL,'{\"branch_id\":1,\"product_id\":30,\"lot_number\":\"LOT-GRN11-30-544\",\"quantity\":50,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:14:58'),(1339,NULL,NULL,'JOURNAL_POSTED','accounting','320',NULL,'{\"entry_number\":\"JE-20261015-0318\",\"source_type\":\"grn\",\"source_id\":11,\"total_amount\":1300}','CLI','CLI','2026-10-15 14:14:58'),(1340,NULL,NULL,'GRN_APPROVED','purchasing','11','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"1300.00\"}','CLI','CLI','2026-10-15 14:14:58'),(1341,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','CLI','CLI','2026-10-15 14:14:58'),(1346,NULL,NULL,'GRN_CREATED','purchasing','12',NULL,'{\"grn_number\":\"GRN-202610-0012\",\"supplier_invoice_no\":\"INV-GRN-UNIQUE-1792054512\",\"actual_grn_total\":15000}','CLI','CLI','2026-10-15 14:25:12'),(1347,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','86',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN12-12-385\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:25:12'),(1348,NULL,NULL,'JOURNAL_POSTED','accounting','321',NULL,'{\"entry_number\":\"JE-20261015-0321\",\"source_type\":\"grn\",\"source_id\":12,\"total_amount\":15000}','CLI','CLI','2026-10-15 14:25:12'),(1349,NULL,NULL,'GRN_APPROVED','purchasing','12','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','CLI','CLI','2026-10-15 14:25:12'),(1350,NULL,NULL,'GRN_CREATED','purchasing','13',NULL,'{\"grn_number\":\"GRN-202610-0013\",\"supplier_invoice_no\":\"INV-GRN-UNIQUE-1792054606\",\"actual_grn_total\":15000}','CLI','CLI','2026-10-15 14:26:46'),(1351,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','86',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN13-12-994\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:26:46'),(1352,NULL,NULL,'JOURNAL_POSTED','accounting','322',NULL,'{\"entry_number\":\"JE-20261015-0322\",\"source_type\":\"grn\",\"source_id\":13,\"total_amount\":15000}','CLI','CLI','2026-10-15 14:26:46'),(1353,NULL,NULL,'GRN_APPROVED','purchasing','13','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','CLI','CLI','2026-10-15 14:26:46'),(1354,NULL,NULL,'GRN_CREATED','purchasing','14',NULL,'{\"grn_number\":\"GRN-202610-0014\",\"supplier_invoice_no\":\"INV-GRN-UNIQUE-1792054643\",\"actual_grn_total\":15000}','CLI','CLI','2026-10-15 14:27:23'),(1355,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','86',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN14-12-502\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:27:23'),(1356,NULL,NULL,'JOURNAL_POSTED','accounting','323',NULL,'{\"entry_number\":\"JE-20261015-0323\",\"source_type\":\"grn\",\"source_id\":14,\"total_amount\":15000}','CLI','CLI','2026-10-15 14:27:23'),(1357,NULL,NULL,'GRN_APPROVED','purchasing','14','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','CLI','CLI','2026-10-15 14:27:23'),(1358,NULL,NULL,'GRN_CREATED','purchasing','15',NULL,'{\"grn_number\":\"GRN-202610-0015\",\"supplier_invoice_no\":\"INV-GRN-UNIQUE-1792054664\",\"actual_grn_total\":15000}','CLI','CLI','2026-10-15 14:27:44'),(1359,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','86',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN15-12-331\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:27:44'),(1360,NULL,NULL,'JOURNAL_POSTED','accounting','324',NULL,'{\"entry_number\":\"JE-20261015-0324\",\"source_type\":\"grn\",\"source_id\":15,\"total_amount\":15000}','CLI','CLI','2026-10-15 14:27:44'),(1361,NULL,NULL,'GRN_APPROVED','purchasing','15','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','CLI','CLI','2026-10-15 14:27:44'),(1362,NULL,NULL,'GRN_CREATED','purchasing','16',NULL,'{\"grn_number\":\"GRN-202610-0016\",\"supplier_invoice_no\":\"INV-GRN-UNIQUE-1792054683\",\"actual_grn_total\":15000}','CLI','CLI','2026-10-15 14:28:03'),(1363,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','87',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN16-12-567\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:28:03'),(1364,NULL,NULL,'JOURNAL_POSTED','accounting','325',NULL,'{\"entry_number\":\"JE-20261015-0324\",\"source_type\":\"grn\",\"source_id\":16,\"total_amount\":15000}','CLI','CLI','2026-10-15 14:28:03'),(1365,NULL,NULL,'GRN_APPROVED','purchasing','16','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','CLI','CLI','2026-10-15 14:28:03'),(1370,NULL,NULL,'PO_CREATED','purchasing','7',NULL,'{\"po_number\":\"PO-202610-0005\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:28:09'),(1371,NULL,NULL,'PO_APPROVED','purchasing','7','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:28:09'),(1372,NULL,NULL,'PO_CREATED','purchasing','8',NULL,'{\"po_number\":\"PO-202610-0008\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:28:09'),(1373,NULL,NULL,'PO_CANCELLED','purchasing','8','{\"status\":\"Draft\"}','{\"status\":\"Cancelled\"}','CLI','CLI','2026-10-15 14:28:09'),(1374,NULL,NULL,'GRN_CREATED','purchasing','17',NULL,'{\"grn_number\":\"GRN-202610-0016\",\"supplier_invoice_no\":\"TEST-INV-8899\",\"actual_grn_total\":1300}','CLI','CLI','2026-10-15 14:28:09'),(1375,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','85',NULL,'{\"branch_id\":1,\"product_id\":30,\"lot_number\":\"LOT-GRN17-30-315\",\"quantity\":50,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:28:09'),(1376,NULL,NULL,'JOURNAL_POSTED','accounting','326',NULL,'{\"entry_number\":\"JE-20261015-0324\",\"source_type\":\"grn\",\"source_id\":17,\"total_amount\":1300}','CLI','CLI','2026-10-15 14:28:09'),(1377,NULL,NULL,'GRN_APPROVED','purchasing','17','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"1300.00\"}','CLI','CLI','2026-10-15 14:28:09'),(1378,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','CLI','CLI','2026-10-15 14:28:10'),(1379,NULL,NULL,'PO_CREATED','purchasing','9',NULL,'{\"po_number\":\"PO-202610-0009\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:29:04'),(1380,NULL,NULL,'PO_APPROVED','purchasing','9','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:29:04'),(1381,NULL,NULL,'PO_CREATED','purchasing','10',NULL,'{\"po_number\":\"PO-202610-0010\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:29:04'),(1382,NULL,NULL,'PO_CANCELLED','purchasing','10','{\"status\":\"Draft\"}','{\"status\":\"Cancelled\"}','CLI','CLI','2026-10-15 14:29:04'),(1383,NULL,NULL,'GRN_CREATED','purchasing','18',NULL,'{\"grn_number\":\"GRN-202610-0018\",\"supplier_invoice_no\":\"TEST-INV-8899\",\"actual_grn_total\":1300}','CLI','CLI','2026-10-15 14:29:04'),(1384,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','88',NULL,'{\"branch_id\":1,\"product_id\":30,\"lot_number\":\"LOT-GRN18-30-372\",\"quantity\":50,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:29:04'),(1385,NULL,NULL,'JOURNAL_POSTED','accounting','327',NULL,'{\"entry_number\":\"JE-20261015-0327\",\"source_type\":\"grn\",\"source_id\":18,\"total_amount\":1300}','CLI','CLI','2026-10-15 14:29:04'),(1386,NULL,NULL,'GRN_APPROVED','purchasing','18','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"1300.00\"}','CLI','CLI','2026-10-15 14:29:04'),(1387,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','CLI','CLI','2026-10-15 14:29:04'),(1388,NULL,NULL,'GRN_CREATED','purchasing','19',NULL,'{\"grn_number\":\"GRN-202610-0018\",\"supplier_invoice_no\":\"INV-GRN-UNIQUE-1792054763\",\"actual_grn_total\":15000}','CLI','CLI','2026-10-15 14:29:23'),(1389,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','89',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN19-12-380\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:29:23'),(1390,NULL,NULL,'JOURNAL_POSTED','accounting','328',NULL,'{\"entry_number\":\"JE-20261015-0328\",\"source_type\":\"grn\",\"source_id\":19,\"total_amount\":15000}','CLI','CLI','2026-10-15 14:29:23'),(1391,NULL,NULL,'GRN_APPROVED','purchasing','19','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','CLI','CLI','2026-10-15 14:29:23'),(1392,NULL,NULL,'PO_CREATED','purchasing','11',NULL,'{\"po_number\":\"PO-202610-0011\",\"supplier_id\":9,\"net_amount\":12000}','CLI','CLI','2026-10-15 14:29:23'),(1393,NULL,NULL,'PO_APPROVED','purchasing','11','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:29:23'),(1394,NULL,NULL,'PO_DELETED','purchasing','11','{\"id\":11,\"po_number\":\"PO-202610-0011\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:29:23\",\"created_at\":\"2026-10-15 14:29:23\",\"updated_at\":\"2026-10-15 14:29:23\",\"deleted_at\":null,\"supplier_name\":\"Chani Importers - Asus Agent\",\"supplier_code\":\"SUP-0009\",\"supplier_status\":\"active\",\"supplier_phone\":null,\"supplier_whatsapp\":null,\"supplier_address\":null,\"branch_name\":\"Main Branch - Kandy\",\"branch_code\":\"BR-KDY-01\",\"branch_address\":\"No 123, Peradeniya Road, Kandy\",\"branch_phone\":\"+94 81 222 3344\",\"branch_email\":\"kandy@curtisspos.com\",\"creator_name\":\"Super Administrator\",\"approver_name\":\"Super Administrator\",\"items\":[{\"id\":25,\"po_id\":11,\"product_id\":12,\"quantity\":\"10.00\",\"received_quantity\":\"0.00\",\"unit_cost\":\"1250.00\",\"selling_price\":\"1800.00\",\"line_total\":\"12500.00\",\"product_name\":\"Asus Gaming Laptop Tuff\",\"sku\":\"10100\",\"barcode\":\"1010\",\"has_expiry\":0,\"product_status\":\"active\"}]}','{\"deleted_at\":\"2026-10-15 10:59:23\"}','CLI','CLI','2026-10-15 14:29:23'),(1395,NULL,NULL,'PO_RESTORED','purchasing','11','{\"id\":11,\"po_number\":\"PO-202610-0011\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:29:23\",\"created_at\":\"2026-10-15 14:29:23\",\"updated_at\":\"2026-10-15 14:29:23\",\"deleted_at\":\"2026-10-15 14:29:23\"}','{\"deleted_at\":null}','CLI','CLI','2026-10-15 14:29:23'),(1396,NULL,NULL,'GRN_CREATED','purchasing','20',NULL,'{\"grn_number\":\"GRN-202610-0018\",\"supplier_invoice_no\":\"INV-GRN-UNIQUE-1792054791\",\"actual_grn_total\":15000}','CLI','CLI','2026-10-15 14:29:51'),(1397,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','90',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN20-12-150\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:29:51'),(1398,NULL,NULL,'JOURNAL_POSTED','accounting','329',NULL,'{\"entry_number\":\"JE-20261015-0328\",\"source_type\":\"grn\",\"source_id\":20,\"total_amount\":15000}','CLI','CLI','2026-10-15 14:29:51'),(1399,NULL,NULL,'GRN_APPROVED','purchasing','20','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','CLI','CLI','2026-10-15 14:29:51'),(1400,NULL,NULL,'PO_CREATED','purchasing','12',NULL,'{\"po_number\":\"PO-202610-0011\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:29:55'),(1401,NULL,NULL,'PO_APPROVED','purchasing','12','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:29:55'),(1402,NULL,NULL,'PO_CREATED','purchasing','13',NULL,'{\"po_number\":\"PO-202610-0013\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:29:55'),(1403,NULL,NULL,'PO_CANCELLED','purchasing','13','{\"status\":\"Draft\"}','{\"status\":\"Cancelled\"}','CLI','CLI','2026-10-15 14:29:55'),(1404,NULL,NULL,'GRN_CREATED','purchasing','21',NULL,'{\"grn_number\":\"GRN-202610-0018\",\"supplier_invoice_no\":\"TEST-INV-8899\",\"actual_grn_total\":1300}','CLI','CLI','2026-10-15 14:29:55'),(1405,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','91',NULL,'{\"branch_id\":1,\"product_id\":30,\"lot_number\":\"LOT-GRN21-30-799\",\"quantity\":50,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:29:55'),(1406,NULL,NULL,'JOURNAL_POSTED','accounting','330',NULL,'{\"entry_number\":\"JE-20261015-0328\",\"source_type\":\"grn\",\"source_id\":21,\"total_amount\":1300}','CLI','CLI','2026-10-15 14:29:55'),(1407,NULL,NULL,'GRN_APPROVED','purchasing','21','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"1300.00\"}','CLI','CLI','2026-10-15 14:29:55'),(1408,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','CLI','CLI','2026-10-15 14:29:55'),(1409,NULL,NULL,'PO_CREATED','purchasing','14',NULL,'{\"po_number\":\"PO-202610-0014\",\"supplier_id\":9,\"net_amount\":12000}','CLI','CLI','2026-10-15 14:29:59'),(1410,NULL,NULL,'PO_APPROVED','purchasing','14','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:29:59'),(1411,NULL,NULL,'PO_DELETED','purchasing','14','{\"id\":14,\"po_number\":\"PO-202610-0014\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:29:59\",\"created_at\":\"2026-10-15 14:29:59\",\"updated_at\":\"2026-10-15 14:29:59\",\"deleted_at\":null,\"supplier_name\":\"Chani Importers - Asus Agent\",\"supplier_code\":\"SUP-0009\",\"supplier_status\":\"active\",\"supplier_phone\":null,\"supplier_whatsapp\":null,\"supplier_address\":null,\"branch_name\":\"Main Branch - Kandy\",\"branch_code\":\"BR-KDY-01\",\"branch_address\":\"No 123, Peradeniya Road, Kandy\",\"branch_phone\":\"+94 81 222 3344\",\"branch_email\":\"kandy@curtisspos.com\",\"creator_name\":\"Super Administrator\",\"approver_name\":\"Super Administrator\",\"items\":[{\"id\":28,\"po_id\":14,\"product_id\":12,\"quantity\":\"10.00\",\"received_quantity\":\"0.00\",\"unit_cost\":\"1250.00\",\"selling_price\":\"1800.00\",\"line_total\":\"12500.00\",\"product_name\":\"Asus Gaming Laptop Tuff\",\"sku\":\"10100\",\"barcode\":\"1010\",\"has_expiry\":0,\"product_status\":\"active\"}]}','{\"deleted_at\":\"2026-10-15 10:59:59\"}','CLI','CLI','2026-10-15 14:29:59'),(1412,NULL,NULL,'PO_RESTORED','purchasing','14','{\"id\":14,\"po_number\":\"PO-202610-0014\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:29:59\",\"created_at\":\"2026-10-15 14:29:59\",\"updated_at\":\"2026-10-15 14:29:59\",\"deleted_at\":\"2026-10-15 14:29:59\"}','{\"deleted_at\":null}','CLI','CLI','2026-10-15 14:29:59'),(1416,1,1,'INVENTORY_LOT_CREATED','inventory','92',NULL,'{\"branch_id\":1,\"product_id\":31,\"lot_number\":\"LOT-20261015-7870\",\"quantity\":20,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:30:08'),(1417,1,1,'JOURNAL_POSTED','accounting','331',NULL,'{\"entry_number\":\"JE-20261015-0331\",\"source_type\":\"opening_balance\",\"source_id\":31,\"total_amount\":18648}','CLI','CLI','2026-10-15 14:30:08'),(1419,1,1,'INVENTORY_LOT_CREATED','inventory','93',NULL,'{\"branch_id\":1,\"product_id\":32,\"lot_number\":\"LOT-20261015-2690\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:30:12'),(1420,1,1,'PRODUCT_CREATED','products','32',NULL,'{\"sku\":\"TEST-SKU-A-1792054812_417\",\"barcode\":\"BAR-A-1792054812_417\",\"name\":\"GRN Test Standard Product 1792054812_417\",\"cost\":100,\"selling_price\":150,\"opening_stock\":0,\"has_expiry\":0,\"branch_id\":1}','CLI','CLI','2026-10-15 14:30:12'),(1421,1,1,'INVENTORY_LOT_CREATED','inventory','94',NULL,'{\"branch_id\":1,\"product_id\":33,\"lot_number\":\"LOT-20261015-5565\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:30:12'),(1422,1,1,'PRODUCT_CREATED','products','33',NULL,'{\"sku\":\"TEST-SKU-B-1792054812_417\",\"barcode\":\"BAR-B-1792054812_417\",\"name\":\"GRN Test Expiry Product 1792054812_417\",\"cost\":200,\"selling_price\":300,\"opening_stock\":0,\"has_expiry\":1,\"branch_id\":1}','CLI','CLI','2026-10-15 14:30:12'),(1425,1,1,'INVENTORY_LOT_CREATED','inventory','95',NULL,'{\"branch_id\":1,\"product_id\":32,\"lot_number\":\"LOT-GRN22-32-339\",\"quantity\":10,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:30:12'),(1426,1,1,'INVENTORY_LOT_CREATED','inventory','96',NULL,'{\"branch_id\":1,\"product_id\":33,\"lot_number\":\"LOT-GRN22-33-607\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:30:12'),(1427,1,1,'JOURNAL_POSTED','accounting','332',NULL,'{\"entry_number\":\"JE-20261015-0331\",\"source_type\":\"grn\",\"source_id\":22,\"total_amount\":5500}','CLI','CLI','2026-10-15 14:30:12'),(1429,1,1,'PO_CREATED','purchasing','15',NULL,'{\"po_number\":\"PO-202610-0014\",\"supplier_id\":9,\"net_amount\":2100}','CLI','CLI','2026-10-15 14:30:12'),(1430,1,1,'PO_APPROVED','purchasing','15','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:30:12'),(1432,1,1,'INVENTORY_LOT_CREATED','inventory','97',NULL,'{\"branch_id\":1,\"product_id\":32,\"lot_number\":\"LOT-GRN23-32-219\",\"quantity\":15,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:30:12'),(1433,1,1,'JOURNAL_POSTED','accounting','333',NULL,'{\"entry_number\":\"JE-20261015-0333\",\"source_type\":\"grn\",\"source_id\":23,\"total_amount\":2100}','CLI','CLI','2026-10-15 14:30:12'),(1437,1,1,'INVENTORY_LOT_CREATED','inventory','98',NULL,'{\"branch_id\":1,\"product_id\":34,\"lot_number\":\"LOT-20261015-6023\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:30:32'),(1439,1,1,'INVENTORY_LOT_CREATED','inventory','99',NULL,'{\"branch_id\":1,\"product_id\":35,\"lot_number\":\"LOT-20261015-7958\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:30:32'),(1443,1,1,'INVENTORY_LOT_CREATED','inventory','100',NULL,'{\"branch_id\":1,\"product_id\":34,\"lot_number\":\"LOT-GRN25-34-391\",\"quantity\":10,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:30:32'),(1444,1,1,'INVENTORY_LOT_CREATED','inventory','101',NULL,'{\"branch_id\":1,\"product_id\":35,\"lot_number\":\"LOT-GRN25-35-978\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:30:32'),(1445,1,1,'JOURNAL_POSTED','accounting','334',NULL,'{\"entry_number\":\"JE-20261015-0331\",\"source_type\":\"grn\",\"source_id\":25,\"total_amount\":5500}','CLI','CLI','2026-10-15 14:30:32'),(1447,1,1,'PO_CREATED','purchasing','16',NULL,'{\"po_number\":\"PO-202610-0016\",\"supplier_id\":9,\"net_amount\":2100}','CLI','CLI','2026-10-15 14:30:32'),(1448,1,1,'PO_APPROVED','purchasing','16','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:30:32'),(1450,1,1,'INVENTORY_LOT_CREATED','inventory','102',NULL,'{\"branch_id\":1,\"product_id\":34,\"lot_number\":\"LOT-GRN26-34-435\",\"quantity\":15,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:30:32'),(1451,1,1,'JOURNAL_POSTED','accounting','335',NULL,'{\"entry_number\":\"JE-20261015-0335\",\"source_type\":\"grn\",\"source_id\":26,\"total_amount\":2100}','CLI','CLI','2026-10-15 14:30:32'),(1461,NULL,NULL,'JOURNAL_POSTED','accounting','336',NULL,'{\"entry_number\":\"JE-20261015-0331\",\"source_type\":\"opening_balance\",\"source_id\":1,\"total_amount\":25000}','CLI','CLI','2026-10-15 14:50:19'),(1463,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 14:51:02'),(1464,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 14:51:03'),(1465,NULL,NULL,'SUPPLIER_CREATED','suppliers','18',NULL,'{\"supplier_code\":\"SUP-0017\",\"name\":\"Temp Rev Test 1792056085\",\"phone\":null,\"whatsapp\":null,\"address\":null,\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','CLI','CLI','2026-10-15 14:51:25'),(1466,NULL,NULL,'JOURNAL_POSTED','accounting','337',NULL,'{\"entry_number\":\"JE-20261015-0331\",\"source_type\":\"opening_balance\",\"source_id\":2,\"total_amount\":5000}','CLI','CLI','2026-10-15 14:51:25'),(1467,NULL,NULL,'SUPPLIER_OPENING_BALANCE_POSTED','suppliers','18',NULL,'{\"ob_id\":2,\"amount\":5000,\"balance_type\":\"owe_supplier\",\"journal_no\":\"JE-20261015-0331\"}','CLI','CLI','2026-10-15 14:51:25'),(1468,NULL,NULL,'JOURNAL_POSTED','accounting','338',NULL,'{\"entry_number\":\"JE-20261015-0338\",\"source_type\":\"reversal\",\"source_id\":2,\"total_amount\":5000}','CLI','CLI','2026-10-15 14:51:25'),(1469,NULL,NULL,'SUPPLIER_OPENING_BALANCE_REVERSED','suppliers','18','{\"id\":2,\"supplier_id\":18,\"branch_id\":1,\"entry_date\":\"2026-10-15\",\"balance_type\":\"owe_supplier\",\"amount\":\"5000.00\",\"paid_amount\":\"0.00\",\"reference_no\":\"REV-1\",\"notes\":\"Testing rev\",\"journal_entry_id\":337,\"status\":\"posted\",\"reversed_by\":null,\"reversed_at\":null,\"reversal_notes\":null,\"reversal_journal_id\":null,\"created_by\":1,\"created_at\":\"2026-10-15 14:51:25\",\"updated_at\":\"2026-10-15 14:51:25\"}','{\"reversal_notes\":\"Testing reversal reason\",\"reversal_je\":\"JE-20261015-0338\"}','CLI','CLI','2026-10-15 14:51:25'),(1470,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 14:51:29'),(1471,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 14:51:30'),(1478,NULL,NULL,'JOURNAL_POSTED','accounting','339',NULL,'{\"entry_number\":\"JE-20261015-0339\",\"source_type\":\"opening_balance\",\"source_id\":3,\"total_amount\":25000}','CLI','CLI','2026-10-15 14:52:22'),(1480,NULL,NULL,'JOURNAL_POSTED','accounting','340',NULL,'{\"entry_number\":\"JE-20261015-0340\",\"source_type\":\"reversal\",\"source_id\":3,\"total_amount\":25000}','CLI','CLI','2026-10-15 14:52:22'),(1482,1,1,'INVENTORY_LOT_CREATED','inventory','103',NULL,'{\"branch_id\":1,\"product_id\":36,\"lot_number\":\"LOT-20261015-7712\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:52:30'),(1484,1,1,'INVENTORY_LOT_CREATED','inventory','104',NULL,'{\"branch_id\":1,\"product_id\":37,\"lot_number\":\"LOT-20261015-4153\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:52:30'),(1488,1,1,'INVENTORY_LOT_CREATED','inventory','105',NULL,'{\"branch_id\":1,\"product_id\":36,\"lot_number\":\"LOT-GRN28-36-852\",\"quantity\":10,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:52:30'),(1489,1,1,'INVENTORY_LOT_CREATED','inventory','106',NULL,'{\"branch_id\":1,\"product_id\":37,\"lot_number\":\"LOT-GRN28-37-633\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:52:30'),(1490,1,1,'JOURNAL_POSTED','accounting','341',NULL,'{\"entry_number\":\"JE-20261015-0339\",\"source_type\":\"grn\",\"source_id\":28,\"total_amount\":5500}','CLI','CLI','2026-10-15 14:52:30'),(1492,1,1,'PO_CREATED','purchasing','17',NULL,'{\"po_number\":\"PO-202610-0016\",\"supplier_id\":9,\"net_amount\":2100}','CLI','CLI','2026-10-15 14:52:30'),(1493,1,1,'PO_APPROVED','purchasing','17','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:52:30'),(1495,1,1,'INVENTORY_LOT_CREATED','inventory','107',NULL,'{\"branch_id\":1,\"product_id\":36,\"lot_number\":\"LOT-GRN29-36-431\",\"quantity\":15,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:52:30'),(1496,1,1,'JOURNAL_POSTED','accounting','342',NULL,'{\"entry_number\":\"JE-20261015-0342\",\"source_type\":\"grn\",\"source_id\":29,\"total_amount\":2100}','CLI','CLI','2026-10-15 14:52:30'),(1500,NULL,NULL,'GRN_CREATED','purchasing','31',NULL,'{\"grn_number\":\"GRN-202610-0025\",\"supplier_invoice_no\":\"INV-GRN-UNIQUE-1792056150\",\"actual_grn_total\":15000}','CLI','CLI','2026-10-15 14:52:30'),(1501,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','108',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN31-12-511\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:52:30'),(1502,NULL,NULL,'JOURNAL_POSTED','accounting','343',NULL,'{\"entry_number\":\"JE-20261015-0339\",\"source_type\":\"grn\",\"source_id\":31,\"total_amount\":15000}','CLI','CLI','2026-10-15 14:52:30'),(1503,NULL,NULL,'GRN_APPROVED','purchasing','31','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','CLI','CLI','2026-10-15 14:52:30'),(1504,NULL,NULL,'PO_CREATED','purchasing','18',NULL,'{\"po_number\":\"PO-202610-0016\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:52:30'),(1505,NULL,NULL,'PO_APPROVED','purchasing','18','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:52:30'),(1506,NULL,NULL,'PO_CREATED','purchasing','19',NULL,'{\"po_number\":\"PO-202610-0019\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:52:30'),(1507,NULL,NULL,'PO_CANCELLED','purchasing','19','{\"status\":\"Draft\"}','{\"status\":\"Cancelled\"}','CLI','CLI','2026-10-15 14:52:30'),(1508,NULL,NULL,'PO_CREATED','purchasing','20',NULL,'{\"po_number\":\"PO-202610-0020\",\"supplier_id\":9,\"net_amount\":12000}','CLI','CLI','2026-10-15 14:52:31'),(1509,NULL,NULL,'PO_APPROVED','purchasing','20','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:52:31'),(1510,NULL,NULL,'PO_DELETED','purchasing','20','{\"id\":20,\"po_number\":\"PO-202610-0020\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:52:31\",\"created_at\":\"2026-10-15 14:52:31\",\"updated_at\":\"2026-10-15 14:52:31\",\"deleted_at\":null,\"supplier_name\":\"Chani Importers - Asus Agent\",\"supplier_code\":\"SUP-0009\",\"supplier_status\":\"active\",\"supplier_phone\":null,\"supplier_whatsapp\":null,\"supplier_address\":null,\"branch_name\":\"Main Branch - Kandy\",\"branch_code\":\"BR-KDY-01\",\"branch_address\":\"No 123, Peradeniya Road, Kandy\",\"branch_phone\":\"+94 81 222 3344\",\"branch_email\":\"kandy@curtisspos.com\",\"creator_name\":\"Super Administrator\",\"approver_name\":\"Super Administrator\",\"items\":[{\"id\":34,\"po_id\":20,\"product_id\":12,\"quantity\":\"10.00\",\"received_quantity\":\"0.00\",\"unit_cost\":\"1250.00\",\"selling_price\":\"1800.00\",\"line_total\":\"12500.00\",\"product_name\":\"Asus Gaming Laptop Tuff\",\"sku\":\"10100\",\"barcode\":\"1010\",\"has_expiry\":0,\"product_status\":\"active\"}]}','{\"deleted_at\":\"2026-10-15 11:22:31\"}','CLI','CLI','2026-10-15 14:52:31'),(1511,NULL,NULL,'PO_RESTORED','purchasing','20','{\"id\":20,\"po_number\":\"PO-202610-0020\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:52:31\",\"created_at\":\"2026-10-15 14:52:31\",\"updated_at\":\"2026-10-15 14:52:31\",\"deleted_at\":\"2026-10-15 14:52:31\"}','{\"deleted_at\":null}','CLI','CLI','2026-10-15 14:52:31'),(1512,NULL,NULL,'PO_CREATED','purchasing','21',NULL,'{\"po_number\":\"PO-202610-0020\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:53:41'),(1513,NULL,NULL,'PO_APPROVED','purchasing','21','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:53:41'),(1514,NULL,NULL,'PO_CREATED','purchasing','22',NULL,'{\"po_number\":\"PO-202610-0022\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:53:41'),(1515,NULL,NULL,'PO_CANCELLED','purchasing','22','{\"status\":\"Draft\"}','{\"status\":\"Cancelled\"}','CLI','CLI','2026-10-15 14:53:41'),(1516,NULL,NULL,'PO_CREATED','purchasing','23',NULL,'{\"po_number\":\"PO-202610-0023\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:53:58'),(1517,NULL,NULL,'PO_CREATED','purchasing','24',NULL,'{\"po_number\":\"PO-202610-0024\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:54:50'),(1518,NULL,NULL,'PO_APPROVED','purchasing','24','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:54:50'),(1519,NULL,NULL,'PO_CREATED','purchasing','25',NULL,'{\"po_number\":\"PO-202610-0025\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:54:50'),(1520,NULL,NULL,'PO_CANCELLED','purchasing','25','{\"status\":\"Draft\"}','{\"status\":\"Cancelled\"}','CLI','CLI','2026-10-15 14:54:50'),(1521,NULL,NULL,'GRN_CREATED','purchasing','32',NULL,'{\"grn_number\":\"GRN-202610-0025\",\"supplier_invoice_no\":\"TEST-INV-8899\",\"actual_grn_total\":1300}','CLI','CLI','2026-10-15 14:54:50'),(1522,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','109',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN32-12-156\",\"quantity\":50,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:54:50'),(1523,NULL,NULL,'JOURNAL_POSTED','accounting','344',NULL,'{\"entry_number\":\"JE-20261015-0339\",\"source_type\":\"grn\",\"source_id\":32,\"total_amount\":1300}','CLI','CLI','2026-10-15 14:54:50'),(1524,NULL,NULL,'GRN_APPROVED','purchasing','32','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"1300.00\"}','CLI','CLI','2026-10-15 14:54:50'),(1525,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','CLI','CLI','2026-10-15 14:54:50'),(1532,NULL,NULL,'JOURNAL_POSTED','accounting','345',NULL,'{\"entry_number\":\"JE-20261015-0345\",\"source_type\":\"opening_balance\",\"source_id\":4,\"total_amount\":25000}','CLI','CLI','2026-10-15 14:55:07'),(1534,NULL,NULL,'JOURNAL_POSTED','accounting','346',NULL,'{\"entry_number\":\"JE-20261015-0346\",\"source_type\":\"reversal\",\"source_id\":4,\"total_amount\":25000}','CLI','CLI','2026-10-15 14:55:07'),(1536,1,1,'INVENTORY_LOT_CREATED','inventory','110',NULL,'{\"branch_id\":1,\"product_id\":38,\"lot_number\":\"LOT-20261015-6922\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:55:07'),(1538,1,1,'INVENTORY_LOT_CREATED','inventory','111',NULL,'{\"branch_id\":1,\"product_id\":39,\"lot_number\":\"LOT-20261015-9955\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:55:07'),(1542,1,1,'INVENTORY_LOT_CREATED','inventory','112',NULL,'{\"branch_id\":1,\"product_id\":38,\"lot_number\":\"LOT-GRN33-38-327\",\"quantity\":10,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:55:07'),(1543,1,1,'INVENTORY_LOT_CREATED','inventory','113',NULL,'{\"branch_id\":1,\"product_id\":39,\"lot_number\":\"LOT-GRN33-39-336\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:55:07'),(1544,1,1,'JOURNAL_POSTED','accounting','347',NULL,'{\"entry_number\":\"JE-20261015-0345\",\"source_type\":\"grn\",\"source_id\":33,\"total_amount\":5500}','CLI','CLI','2026-10-15 14:55:07'),(1546,1,1,'PO_CREATED','purchasing','26',NULL,'{\"po_number\":\"PO-202610-0026\",\"supplier_id\":9,\"net_amount\":2100}','CLI','CLI','2026-10-15 14:55:07'),(1547,1,1,'PO_APPROVED','purchasing','26','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:55:07'),(1549,1,1,'INVENTORY_LOT_CREATED','inventory','114',NULL,'{\"branch_id\":1,\"product_id\":38,\"lot_number\":\"LOT-GRN34-38-846\",\"quantity\":15,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:55:07'),(1550,1,1,'JOURNAL_POSTED','accounting','348',NULL,'{\"entry_number\":\"JE-20261015-0348\",\"source_type\":\"grn\",\"source_id\":34,\"total_amount\":2100}','CLI','CLI','2026-10-15 14:55:07'),(1554,NULL,NULL,'GRN_CREATED','purchasing','36',NULL,'{\"grn_number\":\"GRN-202610-0033\",\"supplier_invoice_no\":\"INV-GRN-UNIQUE-1792056307\",\"actual_grn_total\":15000}','CLI','CLI','2026-10-15 14:55:07'),(1555,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','115',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN36-12-344\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:55:07'),(1556,NULL,NULL,'JOURNAL_POSTED','accounting','349',NULL,'{\"entry_number\":\"JE-20261015-0345\",\"source_type\":\"grn\",\"source_id\":36,\"total_amount\":15000}','CLI','CLI','2026-10-15 14:55:07'),(1557,NULL,NULL,'GRN_APPROVED','purchasing','36','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','CLI','CLI','2026-10-15 14:55:07'),(1558,NULL,NULL,'PO_CREATED','purchasing','27',NULL,'{\"po_number\":\"PO-202610-0026\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:55:07'),(1559,NULL,NULL,'PO_APPROVED','purchasing','27','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:55:07'),(1560,NULL,NULL,'PO_CREATED','purchasing','28',NULL,'{\"po_number\":\"PO-202610-0028\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:55:07'),(1561,NULL,NULL,'PO_CANCELLED','purchasing','28','{\"status\":\"Draft\"}','{\"status\":\"Cancelled\"}','CLI','CLI','2026-10-15 14:55:07'),(1562,NULL,NULL,'GRN_CREATED','purchasing','37',NULL,'{\"grn_number\":\"GRN-202610-0033\",\"supplier_invoice_no\":\"TEST-INV-8899\",\"actual_grn_total\":1300}','CLI','CLI','2026-10-15 14:55:07'),(1563,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','109',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN37-12-744\",\"quantity\":50,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:55:07'),(1564,NULL,NULL,'JOURNAL_POSTED','accounting','350',NULL,'{\"entry_number\":\"JE-20261015-0345\",\"source_type\":\"grn\",\"source_id\":37,\"total_amount\":1300}','CLI','CLI','2026-10-15 14:55:07'),(1565,NULL,NULL,'GRN_APPROVED','purchasing','37','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"1300.00\"}','CLI','CLI','2026-10-15 14:55:07'),(1566,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','CLI','CLI','2026-10-15 14:55:07'),(1567,NULL,NULL,'PO_CREATED','purchasing','29',NULL,'{\"po_number\":\"PO-202610-0029\",\"supplier_id\":9,\"net_amount\":12000}','CLI','CLI','2026-10-15 14:55:07'),(1568,NULL,NULL,'PO_APPROVED','purchasing','29','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:55:07'),(1569,NULL,NULL,'PO_DELETED','purchasing','29','{\"id\":29,\"po_number\":\"PO-202610-0029\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:55:07\",\"created_at\":\"2026-10-15 14:55:07\",\"updated_at\":\"2026-10-15 14:55:07\",\"deleted_at\":null,\"supplier_name\":\"Chani Importers - Asus Agent\",\"supplier_code\":\"SUP-0009\",\"supplier_status\":\"active\",\"supplier_phone\":null,\"supplier_whatsapp\":null,\"supplier_address\":null,\"branch_name\":\"Main Branch - Kandy\",\"branch_code\":\"BR-KDY-01\",\"branch_address\":\"No 123, Peradeniya Road, Kandy\",\"branch_phone\":\"+94 81 222 3344\",\"branch_email\":\"kandy@curtisspos.com\",\"creator_name\":\"Super Administrator\",\"approver_name\":\"Super Administrator\",\"items\":[{\"id\":43,\"po_id\":29,\"product_id\":12,\"quantity\":\"10.00\",\"received_quantity\":\"0.00\",\"unit_cost\":\"1250.00\",\"selling_price\":\"1800.00\",\"line_total\":\"12500.00\",\"product_name\":\"Asus Gaming Laptop Tuff\",\"sku\":\"10100\",\"barcode\":\"1010\",\"has_expiry\":0,\"product_status\":\"active\"}]}','{\"deleted_at\":\"2026-10-15 11:25:07\"}','CLI','CLI','2026-10-15 14:55:07'),(1570,NULL,NULL,'PO_RESTORED','purchasing','29','{\"id\":29,\"po_number\":\"PO-202610-0029\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:55:07\",\"created_at\":\"2026-10-15 14:55:07\",\"updated_at\":\"2026-10-15 14:55:07\",\"deleted_at\":\"2026-10-15 14:55:07\"}','{\"deleted_at\":null}','CLI','CLI','2026-10-15 14:55:07'),(1571,NULL,NULL,'PO_CREATED','purchasing','30',NULL,'{\"po_number\":\"PO-202610-0029\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:55:43'),(1572,NULL,NULL,'PO_APPROVED','purchasing','30','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:55:43'),(1573,NULL,NULL,'PO_CREATED','purchasing','31',NULL,'{\"po_number\":\"PO-202610-0031\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:55:43'),(1574,NULL,NULL,'PO_CANCELLED','purchasing','31','{\"status\":\"Draft\"}','{\"status\":\"Cancelled\"}','CLI','CLI','2026-10-15 14:55:43'),(1575,NULL,NULL,'GRN_CREATED','purchasing','38',NULL,'{\"grn_number\":\"GRN-202610-0038\",\"supplier_invoice_no\":\"TEST-INV-8899\",\"actual_grn_total\":1300}','CLI','CLI','2026-10-15 14:55:43'),(1576,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','109',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN38-12-103\",\"quantity\":50,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:55:43'),(1577,NULL,NULL,'JOURNAL_POSTED','accounting','351',NULL,'{\"entry_number\":\"JE-20261015-0351\",\"source_type\":\"grn\",\"source_id\":38,\"total_amount\":1300}','CLI','CLI','2026-10-15 14:55:43'),(1578,NULL,NULL,'GRN_APPROVED','purchasing','38','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"1300.00\"}','CLI','CLI','2026-10-15 14:55:43'),(1579,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','CLI','CLI','2026-10-15 14:55:43'),(1584,NULL,NULL,'JOURNAL_POSTED','accounting','352',NULL,'{\"entry_number\":\"JE-20261015-0352\",\"source_type\":\"opening_balance\",\"source_id\":5,\"total_amount\":25000}','CLI','CLI','2026-10-15 14:56:41'),(1586,NULL,NULL,'JOURNAL_POSTED','accounting','353',NULL,'{\"entry_number\":\"JE-20261015-0353\",\"source_type\":\"reversal\",\"source_id\":5,\"total_amount\":25000}','CLI','CLI','2026-10-15 14:56:41'),(1588,NULL,NULL,'PO_CREATED','purchasing','32',NULL,'{\"po_number\":\"PO-202610-0032\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:56:41'),(1589,NULL,NULL,'PO_APPROVED','purchasing','32','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:56:41'),(1590,NULL,NULL,'PO_CREATED','purchasing','33',NULL,'{\"po_number\":\"PO-202610-0033\",\"supplier_id\":12,\"net_amount\":1150}','CLI','CLI','2026-10-15 14:56:41'),(1591,NULL,NULL,'PO_CANCELLED','purchasing','33','{\"status\":\"Draft\"}','{\"status\":\"Cancelled\"}','CLI','CLI','2026-10-15 14:56:41'),(1592,NULL,NULL,'GRN_CREATED','purchasing','39',NULL,'{\"grn_number\":\"GRN-202610-0039\",\"supplier_invoice_no\":\"TEST-INV-8899\",\"actual_grn_total\":1300}','CLI','CLI','2026-10-15 14:56:41'),(1593,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','109',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN39-12-292\",\"quantity\":50,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:56:41'),(1594,NULL,NULL,'JOURNAL_POSTED','accounting','354',NULL,'{\"entry_number\":\"JE-20261015-0352\",\"source_type\":\"grn\",\"source_id\":39,\"total_amount\":1300}','CLI','CLI','2026-10-15 14:56:41'),(1595,NULL,NULL,'GRN_APPROVED','purchasing','39','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"1300.00\"}','CLI','CLI','2026-10-15 14:56:41'),(1596,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','CLI','CLI','2026-10-15 14:56:41'),(1597,1,1,'INVENTORY_LOT_CREATED','inventory','116',NULL,'{\"branch_id\":1,\"product_id\":40,\"lot_number\":\"LOT-20261015-9732\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:56:41'),(1599,1,1,'INVENTORY_LOT_CREATED','inventory','117',NULL,'{\"branch_id\":1,\"product_id\":41,\"lot_number\":\"LOT-20261015-8312\",\"quantity\":0,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 14:56:41'),(1603,1,1,'INVENTORY_LOT_CREATED','inventory','118',NULL,'{\"branch_id\":1,\"product_id\":40,\"lot_number\":\"LOT-GRN40-40-585\",\"quantity\":10,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:56:41'),(1604,1,1,'INVENTORY_LOT_CREATED','inventory','119',NULL,'{\"branch_id\":1,\"product_id\":41,\"lot_number\":\"LOT-GRN40-41-505\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:56:41'),(1605,1,1,'JOURNAL_POSTED','accounting','355',NULL,'{\"entry_number\":\"JE-20261015-0355\",\"source_type\":\"grn\",\"source_id\":40,\"total_amount\":5500}','CLI','CLI','2026-10-15 14:56:41'),(1607,1,1,'PO_CREATED','purchasing','34',NULL,'{\"po_number\":\"PO-202610-0034\",\"supplier_id\":9,\"net_amount\":2100}','CLI','CLI','2026-10-15 14:56:41'),(1608,1,1,'PO_APPROVED','purchasing','34','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:56:41'),(1610,1,1,'INVENTORY_LOT_CREATED','inventory','120',NULL,'{\"branch_id\":1,\"product_id\":40,\"lot_number\":\"LOT-GRN41-40-253\",\"quantity\":15,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:56:41'),(1611,1,1,'JOURNAL_POSTED','accounting','356',NULL,'{\"entry_number\":\"JE-20261015-0356\",\"source_type\":\"grn\",\"source_id\":41,\"total_amount\":2100}','CLI','CLI','2026-10-15 14:56:41'),(1615,NULL,NULL,'GRN_CREATED','purchasing','43',NULL,'{\"grn_number\":\"GRN-202610-0040\",\"supplier_invoice_no\":\"INV-GRN-UNIQUE-1792056401\",\"actual_grn_total\":15000}','CLI','CLI','2026-10-15 14:56:41'),(1616,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','121',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN43-12-564\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 14:56:41'),(1617,NULL,NULL,'JOURNAL_POSTED','accounting','357',NULL,'{\"entry_number\":\"JE-20261015-0355\",\"source_type\":\"grn\",\"source_id\":43,\"total_amount\":15000}','CLI','CLI','2026-10-15 14:56:41'),(1618,NULL,NULL,'GRN_APPROVED','purchasing','43','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','CLI','CLI','2026-10-15 14:56:41'),(1619,NULL,NULL,'PO_CREATED','purchasing','35',NULL,'{\"po_number\":\"PO-202610-0034\",\"supplier_id\":9,\"net_amount\":12000}','CLI','CLI','2026-10-15 14:56:41'),(1620,NULL,NULL,'PO_APPROVED','purchasing','35','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 14:56:41'),(1621,NULL,NULL,'PO_DELETED','purchasing','35','{\"id\":35,\"po_number\":\"PO-202610-0034\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:56:41\",\"created_at\":\"2026-10-15 14:56:41\",\"updated_at\":\"2026-10-15 14:56:41\",\"deleted_at\":null,\"supplier_name\":\"Chani Importers - Asus Agent\",\"supplier_code\":\"SUP-0009\",\"supplier_status\":\"active\",\"supplier_phone\":null,\"supplier_whatsapp\":null,\"supplier_address\":null,\"branch_name\":\"Main Branch - Kandy\",\"branch_code\":\"BR-KDY-01\",\"branch_address\":\"No 123, Peradeniya Road, Kandy\",\"branch_phone\":\"+94 81 222 3344\",\"branch_email\":\"kandy@curtisspos.com\",\"creator_name\":\"Super Administrator\",\"approver_name\":\"Super Administrator\",\"items\":[{\"id\":49,\"po_id\":35,\"product_id\":12,\"quantity\":\"10.00\",\"received_quantity\":\"0.00\",\"unit_cost\":\"1250.00\",\"selling_price\":\"1800.00\",\"line_total\":\"12500.00\",\"product_name\":\"Asus Gaming Laptop Tuff\",\"sku\":\"10100\",\"barcode\":\"1010\",\"has_expiry\":0,\"product_status\":\"active\"}]}','{\"deleted_at\":\"2026-10-15 11:26:41\"}','CLI','CLI','2026-10-15 14:56:41'),(1622,NULL,NULL,'PO_RESTORED','purchasing','35','{\"id\":35,\"po_number\":\"PO-202610-0034\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 14:56:41\",\"created_at\":\"2026-10-15 14:56:41\",\"updated_at\":\"2026-10-15 14:56:41\",\"deleted_at\":\"2026-10-15 14:56:41\"}','{\"deleted_at\":null}','CLI','CLI','2026-10-15 14:56:41'),(1623,NULL,NULL,'SUPPLIER_CREATED','suppliers','22',NULL,'{\"supplier_code\":\"SUP-0022\",\"name\":\"AutoTest Supplier Returns\",\"phone\":\"0771234567\",\"whatsapp\":null,\"address\":\"Test Addr\",\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','CLI','CLI','2026-10-15 15:26:28'),(1624,NULL,NULL,'JOURNAL_POSTED','accounting','358',NULL,'{\"entry_number\":\"JE-20261015-0355\",\"source_type\":\"supplier_return\",\"source_id\":5,\"total_amount\":6000}','CLI','CLI','2026-10-15 15:26:28'),(1626,NULL,NULL,'JOURNAL_POSTED','accounting','359',NULL,'{\"entry_number\":\"JE-20261015-0359\",\"source_type\":\"supplier_return\",\"source_id\":6,\"total_amount\":3000}','CLI','CLI','2026-10-15 15:26:28'),(1628,NULL,NULL,'SUPPLIER_CREATED','suppliers','23',NULL,'{\"supplier_code\":\"SUP-0022\",\"name\":\"OptSuite Return Supplier 1792058560\",\"phone\":\"0779988771\",\"whatsapp\":\"0779988771\",\"address\":\"123 Return Way\",\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','CLI','CLI','2026-10-15 15:32:40'),(1629,NULL,NULL,'SUPPLIER_CREATED','suppliers','24',NULL,'{\"supplier_code\":\"SUP-0022\",\"name\":\"OptSuite Return Supplier 1792058583\",\"phone\":\"0779988771\",\"whatsapp\":\"0779988771\",\"address\":\"123 Return Way\",\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','CLI','CLI','2026-10-15 15:33:03'),(1630,NULL,NULL,'SUPPLIER_CREATED','suppliers','25',NULL,'{\"supplier_code\":\"SUP-0022\",\"name\":\"OptSuite Return Supplier 1792058596\",\"phone\":\"0779988771\",\"whatsapp\":\"0779988771\",\"address\":\"123 Return Way\",\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','CLI','CLI','2026-10-15 15:33:16'),(1631,NULL,NULL,'JOURNAL_POSTED','accounting','360',NULL,'{\"entry_number\":\"JE-20261015-0355\",\"source_type\":\"supplier_return\",\"source_id\":7,\"total_amount\":8000}','CLI','CLI','2026-10-15 15:33:16'),(1633,NULL,NULL,'SUPPLIER_CREATED','suppliers','26',NULL,'{\"supplier_code\":\"SUP-0022\",\"name\":\"OptSuite Return Supplier 1792058691\",\"phone\":\"0779988771\",\"whatsapp\":\"0779988771\",\"address\":\"123 Return Way\",\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','CLI','CLI','2026-10-15 15:34:51'),(1634,NULL,NULL,'JOURNAL_POSTED','accounting','361',NULL,'{\"entry_number\":\"JE-20261015-0355\",\"source_type\":\"supplier_return\",\"source_id\":8,\"total_amount\":8000}','CLI','CLI','2026-10-15 15:34:51'),(1636,NULL,NULL,'SUPPLIER_CREATED','suppliers','27',NULL,'{\"supplier_code\":\"SUP-0022\",\"name\":\"OptSuite Return Supplier 1792058754\",\"phone\":\"0779988771\",\"whatsapp\":\"0779988771\",\"address\":\"123 Return Way\",\"bank_name\":null,\"bank_account_name\":null,\"bank_account_no\":null,\"bank_branch\":null,\"status\":\"active\"}','CLI','CLI','2026-10-15 15:35:54'),(1637,NULL,NULL,'JOURNAL_POSTED','accounting','362',NULL,'{\"entry_number\":\"JE-20261015-0355\",\"source_type\":\"supplier_return\",\"source_id\":9,\"total_amount\":8000}','CLI','CLI','2026-10-15 15:35:54'),(1645,NULL,NULL,'JOURNAL_POSTED','accounting','363',NULL,'{\"entry_number\":\"JE-20261015-0355\",\"source_type\":\"opening_balance\",\"source_id\":6,\"total_amount\":25000}','CLI','CLI','2026-10-15 15:35:59'),(1647,NULL,NULL,'JOURNAL_POSTED','accounting','364',NULL,'{\"entry_number\":\"JE-20261015-0364\",\"source_type\":\"reversal\",\"source_id\":6,\"total_amount\":25000}','CLI','CLI','2026-10-15 15:35:59'),(1649,NULL,NULL,'GRN_CREATED','purchasing','49',NULL,'{\"grn_number\":\"GRN-202610-0040\",\"supplier_invoice_no\":\"INV-GRN-UNIQUE-1792058765\",\"actual_grn_total\":15000}','CLI','CLI','2026-10-15 15:36:05'),(1650,NULL,NULL,'INVENTORY_LOT_CREATED','inventory','127',NULL,'{\"branch_id\":1,\"product_id\":12,\"lot_number\":\"LOT-GRN49-12-963\",\"quantity\":20,\"movement_type\":\"purchase_grn\"}','CLI','CLI','2026-10-15 15:36:05'),(1651,NULL,NULL,'JOURNAL_POSTED','accounting','365',NULL,'{\"entry_number\":\"JE-20261015-0355\",\"source_type\":\"grn\",\"source_id\":49,\"total_amount\":15000}','CLI','CLI','2026-10-15 15:36:05'),(1652,NULL,NULL,'GRN_APPROVED','purchasing','49','{\"status\":\"Draft\"}','{\"status\":\"Approved\",\"total\":\"15000.00\"}','CLI','CLI','2026-10-15 15:36:05'),(1653,NULL,NULL,'PO_CREATED','purchasing','36',NULL,'{\"po_number\":\"PO-202610-0034\",\"supplier_id\":9,\"net_amount\":12000}','CLI','CLI','2026-10-15 15:36:11'),(1654,NULL,NULL,'PO_APPROVED','purchasing','36','{\"status\":\"Draft\"}','{\"status\":\"Approved\"}','CLI','CLI','2026-10-15 15:36:11'),(1655,NULL,NULL,'PO_DELETED','purchasing','36','{\"id\":36,\"po_number\":\"PO-202610-0034\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 15:36:11\",\"created_at\":\"2026-10-15 15:36:11\",\"updated_at\":\"2026-10-15 15:36:11\",\"deleted_at\":null,\"supplier_name\":\"Chani Importers - Asus Agent\",\"supplier_code\":\"SUP-0009\",\"supplier_status\":\"active\",\"supplier_phone\":null,\"supplier_whatsapp\":null,\"supplier_address\":null,\"branch_name\":\"Main Branch - Kandy\",\"branch_code\":\"BR-KDY-01\",\"branch_address\":\"No 123, Peradeniya Road, Kandy\",\"branch_phone\":\"+94 81 222 3344\",\"branch_email\":\"kandy@curtisspos.com\",\"creator_name\":\"Super Administrator\",\"approver_name\":\"Super Administrator\",\"items\":[{\"id\":50,\"po_id\":36,\"product_id\":12,\"quantity\":\"10.00\",\"received_quantity\":\"0.00\",\"unit_cost\":\"1250.00\",\"selling_price\":\"1800.00\",\"line_total\":\"12500.00\",\"product_name\":\"Asus Gaming Laptop Tuff\",\"sku\":\"10100\",\"barcode\":\"1010\",\"has_expiry\":0,\"product_status\":\"active\"}]}','{\"deleted_at\":\"2026-10-15 12:06:11\"}','CLI','CLI','2026-10-15 15:36:11'),(1656,NULL,NULL,'PO_RESTORED','purchasing','36','{\"id\":36,\"po_number\":\"PO-202610-0034\",\"supplier_id\":9,\"branch_id\":1,\"order_date\":\"2026-10-15\",\"expected_date\":\"2026-10-22\",\"total_amount\":\"12500.00\",\"discount_amount\":\"500.00\",\"net_amount\":\"12000.00\",\"notes\":\"CLI automated testing order with unique keywords XYZ99\",\"status\":\"Approved\",\"created_by\":1,\"approved_by\":1,\"approved_at\":\"2026-10-15 15:36:11\",\"created_at\":\"2026-10-15 15:36:11\",\"updated_at\":\"2026-10-15 15:36:11\",\"deleted_at\":\"2026-10-15 15:36:11\"}','{\"deleted_at\":null}','CLI','CLI','2026-10-15 15:36:11'),(1660,1,1,'INVENTORY_LOT_CREATED','inventory','128',NULL,'{\"branch_id\":1,\"product_id\":48,\"lot_number\":\"LOT-20261015-4642\",\"quantity\":20,\"movement_type\":\"opening_stock\"}','CLI','CLI','2026-10-15 15:36:17'),(1661,1,1,'JOURNAL_POSTED','accounting','366',NULL,'{\"entry_number\":\"JE-20261015-0355\",\"source_type\":\"opening_balance\",\"source_id\":48,\"total_amount\":18648}','CLI','CLI','2026-10-15 15:36:17'),(1663,1,1,'SESSION_EXPIRED','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 16:51:58'),(1664,1,1,'LOGOUT','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 16:51:58'),(1665,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 16:52:03'),(1666,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 16:52:04'),(1667,1,2,'BRANCH_SWITCH','auth','2','{\"from\":\"Main Branch - Kandy\"}','{\"to\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 16:52:07'),(1668,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 16:52:10'),(1669,1,1,'SESSION_EXPIRED','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 18:08:55'),(1670,1,1,'LOGOUT','auth','1',NULL,NULL,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 18:08:55'),(1671,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 18:08:56'),(1672,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-15 18:08:57'),(1673,1,2,'LOGIN_SUCCESS','auth','1',NULL,'{\"username\":\"superadmin\",\"active_branch\":\"Colombo Branch\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-16 00:30:12'),(1674,1,1,'BRANCH_SWITCH','auth','1','{\"from\":\"Colombo Branch\"}','{\"to\":\"Main Branch - Kandy\"}','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36 Edg/155.0.0.0','2026-10-16 00:30:14'),(1675,NULL,NULL,'LOGIN_FAILED_BAD_PASSWORD','auth','8',NULL,'{\"username\":\"susar.aa\"}','CLI','CLI','2026-10-16 00:59:13'),(1676,8,2,'LOGIN_SUCCESS','auth','8',NULL,'{\"username\":\"susar.aa\",\"active_branch\":\"Colombo Branch\"}','CLI','CLI','2026-10-16 00:59:36');
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(100) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `bank_branch_name` varchar(100) DEFAULT NULL,
  `opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `account_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_number` (`account_number`),
  KEY `account_id` (`account_id`),
  KEY `idx_bankacc_branch` (`branch_id`),
  KEY `idx_bankacc_status` (`status`),
  CONSTRAINT `bank_accounts_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `bank_accounts_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
INSERT INTO `bank_accounts` VALUES (1,'Hatton National Bank','HNB Operational Account','991821970',1,'Kandy Branch',10000.00,'active',59,'2026-10-12 17:06:08',NULL),(2,'Commercial Bank S15','Main Operations S15','CB-61780',1,NULL,10000.00,'active',60,'2026-10-12 17:06:09',NULL),(7,'Sec7 Test Bank','Sec7 Test Account','S7-ACC-TEST-99',1,NULL,0.00,'active',67,'2026-10-14 13:21:18',NULL);
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_deposits`
--

DROP TABLE IF EXISTS `bank_deposits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_deposits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deposit_no` varchar(50) NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `bank_account_id` int(10) unsigned NOT NULL,
  `deposit_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `source_type` enum('active_drawer','closed_day','other_cash') NOT NULL DEFAULT 'active_drawer',
  `business_day_id` bigint(20) unsigned DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `deposit_no` (`deposit_no`),
  KEY `business_day_id` (`business_day_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_dep_bank` (`bank_account_id`),
  KEY `idx_dep_branch` (`branch_id`),
  CONSTRAINT `bank_deposits_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `bank_deposits_ibfk_2` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`),
  CONSTRAINT `bank_deposits_ibfk_3` FOREIGN KEY (`business_day_id`) REFERENCES `business_days` (`id`),
  CONSTRAINT `bank_deposits_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_deposits`
--

LOCK TABLES `bank_deposits` WRITE;
/*!40000 ALTER TABLE `bank_deposits` DISABLE KEYS */;
INSERT INTO `bank_deposits` VALUES (7,'DEP-0001',1,1,'2026-10-12',5000.00,'other_cash',NULL,'DEP-ISO-TEST-18','Bank isolation audit test deposit',155,1,'2026-10-12 17:06:15');
/*!40000 ALTER TABLE `bank_deposits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_reconciliation_items`
--

DROP TABLE IF EXISTS `bank_reconciliation_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_reconciliation_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reconciliation_id` bigint(20) unsigned NOT NULL,
  `journal_line_id` bigint(20) unsigned NOT NULL,
  `is_cleared` tinyint(1) NOT NULL DEFAULT 0,
  `cleared_date` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bri_rec` (`reconciliation_id`),
  KEY `idx_bri_line` (`journal_line_id`),
  CONSTRAINT `bank_reconciliation_items_ibfk_1` FOREIGN KEY (`reconciliation_id`) REFERENCES `bank_reconciliations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bank_reconciliation_items_ibfk_2` FOREIGN KEY (`journal_line_id`) REFERENCES `journal_lines` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliation_items`
--

LOCK TABLES `bank_reconciliation_items` WRITE;
/*!40000 ALTER TABLE `bank_reconciliation_items` DISABLE KEYS */;
INSERT INTO `bank_reconciliation_items` VALUES (9,4,446,0,NULL),(10,5,474,0,NULL),(11,5,761,0,NULL),(12,5,764,0,NULL);
/*!40000 ALTER TABLE `bank_reconciliation_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_reconciliations`
--

DROP TABLE IF EXISTS `bank_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_reconciliations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reconciliation_no` varchar(50) NOT NULL,
  `bank_account_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `statement_start_date` date NOT NULL,
  `statement_end_date` date NOT NULL,
  `statement_opening_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `statement_closing_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `book_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cleared_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `outstanding_deposits` decimal(12,2) NOT NULL DEFAULT 0.00,
  `outstanding_payments` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reconciliation_difference` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','completed') NOT NULL DEFAULT 'draft',
  `reconciled_at` datetime DEFAULT NULL,
  `reconciled_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reconciliation_no` (`reconciliation_no`),
  KEY `created_by` (`created_by`),
  KEY `reconciled_by` (`reconciled_by`),
  KEY `idx_brec_bank_status` (`bank_account_id`,`status`),
  CONSTRAINT `bank_reconciliations_ibfk_1` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`),
  CONSTRAINT `bank_reconciliations_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `bank_reconciliations_ibfk_3` FOREIGN KEY (`reconciled_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliations`
--

LOCK TABLES `bank_reconciliations` WRITE;
/*!40000 ALTER TABLE `bank_reconciliations` DISABLE KEYS */;
INSERT INTO `bank_reconciliations` VALUES (3,'BREC-0001',1,1,'2026-10-01','2026-10-31',500.00,500.00,10000.00,500.00,0.00,0.00,0.00,'completed','2026-10-14 01:33:36',1,NULL,1,'2026-10-12 19:51:50'),(4,'BREC-0004',2,1,'2026-10-01','2026-10-31',500.00,500.00,10500.00,500.00,0.00,0.00,0.00,'draft',NULL,NULL,NULL,1,'2026-10-12 19:52:20'),(5,'BREC-0005',1,1,'2026-10-01','2026-10-31',100000.00,100000.00,-19000.00,100000.00,0.00,0.00,0.00,'draft',NULL,NULL,NULL,1,'2026-10-14 01:33:45');
/*!40000 ALTER TABLE `bank_reconciliations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_inventory`
--

DROP TABLE IF EXISTS `branch_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branch_inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `in_transit_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `damaged_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `low_stock_threshold` decimal(12,2) NOT NULL DEFAULT 10.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_branch_product` (`branch_id`,`product_id`),
  KEY `fk_bi_product` (`product_id`),
  CONSTRAINT `fk_bi_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_inventory`
--

LOCK TABLES `branch_inventory` WRITE;
/*!40000 ALTER TABLE `branch_inventory` DISABLE KEYS */;
INSERT INTO `branch_inventory` VALUES (12,1,12,2414.50,0.00,0.00,10.00,'2026-10-12 11:56:50','2026-10-15 15:36:05'),(13,1,13,22.00,1.00,0.00,10.00,'2026-10-12 11:58:19','2026-10-14 19:10:14'),(18,1,18,36.00,0.00,0.00,10.00,'2026-10-12 12:49:04','2026-10-14 16:39:59'),(35,2,12,1000.50,0.00,0.00,10.00,'2026-10-12 17:06:14','2026-10-14 12:36:51'),(41,2,13,6.00,0.00,0.00,10.00,'2026-10-14 12:31:21','2026-10-14 19:10:14'),(42,1,22,8.00,0.00,0.00,5.00,'2026-10-14 15:54:38','2026-10-14 15:54:38'),(43,1,23,9.00,0.00,0.00,5.00,'2026-10-14 15:56:10','2026-10-14 15:56:10'),(45,1,24,9.00,0.00,0.00,5.00,'2026-10-14 15:57:46','2026-10-14 15:57:46'),(64,1,30,200.00,0.00,0.00,10.00,'2026-10-15 14:14:58','2026-10-15 14:29:55'),(66,1,32,25.00,0.00,0.00,10.00,'2026-10-15 14:30:12','2026-10-15 14:30:12'),(67,1,33,20.00,0.00,0.00,10.00,'2026-10-15 14:30:12','2026-10-15 14:30:12');
/*!40000 ALTER TABLE `branch_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `branches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_branches_status` (`status`),
  KEY `idx_branches_deleted` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Main Branch - Kandy','BR-KDY-01','No 123, Peradeniya Road, Kandy','+94 81 222 3344','kandy@curtisspos.com','active','2026-10-01 18:51:44','2026-10-01 18:51:44',NULL),(2,'Colombo Branch','BR-CMB-02','No 45, Galle Road, Colombo 03','+94 11 255 6677','colombo@curtisspos.com','active','2026-10-01 18:51:44','2026-10-01 18:51:44',NULL);
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `brands`
--

DROP TABLE IF EXISTS `brands`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `brands` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_brands_name` (`name`),
  KEY `idx_brands_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `brands`
--

LOCK TABLES `brands` WRITE;
/*!40000 ALTER TABLE `brands` DISABLE KEYS */;
INSERT INTO `brands` VALUES (7,'Asus','active','2026-10-12 11:46:10','2026-10-12 11:46:10',NULL),(12,'Baby Cheramy','active','2026-10-12 11:58:37','2026-10-12 11:58:37',NULL),(17,'Test Brand Alpha 263315_1791804966 Updated','active','2026-10-12 17:06:06','2026-10-12 17:06:06',NULL),(18,'Sec1 Test Brand','active','2026-10-12 17:06:10','2026-10-12 17:06:10',NULL),(22,'OptBrand_1792053892','active','2026-10-15 14:14:52','2026-10-15 14:14:52',NULL);
/*!40000 ALTER TABLE `brands` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `business_days`
--

DROP TABLE IF EXISTS `business_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `business_days` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int(10) unsigned NOT NULL,
  `business_date` date NOT NULL,
  `opening_cash` decimal(12,2) NOT NULL DEFAULT 20000.00,
  `expected_cash` decimal(12,2) NOT NULL DEFAULT 0.00,
  `actual_cash` decimal(12,2) DEFAULT NULL,
  `card_settlement_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `shortage_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `overage_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `retained_cash` decimal(12,2) NOT NULL DEFAULT 20000.00,
  `cash_to_deposit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `opened_by` int(10) unsigned NOT NULL,
  `closed_by` int(10) unsigned DEFAULT NULL,
  `opened_at` datetime NOT NULL DEFAULT current_timestamp(),
  `closed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `cash_denominations` longtext DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `opened_by` (`opened_by`),
  KEY `closed_by` (`closed_by`),
  KEY `idx_bday_branch_status` (`branch_id`,`status`),
  KEY `idx_bday_date` (`business_date`),
  CONSTRAINT `business_days_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `business_days_ibfk_2` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`),
  CONSTRAINT `business_days_ibfk_3` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `business_days`
--

LOCK TABLES `business_days` WRITE;
/*!40000 ALTER TABLE `business_days` DISABLE KEYS */;
INSERT INTO `business_days` VALUES (12,1,'2026-10-12',20000.00,0.00,NULL,0.00,0.00,0.00,20000.00,0.00,'closed',1,NULL,'2026-10-12 17:06:12',NULL,'Regression Run Stage 31',NULL),(14,1,'2026-10-12',20000.00,0.00,NULL,0.00,0.00,0.00,20000.00,0.00,'open',1,NULL,'2026-10-12 17:06:12',NULL,'Regression Run Stage 32',NULL),(15,1,'2026-09-27',5000.00,0.00,NULL,0.00,0.00,0.00,5000.00,0.00,'closed',1,1,'2026-09-27 08:00:00','2026-09-27 20:00:00',NULL,NULL),(16,2,'2026-10-12',1000.00,0.00,NULL,0.00,0.00,0.00,1000.00,0.00,'open',1,NULL,'2026-10-12 17:06:14',NULL,NULL,NULL);
/*!40000 ALTER TABLE `business_days` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card_processors`
--

DROP TABLE IF EXISTS `card_processors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `card_processors` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `processor_code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `merchant_id` varchar(100) DEFAULT NULL,
  `default_bank_account_id` int(10) unsigned DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `processor_code` (`processor_code`),
  KEY `idx_cp_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_processors`
--

LOCK TABLES `card_processors` WRITE;
/*!40000 ALTER TABLE `card_processors` DISABLE KEYS */;
INSERT INTO `card_processors` VALUES (1,'DFCC_POS_TEST','DFCC Bank POS Acquirer (Updated)','MERCH-DFCC-99-REV2',1,'active','2026-10-12 17:06:14','2026-10-12 17:06:14'),(2,'PROC_TEST_ALT','Alternate Bank POS','MERCH-ALT-02',1,'active','2026-10-12 17:06:14','2026-10-12 17:06:14');
/*!40000 ALTER TABLE `card_processors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card_settlement_items`
--

DROP TABLE IF EXISTS `card_settlement_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `card_settlement_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `settlement_id` bigint(20) unsigned NOT NULL,
  `payment_id` bigint(20) unsigned NOT NULL,
  `gross_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_csi_settlement` (`settlement_id`),
  KEY `idx_csi_payment` (`payment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_settlement_items`
--

LOCK TABLES `card_settlement_items` WRITE;
/*!40000 ALTER TABLE `card_settlement_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `card_settlement_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `card_settlements`
--

DROP TABLE IF EXISTS `card_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `card_settlements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `settlement_no` varchar(50) NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `processor_id` int(10) unsigned DEFAULT NULL,
  `bank_account_id` int(10) unsigned NOT NULL,
  `settlement_date` date NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `gross_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `processing_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_bank_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','posted','reversed') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `settlement_no` (`settlement_no`),
  KEY `idx_cs_branch` (`branch_id`),
  KEY `idx_cs_processor` (`processor_id`),
  KEY `idx_cs_bank_acc` (`bank_account_id`),
  KEY `idx_cs_status` (`status`),
  KEY `idx_cs_date` (`settlement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `card_settlements`
--

LOCK TABLES `card_settlements` WRITE;
/*!40000 ALTER TABLE `card_settlements` DISABLE KEYS */;
/*!40000 ALTER TABLE `card_settlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_drawer_movements`
--

DROP TABLE IF EXISTS `cash_drawer_movements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cash_drawer_movements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `business_day_id` bigint(20) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `movement_type` varchar(50) NOT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `direction` enum('in','out') NOT NULL,
  `reason` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_cdm_bday` (`business_day_id`),
  KEY `idx_cdm_branch` (`branch_id`),
  KEY `idx_cdm_type` (`movement_type`),
  KEY `idx_cdm_source` (`source_type`,`source_id`),
  CONSTRAINT `cash_drawer_movements_ibfk_1` FOREIGN KEY (`business_day_id`) REFERENCES `business_days` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cash_drawer_movements_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `cash_drawer_movements_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=105 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_drawer_movements`
--

LOCK TABLES `cash_drawer_movements` WRITE;
/*!40000 ALTER TABLE `cash_drawer_movements` DISABLE KEYS */;
INSERT INTO `cash_drawer_movements` VALUES (39,12,1,'opening_cash','business_days',12,20000.00,'in','Initial Opening Cash Drawer','Regression Run Stage 31',1,'2026-10-12 17:06:12'),(42,14,1,'opening_cash','business_days',14,20000.00,'in','Initial Opening Cash Drawer','Regression Run Stage 32',1,'2026-10-12 17:06:12'),(54,16,2,'opening_cash','business_days',16,1000.00,'in','Initial Opening Cash Drawer',NULL,1,'2026-10-12 17:06:14'),(61,14,1,'pos_sale','sales',2,1500.00,'in','POS Cash Sale Invoice #0002',NULL,1,'2026-10-12 19:41:09'),(62,14,1,'pos_sale','sales',7,67.00,'in','POS Cash Sale Invoice #0005',NULL,1,'2026-10-12 23:02:17'),(63,14,1,'pos_sale','sales',12,637.50,'in','POS Cash Sale Invoice #0010',NULL,1,'2026-10-13 13:02:12'),(73,16,2,'employee_advance','employee_advances',15,1000.00,'out','Employee Salary Advance (ADV-202610-8482) - Sandaru',NULL,1,'2026-10-13 19:48:19'),(77,14,1,'employee_advance','employee_advances',20,1000.01,'out','Employee Salary Advance (ADV-202610-2965) - Chamath',NULL,1,'2026-10-13 20:11:20'),(81,14,1,'salary_payment','payroll_payments',26,20000.00,'out','Payroll Salary Disbursement (2026-10)',NULL,1,'2026-10-14 01:31:18'),(82,14,1,'salary_payment','payroll_payments',33,101200.00,'out','Payroll Salary Disbursement (CLI-202611)','Full Salary Disbursement CLI Test',1,'2026-10-14 01:55:55'),(83,14,1,'salary_payment','payroll_payments',34,101200.00,'out','Payroll Salary Disbursement (CLI-R-1791923201)','Full Salary Disbursement CLI Test',1,'2026-10-14 01:56:41'),(84,14,1,'salary_payment','payroll_payments',41,101200.00,'out','Payroll Salary Disbursement (CLI-R-1791923899)','Full Salary Disbursement CLI Test',1,'2026-10-14 02:08:19'),(85,14,1,'customer_collection','customer_credit_ledger',12,1500.00,'in','Customer Collection (CPAY-0001-1 - Cash in Hand)',NULL,1,'2026-10-14 13:13:46'),(86,14,1,'customer_collection','customer_credit_ledger',13,1000.00,'in','Customer Collection (CPAY-0001-2 - Cash in Hand)',NULL,1,'2026-10-14 13:13:46'),(87,14,1,'customer_collection','customer_credit_ledger',15,1500.00,'in','Customer Collection (CPAY-0002-1 - Cash in Hand)',NULL,1,'2026-10-14 13:14:14'),(88,14,1,'customer_collection','customer_credit_ledger',16,1000.00,'in','Customer Collection (CPAY-0002-2 - Cash in Hand)',NULL,1,'2026-10-14 13:14:14'),(89,14,1,'customer_collection','customer_credit_ledger',18,1500.00,'in','Customer Collection (CPAY-0001-1 - Cash in Hand)',NULL,1,'2026-10-14 13:14:57'),(90,14,1,'customer_collection','customer_credit_ledger',19,1000.00,'in','Customer Collection (CPAY-0001-2 - Cash in Hand)',NULL,1,'2026-10-14 13:14:57'),(91,14,1,'customer_collection','customer_credit_ledger',21,1000.00,'in','Customer Collection (CPAY-0001 - Cash in Hand)',NULL,1,'2026-10-14 13:15:14'),(92,14,1,'customer_collection','customer_credit_ledger',23,3000.00,'in','Customer Collection (CPAY-0003 - Cash in Hand)',NULL,1,'2026-10-14 13:15:14'),(93,14,1,'customer_collection','customer_credit_ledger',24,4000.00,'in','Customer Collection (CPAY-0004 - Cash in Hand)',NULL,1,'2026-10-14 13:15:14'),(94,14,1,'customer_collection','customer_credit_ledger',25,2000.00,'in','Customer Collection (CPAY-0005 - Cash in Hand)',NULL,1,'2026-10-14 13:15:14'),(95,14,1,'customer_collection','customer_credit_ledger',28,1500.00,'in','Customer Collection (CPAY-0008-1 - Cash in Hand)',NULL,1,'2026-10-14 13:20:14'),(96,14,1,'customer_collection','customer_credit_ledger',31,3000.00,'in','Customer Collection (CPAY-0001 - Cash in Hand)',NULL,1,'2026-10-14 13:20:20'),(97,14,1,'customer_collection','customer_credit_ledger',32,4000.00,'in','Customer Collection (CPAY-0002 - Cash in Hand)',NULL,1,'2026-10-14 13:20:20'),(98,14,1,'customer_collection','customer_credit_ledger',39,3000.00,'in','Customer Collection (CPAY-0001 - Cash in Hand)',NULL,1,'2026-10-14 13:21:18'),(99,14,1,'customer_collection','customer_credit_ledger',40,4000.00,'in','Customer Collection (CPAY-0002 - Cash in Hand)',NULL,1,'2026-10-14 13:21:18'),(100,14,1,'customer_collection','customer_credit_ledger',47,1500.00,'in','Customer Collection (CPAY-0007-1 - Cash in Hand)',NULL,1,'2026-10-14 13:21:54'),(101,16,2,'customer_collection','customer_credit_ledger',50,6000.00,'in','Customer Collection (CPAY-0007 - Cash in Hand)',NULL,1,'2026-10-14 13:52:53'),(102,14,1,'customer_collection','customer_credit_ledger',51,1500.00,'in','Customer Collection (CPAY-0008-1 - Cash in Hand)',NULL,1,'2026-10-14 15:18:45'),(103,14,1,'customer_collection','customer_credit_ledger',54,1200.00,'in','Customer Collection (CPAY-0008-1 - Cash in Hand)',NULL,1,'2026-10-14 15:18:46'),(104,14,1,'customer_collection','customer_credit_ledger',56,500.00,'in','Customer Collection (CPAY-0008-1 - Cash in Hand)',NULL,1,'2026-10-14 15:28:33');
/*!40000 ALTER TABLE `cash_drawer_movements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_categories_name` (`name`),
  KEY `idx_categories_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (7,'Gaming Laptop','active','2026-10-12 11:46:17','2026-10-12 11:46:17',NULL),(12,'Cream','active','2026-10-12 11:58:40','2026-10-12 11:58:40',NULL),(17,'Test Category Beta 263315_1791804966 Updated','active','2026-10-12 17:06:06','2026-10-12 17:06:06',NULL),(18,'Sec1 Test Category','active','2026-10-12 17:06:10','2026-10-12 17:06:10',NULL),(22,'OptCat_1792053892','active','2026-10-15 14:14:52','2026-10-15 14:14:52',NULL);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chart_of_accounts`
--

DROP TABLE IF EXISTS `chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chart_of_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('asset','liability','equity','income','expense') NOT NULL,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_code` (`account_code`),
  KEY `parent_id` (`parent_id`),
  KEY `idx_coa_code` (`account_code`),
  KEY `idx_coa_type` (`account_type`),
  KEY `idx_coa_branch` (`branch_id`),
  CONSTRAINT `chart_of_accounts_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chart_of_accounts_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chart_of_accounts`
--

LOCK TABLES `chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `chart_of_accounts` DISABLE KEYS */;
INSERT INTO `chart_of_accounts` VALUES (1,'1010','Cash in Hand','asset',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(2,'1015','Petty Cash','asset',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(3,'1020','Bank Account','asset',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(4,'1030','Card Receivable','asset',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(5,'1040','Customer Receivables (AR)','asset',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(6,'1200','Inventory Asset','asset',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(7,'1250','Damaged Inventory Loss','expense',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(8,'2010','Gift Voucher Liability','liability',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(9,'2020','Supplier Payables (AP)','liability',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(10,'3010','Opening Balance Equity','equity',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(11,'4010','Sales Revenue','income',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(12,'4020','Sales Returns & Allowances','income',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(13,'5010','Cost of Goods Sold (COGS)','expense',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(14,'5020','Inventory Adjustments','expense',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(15,'5030','Sales Discounts','expense',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(16,'5040','Cash Shortage / Overage','expense',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(17,'5100','General Operating Expenses','expense',NULL,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(20,'5300','Inventory Adjustment Account','expense',16,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(21,'5310','Damage & Wastage Expense Account','expense',16,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(22,'1045','Supplier Credit / Receivable Account','asset',3,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(23,'2025','Outstanding Cheques / Cheques Issued Account','liability',7,NULL,1,'active','2026-10-01 18:51:44','2026-10-01 18:51:44'),(24,'4050','Bank Interest / Other Income Account','income',NULL,NULL,1,'active','2026-10-01 18:51:45','2026-10-01 18:51:45'),(32,'5170','Card Processing Fees','expense',NULL,NULL,1,'active','2026-10-01 18:51:47','2026-10-01 18:51:47'),(33,'5110','Fuel & Transport Expense','expense',NULL,NULL,1,'active','2026-10-01 18:51:47','2026-10-01 18:51:47'),(34,'5120','Meals & Refreshment Expense','expense',NULL,NULL,1,'active','2026-10-01 18:51:47','2026-10-01 18:51:47'),(35,'5130','Utilities & Bills Expense','expense',NULL,NULL,1,'active','2026-10-01 18:51:47','2026-10-01 18:51:47'),(36,'5140','Repairs & Maintenance Expense','expense',NULL,NULL,1,'active','2026-10-01 18:51:47','2026-10-01 18:51:47'),(37,'5150','Accommodation & Travel Expense','expense',NULL,NULL,1,'active','2026-10-01 18:51:47','2026-10-01 18:51:47'),(38,'5160','General Office Expenses','expense',NULL,NULL,1,'active','2026-10-01 18:51:47','2026-10-01 18:51:47'),(47,'1021','Commercial Bank - Personal Account (8018526390)','asset',3,NULL,0,'active','2026-10-11 01:03:28','2026-10-11 01:03:28'),(49,'1012','Cash Drawer','asset',NULL,NULL,1,'active','2026-10-11 01:10:05','2026-10-11 01:10:05'),(50,'1048','Employee Salary Advances','asset',NULL,NULL,1,'active','2026-10-11 23:39:54','2026-10-11 23:39:54'),(51,'2050','Salaries & Wages Payable','liability',NULL,NULL,1,'active','2026-10-11 23:39:54','2026-10-11 23:39:54'),(52,'2055','EPF Payable','liability',NULL,NULL,1,'active','2026-10-11 23:39:54','2026-10-11 23:39:54'),(53,'2060','ETF Payable','liability',NULL,NULL,1,'active','2026-10-11 23:39:54','2026-10-11 23:39:54'),(54,'5200','Salaries & Wages Expense','expense',NULL,NULL,1,'active','2026-10-11 23:39:54','2026-10-11 23:39:54'),(55,'5210','Employer EPF & ETF Contribution Expense','expense',NULL,NULL,1,'active','2026-10-11 23:39:54','2026-10-11 23:39:54'),(56,'5220','Sales Commissions & Employee Allowances Expense','expense',NULL,NULL,1,'active','2026-10-11 23:39:54','2026-10-11 23:39:54'),(57,'1032','KOKO Payments Receivable','asset',NULL,NULL,1,'active','2026-10-12 00:40:02','2026-10-12 00:40:02'),(58,'TEST7251','Test Cleaning Expense','expense',NULL,NULL,0,'active','2026-10-12 17:06:07','2026-10-12 17:06:07'),(59,'1022','Hatton National Bank - HNB Operational Account (991821970)','asset',3,1,0,'active','2026-10-12 17:06:08','2026-10-12 17:06:08'),(60,'1023','Commercial Bank S15 - Main Operations S15 (CB-61780)','asset',3,1,0,'active','2026-10-12 17:06:09','2026-10-12 17:06:09'),(61,'1024','S11 Commercial Bank - S11 Operational Account (ACC-S11-001)','asset',3,NULL,0,'active','2026-10-12 17:06:12','2026-10-12 17:06:12'),(64,'1025','Test Inactive Bank - Inactive Operating Account (INACT-BANK-3370)','asset',3,1,0,'active','2026-10-12 17:06:14','2026-10-12 17:06:14'),(65,'1026','Sec7 Test Bank - Sec7 Test Account (S7-ACC-TEST-99)','asset',3,1,0,'active','2026-10-14 13:15:14','2026-10-14 13:15:14'),(66,'1027','Sec7 Test Bank - Sec7 Test Account (S7-ACC-TEST-99)','asset',3,1,0,'active','2026-10-14 13:20:20','2026-10-14 13:20:20'),(67,'1028','Sec7 Test Bank - Sec7 Test Account (S7-ACC-TEST-99)','asset',3,1,0,'active','2026-10-14 13:21:18','2026-10-14 13:21:18');
/*!40000 ALTER TABLE `chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_cheques`
--

DROP TABLE IF EXISTS `customer_cheques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_cheques` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cheque_no` varchar(100) NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `cheque_date` date NOT NULL,
  `received_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_no` varchar(100) DEFAULT NULL,
  `customer_credit_ledger_id` bigint(20) unsigned DEFAULT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `status` enum('in_hand','deposited','cleared','returned','cancelled') NOT NULL DEFAULT 'in_hand',
  `return_reason` varchar(255) DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `returned_by` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cust_chq_cust` (`customer_id`),
  KEY `idx_cust_chq_branch` (`branch_id`),
  KEY `idx_cust_chq_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_cheques`
--

LOCK TABLES `customer_cheques` WRITE;
/*!40000 ALTER TABLE `customer_cheques` DISABLE KEYS */;
INSERT INTO `customer_cheques` VALUES (11,'S7-CHQ-1001',40,1,'Sampath Bank','2026-10-15','2026-10-14',4000.00,'CPAY-0005',43,NULL,'returned','Insufficient funds in customer account','2026-10-14 13:21:18',1,1,'2026-10-14 13:21:18','2026-10-14 13:21:18'),(12,'S7-CHQ-CANCELLED',40,1,'BOC','2026-10-01','2026-10-01',1000.00,NULL,NULL,NULL,'cancelled',NULL,NULL,NULL,1,'2026-10-14 13:21:18','2026-10-14 13:21:18'),(13,'S7-CHQ-BANKED-1002',40,1,'Sec7 Test Bank','2026-10-14','2026-10-14',3000.00,'CPAY-0006',45,7,'returned','Cleared cheque bounced post-clearance','2026-10-14 13:21:18',1,1,'2026-10-14 13:21:18','2026-10-14 13:21:18');
/*!40000 ALTER TABLE `customer_cheques` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_credit_ledger`
--

DROP TABLE IF EXISTS `customer_credit_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customer_credit_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` int(10) unsigned NOT NULL,
  `transaction_type` enum('credit_sale','payment','opening_balance','adjustment','cheque_return') NOT NULL DEFAULT 'payment',
  `payment_method` varchar(50) DEFAULT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `invoice_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance_after` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_ccl_branch` (`branch_id`),
  KEY `fk_ccl_user` (`user_id`),
  KEY `idx_ccl_customer` (`customer_id`),
  KEY `idx_ccl_type` (`transaction_type`),
  CONSTRAINT `fk_ccl_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_ccl_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ccl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_credit_ledger`
--

LOCK TABLES `customer_credit_ledger` WRITE;
/*!40000 ALTER TABLE `customer_credit_ledger` DISABLE KEYS */;
INSERT INTO `customer_credit_ledger` VALUES (10,16,'credit_sale',NULL,NULL,'0002',9000.00,6500.00,2500.00,2500.00,'POS Credit Sale Invoice #0002',1,1,'2026-10-12 19:41:09'),(39,41,'payment','Cash in Hand',NULL,'CPAY-0001',0.00,3000.00,-3000.00,5000.00,'Collection via Cash in Hand - Section 7 Test Inactive Customer Recovery',1,1,'2026-10-14 13:21:18'),(40,40,'payment','Cash in Hand',NULL,'CPAY-0002',0.00,4000.00,-4000.00,11000.00,'Collection via Cash in Hand - Section 7 Test Cash Collection',1,1,'2026-10-14 13:21:18'),(41,40,'payment','Card',NULL,'CPAY-0003',0.00,2000.00,-2000.00,9000.00,'Collection via Card - Section 7 Test Card Collection',1,1,'2026-10-14 13:21:18'),(42,40,'payment','Bank',1,'CPAY-0004',0.00,3000.00,-3000.00,6000.00,'Collection via Bank - Section 7 Test Bank Collection',1,1,'2026-10-14 13:21:18'),(43,40,'payment','Cheque',NULL,'CPAY-0005',0.00,4000.00,-4000.00,2000.00,'Collection via Cheque - Section 7 Test Cheque Collection',1,1,'2026-10-14 13:21:18'),(44,40,'cheque_return','Cheque',NULL,'CHQ-RET-S7-CHQ-1001',0.00,0.00,4000.00,6000.00,'Cheque #S7-CHQ-1001 returned: Insufficient funds in customer account',1,1,'2026-10-14 13:21:18'),(45,40,'payment','Cheque',NULL,'CPAY-0006',0.00,3000.00,-3000.00,3000.00,'Collection via Cheque - Section 7 Test Cheque for Bank Deposit',1,1,'2026-10-14 13:21:18'),(46,40,'cheque_return','Cheque',7,'CHQ-RET-S7-CHQ-BANKED-1002',0.00,0.00,3000.00,6000.00,'Cheque #S7-CHQ-BANKED-1002 returned: Cleared cheque bounced post-clearance',1,1,'2026-10-14 13:21:18'),(50,40,'payment','Cash in Hand',NULL,'CPAY-0007',0.00,6000.00,-6000.00,0.00,'Collection via Cash in Hand - Customer credit collection payment',2,1,'2026-10-14 13:52:53'),(56,41,'payment','Cash in Hand',NULL,'CPAY-0008-1',0.00,500.00,-500.00,4500.00,'Collection via Cash in Hand - Customer credit collection payment',1,1,'2026-10-14 15:28:33'),(57,41,'payment','Petty Cash',NULL,'CPAY-0008-2',0.00,500.00,-500.00,4000.00,'Collection via Petty Cash - Customer credit collection payment',1,1,'2026-10-14 15:28:33');
/*!40000 ALTER TABLE `customer_credit_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `customers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_code` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `card_barcode` varchar(100) DEFAULT NULL,
  `credit_limit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `credit_balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_walkin` tinyint(1) NOT NULL DEFAULT 0,
  `is_loyalty` tinyint(1) NOT NULL DEFAULT 0,
  `loyalty_discount_override` decimal(5,2) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_code` (`customer_code`),
  UNIQUE KEY `card_barcode` (`card_barcode`),
  KEY `idx_cust_barcode` (`card_barcode`),
  KEY `idx_cust_phone` (`phone`),
  KEY `idx_cust_name` (`name`),
  KEY `idx_cust_status` (`status`),
  KEY `idx_cust_deleted` (`deleted_at`),
  KEY `idx_customers_loyalty` (`is_loyalty`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'CUST-WALKIN','Walk-in Customer',NULL,NULL,'Default POS General Customer',NULL,0.00,0.00,1,0,NULL,'active','2026-10-12 08:56:34','2026-10-12 08:56:34',NULL),(14,'CUS-00002','Buthsara Customer','04711522666',NULL,NULL,NULL,0.00,0.00,0,1,10.00,'active','2026-10-12 19:22:50','2026-10-12 19:22:50',NULL),(15,'CUS-00015','Non Loyality Customer','0761452633',NULL,NULL,NULL,0.00,0.00,0,0,NULL,'active','2026-10-12 19:23:16','2026-10-12 19:23:16',NULL),(16,'CUS-00016','1000 Credit Customer','01236522266',NULL,NULL,NULL,1000.00,2500.00,0,0,NULL,'active','2026-10-12 19:23:39','2026-10-12 19:41:09',NULL),(22,'CUS-00017','Susara Senarathne','0761407875','0761407875',NULL,NULL,0.00,0.00,0,0,NULL,'active','2026-10-12 21:43:58','2026-10-12 21:43:58',NULL),(23,'CUS-00023','Himaya Senarathne','0761407878','0761407878',NULL,NULL,0.00,0.00,0,0,NULL,'active','2026-10-12 21:44:44','2026-10-12 21:44:44',NULL),(40,'CUS-S701','Sec7 Test Active Customer','0771112233','0771112233',NULL,'BAR-S701',50000.00,0.00,0,0,NULL,'active','2026-10-14 13:21:18','2026-10-14 13:52:53',NULL),(41,'CUS-S702','Sec7 Test Inactive Customer','0774445566','0774445566',NULL,'BAR-S702',30000.00,4000.00,0,0,NULL,'active','2026-10-14 13:21:18','2026-10-14 15:28:33',NULL),(42,'CUS-S703','Sec7 Test Zero Balance Customer','0777778899','0777778899',NULL,'BAR-S703',20000.00,0.00,0,0,NULL,'active','2026-10-14 13:21:18','2026-10-14 13:21:18',NULL),(46,'RET-TEST-CUST-1791973406','Return Test Customer','0779998877',NULL,NULL,NULL,0.00,5000.00,0,0,NULL,'active','2026-10-14 15:53:26','2026-10-14 15:53:26',NULL),(47,'RET-TEST-CUST-1791973439','Return Test Customer','0779998877',NULL,NULL,NULL,0.00,5000.00,0,0,NULL,'active','2026-10-14 15:53:59','2026-10-14 15:53:59',NULL),(48,'RET-TEST-CUST-1791973460','Return Test Customer','0779998877',NULL,NULL,NULL,0.00,5000.00,0,0,NULL,'active','2026-10-14 15:54:20','2026-10-14 15:54:20',NULL),(49,'RET-TEST-CUST-1791973478','Return Test Customer','0779998877',NULL,NULL,NULL,0.00,5000.00,0,0,NULL,'active','2026-10-14 15:54:38','2026-10-14 15:54:38',NULL),(50,'RET-TEST-CUST-1791973570','Return Test Customer','0779998877',NULL,NULL,NULL,0.00,5000.00,0,0,NULL,'active','2026-10-14 15:56:10','2026-10-14 15:56:10',NULL),(51,'RET-TEST-CUST-1791973666','Return Test Customer','0779998877',NULL,NULL,NULL,0.00,5000.00,0,0,NULL,'active','2026-10-14 15:57:46','2026-10-14 15:57:46',NULL);
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `damaged_stock`
--

DROP TABLE IF EXISTS `damaged_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `damaged_stock` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `lot_id` int(10) unsigned DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `stock_type` enum('damaged','wastage','expired') NOT NULL DEFAULT 'damaged',
  `cost` decimal(12,2) DEFAULT NULL,
  `sales_return_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('active','disposed') NOT NULL DEFAULT 'active',
  `reason` varchar(255) DEFAULT NULL,
  `logged_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `disposed_at` datetime DEFAULT NULL,
  `disposed_by` int(10) unsigned DEFAULT NULL,
  `disposal_notes` text DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sales_return_id` (`sales_return_id`),
  KEY `logged_by` (`logged_by`),
  KEY `idx_damaged_branch` (`branch_id`),
  KEY `idx_damaged_product` (`product_id`),
  CONSTRAINT `damaged_stock_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `damaged_stock_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `damaged_stock_ibfk_3` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `damaged_stock_ibfk_4` FOREIGN KEY (`logged_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `damaged_stock`
--

LOCK TABLES `damaged_stock` WRITE;
/*!40000 ALTER TABLE `damaged_stock` DISABLE KEYS */;
INSERT INTO `damaged_stock` VALUES (1,1,18,18,2.00,'damaged',NULL,12,'active','Customer Return',1,'2026-10-14 16:39:02',NULL,NULL,NULL,NULL),(2,1,18,18,1.00,'damaged',NULL,14,'active','Customer Return',1,'2026-10-14 16:40:29',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `damaged_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_advances`
--

DROP TABLE IF EXISTS `employee_advances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_advances` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `advance_no` varchar(50) NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `recovered_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(12,2) NOT NULL,
  `disbursement_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash in Hand',
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `source_account_id` int(10) unsigned DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `recovery_period_id` int(10) unsigned DEFAULT NULL,
  `status` enum('active','fully_recovered','cancelled') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` int(10) unsigned DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `advance_no` (`advance_no`),
  KEY `idx_adv_emp` (`employee_id`),
  KEY `idx_adv_branch` (`branch_id`),
  KEY `idx_adv_status` (`status`),
  KEY `idx_emp_adv_branch_status` (`branch_id`,`status`),
  KEY `idx_emp_adv_emp_status` (`employee_id`,`status`),
  CONSTRAINT `fk_adv_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_advances`
--

LOCK TABLES `employee_advances` WRITE;
/*!40000 ALTER TABLE `employee_advances` DISABLE KEYS */;
INSERT INTO `employee_advances` VALUES (15,'ADV-202610-8482',1,2,1000.00,0.00,1000.00,'2026-10-13','Cash Drawer',NULL,49,NULL,1,'active',NULL,NULL,NULL,NULL,1,'2026-10-13 19:48:19','2026-10-13 19:48:19'),(20,'ADV-202610-2965',2,1,1000.01,1000.01,0.00,'2026-10-13','Cash Drawer',NULL,49,NULL,NULL,'fully_recovered',NULL,NULL,NULL,NULL,1,'2026-10-13 20:11:20','2026-10-13 20:49:29');
/*!40000 ALTER TABLE `employee_advances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employees` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `employee_code` varchar(30) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `nic` varchar(30) DEFAULT NULL,
  `job_role` varchar(50) NOT NULL DEFAULT 'Sales Rep',
  `is_sales_rep` tinyint(1) NOT NULL DEFAULT 1,
  `branch_id` int(10) unsigned NOT NULL,
  `joining_date` date DEFAULT NULL,
  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_name` varchar(150) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_code` (`employee_code`),
  KEY `idx_emp_branch` (`branch_id`),
  KEY `idx_emp_status` (`status`),
  KEY `idx_emp_sales_rep` (`is_sales_rep`),
  KEY `idx_emp_deleted` (`deleted_at`),
  CONSTRAINT `fk_emp_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
INSERT INTO `employees` VALUES (1,'EMP-0001','Sandaru','045652156','0761407875',NULL,'2006110022558','Sales Rep',1,2,'2026-10-12',50000.00,'active','COM',NULL,'8018565449',NULL,'2026-10-12 15:29:47','2026-10-12 15:29:47',NULL),(2,'EMP-0002','Chamath','0461523659','0761402569',NULL,'20056665514v','Sales Rep',1,1,'2026-10-12',50000.00,'active','COM',NULL,'8018565449',NULL,'2026-10-12 19:21:34','2026-10-12 19:21:34',NULL),(3,'TEST-ADV-0350','Test Advance Employee','0771234567',NULL,NULL,'991234567V','Cashier',0,1,'2026-10-13',60000.00,'active',NULL,NULL,NULL,NULL,'2026-10-13 14:02:30','2026-10-13 14:02:30',NULL);
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `epf_etf_remittances`
--

DROP TABLE IF EXISTS `epf_etf_remittances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `epf_etf_remittances` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `remittance_no` varchar(50) NOT NULL,
  `payroll_period_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `remittance_type` enum('EPF','ETF','Combined') NOT NULL DEFAULT 'Combined',
  `total_employee_epf` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_employer_epf` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_employer_etf` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `etf_bank_account_id` int(10) unsigned DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `etf_reference_no` varchar(100) DEFAULT NULL,
  `cheque_no` varchar(50) DEFAULT NULL,
  `etf_cheque_no` varchar(50) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `remittance_no` (`remittance_no`),
  KEY `idx_rem_period` (`payroll_period_id`),
  KEY `idx_rem_type` (`remittance_type`),
  CONSTRAINT `fk_rem_period` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `epf_etf_remittances`
--

LOCK TABLES `epf_etf_remittances` WRITE;
/*!40000 ALTER TABLE `epf_etf_remittances` DISABLE KEYS */;
INSERT INTO `epf_etf_remittances` VALUES (11,'REMIT-202610-7249',29,1,'Combined',8800.00,13200.00,3300.00,25300.00,'2026-10-13',2,2,60,NULL,NULL,NULL,NULL,'2026-10-13',NULL,1,'2026-10-14 01:39:25');
/*!40000 ALTER TABLE `epf_etf_remittances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `error_logs`
--

DROP TABLE IF EXISTS `error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `error_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `error_level` varchar(20) NOT NULL,
  `message` text NOT NULL,
  `file` varchar(255) DEFAULT NULL,
  `line` int(10) unsigned DEFAULT NULL,
  `trace` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_error_level` (`error_level`),
  KEY `idx_error_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error_logs`
--

LOCK TABLES `error_logs` WRITE;
/*!40000 ALTER TABLE `error_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `error_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_categories`
--

DROP TABLE IF EXISTS `expense_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expense_categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_expcat_status` (`status`),
  KEY `idx_expcat_account` (`account_id`),
  CONSTRAINT `expense_categories_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_categories`
--

LOCK TABLES `expense_categories` WRITE;
/*!40000 ALTER TABLE `expense_categories` DISABLE KEYS */;
INSERT INTO `expense_categories` VALUES (1,'Fuel & Travelling','EXP-FUEL',17,'Vehicle fuel, travelling allowance and transport costs','active','2026-10-01 18:51:44',NULL),(2,'Meals & Refreshments','EXP-MEALS',17,'Staff tea, meals and client entertainment','active','2026-10-01 18:51:44',NULL),(3,'Stationery & Printing','EXP-STAT',17,'Paper, pens, receipt rolls and office supplies','active','2026-10-01 18:51:44',NULL),(4,'Cleaning & Sanitation','EXP-CLEAN',17,'Cleaning products, detergents and waste management','active','2026-10-01 18:51:44',NULL),(5,'Repairs & Maintenance','EXP-REPAIR',17,'Minor store maintenance, bulb replacement and repairs','active','2026-10-01 18:51:44',NULL),(6,'Utilities & Postage','EXP-UTIL',17,'Minor courier fees, electricity/water bills, postage','active','2026-10-01 18:51:44',NULL),(7,'Miscellaneous Expenses','EXP-MISC',17,'General incidental petty expense items','active','2026-10-01 18:51:44',NULL),(11,'Stage 12 Custom Expense Category','EXP-S12-1791804968-463',17,'S12 Test Expense Category','active','2026-10-12 17:06:08',NULL);
/*!40000 ALTER TABLE `expense_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gift_vouchers`
--

DROP TABLE IF EXISTS `gift_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gift_vouchers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_code` varchar(100) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `issue_date` date NOT NULL,
  `expiry_date` date NOT NULL,
  `status` enum('active','redeemed','expired','cancelled') NOT NULL DEFAULT 'active',
  `created_by` int(10) unsigned NOT NULL,
  `redeemed_sale_id` bigint(20) unsigned DEFAULT NULL,
  `redeemed_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_code` (`voucher_code`),
  KEY `fk_gv_created_by` (`created_by`),
  KEY `fk_gv_redeemed_sale` (`redeemed_sale_id`),
  KEY `idx_gv_code` (`voucher_code`),
  KEY `idx_gv_status` (`status`),
  KEY `idx_gv_expiry` (`expiry_date`),
  CONSTRAINT `fk_gv_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_gv_redeemed_sale` FOREIGN KEY (`redeemed_sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gift_vouchers`
--

LOCK TABLES `gift_vouchers` WRITE;
/*!40000 ALTER TABLE `gift_vouchers` DISABLE KEYS */;
INSERT INTO `gift_vouchers` VALUES (1,'GV-20261012-06E2A0',500.00,'2026-10-12','2026-11-11','active',1,NULL,NULL,'CLI Stage 7 Test Voucher','2026-10-12 17:06:07','2026-10-12 17:06:07'),(2,'GV-20261012-E9CA98',2000.00,'2026-10-12','2026-11-11','redeemed',4,2,'2026-10-12 19:41:09','Voucher Test','2026-10-12 17:06:07','2026-10-12 19:41:09'),(3,'GV-20261012-65E9E5',200.00,'2026-10-12','2027-01-12','active',1,NULL,NULL,'Test Voucher','2026-10-12 17:06:07','2026-10-12 17:06:07'),(4,'GV-20261012-D7CFAD',1000.00,'2026-10-12','2026-11-11','active',1,NULL,NULL,'Gift voucher liability isolation test','2026-10-12 17:06:15','2026-10-12 17:56:41');
/*!40000 ALTER TABLE `gift_vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grn_items`
--

DROP TABLE IF EXISTS `grn_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grn_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `grn_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `lot_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_gi_product` (`product_id`),
  KEY `fk_gi_lot` (`lot_id`),
  KEY `idx_gi_grn` (`grn_id`),
  KEY `idx_grn_items_expiry` (`expiry_date`),
  KEY `idx_grn_items_batch` (`batch_number`),
  CONSTRAINT `fk_gi_grn` FOREIGN KEY (`grn_id`) REFERENCES `grns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_gi_lot` FOREIGN KEY (`lot_id`) REFERENCES `product_lots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_gi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grn_items`
--

LOCK TABLES `grn_items` WRITE;
/*!40000 ALTER TABLE `grn_items` DISABLE KEYS */;
INSERT INTO `grn_items` VALUES (14,8,13,10.00,1500.00,2500.00,NULL,NULL,15000.00,31),(15,9,13,10.00,1500.00,2000.00,NULL,NULL,15000.00,13),(16,7,13,10.00,1000.00,2000.00,NULL,NULL,10000.00,32),(39,10,18,20.00,100.00,250.00,'2026-10-22',NULL,2000.00,65),(40,11,30,50.00,26.00,42.00,NULL,NULL,1300.00,NULL),(41,12,12,20.00,750.00,1100.00,'2027-10-15','BATCH-GRN-99',15000.00,NULL),(42,13,12,20.00,750.00,1100.00,'2027-10-15','BATCH-GRN-99',15000.00,NULL),(43,14,12,20.00,750.00,1100.00,'2027-10-15','BATCH-GRN-99',15000.00,NULL),(46,17,30,50.00,26.00,42.00,NULL,NULL,1300.00,NULL),(50,21,30,50.00,26.00,42.00,NULL,NULL,1300.00,91),(70,32,12,50.00,26.00,42.00,NULL,NULL,1300.00,109),(78,37,12,50.00,26.00,42.00,NULL,NULL,1300.00,109),(79,38,12,50.00,26.00,42.00,NULL,NULL,1300.00,109),(80,39,12,50.00,26.00,42.00,NULL,NULL,1300.00,109);
/*!40000 ALTER TABLE `grn_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grns`
--

DROP TABLE IF EXISTS `grns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grns` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `grn_number` varchar(50) NOT NULL,
  `po_id` int(10) unsigned DEFAULT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `supplier_invoice_no` varchar(100) NOT NULL,
  `supplier_invoice_date` date DEFAULT NULL,
  `received_date` date NOT NULL,
  `payment_due_date` date DEFAULT NULL,
  `actual_grn_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('unpaid','partially_paid','paid') NOT NULL DEFAULT 'unpaid',
  `supplier_invoice_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `difference_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `difference_reason` varchar(100) NOT NULL DEFAULT 'None',
  `notes` text DEFAULT NULL,
  `status` enum('Draft','Approved','Cancelled') NOT NULL DEFAULT 'Draft',
  `created_by` int(10) unsigned NOT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `grn_number` (`grn_number`),
  KEY `fk_grn_po` (`po_id`),
  KEY `fk_grn_created_by` (`created_by`),
  KEY `fk_grn_approved_by` (`approved_by`),
  KEY `idx_grn_supplier` (`supplier_id`),
  KEY `idx_grn_branch` (`branch_id`),
  KEY `idx_grn_invoice` (`supplier_invoice_no`),
  KEY `idx_grn_status` (`status`),
  KEY `idx_grns_payment_due_date` (`payment_due_date`),
  KEY `idx_grn_br_id` (`branch_id`,`id`),
  KEY `idx_grn_br_status` (`branch_id`,`status`),
  KEY `idx_grn_br_supp` (`branch_id`,`supplier_id`),
  CONSTRAINT `fk_grn_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_grn_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `fk_grn_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_grn_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_grn_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grns`
--

LOCK TABLES `grns` WRITE;
/*!40000 ALTER TABLE `grns` DISABLE KEYS */;
INSERT INTO `grns` VALUES (7,'GRN-202610-0001',NULL,9,1,'2029','2026-10-12','2026-10-12',NULL,10000.00,500.00,'partially_paid',15000.00,5000.00,'Price Variance','','Approved',1,1,'2026-10-12 14:32:29','2026-10-12 14:26:36','2026-10-12 17:06:15'),(8,'GRN-202610-0008',NULL,9,1,'2029','2026-10-12','2026-10-12',NULL,15000.00,0.00,'unpaid',15000.00,0.00,'None','','Approved',1,1,'2026-10-12 14:32:28','2026-10-12 14:28:13','2026-10-12 14:32:28'),(9,'GRN-202610-0009',NULL,9,1,'2026','2026-10-12','2026-10-12',NULL,15000.00,0.00,'unpaid',15000.00,0.00,'None','','Approved',1,1,'2026-10-12 14:29:38','2026-10-12 14:28:35','2026-10-12 14:29:38'),(10,'GRN-202610-0010',NULL,12,1,'1456','2026-10-12','2026-10-12',NULL,2000.00,0.00,'unpaid',10000.00,8000.00,'Price Variance','','Approved',1,1,'2026-10-12 17:40:17','2026-10-12 17:40:06','2026-10-12 17:40:17'),(11,'GRN-202610-0011',3,12,1,'TEST-INV-8899','2026-10-15','2026-10-15',NULL,1300.00,0.00,'unpaid',1500.00,200.00,'Price difference','Testing GRN variance and premature stock prevention','Approved',1,1,'2026-10-15 14:14:58','2026-10-15 14:14:58','2026-10-15 14:14:58'),(12,'GRN-202610-0012',NULL,9,1,'INV-GRN-UNIQUE-1792054512','2026-10-15','2026-10-15','2026-11-15',15000.00,0.00,'unpaid',16200.00,1200.00,'Price Variance','CLI automated GRN test record GRNKEY99','Approved',1,1,'2026-10-15 14:25:12','2026-10-15 14:25:12','2026-10-15 14:25:12'),(13,'GRN-202610-0013',NULL,9,1,'INV-GRN-UNIQUE-1792054606','2026-10-15','2026-10-15','2026-11-15',15000.00,0.00,'unpaid',16200.00,1200.00,'Price Variance','CLI automated GRN test record GRNKEY99','Approved',1,1,'2026-10-15 14:26:46','2026-10-15 14:26:46','2026-10-15 14:26:46'),(14,'GRN-202610-0014',NULL,9,1,'INV-GRN-UNIQUE-1792054643','2026-10-15','2026-10-15','2026-11-15',15000.00,0.00,'unpaid',16200.00,1200.00,'Price Variance','CLI automated GRN test record GRNKEY99','Approved',1,1,'2026-10-15 14:27:23','2026-10-15 14:27:23','2026-10-15 14:27:23'),(15,'GRN-202610-0015',NULL,9,1,'INV-GRN-UNIQUE-1792054664','2026-10-15','2026-10-15','2026-11-15',15000.00,0.00,'unpaid',16200.00,1200.00,'Price Variance','CLI automated GRN test record GRNKEY99','Approved',1,1,'2026-10-15 14:27:44','2026-10-15 14:27:44','2026-10-15 14:27:44'),(17,'GRN-202610-0016',7,12,1,'TEST-INV-8899','2026-10-15','2026-10-15',NULL,1300.00,0.00,'unpaid',1500.00,200.00,'Price difference','Testing GRN variance and premature stock prevention','Approved',1,1,'2026-10-15 14:28:09','2026-10-15 14:28:09','2026-10-15 14:28:09'),(21,'GRN-202610-0018',12,12,1,'TEST-INV-8899','2026-10-15','2026-10-15',NULL,1300.00,0.00,'unpaid',1500.00,200.00,'Price difference','Testing GRN variance and premature stock prevention','Approved',1,1,'2026-10-15 14:29:55','2026-10-15 14:29:55','2026-10-15 14:29:55'),(22,'GRN-202610-0022',NULL,9,1,'INV-TEST-1792054812_417','2026-10-15','2026-10-15',NULL,5500.00,0.00,'unpaid',5500.00,0.00,'Price Variance','Direct test GRN creation','Approved',1,1,'2026-10-15 14:30:12','2026-10-15 14:30:12','2026-10-15 14:30:12'),(23,'GRN-202610-0023',15,9,1,'INV-PO-1792054812_417','2026-10-15','2026-10-15',NULL,2100.00,0.00,'unpaid',2100.00,0.00,'None','Receiving linked PO','Approved',1,1,'2026-10-15 14:30:12','2026-10-15 14:30:12','2026-10-15 14:30:12'),(24,'GRN-202610-0024',NULL,9,1,'INV-CANCEL-1792054812_417','2026-10-15','2026-10-15',NULL,500.00,0.00,'unpaid',5000.00,4500.00,'None','Direct test GRN creation','Cancelled',1,NULL,NULL,'2026-10-15 14:30:12','2026-10-15 14:30:12'),(32,'GRN-202610-0025',24,12,1,'TEST-INV-8899','2026-10-15','2026-10-15',NULL,1300.00,0.00,'unpaid',1500.00,200.00,'Price difference','Testing GRN variance and premature stock prevention','Approved',1,1,'2026-10-15 14:54:50','2026-10-15 14:54:50','2026-10-15 14:54:50'),(37,'GRN-202610-0033',27,12,1,'TEST-INV-8899','2026-10-15','2026-10-15',NULL,1300.00,0.00,'unpaid',1500.00,200.00,'Price difference','Testing GRN variance and premature stock prevention','Approved',1,1,'2026-10-15 14:55:07','2026-10-15 14:55:07','2026-10-15 14:55:07'),(38,'GRN-202610-0038',30,12,1,'TEST-INV-8899','2026-10-15','2026-10-15',NULL,1300.00,0.00,'unpaid',1500.00,200.00,'Price difference','Testing GRN variance and premature stock prevention','Approved',1,1,'2026-10-15 14:55:43','2026-10-15 14:55:43','2026-10-15 14:55:43'),(39,'GRN-202610-0039',32,12,1,'TEST-INV-8899','2026-10-15','2026-10-15',NULL,1300.00,0.00,'unpaid',1500.00,200.00,'Price difference','Testing GRN variance and premature stock prevention','Approved',1,1,'2026-10-15 14:56:41','2026-10-15 14:56:41','2026-10-15 14:56:41');
/*!40000 ALTER TABLE `grns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_entries`
--

DROP TABLE IF EXISTS `journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_number` varchar(50) NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `entry_date` date NOT NULL,
  `source_type` enum('grn','supplier_payment','sale','customer_collection','sales_return','manual','reversal','opening_balance','customer_cheque_return','cheque_return','supplier_return','supplier_credit_settlement','cheque_clearing','cheque_bounce','cheque_cancellation','card_settlement','card_settlement_reversal','employee_advance','payroll','payroll_payment','epf_etf_remittance') NOT NULL DEFAULT 'manual',
  `source_id` bigint(20) unsigned DEFAULT NULL,
  `description` text NOT NULL,
  `total_debit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_credit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `posting_status` enum('draft','posted','reversed') NOT NULL DEFAULT 'posted',
  `reversed_journal_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `entry_number` (`entry_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_je_number` (`entry_number`),
  KEY `idx_je_branch` (`branch_id`),
  KEY `idx_je_source` (`source_type`,`source_id`),
  KEY `idx_je_date` (`entry_date`),
  CONSTRAINT `journal_entries_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `journal_entries_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=367 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_entries`
--

LOCK TABLES `journal_entries` WRITE;
/*!40000 ALTER TABLE `journal_entries` DISABLE KEYS */;
INSERT INTO `journal_entries` VALUES (12,'JE-20261012-0010',1,'2026-10-12','opening_balance',12,'Opening Stock for Product: Asus Gaming Laptop Tuff (10100) - Qty: 15 @ Val: LKR 15,000.00',15000.00,15000.00,'posted',NULL,1,'2026-10-12 11:56:50'),(13,'JE-20261012-0013',1,'2026-10-12','opening_balance',13,'Opening Stock for Product: Asus Gaming Laptop ROG (10200) - Qty: 10 @ Val: LKR 15,000.00',15000.00,15000.00,'posted',NULL,1,'2026-10-12 11:58:19'),(15,'JE-20261012-0014',1,'2026-10-12','opening_balance',15,'Opening Stock for Product: Baby Cheramy Cream 10ml Bottle (10300) - Qty: 20 @ Val: LKR 2,000.00',2000.00,2000.00,'reversed',23,1,'2026-10-12 12:05:35'),(18,'JE-20261012-0016',1,'2026-10-12','opening_balance',18,'Opening Stock for Product: Baby Cheramy Cream 10 Ml (10300) - Qty: 20 @ Val: LKR 2,000.00',2000.00,2000.00,'posted',NULL,1,'2026-10-12 12:49:04'),(23,'JE-20261012-0019',1,'2026-10-12','reversal',15,'Reversal of JE-20261012-0014. Reason: Product #15 moved to Trash Bin',2000.00,2000.00,'posted',NULL,1,'2026-10-12 13:23:55'),(25,'JE-20261012-0024',1,'2026-10-12','grn',9,'Approved GRN #GRN-202610-0009 from Chani Importers - Asus Agent',15000.00,15000.00,'posted',NULL,1,'2026-10-12 14:29:38'),(26,'JE-20261012-0026',1,'2026-10-12','grn',8,'Approved GRN #GRN-202610-0008 from Chani Importers - Asus Agent',15000.00,15000.00,'posted',NULL,1,'2026-10-12 14:32:28'),(27,'JE-20261012-0027',1,'2026-10-12','grn',7,'Approved GRN #GRN-202610-0001 from Chani Importers - Asus Agent',10000.00,10000.00,'posted',NULL,1,'2026-10-12 14:32:29'),(167,'JE-20261012-0028',1,'2026-10-12','grn',10,'Approved GRN #GRN-202610-0010 from Ceylon Cream',2000.00,2000.00,'posted',NULL,1,'2026-10-12 17:40:17'),(170,'JE-20261012-0168',1,'2026-10-12','sale',2,'POS Sale Invoice #0002',15500.00,15500.00,'posted',NULL,1,'2026-10-12 19:41:09'),(171,'JE-20261012-0171',1,'2026-10-12','sale',7,'POS Sale Invoice #0005',125.00,125.00,'posted',NULL,1,'2026-10-12 23:02:17'),(172,'JE-20261013-0001',1,'2026-10-13','sale',12,'POS Sale Invoice #0010',1250.00,1250.00,'posted',NULL,1,'2026-10-13 13:02:12'),(173,'JE-20261013-0173',1,'2026-10-31','',3,'Payroll Approval & Accruals: Test Advance Recovery Period Oct 2026 (PR-TEST-0458)',126500.00,126500.00,'posted',NULL,1,'2026-10-13 14:04:18'),(174,'JE-20261013-0174',1,'2026-10-13','',1,'Salary Advance #ADV-202610-5344: Test Advance Employee 321 (LKR 5000) via Cash Drawer',5000.00,5000.00,'posted',NULL,1,'2026-10-13 14:04:45'),(175,'JE-20261013-0175',1,'2026-10-13','',2,'Salary Advance #ADV-202610-5475: Test Advance Employee 321 (LKR 3000) via Cash in Hand',3000.00,3000.00,'posted',NULL,1,'2026-10-13 14:04:45'),(176,'JE-20261013-0176',1,'2026-10-13','',3,'Salary Advance #ADV-202610-3097: Test Advance Employee 321 (LKR 4000) via Bank Transfer',4000.00,4000.00,'posted',NULL,1,'2026-10-13 14:04:45'),(177,'JE-20261013-0177',1,'2026-10-13','',4,'Salary Advance #ADV-202610-8693: Test Advance Employee 321 (LKR 1500) via Cash Drawer',1500.00,1500.00,'posted',NULL,1,'2026-10-13 14:04:45'),(178,'JE-20261013-0178',1,'2026-10-31','',4,'Payroll Approval & Accruals: Test Advance Recovery Period Oct 2026 (PR-TEST-0485)',195500.00,195500.00,'posted',NULL,1,'2026-10-13 14:04:45'),(185,'JE-20261013-0185',1,'2026-10-13','reversal',184,'Reversal of JE-20261013-0184. Reason: Payroll Period Cancelled: Testing payroll cancellation & advance un-recovery',195500.00,195500.00,'posted',NULL,1,'2026-10-13 14:07:55'),(192,'JE-20261013-0192',1,'2026-10-13','reversal',191,'Reversal of JE-20261013-0191. Reason: Payroll Period Cancelled: Testing payroll cancellation & advance un-recovery',195500.00,195500.00,'posted',NULL,1,'2026-10-13 14:08:23'),(194,'JE-20261013-0194',1,'2026-10-31','payroll',7,'Payroll Approval & Accruals: October 2026 (2026-10)',190000.00,190000.00,'posted',NULL,1,'2026-10-13 14:08:29'),(198,'JE-20261013-0195',2,'2026-10-13','employee_advance',15,'Salary Advance #ADV-202610-8482: Sandaru (LKR 1000) via Cash Drawer',1000.00,1000.00,'posted',NULL,1,'2026-10-13 19:48:19'),(205,'JE-20261013-0205',1,'2026-10-13','reversal',204,'Reversal of JE-20261013-0204. Reason: Payroll Period Cancelled: Testing payroll cancellation & advance un-recovery',195500.00,195500.00,'posted',NULL,1,'2026-10-13 20:10:02'),(206,'JE-20261013-0206',1,'2026-10-13','employee_advance',20,'Salary Advance #ADV-202610-2965: Chamath (LKR 1000.01) via Cash Drawer',1000.01,1000.01,'posted',NULL,1,'2026-10-13 20:11:20'),(210,'JE-20261013-0210',1,'2026-10-13','payroll_payment',2,'Salary Payment #PAY-SAL-202610-8902: Chamath (LKR 60900) in 2 splits',60900.00,60900.00,'posted',NULL,1,'2026-10-13 20:51:23'),(211,'JE-20261013-0211',1,'2026-10-13','epf_etf_remittance',3,'EPF/ETF Remittance #REMIT-202610-8477: Sri Lanka Labor Law Test Period (LKR 16100)',16100.00,16100.00,'posted',NULL,1,'2026-10-13 20:51:23'),(213,'JE-20261013-0213',1,'2026-10-13','payroll_payment',4,'Salary Payment #PAY-SAL-202610-7488: Chamath (LKR 60900) in 2 splits',60900.00,60900.00,'posted',NULL,1,'2026-10-13 20:51:44'),(214,'JE-20261013-0214',1,'2026-10-13','epf_etf_remittance',4,'EPF/ETF Remittance #REMIT-202610-9901: Sri Lanka Labor Law Test Period (LKR 16100)',16100.00,16100.00,'posted',NULL,1,'2026-10-13 20:51:44'),(216,'JE-20261013-0216',1,'2026-10-13','payroll_payment',6,'Salary Payment #PAY-SAL-202610-6334: Chamath (LKR 60900) in 2 splits',60900.00,60900.00,'posted',NULL,1,'2026-10-13 20:52:00'),(217,'JE-20261013-0217',1,'2026-10-13','epf_etf_remittance',5,'EPF/ETF Remittance #REMIT-202610-6155: Sri Lanka Labor Law Test Period (LKR 16100)',16100.00,16100.00,'posted',NULL,1,'2026-10-13 20:52:00'),(219,'JE-20261013-0219',1,'2026-10-13','payroll_payment',8,'Salary Payment #PAY-SAL-202610-7232: Chamath (LKR 60900) in 2 splits',60900.00,60900.00,'posted',NULL,1,'2026-10-13 20:52:11'),(220,'JE-20261013-0220',1,'2026-10-13','epf_etf_remittance',6,'EPF/ETF Remittance #REMIT-202610-2117: Sri Lanka Labor Law Test Period (LKR 16100)',16100.00,16100.00,'posted',NULL,1,'2026-10-13 20:52:11'),(222,'JE-20261013-0222',1,'2026-10-13','payroll_payment',10,'Salary Payment #PAY-SAL-202610-2469: Chamath (LKR 60900) in 2 splits',60900.00,60900.00,'posted',NULL,1,'2026-10-13 20:53:36'),(223,'JE-20261013-0223',1,'2026-10-13','epf_etf_remittance',7,'EPF/ETF Remittance #REMIT-202610-2055: Sri Lanka Labor Law Test Period (LKR 16100)',16100.00,16100.00,'posted',NULL,1,'2026-10-13 20:53:36'),(225,'JE-20261013-0225',1,'2026-10-13','payroll_payment',12,'Salary Payment #PAY-SAL-202610-7399: Chamath (LKR 60900) in 2 splits',60900.00,60900.00,'posted',NULL,1,'2026-10-13 21:12:57'),(226,'JE-20261013-0226',1,'2026-10-13','epf_etf_remittance',8,'EPF/ETF Remittance #REMIT-202610-5814: Sri Lanka Labor Law Test Period (LKR 16100)',16100.00,16100.00,'posted',NULL,1,'2026-10-13 21:12:57'),(228,'JE-20261013-0228',1,'2026-10-13','payroll_payment',14,'Salary Payment #PAY-SAL-202610-1316: Chamath (LKR 60900) in 2 splits',60900.00,60900.00,'posted',NULL,1,'2026-10-13 21:59:23'),(229,'JE-20261013-0229',1,'2026-10-13','epf_etf_remittance',9,'EPF/ETF Remittance #REMIT-202610-7448: Sri Lanka Labor Law Test Period (LKR 16100)',16100.00,16100.00,'posted',NULL,1,'2026-10-13 21:59:23'),(236,'JE-20261013-0236',1,'2026-10-13','reversal',235,'Reversal of JE-20261013-0235. Reason: Payroll Period Cancelled: Testing payroll cancellation & advance un-recovery',195500.00,195500.00,'posted',NULL,1,'2026-10-13 21:59:26'),(237,'JE-20261013-0237',1,'2026-10-31','payroll',12,'Payroll Approval & Accruals: October 2026 (2026-10)',127650.00,127650.00,'posted',NULL,1,'2026-10-14 01:10:23'),(245,'JE-20261013-0245',1,'2026-10-13','payroll_payment',22,'Salary Payment #PAY-SAL-202610-2600: Chamath (LKR 60900) in 2 splits',60900.00,60900.00,'posted',NULL,1,'2026-10-14 01:22:35'),(246,'JE-20261013-0246',1,'2026-10-13','epf_etf_remittance',10,'EPF/ETF Remittance #REMIT-202610-5013: Sri Lanka Labor Law Test Period (LKR 16100)',16100.00,16100.00,'posted',NULL,1,'2026-10-14 01:22:35'),(249,'JE-20261013-0247',1,'2026-10-13','payroll_payment',26,'Payroll Salaries Payment #PAY-SAL-202610-7632: October 2026 (LKR 100,000.00) in 7 splits',100000.00,100000.00,'posted',NULL,1,'2026-10-14 01:31:18'),(250,'JE-20261013-0250',1,'2026-10-31','payroll',29,'Payroll Approval & Accruals: October 2026 (2026-101)',126500.00,126500.00,'posted',NULL,1,'2026-10-14 01:36:52'),(251,'JE-20261013-0251',1,'2026-10-13','epf_etf_remittance',11,'EPF/ETF Remittance #REMIT-202610-7249: October 2026 (LKR 25300)',25300.00,25300.00,'posted',NULL,1,'2026-10-14 01:39:25'),(260,'JE-20261013-0260',1,'2026-10-13','payroll_payment',37,'Salary Payment #PAY-SAL-202610-8635: Chamath (LKR 60900) in 2 splits',60900.00,60900.00,'posted',NULL,1,'2026-10-14 01:56:50'),(261,'JE-20261013-0261',1,'2026-10-13','epf_etf_remittance',13,'EPF/ETF Remittance #REMIT-202610-2253: Sri Lanka Labor Law Test Period (LKR 16100)',16100.00,16100.00,'posted',NULL,1,'2026-10-14 01:56:50'),(267,'JE-20261014-0001',1,'2026-10-14','customer_collection',12,'Customer Collection Receipt #CPAY-0001-1 from Split Test Customer',1500.00,1500.00,'posted',NULL,1,'2026-10-14 13:13:46'),(268,'JE-20261014-0268',1,'2026-10-14','customer_collection',13,'Customer Collection Receipt #CPAY-0001-2 from Split Test Customer',1000.00,1000.00,'posted',NULL,1,'2026-10-14 13:13:46'),(269,'JE-20261014-0269',1,'2026-10-14','customer_collection',14,'Customer Collection Receipt #CPAY-0001-3 from Split Test Customer',500.00,500.00,'posted',NULL,1,'2026-10-14 13:13:46'),(270,'JE-20261014-0270',1,'2026-10-14','customer_collection',15,'Customer Collection Receipt #CPAY-0002-1 from Split Test Customer',1500.00,1500.00,'posted',NULL,1,'2026-10-14 13:14:14'),(271,'JE-20261014-0271',1,'2026-10-14','customer_collection',16,'Customer Collection Receipt #CPAY-0002-2 from Split Test Customer',1000.00,1000.00,'posted',NULL,1,'2026-10-14 13:14:14'),(272,'JE-20261014-0272',1,'2026-10-14','customer_collection',17,'Customer Collection Receipt #CPAY-0002-3 from Split Test Customer',500.00,500.00,'posted',NULL,1,'2026-10-14 13:14:14'),(273,'JE-20261014-0273',1,'2026-10-14','customer_collection',18,'Customer Collection Receipt #CPAY-0001-1 from Split Test Customer',1500.00,1500.00,'posted',NULL,1,'2026-10-14 13:14:57'),(274,'JE-20261014-0274',1,'2026-10-14','customer_collection',19,'Customer Collection Receipt #CPAY-0001-2 from Split Test Customer',1000.00,1000.00,'posted',NULL,1,'2026-10-14 13:14:57'),(275,'JE-20261014-0275',1,'2026-10-14','customer_collection',20,'Customer Collection Receipt #CPAY-0001-3 from Split Test Customer',500.00,500.00,'posted',NULL,1,'2026-10-14 13:14:57'),(276,'JE-20261014-0276',1,'2026-10-14','customer_collection',21,'Customer Collection Receipt #CPAY-0001 from Sec7 Test Active Customer',1000.00,1000.00,'posted',NULL,1,'2026-10-14 13:15:14'),(277,'JE-20261014-0277',1,'2026-10-14','customer_collection',22,'Customer Collection Receipt #CPAY-0002 from Sec7 Test Active Customer',1000.00,1000.00,'posted',NULL,1,'2026-10-14 13:15:14'),(278,'JE-20261014-0278',1,'2026-10-14','customer_collection',23,'Customer Collection Receipt #CPAY-0003 from Sec7 Test Inactive Customer',3000.00,3000.00,'posted',NULL,1,'2026-10-14 13:15:14'),(279,'JE-20261014-0279',1,'2026-10-14','customer_collection',24,'Customer Collection Receipt #CPAY-0004 from Sec7 Test Active Customer',4000.00,4000.00,'posted',NULL,1,'2026-10-14 13:15:14'),(280,'JE-20261014-0280',1,'2026-10-14','customer_collection',25,'Customer Collection Receipt #CPAY-0005 from Sec7 Test Active Customer',2000.00,2000.00,'posted',NULL,1,'2026-10-14 13:15:14'),(281,'JE-20261014-0281',1,'2026-10-14','customer_collection',26,'Customer Collection Receipt #CPAY-0006 from Sec7 Test Active Customer',3000.00,3000.00,'posted',NULL,1,'2026-10-14 13:15:14'),(282,'JE-20261014-0282',1,'2026-10-14','customer_collection',27,'Customer Collection Receipt #CPAY-0007 from Sec7 Test Active Customer',4000.00,4000.00,'posted',NULL,1,'2026-10-14 13:15:14'),(283,'JE-20261014-0283',1,'2026-10-14','customer_collection',28,'Customer Collection Receipt #CPAY-0008-1 from Split Test Customer',1500.00,1500.00,'posted',NULL,1,'2026-10-14 13:20:14'),(284,'JE-20261014-0284',1,'2026-10-14','customer_collection',29,'Customer Collection Receipt #CPAY-0008-2 from Split Test Customer',1000.00,1000.00,'posted',NULL,1,'2026-10-14 13:20:14'),(285,'JE-20261014-0285',1,'2026-10-14','customer_collection',31,'Customer Collection Receipt #CPAY-0001 from Sec7 Test Inactive Customer',3000.00,3000.00,'posted',NULL,1,'2026-10-14 13:20:20'),(286,'JE-20261014-0286',1,'2026-10-14','customer_collection',32,'Customer Collection Receipt #CPAY-0002 from Sec7 Test Active Customer',4000.00,4000.00,'posted',NULL,1,'2026-10-14 13:20:20'),(287,'JE-20261014-0287',1,'2026-10-14','customer_collection',33,'Customer Collection Receipt #CPAY-0003 from Sec7 Test Active Customer',2000.00,2000.00,'posted',NULL,1,'2026-10-14 13:20:20'),(288,'JE-20261014-0288',1,'2026-10-14','customer_collection',34,'Customer Collection Receipt #CPAY-0004 from Sec7 Test Active Customer',3000.00,3000.00,'posted',NULL,1,'2026-10-14 13:20:20'),(290,'JE-20261014-0289',1,'2026-10-14','customer_collection',39,'Customer Collection Receipt #CPAY-0001 from Sec7 Test Inactive Customer',3000.00,3000.00,'posted',NULL,1,'2026-10-14 13:21:18'),(291,'JE-20261014-0291',1,'2026-10-14','customer_collection',40,'Customer Collection Receipt #CPAY-0002 from Sec7 Test Active Customer',4000.00,4000.00,'posted',NULL,1,'2026-10-14 13:21:18'),(292,'JE-20261014-0292',1,'2026-10-14','customer_collection',41,'Customer Collection Receipt #CPAY-0003 from Sec7 Test Active Customer',2000.00,2000.00,'posted',NULL,1,'2026-10-14 13:21:18'),(293,'JE-20261014-0293',1,'2026-10-14','customer_collection',42,'Customer Collection Receipt #CPAY-0004 from Sec7 Test Active Customer',3000.00,3000.00,'posted',NULL,1,'2026-10-14 13:21:18'),(294,'JE-20261014-0294',1,'2026-10-14','customer_cheque_return',13,'Customer Cheque Return #S7-CHQ-BANKED-1002 from Sec7 Test Active Customer',3000.00,3000.00,'posted',NULL,1,'2026-10-14 13:21:18'),(295,'JE-20261014-0295',1,'2026-10-14','customer_collection',47,'Customer Collection Receipt #CPAY-0007-1 from Split Test Customer',1500.00,1500.00,'posted',NULL,1,'2026-10-14 13:21:54'),(296,'JE-20261014-0296',1,'2026-10-14','customer_collection',48,'Customer Collection Receipt #CPAY-0007-2 from Split Test Customer',1000.00,1000.00,'posted',NULL,1,'2026-10-14 13:21:54'),(297,'JE-20261014-0297',2,'2026-10-14','customer_collection',50,'Customer Collection Receipt #CPAY-0007 from Sec7 Test Active Customer',6000.00,6000.00,'posted',NULL,1,'2026-10-14 13:52:53'),(298,'JE-20261014-0298',1,'2026-10-14','customer_collection',51,'Customer Collection Receipt #CPAY-0008-1 from Split Test Customer',1500.00,1500.00,'posted',NULL,1,'2026-10-14 15:18:45'),(299,'JE-20261014-0299',1,'2026-10-14','customer_collection',52,'Customer Collection Receipt #CPAY-0008-2 from Split Test Customer',1000.00,1000.00,'posted',NULL,1,'2026-10-14 15:18:46'),(300,'JE-20261014-0300',1,'2026-10-14','customer_collection',54,'Customer Collection Receipt #CPAY-0008-1 from Partial Test Customer',1200.00,1200.00,'posted',NULL,1,'2026-10-14 15:18:46'),(301,'JE-20261014-0301',1,'2026-10-14','customer_collection',55,'Customer Collection Receipt #CPAY-0008-2 from Partial Test Customer',800.00,800.00,'posted',NULL,1,'2026-10-14 15:18:46'),(302,'JE-20261014-0302',1,'2026-10-14','customer_collection',56,'Customer Collection Receipt #CPAY-0008-1 from Sec7 Test Inactive Customer',500.00,500.00,'posted',NULL,1,'2026-10-14 15:28:33'),(303,'JE-20261014-0303',1,'2026-10-14','customer_collection',57,'Customer Collection Receipt #CPAY-0008-2 from Sec7 Test Inactive Customer',500.00,500.00,'posted',NULL,1,'2026-10-14 15:28:33'),(312,'JE-20261014-0304',1,'2026-10-14','sales_return',12,'Sales Return #RET-0001',4100.00,4100.00,'posted',NULL,1,'2026-10-14 16:39:02'),(313,'JE-20261014-0313',1,'2026-10-14','sales_return',13,'Sales Return #RET-0013',3800.00,3800.00,'posted',NULL,1,'2026-10-14 16:39:59'),(314,'JE-20261014-0314',1,'2026-10-14','sales_return',14,'Sales Return #RET-0014',3800.00,3800.00,'posted',NULL,1,'2026-10-14 16:40:29'),(316,'JE-20261015-0316',1,'2026-10-15','reversal',315,'Reversal of JE-20261015-0001. Reason: Product #28 (TestProduct 1792052903) moved to Trash Bin',1500.00,1500.00,'reversed',317,1,'2026-10-15 13:58:23'),(317,'JE-20261015-0317',1,'2026-10-15','reversal',316,'Reversal of JE-20261015-0316. Reason: Product #28 restored from Trash Bin',1500.00,1500.00,'posted',NULL,1,'2026-10-15 13:58:23'),(320,'JE-20261015-0318',1,'2026-10-15','grn',11,'Approved GRN #GRN-202610-0011 from Ceylon Cream',1300.00,1300.00,'posted',NULL,1,'2026-10-15 14:14:58'),(321,'JE-20261015-0321',1,'2026-10-15','grn',12,'Approved GRN #GRN-202610-0012 from Chani Importers - Asus Agent',15000.00,15000.00,'posted',NULL,1,'2026-10-15 14:25:12'),(322,'JE-20261015-0322',1,'2026-10-15','grn',13,'Approved GRN #GRN-202610-0013 from Chani Importers - Asus Agent',15000.00,15000.00,'posted',NULL,1,'2026-10-15 14:26:46'),(323,'JE-20261015-0323',1,'2026-10-15','grn',14,'Approved GRN #GRN-202610-0014 from Chani Importers - Asus Agent',15000.00,15000.00,'posted',NULL,1,'2026-10-15 14:27:23'),(326,'JE-20261015-0324',1,'2026-10-15','grn',17,'Approved GRN #GRN-202610-0016 from Ceylon Cream',1300.00,1300.00,'posted',NULL,1,'2026-10-15 14:28:09'),(327,'JE-20261015-0327',1,'2026-10-15','grn',18,'Approved GRN #GRN-202610-0018 from Ceylon Cream',1300.00,1300.00,'posted',NULL,1,'2026-10-15 14:29:04'),(330,'JE-20261015-0328',1,'2026-10-15','grn',21,'Approved GRN #GRN-202610-0018 from Ceylon Cream',1300.00,1300.00,'posted',NULL,1,'2026-10-15 14:29:55'),(337,'JE-20261015-0331',1,'2026-10-15','opening_balance',2,'Supplier Opening Balance (SUP-0017 - Temp Rev Test 1792056085): We Owe Supplier (Payable)',5000.00,5000.00,'posted',NULL,1,'2026-10-15 14:51:25'),(338,'JE-20261015-0338',1,'2026-10-15','reversal',2,'Reversal of Supplier Opening Balance #2 for Temp Rev Test 1792056085: Testing reversal reason',5000.00,5000.00,'posted',NULL,1,'2026-10-15 14:51:25'),(344,'JE-20261015-0339',1,'2026-10-15','grn',32,'Approved GRN #GRN-202610-0025 from Ceylon Cream',1300.00,1300.00,'posted',NULL,1,'2026-10-15 14:54:50'),(350,'JE-20261015-0345',1,'2026-10-15','grn',37,'Approved GRN #GRN-202610-0033 from Ceylon Cream',1300.00,1300.00,'posted',NULL,1,'2026-10-15 14:55:07'),(351,'JE-20261015-0351',1,'2026-10-15','grn',38,'Approved GRN #GRN-202610-0038 from Ceylon Cream',1300.00,1300.00,'posted',NULL,1,'2026-10-15 14:55:43'),(354,'JE-20261015-0352',1,'2026-10-15','grn',39,'Approved GRN #GRN-202610-0039 from Ceylon Cream',1300.00,1300.00,'posted',NULL,1,'2026-10-15 14:56:41');
/*!40000 ALTER TABLE `journal_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `journal_lines`
--

DROP TABLE IF EXISTS `journal_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `journal_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `journal_entry_id` bigint(20) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `debit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `credit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `memo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_jl_entry` (`journal_entry_id`),
  KEY `idx_jl_account` (`account_id`),
  CONSTRAINT `journal_lines_ibfk_1` FOREIGN KEY (`journal_entry_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `journal_lines_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1059 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `journal_lines`
--

LOCK TABLES `journal_lines` WRITE;
/*!40000 ALTER TABLE `journal_lines` DISABLE KEYS */;
INSERT INTO `journal_lines` VALUES (23,12,6,15000.00,0.00,'Opening Stock asset for Asus Gaming Laptop Tuff'),(24,12,10,0.00,15000.00,'Opening Stock equity balance for Asus Gaming Laptop Tuff'),(25,13,6,15000.00,0.00,'Opening Stock asset for Asus Gaming Laptop ROG'),(26,13,10,0.00,15000.00,'Opening Stock equity balance for Asus Gaming Laptop ROG'),(29,15,6,2000.00,0.00,'Opening Stock asset for Baby Cheramy Cream 10ml Bottle'),(30,15,10,0.00,2000.00,'Opening Stock equity balance for Baby Cheramy Cream 10ml Bottle'),(35,18,6,2000.00,0.00,'Opening Stock asset for Baby Cheramy Cream 10 Ml'),(36,18,10,0.00,2000.00,'Opening Stock equity balance for Baby Cheramy Cream 10 Ml'),(45,23,6,0.00,2000.00,'Reversal: Opening Stock asset for Baby Cheramy Cream 10ml Bottle'),(46,23,10,2000.00,0.00,'Reversal: Opening Stock equity balance for Baby Cheramy Cream 10ml Bottle'),(52,25,6,15000.00,0.00,'Stock received via GRN GRN-202610-0009'),(53,25,9,0.00,15000.00,'Supplier Payable for GRN GRN-202610-0009'),(54,26,6,15000.00,0.00,'Stock received via GRN GRN-202610-0008'),(55,26,9,0.00,15000.00,'Supplier Payable for GRN GRN-202610-0008'),(56,27,6,10000.00,0.00,'Stock received via GRN GRN-202610-0001'),(57,27,9,0.00,10000.00,'Supplier Payable for GRN GRN-202610-0001'),(433,167,6,2000.00,0.00,'Stock received via GRN GRN-202610-0010'),(434,167,9,0.00,2000.00,'Supplier Payable for GRN GRN-202610-0010'),(445,170,4,500.00,0.00,'Card payment'),(446,170,60,500.00,0.00,'Bank payment'),(447,170,5,2500.00,0.00,'Credit sale'),(448,170,57,2000.00,0.00,'KOKO BNPL payment (Ref: KOKO-B1-261012160900-88F04A)'),(449,170,8,2000.00,0.00,'Voucher redemption'),(450,170,49,1500.00,0.00,'Cash terminal payment to Cash Drawer (net of change)'),(451,170,11,0.00,9000.00,'Gross Sales Revenue'),(452,170,13,6500.00,0.00,'COGS for invoice #0002'),(453,170,6,0.00,6500.00,'Inventory reduction'),(454,171,49,67.00,0.00,'Cash terminal payment to Cash Drawer (net of change)'),(455,171,15,8.00,0.00,'Sales Discount'),(456,171,11,0.00,75.00,'Gross Sales Revenue'),(457,171,13,50.00,0.00,'COGS for invoice #0005'),(458,171,6,0.00,50.00,'Inventory reduction'),(459,172,49,637.50,0.00,'Cash terminal payment to Cash Drawer (net of change)'),(460,172,15,112.50,0.00,'Sales Discount'),(461,172,11,0.00,750.00,'Gross Sales Revenue'),(462,172,13,500.00,0.00,'COGS for invoice #0010'),(463,172,6,0.00,500.00,'Inventory reduction'),(464,173,54,110000.00,0.00,'Basic Salaries & Wages - Period Test Advance Recovery Period Oct 2026'),(465,173,55,16500.00,0.00,'Employer EPF (12%) & ETF (3%) Expense - Period Test Advance Recovery Period Oct 2026'),(466,173,51,0.00,101200.00,'Net Salaries Payable - Period Test Advance Recovery Period Oct 2026'),(467,173,52,0.00,22000.00,'EPF Liability (Employee 8% + Employer 12%) - Period Test Advance Recovery Period Oct 2026'),(468,173,53,0.00,3300.00,'ETF Liability (Employer 3%) - Period Test Advance Recovery Period Oct 2026'),(469,174,50,5000.00,0.00,'Salary Advance Disbursed to Test Advance Employee 321 (TEST-ADV-0485)'),(470,174,49,0.00,5000.00,'Salary Advance Disbursement (Cash Drawer) Ref: ADV-202610-5344 / REF-DRAWER-01'),(471,175,50,3000.00,0.00,'Salary Advance Disbursed to Test Advance Employee 321 (TEST-ADV-0485)'),(472,175,1,0.00,3000.00,'Salary Advance Disbursement (Cash in Hand) Ref: ADV-202610-5475 / REF-SAFE-01'),(473,176,50,4000.00,0.00,'Salary Advance Disbursed to Test Advance Employee 321 (TEST-ADV-0485)'),(474,176,59,0.00,4000.00,'Salary Advance Disbursement (Bank Transfer) Ref: ADV-202610-3097 / TRF-100293'),(475,177,50,1500.00,0.00,'Salary Advance Disbursed to Test Advance Employee 321 (TEST-ADV-0485)'),(476,177,49,0.00,1500.00,'Salary Advance Disbursement (Cash Drawer) Ref: ADV-202610-8693 / REF-TO-CANCEL'),(477,178,54,170000.00,0.00,'Basic Salaries & Wages - Period Test Advance Recovery Period Oct 2026'),(478,178,55,25500.00,0.00,'Employer EPF (12%) & ETF (3%) Expense - Period Test Advance Recovery Period Oct 2026'),(479,178,51,0.00,144400.00,'Net Salaries Payable - Period Test Advance Recovery Period Oct 2026'),(480,178,52,0.00,34000.00,'EPF Liability (Employee 8% + Employer 12%) - Period Test Advance Recovery Period Oct 2026'),(481,178,53,0.00,5100.00,'ETF Liability (Employer 3%) - Period Test Advance Recovery Period Oct 2026'),(482,178,50,0.00,12000.00,'Salary Advances Recovered from Payroll - Period Test Advance Recovery Period Oct 2026'),(499,185,54,0.00,170000.00,'Reversal: Basic Salaries & Wages - Period Test Advance Recovery Period Oct 2026'),(500,185,55,0.00,25500.00,'Reversal: Employer EPF (12%) & ETF (3%) Expense - Period Test Advance Recovery Period Oct 2026'),(501,185,51,144400.00,0.00,'Reversal: Net Salaries Payable - Period Test Advance Recovery Period Oct 2026'),(502,185,52,34000.00,0.00,'Reversal: EPF Liability (Employee 8% + Employer 12%) - Period Test Advance Recovery Period Oct 2026'),(503,185,53,5100.00,0.00,'Reversal: ETF Liability (Employer 3%) - Period Test Advance Recovery Period Oct 2026'),(504,185,50,12000.00,0.00,'Reversal: Salary Advances Recovered from Payroll - Period Test Advance Recovery Period Oct 2026'),(521,192,54,0.00,170000.00,'Reversal: Basic Salaries & Wages - Period Test Advance Recovery Period Oct 2026'),(522,192,55,0.00,25500.00,'Reversal: Employer EPF (12%) & ETF (3%) Expense - Period Test Advance Recovery Period Oct 2026'),(523,192,51,144400.00,0.00,'Reversal: Net Salaries Payable - Period Test Advance Recovery Period Oct 2026'),(524,192,52,34000.00,0.00,'Reversal: EPF Liability (Employee 8% + Employer 12%) - Period Test Advance Recovery Period Oct 2026'),(525,192,53,5100.00,0.00,'Reversal: ETF Liability (Employer 3%) - Period Test Advance Recovery Period Oct 2026'),(526,192,50,12000.00,0.00,'Reversal: Salary Advances Recovered from Payroll - Period Test Advance Recovery Period Oct 2026'),(529,194,54,160000.00,0.00,'Basic Salaries & Wages - Period October 2026'),(530,194,56,6000.00,0.00,'Allowances, Commissions & Overtime - Period October 2026'),(531,194,55,24000.00,0.00,'Employer EPF (12%) & ETF (3%) Expense - Period October 2026'),(532,194,51,0.00,148200.00,'Net Salaries Payable - Period October 2026'),(533,194,52,0.00,32000.00,'EPF Liability (Employee 8% + Employer 12%) - Period October 2026'),(534,194,53,0.00,4800.00,'ETF Liability (Employer 3%) - Period October 2026'),(535,194,50,0.00,5000.00,'Salary Advances Recovered from Payroll - Period October 2026'),(542,198,50,1000.00,0.00,'Salary Advance Disbursed to Sandaru (EMP-0001)'),(543,198,49,0.00,1000.00,'Salary Advance Disbursement (Cash Drawer) Ref: ADV-202610-8482'),(560,205,54,0.00,170000.00,'Reversal: Basic Salaries & Wages - Period Test Advance Recovery Period Oct 2026'),(561,205,55,0.00,25500.00,'Reversal: Employer EPF (12%) & ETF (3%) Expense - Period Test Advance Recovery Period Oct 2026'),(562,205,51,144400.00,0.00,'Reversal: Net Salaries Payable - Period Test Advance Recovery Period Oct 2026'),(563,205,52,34000.00,0.00,'Reversal: EPF Liability (Employee 8% + Employer 12%) - Period Test Advance Recovery Period Oct 2026'),(564,205,53,5100.00,0.00,'Reversal: ETF Liability (Employer 3%) - Period Test Advance Recovery Period Oct 2026'),(565,205,50,12000.00,0.00,'Reversal: Salary Advances Recovered from Payroll - Period Test Advance Recovery Period Oct 2026'),(566,206,50,1000.01,0.00,'Salary Advance Disbursed to Chamath (EMP-0002)'),(567,206,49,0.00,1000.01,'Salary Advance Disbursement (Cash Drawer) Ref: ADV-202610-2965'),(590,210,51,60900.00,0.00,'Salary Payment to Chamath (EMP-0002) - Sri Lanka Labor Law Test Period'),(591,210,1,0.00,30450.00,'Salary Disbursement (Cash in Hand) Ref: PAY-SAL-202610-8902-1'),(592,210,2,0.00,30450.00,'Salary Disbursement (Bank Transfer) Ref: PAY-SAL-202610-8902-2 Chq: CHQ-998877'),(593,211,52,14000.00,0.00,'EPF Statutory Remittance to Central Bank - Period Sri Lanka Labor Law Test Period'),(594,211,1,0.00,14000.00,'EPF Remittance Payment Ref: REMIT-202610-8477 Chq: EPF-CHQ-1001'),(595,211,53,2100.00,0.00,'ETF Statutory Remittance to ETF Board - Period Sri Lanka Labor Law Test Period'),(596,211,2,0.00,2100.00,'ETF Remittance Payment Ref: REMIT-202610-8477 Chq: ETF-CHQ-2002'),(604,213,51,60900.00,0.00,'Salary Payment to Chamath (EMP-0002) - Sri Lanka Labor Law Test Period'),(605,213,1,0.00,30450.00,'Salary Disbursement (Cash in Hand) Ref: PAY-SAL-202610-7488-1'),(606,213,2,0.00,30450.00,'Salary Disbursement (Bank Transfer) Ref: PAY-SAL-202610-7488-2 Chq: CHQ-998877'),(607,214,52,14000.00,0.00,'EPF Statutory Remittance to Central Bank - Period Sri Lanka Labor Law Test Period'),(608,214,1,0.00,14000.00,'EPF Remittance Payment Ref: REMIT-202610-9901 Chq: EPF-CHQ-1001'),(609,214,53,2100.00,0.00,'ETF Statutory Remittance to ETF Board - Period Sri Lanka Labor Law Test Period'),(610,214,2,0.00,2100.00,'ETF Remittance Payment Ref: REMIT-202610-9901 Chq: ETF-CHQ-2002'),(618,216,51,60900.00,0.00,'Salary Payment to Chamath (EMP-0002) - Sri Lanka Labor Law Test Period'),(619,216,1,0.00,30450.00,'Salary Disbursement (Cash in Hand) Ref: PAY-SAL-202610-6334-1'),(620,216,2,0.00,30450.00,'Salary Disbursement (Bank Transfer) Ref: PAY-SAL-202610-6334-2 Chq: CHQ-998877'),(621,217,52,14000.00,0.00,'EPF Statutory Remittance to Central Bank - Period Sri Lanka Labor Law Test Period'),(622,217,1,0.00,14000.00,'EPF Remittance Payment Ref: REMIT-202610-6155 Chq: EPF-CHQ-1001'),(623,217,53,2100.00,0.00,'ETF Statutory Remittance to ETF Board - Period Sri Lanka Labor Law Test Period'),(624,217,2,0.00,2100.00,'ETF Remittance Payment Ref: REMIT-202610-6155 Chq: ETF-CHQ-2002'),(632,219,51,60900.00,0.00,'Salary Payment to Chamath (EMP-0002) - Sri Lanka Labor Law Test Period'),(633,219,1,0.00,30450.00,'Salary Disbursement (Cash in Hand) Ref: PAY-SAL-202610-7232-1'),(634,219,2,0.00,30450.00,'Salary Disbursement (Bank Transfer) Ref: PAY-SAL-202610-7232-2 Chq: CHQ-998877'),(635,220,52,14000.00,0.00,'EPF Statutory Remittance to Central Bank - Period Sri Lanka Labor Law Test Period'),(636,220,1,0.00,14000.00,'EPF Remittance Payment Ref: REMIT-202610-2117 Chq: EPF-CHQ-1001'),(637,220,53,2100.00,0.00,'ETF Statutory Remittance to ETF Board - Period Sri Lanka Labor Law Test Period'),(638,220,2,0.00,2100.00,'ETF Remittance Payment Ref: REMIT-202610-2117 Chq: ETF-CHQ-2002'),(646,222,51,60900.00,0.00,'Salary Payment to Chamath (EMP-0002) - Sri Lanka Labor Law Test Period'),(647,222,1,0.00,30450.00,'Salary Disbursement (Cash in Hand) Ref: PAY-SAL-202610-2469-1'),(648,222,2,0.00,30450.00,'Salary Disbursement (Bank Transfer) Ref: PAY-SAL-202610-2469-2 Chq: CHQ-998877'),(649,223,52,14000.00,0.00,'EPF Statutory Remittance to Central Bank - Period Sri Lanka Labor Law Test Period'),(650,223,1,0.00,14000.00,'EPF Remittance Payment Ref: REMIT-202610-2055 Chq: EPF-CHQ-1001'),(651,223,53,2100.00,0.00,'ETF Statutory Remittance to ETF Board - Period Sri Lanka Labor Law Test Period'),(652,223,2,0.00,2100.00,'ETF Remittance Payment Ref: REMIT-202610-2055 Chq: ETF-CHQ-2002'),(660,225,51,60900.00,0.00,'Salary Payment to Chamath (EMP-0002) - Sri Lanka Labor Law Test Period'),(661,225,1,0.00,30450.00,'Salary Disbursement (Cash in Hand) Ref: PAY-SAL-202610-7399-1'),(662,225,2,0.00,30450.00,'Salary Disbursement (Bank Transfer) Ref: PAY-SAL-202610-7399-2 Chq: CHQ-998877'),(663,226,52,14000.00,0.00,'EPF Statutory Remittance to Central Bank - Period Sri Lanka Labor Law Test Period'),(664,226,1,0.00,14000.00,'EPF Remittance Payment Ref: REMIT-202610-5814 Chq: EPF-CHQ-1001'),(665,226,53,2100.00,0.00,'ETF Statutory Remittance to ETF Board - Period Sri Lanka Labor Law Test Period'),(666,226,2,0.00,2100.00,'ETF Remittance Payment Ref: REMIT-202610-5814 Chq: ETF-CHQ-2002'),(674,228,51,60900.00,0.00,'Salary Payment to Chamath (EMP-0002) - Sri Lanka Labor Law Test Period'),(675,228,1,0.00,30450.00,'Salary Disbursement (Cash in Hand) Ref: PAY-SAL-202610-1316-1'),(676,228,2,0.00,30450.00,'Salary Disbursement (Bank Transfer) Ref: PAY-SAL-202610-1316-2 Chq: CHQ-998877'),(677,229,52,14000.00,0.00,'EPF Statutory Remittance to Central Bank - Period Sri Lanka Labor Law Test Period'),(678,229,1,0.00,14000.00,'EPF Remittance Payment Ref: REMIT-202610-7448 Chq: EPF-CHQ-1001'),(679,229,53,2100.00,0.00,'ETF Statutory Remittance to ETF Board - Period Sri Lanka Labor Law Test Period'),(680,229,2,0.00,2100.00,'ETF Remittance Payment Ref: REMIT-202610-7448 Chq: ETF-CHQ-2002'),(697,236,54,0.00,170000.00,'Reversal: Basic Salaries & Wages - Period Test Advance Recovery Period Oct 2026'),(698,236,55,0.00,25500.00,'Reversal: Employer EPF (12%) & ETF (3%) Expense - Period Test Advance Recovery Period Oct 2026'),(699,236,51,144400.00,0.00,'Reversal: Net Salaries Payable - Period Test Advance Recovery Period Oct 2026'),(700,236,52,34000.00,0.00,'Reversal: EPF Liability (Employee 8% + Employer 12%) - Period Test Advance Recovery Period Oct 2026'),(701,236,53,5100.00,0.00,'Reversal: ETF Liability (Employer 3%) - Period Test Advance Recovery Period Oct 2026'),(702,236,50,12000.00,0.00,'Reversal: Salary Advances Recovered from Payroll - Period Test Advance Recovery Period Oct 2026'),(703,237,54,110000.00,0.00,'Basic Salaries & Wages - Period October 2026'),(704,237,56,1000.00,0.00,'Allowances, Commissions & Overtime - Period October 2026'),(705,237,55,16650.00,0.00,'Employer EPF (12%) & ETF (3%) Expense - Period October 2026'),(706,237,51,0.00,100000.00,'Net Salaries Payable - Period October 2026'),(707,237,52,0.00,22200.00,'EPF Liability (Employee 8% + Employer 12%) - Period October 2026'),(708,237,53,0.00,3330.00,'ETF Liability (Employer 3%) - Period October 2026'),(709,237,50,0.00,1000.01,'Salary Advances Recovered from Payroll - Period October 2026'),(710,237,51,0.00,1119.99,'Other Payroll Deductions - Period October 2026'),(742,245,51,60900.00,0.00,'Salary Payment to Chamath (EMP-0002) - Sri Lanka Labor Law Test Period'),(743,245,1,0.00,30450.00,'Salary Disbursement (Cash in Hand) Ref: PAY-SAL-202610-2600-1'),(744,245,2,0.00,30450.00,'Salary Disbursement (Bank Transfer) Ref: PAY-SAL-202610-2600-2 Chq: CHQ-998877'),(745,246,52,14000.00,0.00,'EPF Statutory Remittance to Central Bank - Period Sri Lanka Labor Law Test Period'),(746,246,1,0.00,14000.00,'EPF Remittance Payment Ref: REMIT-202610-5013 Chq: EPF-CHQ-1001'),(747,246,53,2100.00,0.00,'ETF Statutory Remittance to ETF Board - Period Sri Lanka Labor Law Test Period'),(748,246,2,0.00,2100.00,'ETF Remittance Payment Ref: REMIT-202610-5013 Chq: ETF-CHQ-2002'),(757,249,51,100000.00,0.00,'Period Payroll Salaries Disbursement: October 2026 (2026-10)'),(758,249,49,0.00,20000.00,'Payroll Disbursement (Cash Drawer) Ref: PAY-SAL-202610-7632-1'),(759,249,1,0.00,10000.00,'Payroll Disbursement (Cash in Hand) Ref: PAY-SAL-202610-7632-2'),(760,249,2,0.00,25000.00,'Payroll Disbursement (Petty Cash) Ref: PAY-SAL-202610-7632-3'),(761,249,59,0.00,10000.00,'Payroll Disbursement (Bank Transfer) Ref: PAY-SAL-202610-7632-4'),(762,249,60,0.00,10000.00,'Payroll Disbursement (Bank Transfer) Ref: PAY-SAL-202610-7632-5'),(763,249,60,0.00,10000.00,'Payroll Disbursement (Cheque) Ref: PAY-SAL-202610-7632-6'),(764,249,59,0.00,15000.00,'Payroll Disbursement (Cheque) Ref: PAY-SAL-202610-7632-7'),(765,250,54,110000.00,0.00,'Basic Salaries & Wages - Period October 2026'),(766,250,55,16500.00,0.00,'Employer EPF (12%) & ETF (3%) Expense - Period October 2026'),(767,250,51,0.00,101200.00,'Net Salaries Payable - Period October 2026'),(768,250,52,0.00,22000.00,'EPF Liability (Employee 8% + Employer 12%) - Period October 2026'),(769,250,53,0.00,3300.00,'ETF Liability (Employer 3%) - Period October 2026'),(770,251,52,22000.00,0.00,'EPF Statutory Remittance to Central Bank - Period October 2026'),(771,251,60,0.00,22000.00,'EPF Remittance Payment Ref: REMIT-202610-7249'),(772,251,53,3300.00,0.00,'ETF Statutory Remittance to ETF Board - Period October 2026'),(773,251,60,0.00,3300.00,'ETF Remittance Payment Ref: REMIT-202610-7249'),(807,260,51,60900.00,0.00,'Salary Payment to Chamath (EMP-0002) - Sri Lanka Labor Law Test Period'),(808,260,1,0.00,30450.00,'Salary Disbursement (Cash in Hand) Ref: PAY-SAL-202610-8635-1'),(809,260,2,0.00,30450.00,'Salary Disbursement (Bank Transfer) Ref: PAY-SAL-202610-8635-2 Chq: CHQ-998877'),(810,261,52,14000.00,0.00,'EPF Statutory Remittance to Central Bank - Period Sri Lanka Labor Law Test Period'),(811,261,1,0.00,14000.00,'EPF Remittance Payment Ref: REMIT-202610-2253 Chq: EPF-CHQ-1001'),(812,261,53,2100.00,0.00,'ETF Statutory Remittance to ETF Board - Period Sri Lanka Labor Law Test Period'),(813,261,2,0.00,2100.00,'ETF Remittance Payment Ref: REMIT-202610-2253 Chq: ETF-CHQ-2002'),(833,267,1,1500.00,0.00,'Collection via Cash'),(834,267,5,0.00,1500.00,'AR Collection for Split Test Customer'),(835,268,1,1000.00,0.00,'Collection via Cash'),(836,268,5,0.00,1000.00,'AR Collection for Split Test Customer'),(837,269,1,500.00,0.00,'Collection via Cash'),(838,269,5,0.00,500.00,'AR Collection for Split Test Customer'),(839,270,1,1500.00,0.00,'Collection via Cash'),(840,270,5,0.00,1500.00,'AR Collection for Split Test Customer'),(841,271,1,1000.00,0.00,'Collection via Cash'),(842,271,5,0.00,1000.00,'AR Collection for Split Test Customer'),(843,272,1,500.00,0.00,'Collection via Cash'),(844,272,5,0.00,500.00,'AR Collection for Split Test Customer'),(845,273,1,1500.00,0.00,'Collection via Cash'),(846,273,5,0.00,1500.00,'AR Collection for Split Test Customer'),(847,274,1,1000.00,0.00,'Collection via Cash'),(848,274,5,0.00,1000.00,'AR Collection for Split Test Customer'),(849,275,1,500.00,0.00,'Collection via Cash'),(850,275,5,0.00,500.00,'AR Collection for Split Test Customer'),(851,276,1,1000.00,0.00,'Collection via Cash'),(852,276,5,0.00,1000.00,'AR Collection for Sec7 Test Active Customer'),(853,277,1,1000.00,0.00,'Collection via Cash'),(854,277,5,0.00,1000.00,'AR Collection for Sec7 Test Active Customer'),(855,278,1,3000.00,0.00,'Collection via Cash'),(856,278,5,0.00,3000.00,'AR Collection for Sec7 Test Inactive Customer'),(857,279,1,4000.00,0.00,'Collection via Cash'),(858,279,5,0.00,4000.00,'AR Collection for Sec7 Test Active Customer'),(859,280,1,2000.00,0.00,'Collection via Cash'),(860,280,5,0.00,2000.00,'AR Collection for Sec7 Test Active Customer'),(861,281,1,3000.00,0.00,'Collection via Cash'),(862,281,5,0.00,3000.00,'AR Collection for Sec7 Test Active Customer'),(863,282,1,4000.00,0.00,'Collection via Cash'),(864,282,5,0.00,4000.00,'AR Collection for Sec7 Test Active Customer'),(865,283,1,1500.00,0.00,'Collection via Cash in hand'),(866,283,5,0.00,1500.00,'AR Collection for Split Test Customer'),(867,284,59,1000.00,0.00,'Collection via Bank'),(868,284,5,0.00,1000.00,'AR Collection for Split Test Customer'),(869,285,1,3000.00,0.00,'Collection via Cash in hand'),(870,285,5,0.00,3000.00,'AR Collection for Sec7 Test Inactive Customer'),(871,286,1,4000.00,0.00,'Collection via Cash in hand'),(872,286,5,0.00,4000.00,'AR Collection for Sec7 Test Active Customer'),(873,287,4,2000.00,0.00,'Collection via Card'),(874,287,5,0.00,2000.00,'AR Collection for Sec7 Test Active Customer'),(875,288,59,3000.00,0.00,'Collection via Bank'),(876,288,5,0.00,3000.00,'AR Collection for Sec7 Test Active Customer'),(879,290,1,3000.00,0.00,'Collection via Cash in hand'),(880,290,5,0.00,3000.00,'AR Collection for Sec7 Test Inactive Customer'),(881,291,1,4000.00,0.00,'Collection via Cash in hand'),(882,291,5,0.00,4000.00,'AR Collection for Sec7 Test Active Customer'),(883,292,4,2000.00,0.00,'Collection via Card'),(884,292,5,0.00,2000.00,'AR Collection for Sec7 Test Active Customer'),(885,293,59,3000.00,0.00,'Collection via Bank'),(886,293,5,0.00,3000.00,'AR Collection for Sec7 Test Active Customer'),(887,294,5,3000.00,0.00,'Restored AR for returned cheque #S7-CHQ-BANKED-1002'),(888,294,67,0.00,3000.00,'Cheque return reversal for #S7-CHQ-BANKED-1002'),(889,295,1,1500.00,0.00,'Collection via Cash in hand'),(890,295,5,0.00,1500.00,'AR Collection for Split Test Customer'),(891,296,59,1000.00,0.00,'Collection via Bank'),(892,296,5,0.00,1000.00,'AR Collection for Split Test Customer'),(893,297,1,6000.00,0.00,'Collection via Cash in hand'),(894,297,5,0.00,6000.00,'AR Collection for Sec7 Test Active Customer'),(895,298,1,1500.00,0.00,'Collection via Cash in hand'),(896,298,5,0.00,1500.00,'AR Collection for Split Test Customer'),(897,299,59,1000.00,0.00,'Collection via Bank'),(898,299,5,0.00,1000.00,'AR Collection for Split Test Customer'),(899,300,1,1200.00,0.00,'Collection via Cash in hand'),(900,300,5,0.00,1200.00,'AR Collection for Partial Test Customer'),(901,301,59,800.00,0.00,'Collection via Bank'),(902,301,5,0.00,800.00,'AR Collection for Partial Test Customer'),(903,302,1,500.00,0.00,'Collection via Cash in hand'),(904,302,5,0.00,500.00,'AR Collection for Sec7 Test Inactive Customer'),(905,303,2,500.00,0.00,'Collection via Petty cash'),(906,303,5,0.00,500.00,'AR Collection for Sec7 Test Inactive Customer'),(939,312,12,2400.00,0.00,'Sales Return #RET-0001'),(940,312,49,0.00,2400.00,'Cash Drawer refund payout'),(941,312,6,1500.00,0.00,'Restocked returned goods'),(942,312,13,0.00,1500.00,'COGS reduction for good returns'),(943,312,7,200.00,0.00,'Damaged goods return loss'),(944,312,13,0.00,200.00,'COGS reduction for damaged returns'),(945,313,12,2200.00,0.00,'Sales Return #RET-0013'),(946,313,5,0.00,2200.00,'Return exchange credit (No cash payout)'),(947,313,6,1600.00,0.00,'Restocked returned goods'),(948,313,13,0.00,1600.00,'COGS reduction for good returns'),(949,314,12,2200.00,0.00,'Sales Return #RET-0014'),(950,314,5,0.00,2200.00,'Return exchange credit (No cash payout)'),(951,314,6,1500.00,0.00,'Restocked returned goods'),(952,314,13,0.00,1500.00,'COGS reduction for good returns'),(953,314,7,100.00,0.00,'Damaged goods return loss'),(954,314,13,0.00,100.00,'COGS reduction for damaged returns'),(957,316,6,0.00,1500.00,'Reversal: Opening Stock asset for TestProduct 1792052903'),(958,316,10,1500.00,0.00,'Reversal: Opening Stock equity balance for TestProduct 1792052903'),(959,317,6,1500.00,0.00,'Reversal: Reversal: Opening Stock asset for TestProduct 1792052903'),(960,317,10,0.00,1500.00,'Reversal: Reversal: Opening Stock equity balance for TestProduct 1792052903'),(965,320,6,1300.00,0.00,'Stock received via GRN GRN-202610-0011'),(966,320,9,0.00,1300.00,'Supplier Payable for GRN GRN-202610-0011'),(967,321,6,15000.00,0.00,'Stock received via GRN GRN-202610-0012'),(968,321,9,0.00,15000.00,'Supplier Payable for GRN GRN-202610-0012'),(969,322,6,15000.00,0.00,'Stock received via GRN GRN-202610-0013'),(970,322,9,0.00,15000.00,'Supplier Payable for GRN GRN-202610-0013'),(971,323,6,15000.00,0.00,'Stock received via GRN GRN-202610-0014'),(972,323,9,0.00,15000.00,'Supplier Payable for GRN GRN-202610-0014'),(977,326,6,1300.00,0.00,'Stock received via GRN GRN-202610-0016'),(978,326,9,0.00,1300.00,'Supplier Payable for GRN GRN-202610-0016'),(979,327,6,1300.00,0.00,'Stock received via GRN GRN-202610-0018'),(980,327,9,0.00,1300.00,'Supplier Payable for GRN GRN-202610-0018'),(985,330,6,1300.00,0.00,'Stock received via GRN GRN-202610-0018'),(986,330,9,0.00,1300.00,'Supplier Payable for GRN GRN-202610-0018'),(999,337,10,5000.00,0.00,'Opening Balance Equity: Temp Rev Test 1792056085'),(1000,337,9,0.00,5000.00,'Supplier Payable OB: Temp Rev Test 1792056085 (SUP-0017)'),(1001,338,9,5000.00,0.00,'Reverse OB Payable: Temp Rev Test 1792056085'),(1002,338,10,0.00,5000.00,'Reverse OB Equity: Temp Rev Test 1792056085'),(1013,344,6,1300.00,0.00,'Stock received via GRN GRN-202610-0025'),(1014,344,9,0.00,1300.00,'Supplier Payable for GRN GRN-202610-0025'),(1025,350,6,1300.00,0.00,'Stock received via GRN GRN-202610-0033'),(1026,350,9,0.00,1300.00,'Supplier Payable for GRN GRN-202610-0033'),(1027,351,6,1300.00,0.00,'Stock received via GRN GRN-202610-0038'),(1028,351,9,0.00,1300.00,'Supplier Payable for GRN GRN-202610-0038'),(1033,354,6,1300.00,0.00,'Stock received via GRN GRN-202610-0039'),(1034,354,9,0.00,1300.00,'Supplier Payable for GRN GRN-202610-0039');
/*!40000 ALTER TABLE `journal_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `koko_transactions`
--

DROP TABLE IF EXISTS `koko_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `koko_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'LKR',
  `customer_phone` varchar(30) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `koko_order_id` varchar(100) NOT NULL,
  `koko_payment_id` varchar(100) DEFAULT NULL,
  `payment_url` text DEFAULT NULL,
  `qr_data` text DEFAULT NULL,
  `channel` enum('qr','link','sms','direct') NOT NULL DEFAULT 'qr',
  `status` enum('initiated','pending','paid','failed','cancelled','expired') NOT NULL DEFAULT 'pending',
  `response_payload` longtext DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `koko_order_id` (`koko_order_id`),
  KEY `idx_koko_invoice` (`invoice_no`),
  KEY `idx_koko_order` (`koko_order_id`),
  KEY `idx_koko_status` (`status`),
  KEY `idx_koko_branch` (`branch_id`),
  KEY `idx_koko_sale` (`sale_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `koko_transactions`
--

LOCK TABLES `koko_transactions` WRITE;
/*!40000 ALTER TABLE `koko_transactions` DISABLE KEYS */;
INSERT INTO `koko_transactions` VALUES (1,51,'INV-51',1,2000.00,'LKR','','Walk-in Customer','KOKO-B1-261012135426-F71575',NULL,'https://sandbox.paykoko.com/checkout/KOKO-B1-261012135426-F71575?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-51&name=Walk-in+Customer&phone=&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','https://sandbox.paykoko.com/checkout/KOKO-B1-261012135426-F71575?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-51&name=Walk-in+Customer&phone=&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','qr','pending','{\"environment\":\"sandbox\",\"merchant_id\":\"KOKO_MID_SAMPLE\",\"installment_amount\":666.67,\"sms_dispatched\":false,\"sms_message\":\"\",\"client_ip\":\"::1\"}',NULL,1,'2026-10-12 17:24:26','2026-10-12 17:24:26'),(2,51,'INV-51',1,2000.00,'LKR','','Walk-in Customer','KOKO-B1-261012135437-CDB577',NULL,'https://sandbox.paykoko.com/checkout/KOKO-B1-261012135437-CDB577?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-51&name=Walk-in+Customer&phone=&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','https://sandbox.paykoko.com/checkout/KOKO-B1-261012135437-CDB577?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-51&name=Walk-in+Customer&phone=&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','qr','pending','{\"environment\":\"sandbox\",\"merchant_id\":\"KOKO_MID_SAMPLE\",\"installment_amount\":666.67,\"sms_dispatched\":false,\"sms_message\":\"\",\"client_ip\":\"::1\"}',NULL,1,'2026-10-12 17:24:37','2026-10-12 17:24:37'),(3,9,'INV-9',1,1000.00,'LKR','','Walk-in Customer','KOKO-B1-261012160042-CCA7D0',NULL,'https://sandbox.paykoko.com/checkout/KOKO-B1-261012160042-CCA7D0?amount=1000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-9&name=Walk-in+Customer&phone=&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','https://sandbox.paykoko.com/checkout/KOKO-B1-261012160042-CCA7D0?amount=1000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-9&name=Walk-in+Customer&phone=&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','qr','pending','{\"environment\":\"sandbox\",\"merchant_id\":\"KOKO_MID_SAMPLE\",\"installment_amount\":333.33,\"sms_dispatched\":false,\"sms_message\":\"\",\"client_ip\":\"::1\"}',NULL,1,'2026-10-12 19:30:42','2026-10-12 19:30:42'),(4,2,'INV-2',1,5000.00,'LKR','01236522266','1000 Credit Customer','KOKO-B1-261012160900-88F04A',NULL,'https://sandbox.paykoko.com/checkout/KOKO-B1-261012160900-88F04A?amount=5000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-2&name=1000+Credit+Customer&phone=01236522266&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','https://sandbox.paykoko.com/checkout/KOKO-B1-261012160900-88F04A?amount=5000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-2&name=1000+Credit+Customer&phone=01236522266&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','qr','pending','{\"environment\":\"sandbox\",\"merchant_id\":\"KOKO_MID_SAMPLE\",\"installment_amount\":1666.67,\"sms_dispatched\":false,\"sms_message\":\"\",\"client_ip\":\"::1\"}',NULL,1,'2026-10-12 19:39:00','2026-10-12 19:39:00'),(5,2,'INV-2',1,1000.00,'LKR','01236522266','1000 Credit Customer','KOKO-B1-261012160959-87F7D0',NULL,'https://sandbox.paykoko.com/checkout/KOKO-B1-261012160959-87F7D0?amount=1000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-2&name=1000+Credit+Customer&phone=01236522266&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','https://sandbox.paykoko.com/checkout/KOKO-B1-261012160959-87F7D0?amount=1000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-2&name=1000+Credit+Customer&phone=01236522266&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','qr','pending','{\"environment\":\"sandbox\",\"merchant_id\":\"KOKO_MID_SAMPLE\",\"installment_amount\":333.33,\"sms_dispatched\":false,\"sms_message\":\"\",\"client_ip\":\"::1\"}',NULL,1,'2026-10-12 19:39:59','2026-10-12 19:39:59'),(6,9,'INV-9',1,2000.00,'LKR','0761407878','Himaya Senarathne','KOKO-B1-261013075955-10B473',NULL,'https://sandbox.paykoko.com/checkout/KOKO-B1-261013075955-10B473?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-9&name=Himaya+Senarathne&phone=0761407878&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','https://sandbox.paykoko.com/checkout/KOKO-B1-261013075955-10B473?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-9&name=Himaya+Senarathne&phone=0761407878&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','qr','pending','{\"environment\":\"sandbox\",\"merchant_id\":\"KOKO_MID_SAMPLE\",\"installment_amount\":666.67,\"sms_dispatched\":false,\"sms_message\":\"\",\"client_ip\":\"::1\"}',NULL,1,'2026-10-13 11:29:55','2026-10-13 11:29:55'),(7,10,'INV-10',1,2000.00,'LKR','0761407878','Himaya Senarathne','KOKO-B1-261013080258-7F2EDE',NULL,'https://sandbox.paykoko.com/checkout/KOKO-B1-261013080258-7F2EDE?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-10&name=Himaya+Senarathne&phone=0761407878&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','https://sandbox.paykoko.com/checkout/KOKO-B1-261013080258-7F2EDE?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-10&name=Himaya+Senarathne&phone=0761407878&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','qr','pending','{\"environment\":\"sandbox\",\"merchant_id\":\"KOKO_MID_SAMPLE\",\"installment_amount\":666.67,\"sms_dispatched\":false,\"sms_message\":\"\",\"client_ip\":\"::1\"}',NULL,1,'2026-10-13 11:32:58','2026-10-13 11:32:58'),(8,10,'INV-10',1,2000.00,'LKR','0761407878','Himaya Senarathne','KOKO-B1-261013080259-4E7E39',NULL,'https://sandbox.paykoko.com/checkout/KOKO-B1-261013080259-4E7E39?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-10&name=Himaya+Senarathne&phone=0761407878&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','https://sandbox.paykoko.com/checkout/KOKO-B1-261013080259-4E7E39?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-10&name=Himaya+Senarathne&phone=0761407878&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','qr','pending','{\"environment\":\"sandbox\",\"merchant_id\":\"KOKO_MID_SAMPLE\",\"installment_amount\":666.67,\"sms_dispatched\":false,\"sms_message\":\"\",\"client_ip\":\"::1\"}',NULL,1,'2026-10-13 11:32:59','2026-10-13 11:32:59'),(9,13,'INV-13',1,2000.00,'LKR','','Walk-in Customer','KOKO-B1-261013094811-2A8427',NULL,'https://sandbox.paykoko.com/checkout/KOKO-B1-261013094811-2A8427?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-13&name=Walk-in+Customer&phone=&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','https://sandbox.paykoko.com/checkout/KOKO-B1-261013094811-2A8427?amount=2000.00&currency=LKR&mid=KOKO_MID_SAMPLE&ref=INV-13&name=Walk-in+Customer&phone=&channel=qr&callback=http%3A%2F%2Flocalhost%2FPOS%2Fkoko_api.php%3Faction%3Dwebhook','qr','pending','{\"environment\":\"sandbox\",\"merchant_id\":\"KOKO_MID_SAMPLE\",\"installment_amount\":666.67,\"sms_dispatched\":false,\"sms_message\":\"\",\"client_ip\":\"::1\"}',NULL,1,'2026-10-13 13:18:11','2026-10-13 13:18:11');
/*!40000 ALTER TABLE `koko_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_no` varchar(50) NOT NULL,
  `sale_id` bigint(20) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `processor_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash in Hand',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(100) DEFAULT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `settlement_status` enum('unsettled','partially_settled','settled') NOT NULL DEFAULT 'unsettled',
  `settled_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `refunded_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `user_id` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_no` (`payment_no`),
  KEY `fk_pay_voucher` (`voucher_id`),
  KEY `fk_pay_user` (`user_id`),
  KEY `idx_pay_sale` (`sale_id`),
  KEY `idx_pay_branch` (`branch_id`),
  KEY `idx_pay_customer` (`customer_id`),
  KEY `idx_pay_method` (`payment_method`),
  KEY `idx_pmt_processor` (`processor_id`),
  KEY `idx_pmt_settle_status` (`settlement_status`),
  CONSTRAINT `fk_pay_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `fk_pay_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_pay_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_pay_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `gift_vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,'PAY-20261012-5418I2ME',2,1,NULL,NULL,16,'Card',500.00,'',NULL,'unsettled',0.00,0.00,1,'2026-10-12 19:41:09'),(2,'PAY-20261012-6999T2F5',2,1,2,NULL,16,'Bank',500.00,'',NULL,'unsettled',0.00,0.00,1,'2026-10-12 19:41:09'),(3,'PAY-20261012-68321THB',2,1,NULL,NULL,16,'Credit',2500.00,'Credit Account',NULL,'unsettled',0.00,0.00,1,'2026-10-12 19:41:09'),(4,'PAY-20261012-89251STC',2,1,NULL,NULL,16,'Koko',2000.00,'KOKO-B1-261012160900-88F04A',NULL,'unsettled',0.00,0.00,1,'2026-10-12 19:41:09'),(5,'PAY-20261012-3388746Y',2,1,NULL,NULL,16,'Voucher',2000.00,'GV-20261012-E9CA98',2,'unsettled',0.00,0.00,1,'2026-10-12 19:41:09'),(6,'PAY-20261012-53389F76',2,1,NULL,NULL,16,'Cash',1500.00,'',NULL,'unsettled',0.00,0.00,1,'2026-10-12 19:41:09'),(7,'PAY-20261012-24863FX2',7,1,NULL,NULL,1,'Cash',70.00,'',NULL,'unsettled',0.00,0.00,1,'2026-10-12 23:02:17'),(42,'CPAY-0002',27,1,NULL,NULL,40,'Cash in Hand',4000.00,'CPAY-0002',NULL,'unsettled',0.00,0.00,1,'2026-10-14 13:21:18'),(43,'CPAY-0003',27,1,NULL,NULL,40,'Card',2000.00,'CARD-S7-8899',NULL,'unsettled',0.00,0.00,1,'2026-10-14 13:21:18'),(44,'CPAY-0004',28,1,1,NULL,40,'Bank',3000.00,'BANK-TXN-9988',NULL,'unsettled',0.00,0.00,1,'2026-10-14 13:21:18'),(45,'CPAY-0005',28,1,NULL,NULL,40,'Cheque',4000.00,'CPAY-0005',NULL,'unsettled',0.00,0.00,1,'2026-10-14 13:21:18'),(46,'CHQ-RET-S7-CHQ-1001',28,1,NULL,NULL,40,'Cheque Return',-4000.00,'Reversal for S7-CHQ-1001',NULL,'unsettled',0.00,0.00,1,'2026-10-14 13:21:18'),(47,'CPAY-0006',28,1,NULL,NULL,40,'Cheque',3000.00,'CPAY-0006',NULL,'unsettled',0.00,0.00,1,'2026-10-14 13:21:18'),(48,'CHQ-RET-S7-CHQ-BANKED-1002',28,1,NULL,NULL,40,'Cheque Return',-3000.00,'Reversal for S7-CHQ-BANKED-1002',NULL,'unsettled',0.00,0.00,1,'2026-10-14 13:21:18'),(53,'CPAY-0007',28,2,NULL,NULL,40,'Cash in Hand',6000.00,'CPAY-0007',NULL,'unsettled',0.00,0.00,1,'2026-10-14 13:52:53');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_entries`
--

DROP TABLE IF EXISTS `payroll_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_entries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `payroll_period_id` int(10) unsigned NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `basic_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `allowances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `bonuses` decimal(12,2) NOT NULL DEFAULT 0.00,
  `commissions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `overtime_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `gross_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `employee_epf_rate` decimal(5,2) NOT NULL DEFAULT 8.00,
  `employee_epf_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `salary_advances_deducted` decimal(12,2) NOT NULL DEFAULT 0.00,
  `other_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `employer_epf_rate` decimal(5,2) NOT NULL DEFAULT 12.00,
  `employer_epf_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `employer_etf_rate` decimal(5,2) NOT NULL DEFAULT 3.00,
  `employer_etf_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','approved','partially_paid','paid','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pp_emp` (`payroll_period_id`,`employee_id`),
  KEY `idx_pe_emp` (`employee_id`),
  KEY `idx_pe_branch` (`branch_id`),
  KEY `idx_pe_status` (`status`),
  CONSTRAINT `fk_pe_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `fk_pe_period` FOREIGN KEY (`payroll_period_id`) REFERENCES `payroll_periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_entries`
--

LOCK TABLES `payroll_entries` WRITE;
/*!40000 ALTER TABLE `payroll_entries` DISABLE KEYS */;
INSERT INTO `payroll_entries` VALUES (26,12,2,1,50000.00,0.00,0.00,1000.00,0.00,51000.00,8.00,4080.00,1000.01,19.99,5100.00,45900.00,12.00,6120.00,3.00,1530.00,45900.00,'paid',NULL,'2026-10-13 20:12:20','2026-10-14 01:31:18'),(27,12,3,1,60000.00,0.00,0.00,0.00,0.00,60000.00,8.00,4800.00,0.00,1100.00,5900.00,54100.00,12.00,7200.00,3.00,1800.00,54100.00,'paid',NULL,'2026-10-13 20:12:20','2026-10-14 01:31:18'),(61,29,2,1,50000.00,0.00,0.00,0.00,0.00,50000.00,8.00,4000.00,0.00,0.00,4000.00,46000.00,12.00,6000.00,3.00,1500.00,0.00,'approved',NULL,'2026-10-14 01:36:47','2026-10-14 01:36:52'),(62,29,3,1,60000.00,0.00,0.00,0.00,0.00,60000.00,8.00,4800.00,0.00,0.00,4800.00,55200.00,12.00,7200.00,3.00,1800.00,0.00,'approved',NULL,'2026-10-14 01:36:47','2026-10-14 01:36:52');
/*!40000 ALTER TABLE `payroll_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_payments`
--

DROP TABLE IF EXISTS `payroll_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `payment_no` varchar(50) NOT NULL,
  `payroll_entry_id` int(10) unsigned NOT NULL,
  `employee_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash',
  `payment_date` date NOT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `account_id` int(10) unsigned DEFAULT NULL,
  `cheque_number` varchar(50) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_no` (`payment_no`),
  KEY `idx_pay_pe` (`payroll_entry_id`),
  KEY `idx_pay_emp` (`employee_id`),
  KEY `idx_pay_method` (`payment_method`),
  CONSTRAINT `fk_pay_emp` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  CONSTRAINT `fk_pay_pe` FOREIGN KEY (`payroll_entry_id`) REFERENCES `payroll_entries` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_payments`
--

LOCK TABLES `payroll_payments` WRITE;
/*!40000 ALTER TABLE `payroll_payments` DISABLE KEYS */;
INSERT INTO `payroll_payments` VALUES (26,'PAY-SAL-202610-7632-1',26,2,1,20000.00,'Cash Drawer','2026-10-13',NULL,49,NULL,NULL,NULL,'Period Payroll: 2026-10',1,'2026-10-14 01:31:18'),(27,'PAY-SAL-202610-7632-2',26,2,1,10000.00,'Cash in Hand','2026-10-13',NULL,1,NULL,NULL,NULL,'Period Payroll: 2026-10',1,'2026-10-14 01:31:18'),(28,'PAY-SAL-202610-7632-3',26,2,1,25000.00,'Petty Cash','2026-10-13',NULL,2,NULL,NULL,NULL,'Period Payroll: 2026-10',1,'2026-10-14 01:31:18'),(29,'PAY-SAL-202610-7632-4',26,2,1,10000.00,'Bank Transfer','2026-10-13',1,59,NULL,NULL,NULL,'Period Payroll: 2026-10',1,'2026-10-14 01:31:18'),(30,'PAY-SAL-202610-7632-5',26,2,1,10000.00,'Bank Transfer','2026-10-13',2,60,NULL,NULL,NULL,'Period Payroll: 2026-10',1,'2026-10-14 01:31:18'),(31,'PAY-SAL-202610-7632-6',26,2,1,10000.00,'Cheque','2026-10-13',2,60,NULL,'2026-10-13',NULL,'Period Payroll: 2026-10',1,'2026-10-14 01:31:18'),(32,'PAY-SAL-202610-7632-7',26,2,1,15000.00,'Cheque','2026-10-13',1,59,NULL,'2026-10-13',NULL,'Period Payroll: 2026-10',1,'2026-10-14 01:31:18');
/*!40000 ALTER TABLE `payroll_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_periods`
--

DROP TABLE IF EXISTS `payroll_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_periods` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `period_code` varchar(20) NOT NULL,
  `period_name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `branch_id` int(10) unsigned DEFAULT NULL,
  `status` enum('draft','approved','completed','cancelled') NOT NULL DEFAULT 'draft',
  `total_basic` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_allowances` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_commissions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_gross` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_employee_epf` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_employer_epf` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_employer_etf` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_net_salary` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_by` int(10) unsigned NOT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pp_period_code` (`period_code`),
  KEY `idx_pp_status` (`status`),
  KEY `idx_pp_branch` (`branch_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_periods`
--

LOCK TABLES `payroll_periods` WRITE;
/*!40000 ALTER TABLE `payroll_periods` DISABLE KEYS */;
INSERT INTO `payroll_periods` VALUES (12,'2026-10','October 2026','2026-10-01','2026-10-31',1,'completed',110000.00,0.00,1000.00,111000.00,8880.00,13320.00,3330.00,11000.00,100000.00,100000.00,1,1,'2026-10-14 01:10:23','','2026-10-13 20:12:20','2026-10-14 01:31:18'),(29,'2026-101','October 2026','2026-10-01','2026-10-31',1,'approved',110000.00,0.00,0.00,110000.00,8800.00,13200.00,3300.00,8800.00,101200.00,0.00,1,1,'2026-10-14 01:36:52','','2026-10-14 01:36:47','2026-10-14 01:36:52');
/*!40000 ALTER TABLE `payroll_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `permission_code` varchar(80) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_code` (`permission_code`)
) ENGINE=InnoDB AUTO_INCREMENT=94 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard.view','dashboard','View main dashboard','2026-10-01 18:51:44'),(2,'branches.view','branches','View branch list and details','2026-10-01 18:51:44'),(3,'branches.manage','branches','Create and edit branches','2026-10-01 18:51:44'),(4,'users.view','users','View user accounts','2026-10-01 18:51:44'),(5,'users.manage','users','Create and manage user accounts and branch assignments','2026-10-01 18:51:44'),(6,'settings.view','settings','View system settings','2026-10-01 18:51:44'),(7,'settings.manage','settings','Update system configuration','2026-10-01 18:51:44'),(8,'audit.view','audit','View system audit logs','2026-10-01 18:51:44'),(9,'products.view','products','View products list and details','2026-10-01 18:51:44'),(10,'products.create','products','Create new products','2026-10-01 18:51:44'),(11,'products.edit','products','Edit existing product details','2026-10-01 18:51:44'),(12,'products.status','products','Activate or deactivate products','2026-10-01 18:51:44'),(13,'brands.manage','brands','Manage brands list and status','2026-10-01 18:51:44'),(14,'categories.manage','categories','Manage categories list and status','2026-10-01 18:51:44'),(15,'suppliers.manage','suppliers','Manage suppliers list and status','2026-10-01 18:51:44'),(16,'inventory.view','inventory','View branch inventory levels, lots and ledger history','2026-10-01 18:51:44'),(17,'inventory.manage','inventory','Manage opening stock and stock adjustments','2026-10-01 18:51:44'),(18,'inventory.lots','inventory','View product price and cost lots','2026-10-01 18:51:44'),(19,'po.view','purchasing','View Purchase Orders','2026-10-01 18:51:44'),(20,'po.create','purchasing','Create Purchase Orders','2026-10-01 18:51:44'),(21,'po.approve','purchasing','Approve and cancel Purchase Orders','2026-10-01 18:51:44'),(22,'grn.view','purchasing','View Goods Received Notes','2026-10-01 18:51:44'),(23,'grn.create','purchasing','Create Goods Received Notes','2026-10-01 18:51:44'),(24,'grn.approve','purchasing','Approve Goods Received Notes','2026-10-01 18:51:44'),(25,'customers.view','customers','View customer list, profile and credit history','2026-10-01 18:51:44'),(26,'customers.manage','customers','Create and edit customer accounts and barcode cards','2026-10-01 18:51:44'),(27,'customers.status','customers','Activate or deactivate customer accounts','2026-10-01 18:51:44'),(28,'pos.access','pos','Access POS billing screen','2026-10-01 18:51:44'),(29,'sales.create','pos','Create POS billing transactions','2026-10-01 18:51:44'),(30,'sales.hold','pos','Hold and resume POS invoices','2026-10-01 18:51:44'),(31,'sales.override_price','pos','Override product selling price manually','2026-10-01 18:51:44'),(32,'sales.discount','pos','Apply item and invoice level discounts','2026-10-01 18:51:44'),(33,'vouchers.view','vouchers','View gift vouchers list and redemption status','2026-10-01 18:51:44'),(34,'vouchers.manage','vouchers','Create and manage gift vouchers','2026-10-01 18:51:44'),(35,'vouchers.redeem','vouchers','Redeem gift vouchers during POS payment settlement','2026-10-01 18:51:44'),(36,'sales.reprint_receipt','pos','Reprint customer sales receipt','2026-10-01 18:51:44'),(37,'returns.view','returns','View sales returns and history','2026-10-01 18:51:45'),(38,'returns.create','returns','Process sales returns, refunds and exchanges','2026-10-01 18:51:45'),(39,'returns.no_invoice','returns','Process returns without original invoice','2026-10-01 18:51:45'),(40,'customers.pay','customers','Process customer credit outstanding collections','2026-10-01 18:51:45'),(41,'suppliers.view_payments','purchasing','View supplier outstanding balances and payment history','2026-10-01 18:51:45'),(42,'suppliers.pay','purchasing','Process payments to suppliers against approved GRNs','2026-10-01 18:51:45'),(43,'accounting.view','accounting','View Chart of Accounts, Journal Entries, and General Ledger','2026-10-01 18:51:44'),(44,'accounts.manage','accounting','Create and manage Chart of Accounts','2026-10-01 18:51:44'),(45,'accounting.post_manual','accounting','Create manual double-entry journal postings','2026-10-01 18:51:44'),(46,'accounting.reverse','accounting','Post journal reversal entries','2026-10-01 18:51:44'),(47,'accounting.backfill','accounting','Execute historical accounting backfill migration','2026-10-01 18:51:44'),(48,'start_business_day','cash_drawer','Start / Open new business day for branch','2026-10-01 18:51:44'),(49,'manual_cash_movement','cash_drawer','Perform manual drawer cash in and cash out','2026-10-01 18:51:44'),(50,'close_business_day','cash_drawer','Perform day closing and cash reconciliation','2026-10-01 18:51:44'),(51,'view_closed_days','cash_drawer','View closed business days log history','2026-10-01 18:51:44'),(52,'view_cash_drawer','cash_drawer','View active cash drawer status and movement history','2026-10-01 18:51:44'),(53,'view_petty_cash','petty_cash','View petty cash dashboard, balance and transactions','2026-10-01 18:51:44'),(54,'create_expense','petty_cash','Create petty cash expense vouchers','2026-10-01 18:51:44'),(55,'manage_expense_categories','petty_cash','Create and edit expense categories & COA mapping','2026-10-01 18:51:44'),(56,'reimburse_petty_cash','petty_cash','Reimburse / top-up petty cash fund from bank or drawer','2026-10-01 18:51:44'),(57,'adjust_petty_cash','petty_cash','Perform manual petty cash adjustments with accounting','2026-10-01 18:51:44'),(58,'reverse_petty_cash_voucher','petty_cash','Cancel / reverse petty cash expense vouchers','2026-10-01 18:51:44'),(59,'view_bank_accounts','banking','View company bank accounts and balances','2026-10-01 18:51:44'),(60,'manage_bank_accounts','banking','Create, edit and manage company bank accounts and COA mapping','2026-10-01 18:51:44'),(61,'make_bank_deposit','banking','Deposit cash into company bank account','2026-10-01 18:51:44'),(62,'view_bank_register','banking','View bank transaction activity register and running balances','2026-10-01 18:51:44'),(63,'manage_bank_reconciliation','banking','Create, edit and complete bank reconciliation sessions','2026-10-01 18:51:44'),(64,'post_bank_adjustment','banking','Post bank charges, interest and direct adjustments','2026-10-01 18:51:44'),(65,'view_stock_transfers','transfers','View branch stock transfer requests and history','2026-10-01 18:51:44'),(66,'create_stock_transfer','transfers','Create and send branch stock transfers','2026-10-01 18:51:44'),(67,'receive_stock_transfer','transfers','Receive and confirm branch stock transfers','2026-10-01 18:51:44'),(68,'manage_stock_adjustments','inventory','Post manual stock adjustments (increase and decrease)','2026-10-01 18:51:44'),(69,'manage_damaged_stock','inventory','Move stock to damaged or wastage and record disposal','2026-10-01 18:51:44'),(70,'manage_stock_audits','inventory','Create, count, review and post physical stock audits','2026-10-01 18:51:44'),(71,'view_stock_ledger','inventory','View full append-only stock movement ledger history','2026-10-01 18:51:44'),(72,'view_supplier_returns','supplier_returns','View supplier purchase returns and credit history','2026-10-01 18:51:44'),(73,'create_supplier_return','supplier_returns','Create supplier purchase returns against GRN','2026-10-01 18:51:44'),(74,'manage_supplier_credits','supplier_returns','Settle supplier credit via refund or future GRN offset','2026-10-01 18:51:44'),(75,'view_cheques','cheques','View supplier cheques management register','2026-10-01 18:51:44'),(76,'clear_cheque','cheques','Confirm supplier cheque clearing to bank','2026-10-01 18:51:44'),(77,'bounce_cheque','cheques','Mark supplier cheque as bounced/returned and restore AP','2026-10-01 18:51:44'),(78,'cancel_cheque','cheques','Cancel pending supplier cheque and reverse allocation','2026-10-01 18:51:44'),(79,'view_reports','reports','View central reports and analytics viewer','2026-10-01 18:51:45'),(80,'export_reports','reports','Export reports to Excel/CSV, PDF, and print format','2026-10-01 18:51:45'),(81,'view_financial_statements','reports','View financial statements (Trial Balance, P&L, Balance Sheet)','2026-10-01 18:51:45'),(82,'view_card_settlements','banking','View Card Settlements & Unsettled Card Reports','2026-10-01 18:51:47'),(83,'create_card_settlement','banking','Create and Edit Card Settlement Drafts','2026-10-01 18:51:47'),(84,'post_card_settlement','banking','Post Card Settlements to Bank & General Ledger','2026-10-01 18:51:47'),(85,'reverse_card_settlement','banking','Reverse Posted Card Settlements','2026-10-01 18:51:47'),(86,'manage_card_processors','banking','Manage Card Processor / Acquirer Masters','2026-10-01 18:51:47'),(87,'employees.view','employees','View Employees Directory','2026-10-11 23:40:25'),(88,'employees.manage','employees','Create & Edit Employees','2026-10-11 23:40:25'),(89,'payroll.view','payroll','View Payroll Ledgers & Periods','2026-10-11 23:40:25'),(90,'payroll.create','payroll','Create & Draft Payroll Periods','2026-10-11 23:40:25'),(91,'payroll.approve','payroll','Approve & Post Payroll Journals','2026-10-11 23:40:25'),(92,'payroll.pay','payroll','Disburse Salaries & Advances','2026-10-11 23:40:25'),(93,'payroll.remit','payroll','Remit Statutory EPF & ETF','2026-10-11 23:40:25');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `petty_cash_adjustments`
--

DROP TABLE IF EXISTS `petty_cash_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `petty_cash_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_no` varchar(50) NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `adjustment_type` enum('positive','negative') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` text NOT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `adjustment_no` (`adjustment_no`),
  KEY `created_by` (`created_by`),
  KEY `idx_pca_branch` (`branch_id`),
  CONSTRAINT `petty_cash_adjustments_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `petty_cash_adjustments_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `petty_cash_adjustments`
--

LOCK TABLES `petty_cash_adjustments` WRITE;
/*!40000 ALTER TABLE `petty_cash_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `petty_cash_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `petty_cash_reimbursements`
--

DROP TABLE IF EXISTS `petty_cash_reimbursements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `petty_cash_reimbursements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reimbursement_no` varchar(50) NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `reimbursement_date` date NOT NULL,
  `source_type` enum('bank','cash_in_hand') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `reimbursement_no` (`reimbursement_no`),
  KEY `created_by` (`created_by`),
  KEY `idx_pcr_branch_date` (`branch_id`,`reimbursement_date`),
  KEY `bank_account_id` (`bank_account_id`),
  CONSTRAINT `petty_cash_reimbursements_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `petty_cash_reimbursements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `petty_cash_reimbursements_ibfk_3` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `petty_cash_reimbursements`
--

LOCK TABLES `petty_cash_reimbursements` WRITE;
/*!40000 ALTER TABLE `petty_cash_reimbursements` DISABLE KEYS */;
/*!40000 ALTER TABLE `petty_cash_reimbursements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `petty_cash_vouchers`
--

DROP TABLE IF EXISTS `petty_cash_vouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `petty_cash_vouchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_no` varchar(50) NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `voucher_date` date NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `account_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payee` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `receipt_ref` varchar(100) DEFAULT NULL,
  `status` enum('posted','reversed') NOT NULL DEFAULT 'posted',
  `reversed_at` datetime DEFAULT NULL,
  `reversed_by` int(10) unsigned DEFAULT NULL,
  `reversal_reason` text DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `reversal_journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_no` (`voucher_no`),
  KEY `category_id` (`category_id`),
  KEY `account_id` (`account_id`),
  KEY `created_by` (`created_by`),
  KEY `reversed_by` (`reversed_by`),
  KEY `idx_pcv_branch_date` (`branch_id`,`voucher_date`),
  KEY `idx_pcv_status` (`status`),
  CONSTRAINT `petty_cash_vouchers_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `petty_cash_vouchers_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `expense_categories` (`id`),
  CONSTRAINT `petty_cash_vouchers_ibfk_3` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`),
  CONSTRAINT `petty_cash_vouchers_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `petty_cash_vouchers_ibfk_5` FOREIGN KEY (`reversed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `petty_cash_vouchers`
--

LOCK TABLES `petty_cash_vouchers` WRITE;
/*!40000 ALTER TABLE `petty_cash_vouchers` DISABLE KEYS */;
/*!40000 ALTER TABLE `petty_cash_vouchers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_import_batches`
--

DROP TABLE IF EXISTS `product_import_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_import_batches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `batch_no` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `import_date` datetime NOT NULL,
  `import_mode` varchar(50) NOT NULL,
  `total_rows` int(10) unsigned NOT NULL DEFAULT 0,
  `created_count` int(10) unsigned NOT NULL DEFAULT 0,
  `updated_count` int(10) unsigned NOT NULL DEFAULT 0,
  `skipped_count` int(10) unsigned NOT NULL DEFAULT 0,
  `failed_count` int(10) unsigned NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `batch_no` (`batch_no`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `product_import_batches_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_import_batches`
--

LOCK TABLES `product_import_batches` WRITE;
/*!40000 ALTER TABLE `product_import_batches` DISABLE KEYS */;
INSERT INTO `product_import_batches` VALUES (2,'IMP-2026OCTMON-133610-292','product_import.csv',1,'2026-10-12 17:06:10','create_and_update',2,2,0,0,0,'Import executed in create_and_update mode.','2026-10-12 17:06:10');
/*!40000 ALTER TABLE `product_import_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_lots`
--

DROP TABLE IF EXISTS `product_lots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_lots` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lot_number` varchar(50) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `initial_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remaining_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `expiry_date` date DEFAULT NULL,
  `source_type` enum('opening_stock','grn','transfer','adjustment') NOT NULL DEFAULT 'grn',
  `source_id` int(10) unsigned DEFAULT NULL,
  `status` enum('active','depleted','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lot_product` (`product_id`),
  KEY `idx_lot_branch` (`branch_id`),
  KEY `idx_lot_status` (`status`),
  KEY `idx_lots_expiry` (`expiry_date`),
  KEY `idx_lots_batch` (`batch_number`),
  KEY `idx_lots_source` (`source_type`,`source_id`),
  CONSTRAINT `fk_lot_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lot_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_lots`
--

LOCK TABLES `product_lots` WRITE;
/*!40000 ALTER TABLE `product_lots` DISABLE KEYS */;
INSERT INTO `product_lots` VALUES (12,'LOT-20261012-5363',NULL,12,1,1000.00,2000.00,15.00,1956.00,NULL,'opening_stock',12,'active','2026-10-12 11:56:50','2026-10-14 12:36:51'),(13,'LOT-20261012-1721',NULL,13,1,1500.00,2000.00,20.00,4.00,NULL,'opening_stock',13,'active','2026-10-12 11:58:19','2026-10-14 16:40:29'),(18,'LOT-20261012-1052',NULL,18,1,100.00,200.00,20.00,16.00,'2026-10-20','opening_stock',18,'active','2026-10-12 12:49:04','2026-10-14 16:39:59'),(31,'LOT-GRN8-13-482',NULL,13,1,1500.00,2500.00,10.00,8.00,NULL,'grn',8,'active','2026-10-12 14:32:28','2026-10-14 12:31:21'),(32,'LOT-GRN7-13-886',NULL,13,1,1000.00,2000.00,10.00,10.00,NULL,'grn',7,'active','2026-10-12 14:32:29','2026-10-12 14:32:29'),(50,'LOT-GRN17-12-587',NULL,12,1,50.00,75.00,200.00,199.00,NULL,'grn',17,'active','2026-10-12 17:06:10','2026-10-12 23:02:17'),(65,'LOT-GRN10-18-504',NULL,18,1,100.00,250.00,20.00,20.00,'2026-10-22','grn',10,'active','2026-10-12 17:40:17','2026-10-12 17:40:17'),(74,'LOT-20261012-1721-TRF2',NULL,13,2,1500.00,2000.00,5.00,5.00,NULL,'transfer',8,'active','2026-10-14 12:40:38','2026-10-14 12:40:38'),(75,'LOT-GRN8-13-482-TRF2',NULL,13,2,1500.00,2500.00,2.00,1.00,NULL,'transfer',8,'active','2026-10-14 12:40:38','2026-10-14 19:10:14'),(76,'LOT-TEST-1791973478',NULL,22,1,1000.00,1500.00,10.00,8.00,NULL,'grn',NULL,'active','2026-10-14 15:54:38','2026-10-14 15:54:38'),(77,'LOT-TEST-1791973570',NULL,23,1,1000.00,1500.00,10.00,9.00,NULL,'grn',NULL,'active','2026-10-14 15:56:10','2026-10-14 15:56:10'),(78,'LOT-TEST-1791973666',NULL,24,1,1000.00,1500.00,10.00,9.00,NULL,'grn',NULL,'active','2026-10-14 15:57:46','2026-10-14 15:57:46'),(91,'LOT-GRN21-30-799',NULL,30,1,26.00,42.00,50.00,50.00,NULL,'grn',21,'active','2026-10-15 14:29:55','2026-10-15 14:29:55'),(93,'LOT-20261015-2690',NULL,32,1,100.00,150.00,0.00,0.00,NULL,'opening_stock',32,'depleted','2026-10-15 14:30:12','2026-10-15 14:30:12'),(94,'LOT-20261015-5565',NULL,33,1,200.00,300.00,0.00,0.00,NULL,'opening_stock',33,'depleted','2026-10-15 14:30:12','2026-10-15 14:30:12'),(109,'LOT-GRN32-12-156',NULL,12,1,26.00,42.00,200.00,200.00,NULL,'grn',32,'active','2026-10-15 14:54:50','2026-10-15 14:56:41');
/*!40000 ALTER TABLE `product_lots` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `barcode` varchar(50) NOT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `brand_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  `name` varchar(200) NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `opening_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `low_stock_threshold` decimal(10,2) NOT NULL DEFAULT 10.00,
  `has_expiry` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_prod_brand` (`brand_id`),
  KEY `fk_prod_cat` (`category_id`),
  KEY `fk_prod_supplier` (`supplier_id`),
  KEY `idx_prod_barcode` (`barcode`),
  KEY `idx_prod_name` (`name`),
  KEY `idx_prod_status` (`status`),
  KEY `idx_prod_has_expiry` (`has_expiry`),
  KEY `idx_prod_sku` (`sku`),
  KEY `idx_prod_deleted_at` (`deleted_at`),
  KEY `idx_prod_del_status` (`deleted_at`,`status`),
  KEY `idx_prod_del_brand` (`deleted_at`,`brand_id`),
  KEY `idx_prod_del_cat` (`deleted_at`,`category_id`),
  KEY `idx_prod_del_supp` (`deleted_at`,`supplier_id`),
  CONSTRAINT `fk_prod_brand` FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`),
  CONSTRAINT `fk_prod_cat` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `fk_prod_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (12,'1010','10100',7,7,'Asus Gaming Laptop Tuff',9,750.00,1100.00,15.00,10.00,0,'active','2026-10-12 11:56:50','2026-10-15 15:36:05',NULL),(13,'1020','10200',7,7,'Asus Gaming Laptop ROG',9,1000.00,2000.00,10.00,10.00,0,'active','2026-10-12 11:58:19','2026-10-12 14:32:29',NULL),(18,'1030','10300',12,12,'Baby Cheramy Cream 10 Ml',12,100.00,250.00,20.00,10.00,1,'active','2026-10-12 12:49:04','2026-10-12 17:40:17',NULL),(22,'TEST-RET-PROD-1791973478',NULL,7,7,'Return Test Laptop',9,0.00,0.00,0.00,10.00,0,'active','2026-10-14 15:54:38','2026-10-14 15:54:38',NULL),(23,'TEST-RET-PROD-1791973570',NULL,7,7,'Return Test Laptop',9,0.00,0.00,0.00,10.00,0,'active','2026-10-14 15:56:10','2026-10-14 15:56:10',NULL),(24,'TEST-RET-PROD-1791973666',NULL,7,7,'Return Test Laptop',9,0.00,0.00,0.00,10.00,0,'active','2026-10-14 15:57:46','2026-10-14 15:57:46',NULL),(30,'OPTBAR_1792053892','OPTSKU_1792053892',22,22,'OptProduct Special Alpha',15,26.00,42.00,20.00,10.00,0,'active','2026-10-15 14:14:52','2026-10-15 14:29:55',NULL),(32,'BAR-A-1792054812_417','TEST-SKU-A-1792054812_417',7,7,'GRN Test Standard Product 1792054812_417',9,140.00,160.00,0.00,5.00,0,'active','2026-10-15 14:30:12','2026-10-15 14:30:12',NULL),(33,'BAR-B-1792054812_417','TEST-SKU-B-1792054812_417',7,7,'GRN Test Expiry Product 1792054812_417',9,200.00,320.00,0.00,5.00,1,'active','2026-10-15 14:30:12','2026-10-15 14:30:12',NULL),(38,'BAR-A-1792056307_972','TEST-SKU-A-1792056307_972',7,7,'GRN Test Standard Product 1792056307_972',9,140.00,160.00,0.00,5.00,0,'active','2026-10-15 14:55:07','2026-10-15 14:55:07',NULL),(39,'BAR-B-1792056307_972','TEST-SKU-B-1792056307_972',7,7,'GRN Test Expiry Product 1792056307_972',9,200.00,320.00,0.00,5.00,1,'active','2026-10-15 14:55:07','2026-10-15 14:55:07',NULL);
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_order_items`
--

DROP TABLE IF EXISTS `purchase_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `po_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `received_quantity` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_poi_po` (`po_id`),
  KEY `idx_poi_product` (`product_id`),
  CONSTRAINT `fk_poi_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_poi_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_order_items`
--

LOCK TABLES `purchase_order_items` WRITE;
/*!40000 ALTER TABLE `purchase_order_items` DISABLE KEYS */;
INSERT INTO `purchase_order_items` VALUES (15,1,12,10.00,0.00,1250.00,1800.00,12500.00),(17,3,30,50.00,50.00,25.00,40.00,1250.00),(18,4,30,50.00,0.00,25.00,40.00,1250.00),(21,7,30,50.00,50.00,25.00,40.00,1250.00),(22,8,30,50.00,0.00,25.00,40.00,1250.00),(24,10,30,50.00,0.00,25.00,40.00,1250.00),(26,12,30,50.00,50.00,25.00,40.00,1250.00),(27,13,30,50.00,0.00,25.00,40.00,1250.00),(29,15,32,15.00,15.00,140.00,0.00,2100.00),(32,18,33,50.00,0.00,25.00,40.00,1250.00),(33,19,33,50.00,0.00,25.00,40.00,1250.00),(35,21,33,50.00,0.00,25.00,40.00,1250.00),(36,22,33,50.00,0.00,25.00,40.00,1250.00),(37,23,33,50.00,0.00,25.00,40.00,1250.00),(38,24,12,50.00,50.00,25.00,40.00,1250.00),(39,25,12,50.00,0.00,25.00,40.00,1250.00),(41,27,12,50.00,50.00,25.00,40.00,1250.00),(42,28,12,50.00,0.00,25.00,40.00,1250.00),(44,30,12,50.00,50.00,25.00,40.00,1250.00),(45,31,12,50.00,0.00,25.00,40.00,1250.00),(46,32,12,50.00,50.00,25.00,40.00,1250.00),(47,33,12,50.00,0.00,25.00,40.00,1250.00);
/*!40000 ALTER TABLE `purchase_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `purchase_orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `po_number` varchar(50) NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `order_date` date NOT NULL,
  `expected_date` date DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` enum('Draft','Approved','Cancelled','Completed') NOT NULL DEFAULT 'Draft',
  `created_by` int(10) unsigned NOT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `po_number` (`po_number`),
  KEY `fk_po_created_by` (`created_by`),
  KEY `fk_po_approved_by` (`approved_by`),
  KEY `idx_po_supplier` (`supplier_id`),
  KEY `idx_po_branch` (`branch_id`),
  KEY `idx_po_status` (`status`),
  KEY `idx_po_deleted_at` (`deleted_at`),
  KEY `idx_po_br_del_id` (`branch_id`,`deleted_at`,`id`),
  KEY `idx_po_br_del_status` (`branch_id`,`deleted_at`,`status`),
  KEY `idx_po_br_del_supp` (`branch_id`,`deleted_at`,`supplier_id`),
  CONSTRAINT `fk_po_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_po_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `fk_po_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_po_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_orders`
--

LOCK TABLES `purchase_orders` WRITE;
/*!40000 ALTER TABLE `purchase_orders` DISABLE KEYS */;
INSERT INTO `purchase_orders` VALUES (1,'PO-202610-0001',9,1,'2026-10-15','2026-10-22',12500.00,500.00,12000.00,'CLI automated testing order with unique keywords XYZ99','Approved',1,1,'2026-10-15 14:14:27','2026-10-15 14:14:27','2026-10-15 14:14:27',NULL),(3,'PO-202610-0002',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'Stage 4 Automated Test PO','Completed',1,1,'2026-10-15 14:14:58','2026-10-15 14:14:58','2026-10-15 14:14:58',NULL),(4,'PO-202610-0004',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'PO to cancel','Cancelled',1,NULL,NULL,'2026-10-15 14:14:58','2026-10-15 14:14:58',NULL),(7,'PO-202610-0005',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'Stage 4 Automated Test PO','Completed',1,1,'2026-10-15 14:28:09','2026-10-15 14:28:09','2026-10-15 14:28:09',NULL),(8,'PO-202610-0008',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'PO to cancel','Cancelled',1,NULL,NULL,'2026-10-15 14:28:09','2026-10-15 14:28:09',NULL),(10,'PO-202610-0010',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'PO to cancel','Cancelled',1,NULL,NULL,'2026-10-15 14:29:04','2026-10-15 14:29:04',NULL),(12,'PO-202610-0011',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'Stage 4 Automated Test PO','Completed',1,1,'2026-10-15 14:29:55','2026-10-15 14:29:55','2026-10-15 14:29:55',NULL),(13,'PO-202610-0013',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'PO to cancel','Cancelled',1,NULL,NULL,'2026-10-15 14:29:55','2026-10-15 14:29:55',NULL),(15,'PO-202610-0014',9,1,'2026-10-15',NULL,2100.00,0.00,2100.00,'Test PO for GRN receiving','Completed',1,1,'2026-10-15 14:30:12','2026-10-15 14:30:12','2026-10-15 14:30:12',NULL),(18,'PO-202610-0016',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'Stage 4 Automated Test PO','Approved',1,1,'2026-10-15 14:52:30','2026-10-15 14:52:30','2026-10-15 14:52:30',NULL),(19,'PO-202610-0019',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'PO to cancel','Cancelled',1,NULL,NULL,'2026-10-15 14:52:30','2026-10-15 14:52:30',NULL),(21,'PO-202610-0020',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'Stage 4 Automated Test PO','Approved',1,1,'2026-10-15 14:53:41','2026-10-15 14:53:41','2026-10-15 14:53:41',NULL),(22,'PO-202610-0022',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'PO to cancel','Cancelled',1,NULL,NULL,'2026-10-15 14:53:41','2026-10-15 14:53:41',NULL),(23,'PO-202610-0023',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'Stage 4 Automated Test PO','Draft',1,NULL,NULL,'2026-10-15 14:53:58','2026-10-15 14:53:58',NULL),(24,'PO-202610-0024',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'Stage 4 Automated Test PO','Completed',1,1,'2026-10-15 14:54:50','2026-10-15 14:54:50','2026-10-15 14:54:50',NULL),(25,'PO-202610-0025',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'PO to cancel','Cancelled',1,NULL,NULL,'2026-10-15 14:54:50','2026-10-15 14:54:50',NULL),(27,'PO-202610-0026',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'Stage 4 Automated Test PO','Completed',1,1,'2026-10-15 14:55:07','2026-10-15 14:55:07','2026-10-15 14:55:07',NULL),(28,'PO-202610-0028',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'PO to cancel','Cancelled',1,NULL,NULL,'2026-10-15 14:55:07','2026-10-15 14:55:07',NULL),(30,'PO-202610-0029',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'Stage 4 Automated Test PO','Completed',1,1,'2026-10-15 14:55:43','2026-10-15 14:55:43','2026-10-15 14:55:43',NULL),(31,'PO-202610-0031',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'PO to cancel','Cancelled',1,NULL,NULL,'2026-10-15 14:55:43','2026-10-15 14:55:43',NULL),(32,'PO-202610-0032',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'Stage 4 Automated Test PO','Completed',1,1,'2026-10-15 14:56:41','2026-10-15 14:56:41','2026-10-15 14:56:41',NULL),(33,'PO-202610-0033',12,1,'2026-10-15','2026-10-20',1250.00,100.00,1150.00,'PO to cancel','Cancelled',1,NULL,NULL,'2026-10-15 14:56:41','2026-10-15 14:56:41',NULL);
/*!40000 ALTER TABLE `purchase_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `refunds`
--

DROP TABLE IF EXISTS `refunds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `refunds` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_return_id` bigint(20) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `refund_method` varchar(50) NOT NULL DEFAULT 'cash',
  `amount` decimal(12,2) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `replacement_voucher_id` int(10) DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `branch_id` (`branch_id`),
  KEY `customer_id` (`customer_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_refund_return` (`sales_return_id`),
  KEY `idx_refund_method` (`refund_method`),
  KEY `bank_account_id` (`bank_account_id`),
  CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `refunds_ibfk_3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `refunds_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `refunds_ibfk_5` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `refunds`
--

LOCK TABLES `refunds` WRITE;
/*!40000 ALTER TABLE `refunds` DISABLE KEYS */;
INSERT INTO `refunds` VALUES (6,12,1,NULL,16,'cash_drawer',2400.00,NULL,NULL,1,'2026-10-14 16:39:02');
/*!40000 ALTER TABLE `refunds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `role_id` int(10) unsigned NOT NULL,
  `permission_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  KEY `fk_rp_permission` (`permission_id`),
  CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,1),(1,2),(1,3),(1,4),(1,5),(1,6),(1,7),(1,8),(1,9),(1,10),(1,11),(1,12),(1,13),(1,14),(1,15),(1,16),(1,17),(1,18),(1,19),(1,20),(1,21),(1,22),(1,23),(1,24),(1,25),(1,26),(1,27),(1,28),(1,29),(1,30),(1,31),(1,32),(1,33),(1,34),(1,35),(1,36),(1,37),(1,38),(1,39),(1,40),(1,41),(1,42),(1,43),(1,44),(1,45),(1,46),(1,47),(1,48),(1,49),(1,50),(1,51),(1,52),(1,53),(1,54),(1,55),(1,56),(1,57),(1,58),(1,59),(1,60),(1,61),(1,62),(1,63),(1,64),(1,65),(1,66),(1,67),(1,68),(1,69),(1,70),(1,71),(1,72),(1,73),(1,74),(1,75),(1,76),(1,77),(1,78),(1,79),(1,80),(1,81),(1,82),(1,83),(1,84),(1,85),(1,86),(1,87),(1,88),(1,89),(1,90),(1,91),(1,92),(1,93),(2,1),(2,2),(2,3),(2,4),(2,5),(2,6),(2,7),(2,8),(2,9),(2,10),(2,11),(2,12),(2,13),(2,14),(2,15),(2,16),(2,17),(2,18),(2,19),(2,20),(2,21),(2,22),(2,23),(2,24),(2,25),(2,26),(2,27),(2,28),(2,29),(2,30),(2,31),(2,32),(2,33),(2,34),(2,35),(2,36),(2,37),(2,38),(2,39),(2,40),(2,41),(2,42),(2,43),(2,44),(2,45),(2,46),(2,47),(2,48),(2,49),(2,50),(2,51),(2,52),(2,53),(2,54),(2,55),(2,56),(2,57),(2,58),(2,59),(2,60),(2,61),(2,62),(2,63),(2,64),(2,65),(2,66),(2,67),(2,68),(2,69),(2,70),(2,71),(2,72),(2,73),(2,74),(2,75),(2,76),(2,77),(2,78),(2,79),(2,80),(2,81),(2,82),(2,83),(2,84),(2,85),(2,86),(2,87),(2,88),(2,89),(2,90),(2,91),(2,92),(2,93),(3,1),(3,2),(3,4),(3,6),(3,9),(3,10),(3,11),(3,12),(3,13),(3,14),(3,15),(3,16),(3,17),(3,18),(3,19),(3,20),(3,21),(3,22),(3,23),(3,24),(3,25),(3,26),(3,27),(3,28),(3,29),(3,30),(3,31),(3,32),(3,33),(3,34),(3,35),(3,36),(3,37),(3,38),(3,39),(3,40),(3,41),(3,42),(3,43),(3,44),(3,45),(3,46),(3,47),(3,48),(3,49),(3,50),(3,51),(3,52),(3,53),(3,54),(3,55),(3,56),(3,57),(3,58),(3,59),(3,60),(3,61),(3,62),(3,63),(3,64),(3,65),(3,66),(3,67),(3,68),(3,69),(3,70),(3,71),(3,72),(3,73),(3,74),(3,75),(3,76),(3,77),(3,78),(3,79),(3,80),(3,81),(3,82),(3,83),(3,84),(3,85),(3,86),(3,87),(3,88),(3,89),(3,90),(3,91),(3,92),(3,93),(4,1),(4,9),(4,16),(4,25),(4,26),(4,28),(4,29),(4,30),(4,31),(4,32),(4,33),(4,35),(4,36),(4,37),(4,38),(4,39),(4,40),(4,48),(4,49),(4,50),(4,51),(4,52),(4,53),(4,54),(4,59),(4,61),(4,65),(4,67),(4,71),(4,87),(4,88),(4,89),(4,90),(4,91),(4,92),(4,93),(5,1),(5,6),(5,8),(5,9),(5,16),(5,18),(5,19),(5,22),(5,25),(5,26),(5,28),(5,29),(5,30),(5,33),(5,36),(5,40),(5,41),(5,42),(5,43),(5,44),(5,45),(5,46),(5,47),(5,48),(5,49),(5,50),(5,51),(5,52),(5,53),(5,54),(5,55),(5,56),(5,57),(5,58),(5,59),(5,60),(5,61),(5,62),(5,63),(5,64),(5,65),(5,66),(5,67),(5,68),(5,69),(5,70),(5,71),(5,72),(5,73),(5,74),(5,75),(5,76),(5,77),(5,78),(5,79),(5,80),(5,81),(5,82),(5,83),(5,84),(5,85),(5,86),(6,1),(6,9),(6,16),(6,19),(6,22),(6,25),(6,79),(6,81);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(30) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','super_admin','System owner & developer account with unrestricted global access','2026-10-01 18:51:44','2026-10-01 18:51:44'),(2,'Admin','admin','Shop owner with multi-branch management capabilities','2026-10-01 18:51:44','2026-10-01 18:51:44'),(3,'Manager','manager','Branch manager with branch-level operational control','2026-10-01 18:51:44','2026-10-01 18:51:44'),(4,'Cashier','cashier','POS billing operator with terminal transaction access','2026-10-01 18:51:44','2026-10-01 18:51:44'),(5,'Accountant','accountant','Financial and ledger record manager','2026-10-01 18:51:44','2026-10-01 18:51:44'),(6,'Special User','special_user','Restricted view user with configurable visibility percentages','2026-10-01 18:51:44','2026-10-01 18:51:44');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sale_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sale_id` bigint(20) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `lot_id` int(10) unsigned NOT NULL,
  `quantity` decimal(12,2) NOT NULL DEFAULT 1.00,
  `original_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_price` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(20) DEFAULT NULL,
  `discount_value` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `line_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_si_sale` (`sale_id`),
  KEY `idx_si_product` (`product_id`),
  KEY `idx_si_lot` (`lot_id`),
  CONSTRAINT `fk_si_lot` FOREIGN KEY (`lot_id`) REFERENCES `product_lots` (`id`),
  CONSTRAINT `fk_si_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_si_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
INSERT INTO `sale_items` VALUES (1,1,13,13,1.00,2000.00,2000.00,1500.00,'none','',0.00,2000.00,1500.00),(2,1,18,18,1.00,200.00,200.00,100.00,'none','',0.00,200.00,100.00),(5,2,13,13,4.00,2000.00,2000.00,1500.00,'none','',0.00,8000.00,6000.00),(6,2,18,18,5.00,200.00,200.00,100.00,'none','',0.00,1000.00,500.00),(7,3,12,12,1.00,3000.00,3000.00,1000.00,'none','',0.00,3000.00,1000.00),(10,6,13,31,1.00,2500.00,2500.00,1500.00,'none','',0.00,2500.00,1500.00),(11,7,12,50,1.00,75.00,75.00,50.00,'none','',0.00,75.00,50.00),(12,8,13,13,1.00,2000.00,2000.00,1500.00,'none','',0.00,2000.00,1500.00),(13,9,12,12,1.00,2000.00,2000.00,1000.00,'none','',0.00,2000.00,1000.00),(14,10,13,13,1.00,2000.00,2000.00,1500.00,'none','',0.00,2000.00,1500.00),(15,11,12,12,1.00,2000.00,2000.00,1000.00,'none','',0.00,2000.00,1000.00),(17,13,12,12,1.00,2000.00,2000.00,1000.00,'none','',0.00,2000.00,1000.00),(19,32,22,76,2.00,1500.00,1500.00,1000.00,NULL,NULL,0.00,3000.00,2000.00),(20,33,23,77,2.00,1500.00,1500.00,1000.00,NULL,NULL,0.00,3000.00,2000.00),(21,34,24,78,2.00,1500.00,1500.00,1000.00,NULL,NULL,0.00,3000.00,2000.00);
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(50) NOT NULL,
  `offline_uuid` varchar(64) DEFAULT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned NOT NULL,
  `sales_rep_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(20) DEFAULT NULL,
  `discount_value` varchar(50) DEFAULT NULL,
  `discount_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `roundup_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','held','completed','cancelled') NOT NULL DEFAULT 'draft',
  `held_at` datetime DEFAULT NULL,
  `held_notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  UNIQUE KEY `offline_uuid` (`offline_uuid`),
  KEY `fk_sales_user` (`user_id`),
  KEY `idx_sales_invoice` (`invoice_no`),
  KEY `idx_sales_branch` (`branch_id`),
  KEY `idx_sales_customer` (`customer_id`),
  KEY `idx_sales_status` (`status`),
  KEY `idx_sales_created` (`created_at`),
  KEY `idx_sales_rep_id` (`sales_rep_id`),
  CONSTRAINT `fk_sales_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `fk_sales_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `fk_sales_rep` FOREIGN KEY (`sales_rep_id`) REFERENCES `employees` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES (1,'0001',NULL,1,1,NULL,1,2200.00,'none','',0.00,0.00,2200.00,1600.00,'draft',NULL,NULL,'2026-10-12 19:32:10','2026-10-12 19:32:10'),(2,'0002',NULL,1,16,2,1,9000.00,'none','',0.00,0.00,9000.00,6500.00,'completed',NULL,NULL,'2026-10-12 19:37:09','2026-10-12 19:41:09'),(3,'0003',NULL,1,1,NULL,1,3000.00,'none','',0.00,0.00,3000.00,1000.00,'draft',NULL,NULL,'2026-10-12 20:26:52','2026-10-12 20:26:52'),(6,'0004',NULL,1,22,2,1,2500.00,'none','',0.00,0.00,2500.00,1500.00,'draft',NULL,NULL,'2026-10-12 22:09:58','2026-10-12 22:09:58'),(7,'0005',NULL,1,1,2,1,75.00,'roundup','11%+0.25',8.00,0.25,67.00,50.00,'completed',NULL,NULL,'2026-10-12 23:02:09','2026-10-12 23:02:17'),(8,'0006',NULL,1,1,2,1,2000.00,'none','',0.00,0.00,2000.00,1500.00,'draft',NULL,NULL,'2026-10-13 11:14:08','2026-10-13 11:14:08'),(9,'0007',NULL,1,23,2,1,2000.00,'none','',0.00,0.00,2000.00,1000.00,'draft',NULL,NULL,'2026-10-13 11:28:12','2026-10-13 11:28:12'),(10,'0008',NULL,1,23,2,1,2000.00,'none','',0.00,0.00,2000.00,1500.00,'draft',NULL,NULL,'2026-10-13 11:32:51','2026-10-13 11:32:51'),(11,'0009',NULL,1,15,2,1,2000.00,'none','',0.00,0.00,2000.00,1000.00,'draft',NULL,NULL,'2026-10-13 12:35:43','2026-10-13 12:35:43'),(13,'0010',NULL,1,1,2,1,2000.00,'none','',0.00,0.00,2000.00,1000.00,'draft',NULL,NULL,'2026-10-13 13:18:04','2026-10-13 13:18:04'),(27,'INV-S7-001',NULL,1,40,NULL,1,0.00,NULL,NULL,0.00,0.00,6000.00,0.00,'completed',NULL,NULL,'2026-10-14 13:21:18','2026-10-14 13:21:18'),(28,'INV-S7-002',NULL,1,40,NULL,1,0.00,NULL,NULL,0.00,0.00,9000.00,0.00,'completed',NULL,NULL,'2026-10-14 13:21:18','2026-10-14 13:21:18'),(32,'INV-RET-1791973478',NULL,1,49,NULL,1,3000.00,NULL,NULL,0.00,0.00,3000.00,2000.00,'completed',NULL,NULL,'2026-10-14 15:54:38','2026-10-14 15:54:38'),(33,'INV-RET-1791973570',NULL,1,50,NULL,1,3000.00,NULL,NULL,0.00,0.00,3000.00,2000.00,'completed',NULL,NULL,'2026-10-14 15:56:10','2026-10-14 15:56:10'),(34,'INV-RET-1791973666',NULL,1,51,NULL,1,3000.00,NULL,NULL,0.00,0.00,3000.00,2000.00,'completed',NULL,NULL,'2026-10-14 15:57:46','2026-10-14 15:57:46');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_return_items`
--

DROP TABLE IF EXISTS `sales_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sales_return_id` bigint(20) unsigned NOT NULL,
  `sale_item_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `lot_id` int(10) unsigned NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(12,2) NOT NULL,
  `cost_price` decimal(12,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `item_condition` enum('good','damaged') NOT NULL DEFAULT 'good',
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `lot_id` (`lot_id`),
  KEY `idx_sri_return` (`sales_return_id`),
  KEY `idx_sri_sale_item` (`sale_item_id`),
  CONSTRAINT `sales_return_items_ibfk_1` FOREIGN KEY (`sales_return_id`) REFERENCES `sales_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_return_items_ibfk_2` FOREIGN KEY (`sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_return_items_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `sales_return_items_ibfk_4` FOREIGN KEY (`lot_id`) REFERENCES `product_lots` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_return_items`
--

LOCK TABLES `sales_return_items` WRITE;
/*!40000 ALTER TABLE `sales_return_items` DISABLE KEYS */;
INSERT INTO `sales_return_items` VALUES (11,12,5,13,13,1.00,2000.00,1500.00,2000.00,'good','Customer Return'),(12,12,6,18,18,2.00,200.00,100.00,400.00,'damaged','Customer Return'),(13,13,5,13,13,1.00,2000.00,1500.00,2000.00,'good','Customer Return'),(14,13,6,18,18,1.00,200.00,100.00,200.00,'good','Customer Return'),(15,14,5,13,13,1.00,2000.00,1500.00,2000.00,'good','Customer Return'),(16,14,6,18,18,1.00,200.00,100.00,200.00,'damaged','Customer Return');
/*!40000 ALTER TABLE `sales_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales_returns`
--

DROP TABLE IF EXISTS `sales_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sales_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_no` varchar(50) NOT NULL,
  `sale_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `customer_id` int(10) unsigned DEFAULT NULL,
  `return_type` enum('return_only','exchange') NOT NULL DEFAULT 'return_only',
  `total_return_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_exchange_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `net_difference` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `exchange_sale_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_no` (`return_no`),
  KEY `exchange_sale_id` (`exchange_sale_id`),
  KEY `customer_id` (`customer_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_return_no` (`return_no`),
  KEY `idx_return_sale` (`sale_id`),
  KEY `idx_return_branch` (`branch_id`),
  CONSTRAINT `sales_returns_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_returns_ibfk_2` FOREIGN KEY (`exchange_sale_id`) REFERENCES `sales` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sales_returns_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `sales_returns_ibfk_4` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  CONSTRAINT `sales_returns_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales_returns`
--

LOCK TABLES `sales_returns` WRITE;
/*!40000 ALTER TABLE `sales_returns` DISABLE KEYS */;
INSERT INTO `sales_returns` VALUES (12,'RET-0001',2,1,16,'return_only',2400.00,0.00,-2400.00,'completed',NULL,'Invoice Return',1,'2026-10-14 16:39:02'),(13,'RET-0013',2,1,16,'return_only',2200.00,0.00,-2200.00,'completed',NULL,'POS Exchange Return (No cash payout)',1,'2026-10-14 16:39:59'),(14,'RET-0014',2,1,16,'return_only',2200.00,0.00,-2200.00,'completed',NULL,'POS Exchange Return (No cash payout)',1,'2026-10-14 16:40:29');
/*!40000 ALTER TABLE `sales_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_adjustments`
--

DROP TABLE IF EXISTS `stock_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `adjustment_number` varchar(50) NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `lot_id` int(10) unsigned NOT NULL,
  `adjustment_type` enum('increase','decrease') NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `cost` decimal(12,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `adjustment_number` (`adjustment_number`),
  KEY `lot_id` (`lot_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_sa_branch` (`branch_id`),
  KEY `idx_sa_product` (`product_id`),
  KEY `idx_sa_created_at` (`created_at`),
  CONSTRAINT `stock_adjustments_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `stock_adjustments_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `stock_adjustments_ibfk_3` FOREIGN KEY (`lot_id`) REFERENCES `product_lots` (`id`),
  CONSTRAINT `stock_adjustments_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_adjustments`
--

LOCK TABLES `stock_adjustments` WRITE;
/*!40000 ALTER TABLE `stock_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_audit_items`
--

DROP TABLE IF EXISTS `stock_audit_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_audit_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `audit_id` bigint(20) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `lot_id` int(10) unsigned NOT NULL,
  `expected_quantity` decimal(12,2) NOT NULL,
  `counted_quantity` decimal(12,2) NOT NULL,
  `variance_quantity` decimal(12,2) NOT NULL,
  `cost` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lot_id` (`lot_id`),
  KEY `idx_sai_audit` (`audit_id`),
  KEY `idx_sai_product` (`product_id`),
  CONSTRAINT `stock_audit_items_ibfk_1` FOREIGN KEY (`audit_id`) REFERENCES `stock_audits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_audit_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `stock_audit_items_ibfk_3` FOREIGN KEY (`lot_id`) REFERENCES `product_lots` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_audit_items`
--

LOCK TABLES `stock_audit_items` WRITE;
/*!40000 ALTER TABLE `stock_audit_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_audit_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_audits`
--

DROP TABLE IF EXISTS `stock_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_audits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `audit_number` varchar(50) NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `audit_date` date NOT NULL,
  `status` enum('draft','counting','review','posted') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `posted_by` int(10) unsigned DEFAULT NULL,
  `posted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_number` (`audit_number`),
  KEY `created_by` (`created_by`),
  KEY `posted_by` (`posted_by`),
  KEY `idx_aud_branch` (`branch_id`),
  KEY `idx_aud_status` (`status`),
  CONSTRAINT `stock_audits_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `stock_audits_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `stock_audits_ibfk_3` FOREIGN KEY (`posted_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_audits`
--

LOCK TABLES `stock_audits` WRITE;
/*!40000 ALTER TABLE `stock_audits` DISABLE KEYS */;
/*!40000 ALTER TABLE `stock_audits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_ledger`
--

DROP TABLE IF EXISTS `stock_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `lot_id` int(10) unsigned DEFAULT NULL,
  `movement_type` enum('opening_stock','purchase_grn','sale','customer_return','supplier_return','transfer_in','transfer_out','adjustment','damage_wastage') NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `balance_after` decimal(12,2) NOT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_sl_lot` (`lot_id`),
  KEY `idx_sl_branch` (`branch_id`),
  KEY `idx_sl_product` (`product_id`),
  KEY `idx_sl_movement` (`movement_type`),
  CONSTRAINT `fk_sl_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `fk_sl_lot` FOREIGN KEY (`lot_id`) REFERENCES `product_lots` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_sl_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_ledger`
--

LOCK TABLES `stock_ledger` WRITE;
/*!40000 ALTER TABLE `stock_ledger` DISABLE KEYS */;
INSERT INTO `stock_ledger` VALUES (12,1,12,12,'opening_stock',15.00,15.00,'INIT-STOCK-BR1','Initial opening stock lot',1,'2026-10-12 11:56:50'),(13,1,13,13,'opening_stock',10.00,10.00,'INIT-STOCK-BR1','Initial opening stock lot',1,'2026-10-12 11:58:19'),(18,1,18,18,'opening_stock',20.00,20.00,'INIT-STOCK-BR1','Initial opening stock lot',1,'2026-10-12 12:49:04'),(30,1,13,13,'purchase_grn',10.00,20.00,'GRN-202610-0009','GRN Purchase (Invoice: 2026)',1,'2026-10-12 14:29:38'),(31,1,13,31,'purchase_grn',10.00,30.00,'GRN-202610-0008','GRN Purchase (Invoice: 2029)',1,'2026-10-12 14:32:28'),(32,1,13,32,'purchase_grn',10.00,40.00,'GRN-202610-0001','GRN Purchase (Invoice: 2029)',1,'2026-10-12 14:32:29'),(41,1,12,12,'sale',-1.00,14.00,'0003','POS Sale Finalization Invoice #0003',4,'2026-10-12 17:06:07'),(42,1,12,12,'sale',-10.00,4.00,'0004','POS Sale Finalization Invoice #0004',1,'2026-10-12 17:06:07'),(56,1,13,13,'sale',-2.00,38.00,'0007','Offline POS Sale Sync Invoice #0007',1,'2026-10-12 17:06:09'),(57,1,13,13,'sale',-2.00,36.00,'0008','Offline POS Sale Sync Invoice #0008',1,'2026-10-12 17:06:09'),(59,1,13,13,'sale',-2.00,32.00,'0010','Offline POS Sale Sync Invoice #0010',1,'2026-10-12 17:06:09'),(60,1,13,13,'sale',-2.00,30.00,'0011','Offline POS Sale Sync Invoice #0011',1,'2026-10-12 17:06:09'),(61,1,13,13,'sale',-1.00,29.00,'0012','Offline POS Sale Sync Invoice #0012',1,'2026-10-12 17:06:10'),(65,1,12,50,'purchase_grn',70.00,74.00,'GRN-202610-0017','GRN Purchase (Invoice: INV-GRN-101)',1,'2026-10-12 17:06:10'),(66,1,12,50,'purchase_grn',50.00,124.00,'GRN-202610-0019','GRN Purchase (Invoice: INV-GRN-103)',1,'2026-10-12 17:06:10'),(67,1,12,50,'purchase_grn',80.00,204.00,'GRN-202610-0020','GRN Purchase (Invoice: INV-VAR-999)',1,'2026-10-12 17:06:10'),(87,1,12,12,'sale',-100.00,2100.00,'0029','POS Sale Finalization Invoice #0029',1,'2026-10-12 17:06:14'),(88,1,12,12,'sale',-100.00,2000.00,'0030','POS Sale Finalization Invoice #0030',1,'2026-10-12 17:06:14'),(89,1,12,12,'sale',-10.00,1990.00,'0031','POS Sale Finalization Invoice #0031',1,'2026-10-12 17:06:14'),(90,1,12,12,'sale',-10.00,1980.00,'0032','POS Sale Finalization Invoice #0032',1,'2026-10-12 17:06:14'),(91,1,12,12,'sale',-100.00,1880.00,'0033','POS Sale Finalization Invoice #0033',1,'2026-10-12 17:06:14'),(92,1,12,12,'sale',-10.00,1870.00,'0034','POS Sale Finalization Invoice #0034',1,'2026-10-12 17:06:14'),(93,1,12,12,'sale',-100.00,1770.00,'0035','POS Sale Finalization Invoice #0035',1,'2026-10-12 17:06:14'),(94,1,12,12,'customer_return',5.00,1775.00,'RET-0001','Good condition sales return stock restoration',1,'2026-10-12 17:06:14'),(95,1,12,12,'sale',-10.00,1765.00,'0036','POS Sale Finalization Invoice #0036',1,'2026-10-12 17:06:14'),(96,1,12,12,'sale',-10.00,1755.00,'0037','POS Sale Finalization Invoice #0037',1,'2026-10-12 17:06:14'),(97,2,12,NULL,'sale',-10.00,990.00,'0038','POS Sale Finalization Invoice #0038',1,'2026-10-12 17:06:14'),(98,1,12,12,'sale',-5.00,2195.00,'0039','POS Sale Finalization Invoice #0039',1,'2026-10-12 17:06:14'),(102,1,12,12,'sale',-2.00,2193.00,'0040','POS Sale Finalization Invoice #0040',1,'2026-10-12 17:06:15'),(104,1,12,12,'sale',-10.00,2193.00,'0041','POS Sale Finalization Invoice #0041',1,'2026-10-12 17:06:15'),(105,1,12,12,'sale',-1.00,2192.00,'0042','POS Sale Finalization Invoice #0042',1,'2026-10-12 17:06:15'),(106,1,12,12,'sale',-1.00,2191.00,'0043','POS Sale Finalization Invoice #0043',1,'2026-10-12 17:06:15'),(107,1,12,12,'sale',-5.00,2186.00,'0044','POS Sale Finalization Invoice #0044',1,'2026-10-12 17:06:15'),(108,1,12,12,'sale',-20.00,2166.00,'0045','POS Sale Finalization Invoice #0045',1,'2026-10-12 17:06:15'),(114,1,18,65,'purchase_grn',20.00,40.00,'GRN-202610-0010','GRN Purchase (Invoice: 1456)',1,'2026-10-12 17:40:17'),(117,1,13,13,'sale',-4.00,5.00,'0002','POS Sale Finalization Invoice #0002',1,'2026-10-12 19:41:09'),(118,1,18,18,'sale',-5.00,35.00,'0002','POS Sale Finalization Invoice #0002',1,'2026-10-12 19:41:09'),(119,1,12,50,'sale',-1.00,2165.00,'0005','POS Sale Finalization Invoice #0005',1,'2026-10-12 23:02:17'),(133,1,13,13,'transfer_out',-5.00,20.00,'TRF-0001','Stock Transfer Sent to Branch #2',1,'2026-10-14 12:31:21'),(134,1,13,31,'transfer_out',-2.00,18.00,'TRF-0001','Stock Transfer Sent to Branch #2',1,'2026-10-14 12:31:21'),(137,2,13,74,'transfer_in',5.00,5.00,'TRF-0001','Stock Transfer Received from Branch #1',1,'2026-10-14 12:40:38'),(138,2,13,75,'transfer_in',2.00,7.00,'TRF-0001','Stock Transfer Received from Branch #1',1,'2026-10-14 12:40:38'),(139,1,23,77,'customer_return',1.00,9.00,'RET-0001','Good condition sales return stock restoration',1,'2026-10-14 15:56:10'),(140,1,24,78,'customer_return',1.00,9.00,'RET-0004','Good condition sales return stock restoration',1,'2026-10-14 15:57:46'),(145,1,13,13,'customer_return',1.00,19.00,'RET-0001','Good condition sales return stock restoration',1,'2026-10-14 16:34:38'),(148,1,13,13,'customer_return',1.00,20.00,'RET-0001','Good condition sales return stock restoration',1,'2026-10-14 16:39:02'),(149,1,18,18,'damage_wastage',0.00,35.00,'RET-0001','Damaged item return logged to damaged stock log (Qty: 2)',1,'2026-10-14 16:39:02'),(150,1,13,13,'customer_return',1.00,21.00,'RET-0013','Good condition sales return stock restoration',1,'2026-10-14 16:39:59'),(151,1,18,18,'customer_return',1.00,36.00,'RET-0013','Good condition sales return stock restoration',1,'2026-10-14 16:39:59'),(152,1,13,13,'customer_return',1.00,22.00,'RET-0014','Good condition sales return stock restoration',1,'2026-10-14 16:40:29'),(153,1,18,18,'damage_wastage',0.00,36.00,'RET-0014','Damaged item return logged to damaged stock log (Qty: 1)',1,'2026-10-14 16:40:29'),(154,2,13,75,'transfer_out',-1.00,6.00,'TRF-0009','Stock Transfer Sent to Branch #1',1,'2026-10-14 19:10:14'),(158,1,30,NULL,'purchase_grn',50.00,50.00,'GRN-202610-0011','GRN Purchase (Invoice: TEST-INV-8899)',1,'2026-10-15 14:14:58'),(159,1,12,NULL,'purchase_grn',20.00,2174.50,'GRN-202610-0012','GRN Purchase (Invoice: INV-GRN-UNIQUE-1792054512) (Batch: BATCH-GRN-99)',1,'2026-10-15 14:25:12'),(160,1,12,NULL,'purchase_grn',20.00,2194.50,'GRN-202610-0013','GRN Purchase (Invoice: INV-GRN-UNIQUE-1792054606) (Batch: BATCH-GRN-99)',1,'2026-10-15 14:26:46'),(161,1,12,NULL,'purchase_grn',20.00,2214.50,'GRN-202610-0014','GRN Purchase (Invoice: INV-GRN-UNIQUE-1792054643) (Batch: BATCH-GRN-99)',1,'2026-10-15 14:27:23'),(164,1,30,NULL,'purchase_grn',50.00,100.00,'GRN-202610-0016','GRN Purchase (Invoice: TEST-INV-8899)',1,'2026-10-15 14:28:09'),(168,1,30,91,'purchase_grn',50.00,200.00,'GRN-202610-0018','GRN Purchase (Invoice: TEST-INV-8899)',1,'2026-10-15 14:29:55'),(170,1,32,93,'opening_stock',0.00,0.00,'INIT-STOCK-BR1','Initial opening stock lot',1,'2026-10-15 14:30:12'),(171,1,33,94,'opening_stock',0.00,0.00,'INIT-STOCK-BR1','Initial opening stock lot',1,'2026-10-15 14:30:12'),(186,1,12,109,'purchase_grn',50.00,2264.50,'GRN-202610-0025','GRN Purchase (Invoice: TEST-INV-8899)',1,'2026-10-15 14:54:50'),(193,1,12,109,'purchase_grn',50.00,2314.50,'GRN-202610-0033','GRN Purchase (Invoice: TEST-INV-8899)',1,'2026-10-15 14:55:07'),(194,1,12,109,'purchase_grn',50.00,2364.50,'GRN-202610-0038','GRN Purchase (Invoice: TEST-INV-8899)',1,'2026-10-15 14:55:43'),(195,1,12,109,'purchase_grn',50.00,2414.50,'GRN-202610-0039','GRN Purchase (Invoice: TEST-INV-8899)',1,'2026-10-15 14:56:41'),(203,1,42,122,'supplier_return',-2.00,4.00,'SRET-0006','Supplier Return SRET-0006 for supplier AutoTest Supplier Returns',1,'2026-10-15 15:26:28');
/*!40000 ALTER TABLE `stock_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transfer_items`
--

DROP TABLE IF EXISTS `stock_transfer_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transfer_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_id` bigint(20) unsigned NOT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `source_lot_id` int(10) unsigned NOT NULL,
  `dest_lot_id` int(10) unsigned DEFAULT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `cost` decimal(12,2) NOT NULL,
  `selling_price` decimal(12,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `source_lot_id` (`source_lot_id`),
  KEY `dest_lot_id` (`dest_lot_id`),
  KEY `idx_sti_transfer` (`transfer_id`),
  KEY `idx_sti_product` (`product_id`),
  CONSTRAINT `stock_transfer_items_ibfk_1` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `stock_transfer_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `stock_transfer_items_ibfk_3` FOREIGN KEY (`source_lot_id`) REFERENCES `product_lots` (`id`),
  CONSTRAINT `stock_transfer_items_ibfk_4` FOREIGN KEY (`dest_lot_id`) REFERENCES `product_lots` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transfer_items`
--

LOCK TABLES `stock_transfer_items` WRITE;
/*!40000 ALTER TABLE `stock_transfer_items` DISABLE KEYS */;
INSERT INTO `stock_transfer_items` VALUES (15,8,13,13,74,5.00,1500.00,2000.00),(16,8,13,31,75,2.00,1500.00,2500.00),(19,10,13,75,NULL,1.00,1500.00,2500.00);
/*!40000 ALTER TABLE `stock_transfer_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stock_transfers`
--

DROP TABLE IF EXISTS `stock_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `stock_transfers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `transfer_number` varchar(50) NOT NULL,
  `from_branch_id` int(10) unsigned NOT NULL,
  `to_branch_id` int(10) unsigned NOT NULL,
  `transfer_date` date NOT NULL,
  `status` enum('draft','sent','received','cancelled') NOT NULL DEFAULT 'draft',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `sent_by` int(10) unsigned DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `received_by` int(10) unsigned DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `cancelled_by` int(10) unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transfer_number` (`transfer_number`),
  KEY `created_by` (`created_by`),
  KEY `sent_by` (`sent_by`),
  KEY `received_by` (`received_by`),
  KEY `cancelled_by` (`cancelled_by`),
  KEY `idx_st_from` (`from_branch_id`),
  KEY `idx_st_to` (`to_branch_id`),
  KEY `idx_st_status` (`status`),
  CONSTRAINT `stock_transfers_ibfk_1` FOREIGN KEY (`from_branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `stock_transfers_ibfk_2` FOREIGN KEY (`to_branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `stock_transfers_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `stock_transfers_ibfk_4` FOREIGN KEY (`sent_by`) REFERENCES `users` (`id`),
  CONSTRAINT `stock_transfers_ibfk_5` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`),
  CONSTRAINT `stock_transfers_ibfk_6` FOREIGN KEY (`cancelled_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stock_transfers`
--

LOCK TABLES `stock_transfers` WRITE;
/*!40000 ALTER TABLE `stock_transfers` DISABLE KEYS */;
INSERT INTO `stock_transfers` VALUES (8,'TRF-0001',1,2,'2026-10-14','received',NULL,1,1,'2026-10-14 12:31:21',1,'2026-10-14 12:40:38',NULL,NULL,'2026-10-14 12:31:21'),(10,'TRF-0009',2,1,'2026-10-14','sent',NULL,1,1,'2026-10-14 19:10:14',NULL,NULL,NULL,NULL,'2026-10-14 19:10:14');
/*!40000 ALTER TABLE `stock_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_cheques`
--

DROP TABLE IF EXISTS `supplier_cheques`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_cheques` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_payment_id` bigint(20) unsigned DEFAULT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `cheque_number` varchar(100) NOT NULL,
  `cheque_date` date NOT NULL,
  `bank_name` varchar(100) NOT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','cleared','bounced','cancelled') NOT NULL DEFAULT 'pending',
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `cleared_at` datetime DEFAULT NULL,
  `cleared_by` int(10) unsigned DEFAULT NULL,
  `bounced_at` datetime DEFAULT NULL,
  `bounced_by` int(10) unsigned DEFAULT NULL,
  `bounced_reason` varchar(255) DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `cancelled_by` int(10) unsigned DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supplier_payment_id` (`supplier_payment_id`),
  KEY `branch_id` (`branch_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_cheque_supplier` (`supplier_id`),
  KEY `idx_cheque_status` (`status`),
  KEY `bank_account_id` (`bank_account_id`),
  CONSTRAINT `supplier_cheques_ibfk_1` FOREIGN KEY (`supplier_payment_id`) REFERENCES `supplier_payments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_cheques_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `supplier_cheques_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `supplier_cheques_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `supplier_cheques_ibfk_5` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_cheques`
--

LOCK TABLES `supplier_cheques` WRITE;
/*!40000 ALTER TABLE `supplier_cheques` DISABLE KEYS */;
INSERT INTO `supplier_cheques` VALUES (1,3,16,1,'CHQ-S15-8065','2026-10-12','Sampath Bank',2,300.00,'cleared',1,'2026-10-12 17:06:09','2026-10-12 17:06:09',1,NULL,NULL,NULL,NULL,NULL,NULL,65),(2,4,16,1,'CHQ-S15-2258','2026-10-12','HNB',NULL,100.00,'bounced',1,'2026-10-12 17:06:09',NULL,NULL,'2026-10-12 17:06:09',1,'Signature mismatch',NULL,NULL,NULL,NULL),(3,5,16,1,'CHQ-S15-5040','2026-10-12','BOC',NULL,50.00,'cancelled',1,'2026-10-12 17:06:09',NULL,NULL,NULL,NULL,NULL,'2026-10-12 17:06:09',1,'Entry error',NULL),(4,10,21,1,'S8-CHQ-9001','2026-10-20','Commercial Bank',1,3000.00,'bounced',1,'2026-10-12 17:06:11','2026-10-12 17:06:11',1,'2026-10-12 17:06:11',1,'Cheque bounced after clearance test',NULL,NULL,NULL,92),(5,11,21,1,'S8-CHQ-9002','2026-10-25','Commercial Bank',NULL,2000.00,'cancelled',1,'2026-10-12 17:06:11',NULL,NULL,NULL,NULL,NULL,'2026-10-12 17:06:11',1,'Duplicate issuance',NULL);
/*!40000 ALTER TABLE `supplier_cheques` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_credit_settlements`
--

DROP TABLE IF EXISTS `supplier_credit_settlements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_credit_settlements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `settlement_number` varchar(50) NOT NULL,
  `supplier_credit_id` bigint(20) unsigned NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `settlement_date` date NOT NULL,
  `settlement_type` enum('bank_refund','cash_refund','future_grn_offset') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `target_grn_id` int(10) unsigned DEFAULT NULL,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `settlement_number` (`settlement_number`),
  KEY `bank_account_id` (`bank_account_id`),
  KEY `target_grn_id` (`target_grn_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_scs_credit` (`supplier_credit_id`),
  KEY `idx_scs_supplier` (`supplier_id`),
  KEY `idx_scs_branch_credit` (`branch_id`,`supplier_credit_id`,`id`),
  CONSTRAINT `supplier_credit_settlements_ibfk_1` FOREIGN KEY (`supplier_credit_id`) REFERENCES `supplier_credits` (`id`),
  CONSTRAINT `supplier_credit_settlements_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `supplier_credit_settlements_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `supplier_credit_settlements_ibfk_4` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`),
  CONSTRAINT `supplier_credit_settlements_ibfk_5` FOREIGN KEY (`target_grn_id`) REFERENCES `grns` (`id`),
  CONSTRAINT `supplier_credit_settlements_ibfk_6` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_credit_settlements`
--

LOCK TABLES `supplier_credit_settlements` WRITE;
/*!40000 ALTER TABLE `supplier_credit_settlements` DISABLE KEYS */;
INSERT INTO `supplier_credit_settlements` VALUES (1,'SSET-0001',1,16,1,'2026-10-12','cash_refund',200.00,NULL,NULL,NULL,'Cash refund settlement',59,1,'2026-10-12 17:06:09'),(2,'SSET-0002',1,16,1,'2026-10-12','bank_refund',200.00,2,NULL,NULL,'Bank refund settlement',61,1,'2026-10-12 17:06:09'),(3,'SSET-0003',1,16,1,'2026-10-12','future_grn_offset',200.00,NULL,15,NULL,'Future GRN offset settlement',63,1,'2026-10-12 17:06:09'),(4,'SSET-0004',2,21,1,'2026-10-12','cash_refund',1000.00,NULL,NULL,NULL,'Cash refund from supplier',99,1,'2026-10-12 17:06:11'),(5,'SSET-0005',2,21,1,'2026-10-12','bank_refund',1000.00,1,NULL,NULL,'Bank refund into HNB account',100,1,'2026-10-12 17:06:11'),(6,'SSET-0006',2,21,1,'2026-10-12','future_grn_offset',1000.00,NULL,23,NULL,'Offset remaining credit against GRN #2',101,1,'2026-10-12 17:06:11');
/*!40000 ALTER TABLE `supplier_credit_settlements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_credits`
--

DROP TABLE IF EXISTS `supplier_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_credits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `credit_number` varchar(50) NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `supplier_return_id` bigint(20) unsigned DEFAULT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `credit_date` date NOT NULL,
  `total_credit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remaining_credit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','fully_settled','void') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_number` (`credit_number`),
  KEY `supplier_return_id` (`supplier_return_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_scrd_supplier` (`supplier_id`),
  KEY `idx_scrd_status` (`status`),
  KEY `idx_scrd_supp_status` (`supplier_id`,`status`),
  KEY `idx_scrd_branch_status` (`branch_id`,`status`,`id`),
  CONSTRAINT `supplier_credits_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `supplier_credits_ibfk_2` FOREIGN KEY (`supplier_return_id`) REFERENCES `supplier_returns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `supplier_credits_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `supplier_credits_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_credits`
--

LOCK TABLES `supplier_credits` WRITE;
/*!40000 ALTER TABLE `supplier_credits` DISABLE KEYS */;
INSERT INTO `supplier_credits` VALUES (1,'SCRD-0001',16,3,1,'2026-10-12',600.00,0.00,'fully_settled','Over-return credit from Return SRET-0003',1,'2026-10-12 17:06:09'),(2,'SCRD-0002',21,4,1,'2026-10-12',3000.00,0.00,'fully_settled','Over-return credit from Return SRET-0004',1,'2026-10-12 17:06:11');
/*!40000 ALTER TABLE `supplier_credits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_opening_balances`
--

DROP TABLE IF EXISTS `supplier_opening_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_opening_balances` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL DEFAULT 1,
  `entry_date` date NOT NULL,
  `balance_type` enum('owe_supplier','supplier_owes_us') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('posted','reversed') NOT NULL DEFAULT 'posted',
  `reversed_by` int(10) unsigned DEFAULT NULL,
  `reversed_at` datetime DEFAULT NULL,
  `reversal_notes` text DEFAULT NULL,
  `reversal_journal_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `supplier_id` (`supplier_id`),
  KEY `branch_id` (`branch_id`),
  KEY `status` (`status`),
  KEY `idx_sob_supp_status` (`supplier_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_opening_balances`
--

LOCK TABLES `supplier_opening_balances` WRITE;
/*!40000 ALTER TABLE `supplier_opening_balances` DISABLE KEYS */;
INSERT INTO `supplier_opening_balances` VALUES (2,18,1,'2026-10-15','owe_supplier',5000.00,0.00,'REV-1','Testing rev',337,'reversed',1,'2026-10-15 14:51:25','Testing reversal reason',338,1,'2026-10-15 14:51:25','2026-10-15 14:51:25');
/*!40000 ALTER TABLE `supplier_opening_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_payment_grns`
--

DROP TABLE IF EXISTS `supplier_payment_grns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_payment_grns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_payment_id` bigint(20) unsigned NOT NULL,
  `grn_id` int(10) unsigned NOT NULL,
  `allocated_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_spg_payment` (`supplier_payment_id`),
  KEY `idx_spg_grn` (`grn_id`),
  CONSTRAINT `supplier_payment_grns_ibfk_1` FOREIGN KEY (`supplier_payment_id`) REFERENCES `supplier_payments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_payment_grns_ibfk_2` FOREIGN KEY (`grn_id`) REFERENCES `grns` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_payment_grns`
--

LOCK TABLES `supplier_payment_grns` WRITE;
/*!40000 ALTER TABLE `supplier_payment_grns` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_payment_grns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_payments`
--

DROP TABLE IF EXISTS `supplier_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `payment_no` varchar(50) NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `bank_account_id` int(10) unsigned DEFAULT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash in Hand',
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference_no` varchar(100) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payment_no` (`payment_no`),
  KEY `created_by` (`created_by`),
  KEY `idx_sp_supplier` (`supplier_id`),
  KEY `idx_sp_branch` (`branch_id`),
  KEY `bank_account_id` (`bank_account_id`),
  CONSTRAINT `supplier_payments_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `supplier_payments_ibfk_2` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `supplier_payments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `supplier_payments_ibfk_4` FOREIGN KEY (`bank_account_id`) REFERENCES `bank_accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_payments`
--

LOCK TABLES `supplier_payments` WRITE;
/*!40000 ALTER TABLE `supplier_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_return_items`
--

DROP TABLE IF EXISTS `supplier_return_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_return_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_return_id` bigint(20) unsigned NOT NULL,
  `grn_item_id` bigint(20) unsigned DEFAULT NULL,
  `product_id` int(10) unsigned NOT NULL,
  `lot_id` int(10) unsigned NOT NULL,
  `quantity` decimal(12,2) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lot_id` (`lot_id`),
  KEY `idx_srti_return` (`supplier_return_id`),
  KEY `idx_srti_product` (`product_id`),
  KEY `idx_srti_grn_item` (`grn_item_id`),
  CONSTRAINT `supplier_return_items_ibfk_1` FOREIGN KEY (`supplier_return_id`) REFERENCES `supplier_returns` (`id`) ON DELETE CASCADE,
  CONSTRAINT `supplier_return_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `supplier_return_items_ibfk_3` FOREIGN KEY (`lot_id`) REFERENCES `product_lots` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_return_items`
--

LOCK TABLES `supplier_return_items` WRITE;
/*!40000 ALTER TABLE `supplier_return_items` DISABLE KEYS */;
INSERT INTO `supplier_return_items` VALUES (1,1,19,30,45,4.00,100.00,400.00,'Defective'),(2,2,19,30,45,6.00,100.00,600.00,'Remaining returned'),(3,3,21,30,46,3.00,200.00,600.00,'Return paid item'),(4,4,29,38,53,30.00,100.00,3000.00,'Defective batch');
/*!40000 ALTER TABLE `supplier_return_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supplier_returns`
--

DROP TABLE IF EXISTS `supplier_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplier_returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_number` varchar(50) NOT NULL,
  `supplier_id` int(10) unsigned NOT NULL,
  `grn_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `return_date` date NOT NULL,
  `total_return_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `applied_to_payable` decimal(12,2) NOT NULL DEFAULT 0.00,
  `credit_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `journal_entry_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_number` (`return_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_srtn_supplier` (`supplier_id`),
  KEY `idx_srtn_grn` (`grn_id`),
  KEY `idx_srtn_branch` (`branch_id`),
  KEY `idx_srtn_date` (`return_date`),
  KEY `idx_sret_branch_id` (`branch_id`,`id`),
  KEY `idx_sret_branch_supp` (`branch_id`,`supplier_id`,`id`),
  KEY `idx_sret_branch_date` (`branch_id`,`return_date`,`id`),
  CONSTRAINT `supplier_returns_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `supplier_returns_ibfk_2` FOREIGN KEY (`grn_id`) REFERENCES `grns` (`id`),
  CONSTRAINT `supplier_returns_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `supplier_returns_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supplier_returns`
--

LOCK TABLES `supplier_returns` WRITE;
/*!40000 ALTER TABLE `supplier_returns` DISABLE KEYS */;
/*!40000 ALTER TABLE `supplier_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `suppliers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `supplier_code` varchar(50) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bank_name` varchar(150) DEFAULT NULL,
  `bank_account_name` varchar(150) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `bank_branch` varchar(150) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_suppliers_name` (`name`),
  KEY `idx_suppliers_status` (`status`),
  KEY `idx_supplier_code` (`supplier_code`),
  KEY `idx_suppliers_del_name` (`deleted_at`,`name`),
  KEY `idx_suppliers_del_status` (`deleted_at`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (9,'SUP-0009','Chani Importers - Asus Agent',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-10-12 11:46:30','2026-10-12 11:46:30',NULL),(12,'SUP-0010','Ceylon Cream',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-10-12 11:58:50','2026-10-12 11:58:50',NULL),(15,'SUP-0013','OptSupp_1792053892','0779998888',NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-10-15 14:14:52','2026-10-15 14:14:52',NULL),(16,'SUP-0016','OptSupp_1792054808','0779998888',NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-10-15 14:30:08','2026-10-15 14:30:08',NULL),(18,'SUP-0017','Temp Rev Test 1792056085',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'active','2026-10-15 14:51:25','2026-10-15 14:51:25',NULL),(21,'SUP-0019','Updated Vendor 1792056401_940','0712223333','0779876543','New Address','Commercial Bank','Test Vendor Ent','8899001122','City Branch','active','2026-10-15 14:56:41','2026-10-15 14:56:41',NULL);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(80) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(10) unsigned DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'business_name','Falcon POS','general','Business brand name displayed across system UI and receipts',1,'2026-10-16 00:39:04'),(2,'business_phone','+94 81 222 3344','general','Main business contact hotline',1,'2026-10-08 22:04:16'),(3,'business_email','info@curtisspos.com','general','Main business email address',1,'2026-10-08 22:04:16'),(4,'currency_symbol','LKR','general','Currency symbol used for monetary displays',1,'2026-10-08 22:04:16'),(5,'session_inactivity_timeout','3600','security','Session inactivity timeout in seconds (1 hour / 3600 seconds)',1,'2026-10-08 22:04:16'),(6,'default_opening_cash','20000.00','pos','Default register opening cash float (LKR 20,000)',1,'2026-10-08 22:04:16'),(7,'low_stock_default','10','inventory','Global default threshold for low stock warnings (Default = 10)',1,'2026-10-01 18:51:44'),(8,'receipt_printer_width','88mm','pos','Default receipt width format',1,'2026-10-01 18:51:44'),(9,'special_user_visibility_pct','60.00','general','Default visibility percentage for Special User role',1,'2026-10-08 22:04:16'),(10,'special_user_display_percentage','60.00','security','Configured percentage for presentation-layer masking of sensitive financial & inventory values for Special User',1,'2026-10-12 17:06:13'),(13,'koko_enabled','1','payment_gateways',NULL,NULL,'2026-10-12 00:40:20'),(14,'koko_environment','sandbox','payment_gateways',NULL,1,'2026-10-12 00:55:39'),(15,'koko_merchant_id','KOKO_MID_SAMPLE','payment_gateways',NULL,1,'2026-10-12 00:55:39'),(16,'koko_api_key','koko_live_or_sandbox_key','payment_gateways',NULL,NULL,'2026-10-12 00:40:20'),(17,'koko_api_secret','koko_live_or_sandbox_secret','payment_gateways',NULL,NULL,'2026-10-12 00:40:20'),(18,'koko_sms_enabled','1','payment_gateways',NULL,NULL,'2026-10-12 00:40:20'),(19,'koko_merchant_name','Falcon POS','payment_gateways',NULL,NULL,'2026-10-16 00:39:04'),(20,'koko_currency','LKR','payment_gateways',NULL,NULL,'2026-10-12 00:40:20'),(21,'expiry_alert_days_soon','30','inventory','Expiry Tracking System Parameter',NULL,'2026-10-12 01:09:56'),(22,'expiry_block_sale_days','0','inventory','Expiry Tracking System Parameter',NULL,'2026-10-12 01:09:56'),(23,'expiry_fefo_enabled','1','inventory','Expiry Tracking System Parameter',NULL,'2026-10-12 01:09:56'),(24,'loyalty_discount_percentage','5.00','pos','Default bill discount percentage for Loyalty Customers',1,'2026-10-13 13:02:12'),(25,'label_printer_model','TSC TE244','general',NULL,1,'2026-10-14 02:15:42'),(26,'label_width_mm','25','general',NULL,1,'2026-10-14 02:15:42'),(27,'label_height_mm','40','general',NULL,1,'2026-10-14 02:15:42'),(28,'labels_per_row','2','general',NULL,1,'2026-10-14 02:15:42'),(29,'label_column_gap_mm','2','general',NULL,1,'2026-10-14 02:15:42'),(30,'label_row_gap_mm','2','general',NULL,1,'2026-10-14 02:15:42'),(31,'label_margin_top_mm','0','general',NULL,1,'2026-10-14 02:15:42'),(32,'label_margin_left_mm','0','general',NULL,1,'2026-10-14 02:15:42'),(33,'label_rotation_deg','0','general',NULL,1,'2026-10-14 02:15:42'),(34,'label_shop_name_custom','Falcon POS','general',NULL,1,'2026-10-16 00:39:04'),(35,'label_price_prefix','Rs.','general',NULL,1,'2026-10-14 02:15:42'),(36,'label_show_shop_name','1','general',NULL,1,'2026-10-14 02:15:42'),(37,'label_show_product_code','1','general',NULL,1,'2026-10-14 02:15:42'),(38,'label_show_price','1','general',NULL,1,'2026-10-14 02:15:42'),(39,'label_barcode_height_mm','12','general',NULL,1,'2026-10-14 02:15:42'),(40,'label_font_size_name_pt','8','general',NULL,1,'2026-10-14 02:15:42'),(41,'label_font_size_price_pt','11','general',NULL,1,'2026-10-14 02:15:42'),(42,'company_logo','','general',NULL,1,'2026-10-14 17:21:46');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_branches`
--

DROP TABLE IF EXISTS `user_branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_branches` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `branch_id` int(10) unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_branch` (`user_id`,`branch_id`),
  KEY `fk_ub_branch` (`branch_id`),
  CONSTRAINT `fk_ub_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_branches`
--

LOCK TABLES `user_branches` WRITE;
/*!40000 ALTER TABLE `user_branches` DISABLE KEYS */;
INSERT INTO `user_branches` VALUES (1,1,1,1,'2026-10-01 18:51:44'),(2,1,2,0,'2026-10-01 18:51:44'),(3,2,1,1,'2026-10-01 18:51:44'),(4,2,2,0,'2026-10-01 18:51:44'),(5,3,1,1,'2026-10-01 18:51:44'),(6,4,1,1,'2026-10-01 18:51:44'),(7,5,1,1,'2026-10-01 18:51:44'),(8,5,2,0,'2026-10-01 18:51:44'),(9,6,1,1,'2026-10-01 18:51:44'),(10,8,1,1,'2026-10-16 00:59:02');
/*!40000 ALTER TABLE `user_branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `pin` varchar(64) DEFAULT NULL,
  `pin_hash` varchar(255) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_status` (`status`),
  KEY `idx_users_deleted` (`deleted_at`),
  KEY `idx_users_pin` (`pin`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'superadmin','superadmin@curtisspos.com','$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6','Super Administrator','+94 77 000 0000',1,'active','2026-10-16 00:30:12','2026-10-01 18:51:44','2026-10-16 00:30:12',NULL),(2,'admin','admin@curtisspos.com','$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6','Shop Owner Admin','+94 77 111 2222',2,'active',NULL,'2026-10-01 18:51:44','2026-10-01 18:51:44',NULL),(3,'manager1','manager1@curtisspos.com','$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6','Kandy Manager','+94 77 222 3333',3,'active',NULL,'2026-10-01 18:51:44','2026-10-01 18:51:44',NULL),(4,'cashier1','cashier1@curtisspos.com','$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6','Kandy Cashier 01','+94 77 333 4444',4,'active',NULL,'2026-10-01 18:51:44','2026-10-01 18:51:44',NULL),(5,'accountant','accountant@curtisspos.com','$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6','Lead Accountant','+94 77 444 5555',5,'active',NULL,'2026-10-01 18:51:44','2026-10-01 18:51:44',NULL),(6,'specialuser','specialuser@curtisspos.com','$2y$10$zhgicxbe8El0OBpuZzOUtumUoDdURyr6MtNGcZYGmntYTkdUsoKl6','Auditor Special User','+94 77 555 6666',6,'active',NULL,'2026-10-01 18:51:44','2026-10-01 18:51:44',NULL),(7,'inact_test','','$2y$10$yLBKv9ZJTTwC7tf8jOEUh.lxOrQhLhPMkqmDldejOvgRpbyl92Opi','Inactive Test',NULL,4,'inactive',NULL,'2026-10-08 14:01:35','2026-10-08 14:01:35',NULL),(8,'susar.aa','susar.aa@falconpos.lk','$2y$10$VOX8D1Q6hSuX6c7bverNUeWRQhQPUo.bMPdCO.AKNOyMaDWtdtf06','Susara Senaratne','+94 77 123 4567',1,'active','2026-10-16 00:59:36','2026-10-16 00:59:02','2026-10-16 00:59:36',NULL);
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

-- Dump completed on 2026-10-16  0:59:49
