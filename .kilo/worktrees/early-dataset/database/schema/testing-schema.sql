-- ⚠️ NOT SSOT
-- This file is a snapshot for testing only.
-- Source of truth = live MySQL schema


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
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `causer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `advisor_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `advisor_photos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kisi_id` bigint unsigned NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `width` int NOT NULL,
  `height` int NOT NULL,
  `file_size` int NOT NULL,
  `quality_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `quality_metrics` json DEFAULT NULL,
  `analysis_details` json DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `featured` tinyint(1) NOT NULL DEFAULT '0',
  `improvement_suggestions` json DEFAULT NULL,
  `visual_keywords` json DEFAULT NULL,
  `analyzed_at` timestamp NULL DEFAULT NULL,
  `featured_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `advisor_photos_kisi_id_featured_index` (`kisi_id`,`featured`),
  KEY `advisor_photos_quality_score_index` (`quality_score`),
  KEY `advisor_photos_display_order_index` (`display_order`),
  KEY `advisor_photos_featured_index` (`featured`),
  KEY `advisor_photos_analyzed_at_index` (`analyzed_at`),
  CONSTRAINT `advisor_photos_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agent_memory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_memory` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `memory_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `memory_key` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `memory_value` json NOT NULL,
  `agent_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `agent_memory_agent_name_memory_key_unique` (`agent_name`,`memory_key`),
  KEY `agent_memory_memory_type_index` (`memory_type`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `agent_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `agent_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agent_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agent_durumu` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'running',
  `started_at` timestamp NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `duration_ms` int unsigned DEFAULT NULL,
  `input_summary` json DEFAULT NULL,
  `output_summary` json DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `findings_count` int unsigned NOT NULL DEFAULT '0',
  `decisions_count` int unsigned NOT NULL DEFAULT '0',
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `agent_runs_agent_name_agent_durumu_index` (`agent_name`,`agent_durumu`),
  KEY `agent_runs_started_at_index` (`started_at`),
  KEY `agent_runs_agent_name_index` (`agent_name`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_abuse_signals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_abuse_signals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `action_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `count_1h` int NOT NULL DEFAULT '0',
  `count_24h` int NOT NULL DEFAULT '0',
  `anomaly_score` double(8,2) NOT NULL DEFAULT '0.00',
  `detected_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `metadata` json DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_abuse_signals_user_id_index` (`user_id`),
  KEY `ai_abuse_signals_action_type_index` (`action_type`),
  KEY `ai_abuse_signals_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_call_analyses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_call_analyses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_activity_id` bigint unsigned NOT NULL,
  `audio_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ses dosyası yolu (S3/Local)',
  `transkript_metni` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Whisper çıktısı',
  `ozet_metni` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'LLM özeti',
  `duygu_skoru` tinyint unsigned DEFAULT NULL COMMENT '1-10 arası sentiment (1=Negatif, 10=Pozitif)',
  `anahtar_kelimeler` json DEFAULT NULL COMMENT 'Tespit edilen önemli terimler',
  `analiz_durumu` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0:Bekliyor, 1:Tamamlandı, 2:Hatalı',
  `maliyet_usd` decimal(8,4) NOT NULL DEFAULT '0.0000' COMMENT 'Analiz maliyeti',
  `hata_detayi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_call_analyses_lead_activity_id_foreign` (`lead_activity_id`),
  KEY `ai_call_analyses_analiz_durumu_index` (`analiz_durumu`),
  KEY `ai_call_analyses_duygu_skoru_index` (`duygu_skoru`),
  CONSTRAINT `ai_call_analyses_lead_activity_id_foreign` FOREIGN KEY (`lead_activity_id`) REFERENCES `lead_activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_category_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_category_analytics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int NOT NULL,
  `suggestion_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `confidence_score` decimal(3,2) NOT NULL,
  `ai_response` json NOT NULL,
  `user_accepted` tinyint(1) NOT NULL,
  `suggested_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_category_analytics_category_id_suggested_at_index` (`category_id`,`suggested_at`),
  KEY `ai_category_analytics_suggestion_type_confidence_score_index` (`suggestion_type`,`confidence_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_deneyler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_deneyler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `deney_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deney_slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hedef_kategori` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `varyasyonlar` json NOT NULL,
  `kazanan_varyasyon_anahtari` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baslangic_tarihi` timestamp NULL DEFAULT NULL,
  `bitis_tarihi` timestamp NULL DEFAULT NULL,
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_deneyler_deney_slug_unique` (`deney_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_esik_profilleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_esik_profilleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kategori_id` int unsigned DEFAULT NULL,
  `yayin_tipi_id` int unsigned DEFAULT NULL,
  `saglayici` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'openai | vertex | global',
  `min_ornek_sayisi` int unsigned NOT NULL DEFAULT '50',
  `auto_apply_esigi` decimal(4,3) NOT NULL COMMENT '0.850 format',
  `suggest_esigi` decimal(4,3) NOT NULL COMMENT '0.500 format',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_threshold_unique` (`kategori_id`,`yayin_tipi_id`,`saglayici`),
  KEY `ai_esik_profilleri_kategori_id_index` (`kategori_id`),
  KEY `ai_esik_profilleri_yayin_tipi_id_index` (`yayin_tipi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_feature_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_feature_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plan_id` bigint unsigned NOT NULL,
  `feature_slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_cost_credits` int unsigned NOT NULL,
  `is_dynamic` tinyint(1) NOT NULL DEFAULT '0',
  `multiplier` decimal(8,2) NOT NULL DEFAULT '1.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_feature_prices_plan_id_feature_slug_unique` (`plan_id`,`feature_slug`),
  KEY `ai_feature_prices_feature_slug_index` (`feature_slug`),
  CONSTRAINT `ai_feature_prices_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `ai_pricing_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_feature_usages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_feature_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `ilan_id` bigint unsigned DEFAULT NULL COMMENT 'İlan referansı',
  `kategori_id` bigint unsigned NOT NULL COMMENT 'Kategori ID',
  `yayin_tipi_id` bigint unsigned NOT NULL COMMENT 'Yayın Tipi ID',
  `feature_slug` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Özellik slug (ortak-havuz, balkon, vb.)',
  `confidence` decimal(5,2) NOT NULL COMMENT 'AI güven skoru (0.00-1.00)',
  `source_tipi` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'text | image | mixed',
  `provider` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aksiyon` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'auto_applied | user_applied | dismissed | skipped_ups_guard | api_error',
  `latency_ms` int DEFAULT NULL,
  `cache_hit` tinyint(1) NOT NULL DEFAULT '0',
  `neden` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Kullanıcıya görünen açıklama',
  `neden_detay` json DEFAULT NULL COMMENT 'Detaylı explainability (signals, factors)',
  `explainability_v2_json` json DEFAULT NULL,
  `istek_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Request correlation ID',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deney_id` bigint unsigned DEFAULT NULL,
  `deney_varyasyon_anahtari` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `etkilesim_suresi_ms` int DEFAULT NULL COMMENT 'Kullanıcının öneriye tepki verme süresi',
  `tahmini_tasarruf_sn` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Otomatik uygulama ile kazanılan saniye',
  `maliyet_usd` decimal(10,6) DEFAULT NULL COMMENT 'O anki servis maliyeti',
  PRIMARY KEY (`id`),
  KEY `idx_ai_kategori_yayin` (`kategori_id`,`yayin_tipi_id`),
  KEY `ai_feature_usages_feature_slug_index` (`feature_slug`),
  KEY `ai_feature_usages_ilan_id_index` (`ilan_id`),
  KEY `ai_feature_usages_istek_id_index` (`istek_id`),
  KEY `ai_feature_usages_aksiyon_index` (`aksiyon`),
  KEY `ai_feature_usages_created_at_index` (`created_at`),
  KEY `idx_ai_feature_usages_created_at` (`created_at`),
  KEY `idx_ai_feature_usages_kategori_created` (`kategori_id`,`created_at`),
  KEY `idx_ai_feature_usages_provider_created` (`provider`,`created_at`),
  KEY `ai_feature_usages_deney_id_foreign` (`deney_id`),
  KEY `ai_feature_usages_tenant_id_index` (`tenant_id`),
  CONSTRAINT `ai_feature_usages_deney_id_foreign` FOREIGN KEY (`deney_id`) REFERENCES `ai_deneyler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_feature_usages_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_feature_usages_archive` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `feature_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kategori_id` bigint unsigned DEFAULT NULL,
  `yayin_tipi_id` bigint unsigned DEFAULT NULL,
  `cost_usd` decimal(10,6) DEFAULT NULL,
  `latency_ms` int DEFAULT NULL,
  `cache_hit` tinyint(1) NOT NULL DEFAULT '0',
  `experiment_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ai_feature_usages_archive_archived_at_index` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_field_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_field_suggestions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `group_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `main_category_id` bigint unsigned NOT NULL,
  `sub_category_id` bigint unsigned DEFAULT NULL,
  `listing_type_id` bigint unsigned NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `score_json` json DEFAULT NULL,
  `total_score` smallint unsigned NOT NULL DEFAULT '0',
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ai_engine',
  `oneri_durumu` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `conflicts_json` json DEFAULT NULL,
  `feature_id` bigint unsigned DEFAULT NULL,
  `applied_assignment_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `afs_category_listing_idx` (`main_category_id`,`listing_type_id`),
  KEY `afs_oneri_durumu_idx` (`oneri_durumu`),
  KEY `afs_total_score_idx` (`total_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_lead_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_lead_scores` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned NOT NULL,
  `skor_degeri` tinyint unsigned NOT NULL DEFAULT '50' COMMENT '0-100 arası skor (Sıcaklık)',
  `skor_etiketi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Sıcak, Ilık, Soğuk',
  `skor_nedeni` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'AI/Kural tabanlı gerekçe',
  `win_probability` tinyint DEFAULT NULL COMMENT '0-100% win chance',
  `sinyaller` json DEFAULT NULL COMMENT 'Skoru etkileyen faktörler (Call, Email, Site)',
  `hesaplama_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `model_versiyonu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'v1.0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_lead_scores_lead_id_index` (`lead_id`),
  KEY `ai_lead_scores_skor_degeri_index` (`skor_degeri`),
  KEY `ai_lead_scores_hesaplama_tarihi_index` (`hesaplama_tarihi`),
  CONSTRAINT `ai_lead_scores_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_learning_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_learning_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `learning_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `extracted_patterns` json DEFAULT NULL,
  `generated_insights` json DEFAULT NULL,
  `confidence_score` decimal(5,2) DEFAULT NULL,
  `applied` tinyint(1) NOT NULL DEFAULT '0',
  `application_result` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `yalihan_bekci_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0',
  `learned_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_learning_sessions_session_type_learned_at_index` (`session_type`,`learned_at`),
  KEY `ai_learning_sessions_applied_learned_at_index` (`applied`,`learned_at`),
  KEY `ai_learning_sessions_confidence_score_index` (`confidence_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content_id` bigint DEFAULT NULL,
  `model` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_tokens` int DEFAULT NULL,
  `output_tokens` int DEFAULT NULL,
  `total_tokens` int DEFAULT NULL,
  `duration_ms` int NOT NULL,
  `aktiflik_kodu` int NOT NULL DEFAULT '200',
  `correlation_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `calisma_durumu` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `request_payload` json DEFAULT NULL,
  `response_payload` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `version` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ai_logs_user_id_foreign` (`user_id`),
  KEY `ai_logs_provider_index` (`provider`),
  KEY `ai_logs_endpoint_index` (`endpoint`),
  KEY `idx_ai_logs_olusturma_tarihi` (`olusturma_tarihi`),
  KEY `idx_ai_logs_provider_olusturma_tarihi` (`provider`,`olusturma_tarihi`),
  KEY `ai_logs_tenant_id_index` (`tenant_id`),
  KEY `ai_logs_model_index` (`model`),
  KEY `idx_telemetry_aggregation` (`olusturma_tarihi`,`provider`,`endpoint`),
  KEY `ai_logs_request_type_index` (`request_type`),
  KEY `ai_logs_event_type_index` (`event_type`),
  KEY `ai_logs_content_type_index` (`content_type`),
  KEY `ai_logs_content_id_index` (`content_id`),
  KEY `ai_logs_calisma_durumu_index` (`calisma_durumu`),
  KEY `ai_logs_correlation_id_index` (`correlation_id`),
  CONSTRAINT `ai_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_logs_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_logs_archive` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `feature_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_id` bigint unsigned DEFAULT NULL,
  `yayin_tipi_id` bigint unsigned DEFAULT NULL,
  `execution_state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost_usd` decimal(10,6) DEFAULT NULL,
  `latency_ms` int DEFAULT NULL,
  `cache_hit` tinyint(1) NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ai_logs_archive_archived_at_index` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_ogrenme_sinyalleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_ogrenme_sinyalleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ai_feature_usage_id` bigint unsigned NOT NULL,
  `kategori_id` int unsigned DEFAULT NULL,
  `yayin_tipi_id` int unsigned DEFAULT NULL,
  `feature_slug` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `confidence` decimal(5,2) NOT NULL,
  `karar_tipi` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'applied | dismissed | auto_applied | auto_reverted',
  `skor` int NOT NULL COMMENT '+1 | -1 | -2',
  `context_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sinyaller_json` json DEFAULT NULL COMMENT 'Normalized signals from explainability',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ai_ogrenme_sinyalleri_ai_feature_usage_id_foreign` (`ai_feature_usage_id`),
  KEY `ai_ogrenme_sinyalleri_kategori_id_feature_slug_index` (`kategori_id`,`feature_slug`),
  KEY `ai_ogrenme_sinyalleri_context_hash_index` (`context_hash`),
  CONSTRAINT `ai_ogrenme_sinyalleri_ai_feature_usage_id_foreign` FOREIGN KEY (`ai_feature_usage_id`) REFERENCES `ai_feature_usages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_opportunity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_opportunity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned NOT NULL,
  `opportunity_score` int NOT NULL,
  `opportunity_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ek_bilgiler` json DEFAULT NULL,
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '0=pasif, 1=aktif',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_opportunity_logs_listing_id_index` (`listing_id`),
  KEY `ai_opportunity_logs_opportunity_score_index` (`opportunity_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_optimization_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_optimization_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `window` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_count` int NOT NULL DEFAULT '0',
  `diff_json` json DEFAULT NULL,
  `executed_by` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_pricing_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_pricing_plans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_pricing_plans_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_prompt_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_prompt_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `prompt_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `template_id` bigint unsigned DEFAULT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `governance_score` int NOT NULL DEFAULT '0',
  `has_violation` tinyint(1) NOT NULL DEFAULT '0',
  `violations` json DEFAULT NULL,
  `prompt_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `duration_ms` int NOT NULL DEFAULT '0',
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_prompt_logs_prompt_hash_unique` (`prompt_hash`),
  KEY `ai_prompt_logs_user_id_foreign` (`user_id`),
  KEY `ai_prompt_logs_template_id_governance_score_index` (`template_id`,`governance_score`),
  KEY `ai_prompt_logs_created_at_index` (`created_at`),
  KEY `ai_prompt_logs_template_id_created_at_index` (`template_id`,`created_at`),
  KEY `ai_prompt_logs_governance_score_created_at_index` (`governance_score`,`created_at`),
  KEY `ai_prompt_logs_has_violation_created_at_index` (`has_violation`,`created_at`),
  CONSTRAINT `ai_prompt_logs_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `ups_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_prompt_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_provider_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_provider_decisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned DEFAULT NULL,
  `correlation_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kategori_id` bigint unsigned DEFAULT NULL,
  `yayin_tipi_id` bigint unsigned DEFAULT NULL,
  `chosen_provider` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scores_json` json NOT NULL,
  `reason_json` json NOT NULL,
  `debug_metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_provider_decisions_correlation_id_index` (`correlation_id`),
  KEY `ai_provider_decisions_kategori_id_index` (`kategori_id`),
  KEY `ai_provider_decisions_yayin_tipi_id_index` (`yayin_tipi_id`),
  KEY `idx_ai_provider_decisions_created_at` (`created_at`),
  KEY `idx_ai_provider_decisions_correlation` (`correlation_id`),
  KEY `ai_provider_decisions_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_provider_decisions_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_provider_decisions_archive` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `correlation_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kategori_id` bigint unsigned DEFAULT NULL,
  `yayin_tipi_id` bigint unsigned DEFAULT NULL,
  `chosen_provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `scores_json` json DEFAULT NULL,
  `reason_json` json DEFAULT NULL,
  `debug_metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ai_provider_decisions_archive_archived_at_index` (`archived_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_provider_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_provider_profiles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `window` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_id` bigint unsigned DEFAULT NULL,
  `accept_rate` decimal(4,3) NOT NULL DEFAULT '0.000',
  `avg_latency_ms` int NOT NULL DEFAULT '0',
  `avg_cost_usd` decimal(8,6) NOT NULL DEFAULT '0.000000',
  `error_rate` decimal(4,3) NOT NULL DEFAULT '0.000',
  `cache_hit_rate` decimal(4,3) NOT NULL DEFAULT '0.000',
  `sample_size` int NOT NULL DEFAULT '0',
  `computed_score` decimal(4,3) NOT NULL DEFAULT '0.000',
  `computed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `provider_window_cat_unique` (`provider`,`window`,`kategori_id`),
  KEY `ai_provider_profiles_provider_index` (`provider`),
  KEY `ai_provider_profiles_kategori_id_index` (`kategori_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_query_failures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_query_failures` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `query_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `failure_reason` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_context` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ai_query_failures_failure_reason_index` (`failure_reason`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_query_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_query_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `query` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `intent` json DEFAULT NULL,
  `execution_time` double(8,2) DEFAULT NULL,
  `result_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_query_telemetry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_query_telemetry` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `query_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `intent_detected` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_il` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_ilce` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_mahalle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asset_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_m2` int DEFAULT NULL,
  `confidence_score` double(8,2) DEFAULT NULL,
  `engine_called` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `execution_time_ms` int DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ai_query_telemetry_intent_detected_index` (`intent_detected`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_saglayici_profilleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_saglayici_profilleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kategori_id` int unsigned DEFAULT NULL,
  `yayin_tipi_id` int unsigned DEFAULT NULL,
  `saglayici` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ort_gecikme_ms` int unsigned NOT NULL DEFAULT '0',
  `ort_maliyet_usd` decimal(8,6) NOT NULL DEFAULT '0.000000',
  `kabul_orani` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT '0.00-100.00',
  `ornek_sayisi` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_provider_unique` (`kategori_id`,`yayin_tipi_id`,`saglayici`),
  KEY `ai_saglayici_profilleri_kategori_id_index` (`kategori_id`),
  KEY `ai_saglayici_profilleri_yayin_tipi_id_index` (`yayin_tipi_id`),
  KEY `ai_saglayici_profilleri_saglayici_index` (`saglayici`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_suggestion_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_suggestion_actions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `suggestion_id` bigint unsigned NOT NULL,
  `action` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `snapshot_json` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asa_suggestion_idx` (`suggestion_id`),
  KEY `asa_user_idx` (`user_id`),
  CONSTRAINT `ai_suggestion_actions_suggestion_id_foreign` FOREIGN KEY (`suggestion_id`) REFERENCES `ai_field_suggestions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_telemetry_hourly`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_telemetry_hourly` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tarih_saat` datetime NOT NULL COMMENT 'Aggregation hour timestamp',
  `provider_adi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AI provider: openai, gemini, ollama',
  `endpoint_adi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'API endpoint name',
  `toplam_istek` int unsigned NOT NULL DEFAULT '0' COMMENT 'Total requests in this hour',
  `basarili_istek` int unsigned NOT NULL DEFAULT '0' COMMENT 'Successful requests (2xx)',
  `hatali_istek` int unsigned NOT NULL DEFAULT '0' COMMENT 'Failed requests (4xx, 5xx)',
  `toplam_token` bigint unsigned NOT NULL DEFAULT '0' COMMENT 'Total tokens consumed',
  `toplam_maliyet_usd` decimal(10,4) NOT NULL DEFAULT '0.0000' COMMENT 'Total cost in USD',
  `ortalama_gecikme_ms` int unsigned NOT NULL DEFAULT '0' COMMENT 'Average latency in milliseconds',
  `min_gecikme_ms` int unsigned NOT NULL DEFAULT '0' COMMENT 'Minimum latency',
  `max_gecikme_ms` int unsigned NOT NULL DEFAULT '0' COMMENT 'Maximum latency',
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_hourly_aggregation` (`tarih_saat`,`provider_adi`,`endpoint_adi`),
  KEY `idx_tarih_provider` (`tarih_saat`,`provider_adi`),
  KEY `idx_provider_endpoint` (`provider_adi`,`endpoint_adi`),
  KEY `idx_tarih_saat_only` (`tarih_saat`),
  KEY `ai_telemetry_hourly_tarih_saat_index` (`tarih_saat`),
  KEY `ai_telemetry_hourly_provider_adi_index` (`provider_adi`),
  KEY `ai_telemetry_hourly_endpoint_adi_index` (`endpoint_adi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_tenant_quotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_tenant_quotas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `monthly_budget_usd` decimal(10,2) NOT NULL DEFAULT '100.00',
  `max_calls_per_month` int NOT NULL DEFAULT '1000',
  `current_month_spend` decimal(10,2) NOT NULL DEFAULT '0.00',
  `current_month_calls` int NOT NULL DEFAULT '0',
  `overflow_policy` enum('block','downgrade','allow') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'block',
  `reset_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_tenant_quotas_tenant_id_unique` (`tenant_id`),
  KEY `ai_tenant_quotas_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_tenant_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_tenant_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `vision_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `title_generation_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `description_generation_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `auto_apply_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `confidence_threshold_vision` decimal(5,4) NOT NULL DEFAULT '0.7000',
  `confidence_threshold_title` decimal(5,4) NOT NULL DEFAULT '0.7000',
  `confidence_threshold_description` decimal(5,4) NOT NULL DEFAULT '0.7000',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_tenant_settings_tenant_id_unique` (`tenant_id`),
  KEY `ai_tenant_settings_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_threshold_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_threshold_overrides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kategori_id` bigint unsigned DEFAULT NULL,
  `yayin_tipi_id` bigint unsigned DEFAULT NULL,
  `auto_apply_threshold` decimal(4,3) NOT NULL,
  `suggest_threshold` decimal(4,3) NOT NULL,
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'continuous_optimization',
  `run_id` bigint unsigned DEFAULT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_threshold_overrides_kategori_id_index` (`kategori_id`),
  KEY `ai_threshold_overrides_yayin_tipi_id_index` (`yayin_tipi_id`),
  KEY `ai_threshold_overrides_run_id_index` (`run_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `wallet_id` bigint unsigned NOT NULL,
  `amount` bigint NOT NULL,
  `final_balance` bigint unsigned NOT NULL,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ai_transactions_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `ai_transactions_wallet_id_foreign` (`wallet_id`),
  KEY `ai_transactions_tenant_id_created_at_index` (`tenant_id`,`created_at`),
  KEY `ai_transactions_reason_index` (`reason`),
  CONSTRAINT `ai_transactions_wallet_id_foreign` FOREIGN KEY (`wallet_id`) REFERENCES `ai_workspace_wallets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_translation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_translation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned NOT NULL,
  `source_locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `islem_durumu` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `review_required` tinyint(1) NOT NULL DEFAULT '0',
  `execution_time` double(8,2) DEFAULT NULL,
  `provider` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quality_score` double(8,2) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_translation_logs_listing_id_index` (`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_workspace_wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_workspace_wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `balance` bigint unsigned NOT NULL DEFAULT '0',
  `currency` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AI_CREDIT',
  `low_balance_threshold` int unsigned NOT NULL DEFAULT '100',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_workspace_wallets_tenant_id_unique` (`tenant_id`),
  KEY `ai_workspace_wallets_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alt_kategori_yayin_tipi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alt_kategori_yayin_tipi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `alt_kategori_id` bigint unsigned NOT NULL,
  `yayin_tipi_id` bigint unsigned NOT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alt_kat_yayin_tipi_unique_v2` (`alt_kategori_id`,`yayin_tipi_id`),
  KEY `akyt_alt_kat_aktif_index_v2` (`alt_kategori_id`,`aktiflik_durumu`),
  CONSTRAINT `akyt_alt_kat_id_fk` FOREIGN KEY (`alt_kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `anahtar_yonetimi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `anahtar_yonetimi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `anahtar_statusu` enum('Beklemede','Hazır','Teslim Edildi','Geri Alındı','Kayıp') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Beklemede',
  `teslim_tarihi` datetime DEFAULT NULL,
  `teslim_eden_kisi_id` bigint unsigned DEFAULT NULL,
  `teslim_alan_kisi_id` bigint unsigned DEFAULT NULL,
  `anahtar_konumu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anahtar_notlari` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anahtar_tipi` enum('Ana Anahtar','Yedek Anahtar','Kodlu Anahtar','Kartlı Anahtar','Uzaktan Kumanda') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ana Anahtar',
  `anahtar_sayisi` int NOT NULL DEFAULT '1',
  `anahtar_ozellikleri` json DEFAULT NULL,
  `anahtar_durumu` enum('Aktif','Pasif','Silindi') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `anahtar_yonetimi_teslim_eden_kisi_id_foreign` (`teslim_eden_kisi_id`),
  KEY `anahtar_yonetimi_teslim_alan_kisi_id_foreign` (`teslim_alan_kisi_id`),
  KEY `anahtar_yonetimi_created_by_foreign` (`created_by`),
  KEY `anahtar_yonetimi_updated_by_foreign` (`updated_by`),
  KEY `anahtar_yonetimi_ilan_id_anahtar_statusu_index` (`ilan_id`,`anahtar_statusu`),
  KEY `anahtar_yonetimi_teslim_tarihi_index` (`teslim_tarihi`),
  KEY `anahtar_yonetimi_anahtar_durumu_index` (`anahtar_durumu`),
  CONSTRAINT `anahtar_yonetimi_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `anahtar_yonetimi_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `anahtar_yonetimi_teslim_alan_kisi_id_foreign` FOREIGN KEY (`teslim_alan_kisi_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `anahtar_yonetimi_teslim_eden_kisi_id_foreign` FOREIGN KEY (`teslim_eden_kisi_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `anahtar_yonetimi_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytics_dashboard_filters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_dashboard_filters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `filtre_adi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `analiz_durumu` enum('aktif','sonlandirildi','kilitli','arsiv') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif' COMMENT 'Analiz durumu (Phase 6: analiz_durumu canonical)',
  `siralama_sirasi` int NOT NULL DEFAULT '0' COMMENT 'Sıralama sırası (Context7: order → siralama_sirasi)',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Aktif/Pasif durum (Context7: status → aktiflik_durumu)',
  `varsayilan_mi` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Varsayılan filtre (Context7: is_default → varsayilan_mi)',
  `filtre_kurallari` json DEFAULT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analytics_dashboard_filters_user_id_index` (`user_id`),
  CONSTRAINT `analytics_dashboard_filters_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytics_dashboard_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_dashboard_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned DEFAULT NULL,
  `metrik_adi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `metrik_durumu` enum('hesaplandi','guncelleniyor','hata','beklemede') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beklemede' COMMENT 'Metrik durumu (Phase 6: metrik_durumu canonical)',
  `siralama_sirasi` int NOT NULL DEFAULT '0' COMMENT 'Sıralama sırası (Context7: order → siralama_sirasi)',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Aktif/Pasif durum (Context7: status → aktiflik_durumu)',
  `deger` decimal(10,2) DEFAULT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `detaylar` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analytics_dashboard_metrics_ilan_id_index` (`ilan_id`),
  CONSTRAINT `analytics_dashboard_metrics_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytics_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `metric_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_data` json NOT NULL,
  `metric_value` decimal(10,2) DEFAULT NULL,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `recorded_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analytics_metrics_metric_type_recorded_at_index` (`metric_type`,`recorded_at`),
  KEY `analytics_metrics_metric_name_recorded_at_index` (`metric_name`,`recorded_at`),
  KEY `analytics_metrics_source_recorded_at_index` (`source`,`recorded_at`),
  KEY `analytics_metrics_severity_index` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytics_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `rapor_adi` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rapor_durumu` enum('hazirlanıyor','tamamlandı','gonderildi','hata') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hazirlanıyor' COMMENT 'Rapor durumu (Phase 6: rapor_durumu canonical)',
  `siralama_sirasi` int NOT NULL DEFAULT '0' COMMENT 'Sıralama sırası (Context7: order → siralama_sirasi)',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Aktif/Pasif durum (Context7: status → aktiflik_durumu)',
  `baslangic_tarihi` datetime NOT NULL,
  `bitis_tarihi` datetime DEFAULT NULL,
  `parametreler` json DEFAULT NULL,
  `dosya_yolu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analytics_reports_user_id_index` (`user_id`),
  CONSTRAINT `analytics_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `buyer_intent_projection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_intent_projection` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `buyer_id` bigint unsigned NOT NULL,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_budget` decimal(15,2) DEFAULT NULL,
  `max_budget` decimal(15,2) DEFAULT NULL,
  `property_types` json DEFAULT NULL,
  `room_preferences` json DEFAULT NULL,
  `feature_preferences` json DEFAULT NULL,
  `urgency_level` int NOT NULL DEFAULT '0',
  `recent_activity_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `last_contact_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_intent_projection_buyer_id_unique` (`buyer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `buyer_interest_projections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_interest_projections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned NOT NULL,
  `candidate_count` int NOT NULL DEFAULT '0',
  `avg_match_score` int NOT NULL DEFAULT '0',
  `top_match_score` int NOT NULL DEFAULT '0',
  `high_intent_buyer_count` int NOT NULL DEFAULT '0',
  `recent_query_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `buyer_interest_projections_listing_id_unique` (`listing_id`),
  CONSTRAINT `buyer_interest_projections_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `buyer_match_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_match_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL COMMENT 'SAB: Ilan matching entry',
  `buyer_id` bigint unsigned NOT NULL,
  `talep_id` bigint unsigned DEFAULT NULL,
  `match_score` decimal(5,2) NOT NULL,
  `price_fit_score` decimal(5,2) NOT NULL,
  `location_fit_score` decimal(5,2) NOT NULL,
  `feature_fit_score` decimal(5,2) NOT NULL,
  `intent_fit_score` decimal(5,2) NOT NULL,
  `churn_risk_score` decimal(5,2) NOT NULL,
  `action_score` decimal(5,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `buyer_match_logs_ilan_id_index` (`ilan_id`),
  KEY `buyer_match_logs_buyer_id_index` (`buyer_id`),
  KEY `buyer_match_logs_talep_id_index` (`talep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `buyer_match_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buyer_match_snapshots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `total_candidates` int NOT NULL,
  `top_match_score` decimal(5,2) NOT NULL,
  `top_buyer_id` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `buyer_match_snapshots_ilan_id_index` (`ilan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `category_feature_whitelist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `category_feature_whitelist` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kategori_id` bigint unsigned NOT NULL,
  `feature_category_slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_kategori_feature_slug` (`kategori_id`,`feature_category_slug`),
  KEY `category_feature_whitelist_feature_category_slug_index` (`feature_category_slug`),
  KEY `category_feature_whitelist_aktiflik_durumu_index` (`aktiflik_durumu`),
  CONSTRAINT `category_feature_whitelist_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `context7_compliance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `context7_compliance_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `violation_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `violation_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line_number` int DEFAULT NULL,
  `violation_context` json DEFAULT NULL,
  `auto_fixed` tinyint(1) NOT NULL DEFAULT '0',
  `fix_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `severity` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `detected_at` timestamp NOT NULL,
  `fixed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `context7_compliance_logs_violation_type_detected_at_index` (`violation_type`,`detected_at`),
  KEY `context7_compliance_logs_auto_fixed_detected_at_index` (`auto_fixed`,`detected_at`),
  KEY `context7_compliance_logs_severity_detected_at_index` (`severity`,`detected_at`),
  KEY `context7_compliance_logs_file_path_index` (`file_path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `copilot_action_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `copilot_action_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'field_autofill|multi_field_apply|template_apply|full_listing_generate|pricing_apply|auto_run_preview',
  `user_id` bigint unsigned DEFAULT NULL,
  `ilan_id` bigint unsigned DEFAULT NULL,
  `main_category_id` bigint unsigned DEFAULT NULL,
  `listing_type_id` int unsigned DEFAULT NULL,
  `request_payload` json DEFAULT NULL COMMENT 'Input sent to copilot action',
  `response_payload` json DEFAULT NULL COMMENT 'Generated action result',
  `applied_fields` json DEFAULT NULL COMMENT 'Fields actually applied by user',
  `diff_snapshot` json DEFAULT NULL COMMENT 'Before/after diff for undo',
  `aksiyon_durumu` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'preview' COMMENT 'preview|applied|undone|rejected',
  `confidence_score` double(8,2) DEFAULT NULL,
  `duration_ms` int unsigned DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_unicode_ci,
  `applied_at` timestamp NULL DEFAULT NULL,
  `undone_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cal_user_action_idx` (`user_id`,`action_type`),
  KEY `cal_ilan_idx` (`ilan_id`),
  KEY `cal_aksiyon_durumu_idx` (`aksiyon_durumu`),
  KEY `cal_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `country_financial_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `country_financial_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rental_commission_rate` decimal(5,4) NOT NULL,
  `sales_commission_rate` decimal(5,4) NOT NULL,
  `advisory_fee_rate` decimal(5,4) NOT NULL DEFAULT '0.0000',
  `tax_rate` decimal(5,4) NOT NULL DEFAULT '0.0000',
  `default_currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_financial_rules_country_code_unique` (`country_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `symbol` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `varsayilan_durumu` tinyint(1) NOT NULL DEFAULT '0',
  `decimal_precision` int NOT NULL DEFAULT '2',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `currencies_code_unique` (`code`),
  KEY `currencies_is_active_index` (`aktiflik_durumu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `danisman_chat_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `danisman_chat_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint unsigned NOT NULL,
  `role` enum('user','assistant','system') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `is_error` tinyint(1) NOT NULL DEFAULT '0',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `danisman_chat_messages_session_id_foreign` (`session_id`),
  KEY `danisman_chat_messages_role_index` (`role`),
  CONSTRAINT `danisman_chat_messages_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `danisman_chat_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `danisman_chat_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `danisman_chat_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '0=pasif, 1=aktif, 2=arsivlendi',
  `context_data` json DEFAULT NULL,
  `ai_config_snapshot` json DEFAULT NULL,
  `last_message_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `danisman_chat_sessions_session_id_unique` (`session_id`),
  KEY `danisman_chat_sessions_user_id_foreign` (`user_id`),
  KEY `danisman_chat_sessions_status_index` (`aktiflik_durumu`),
  CONSTRAINT `danisman_chat_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dashboard_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_widgets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `position_x` int NOT NULL DEFAULT '0',
  `position_y` int NOT NULL DEFAULT '0',
  `width` int NOT NULL DEFAULT '6',
  `height` int NOT NULL DEFAULT '2',
  `settings` json DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dashboard_widgets_user_id_index` (`user_id`),
  KEY `dashboard_widgets_user_id_aktiflik_durumu_index` (`user_id`,`aktiflik_durumu`),
  KEY `dashboard_widgets_display_order_index` (`display_order`),
  CONSTRAINT `dashboard_widgets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deal_prediction_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deal_prediction_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned NOT NULL,
  `sale_probability` int NOT NULL,
  `estimated_days_to_sell` int NOT NULL,
  `price_accuracy_score` int NOT NULL,
  `market_heat_score` int NOT NULL,
  `buyer_interest_score` int NOT NULL,
  `deal_quality_score` int NOT NULL,
  `opportunity_score` int DEFAULT NULL,
  `top_buyer_match_score` int DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `locale` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tr',
  `model_version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deal_prediction_logs_listing_id_index` (`listing_id`),
  KEY `deal_prediction_logs_deal_quality_score_index` (`deal_quality_score`),
  CONSTRAINT `deal_prediction_logs_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deal_prediction_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deal_prediction_snapshots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned NOT NULL,
  `snapshot_date` date NOT NULL,
  `sale_probability` int NOT NULL,
  `estimated_days_to_sell` int NOT NULL,
  `deal_quality_score` int NOT NULL,
  `market_heat_score` int NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deal_prediction_snapshots_listing_id_snapshot_date_index` (`listing_id`,`snapshot_date`),
  CONSTRAINT `deal_prediction_snapshots_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `design_token_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `design_token_usage` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `component_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokens_used` json NOT NULL,
  `token_count` int NOT NULL,
  `compliance_score` decimal(3,2) NOT NULL,
  `analyzed_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `design_token_usage_page_name_analyzed_at_index` (`page_name`,`analyzed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `development_velocity_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `development_velocity_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `developer_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commits_count` int NOT NULL DEFAULT '0',
  `files_changed` int NOT NULL DEFAULT '0',
  `lines_added` int NOT NULL DEFAULT '0',
  `lines_deleted` int NOT NULL DEFAULT '0',
  `code_quality_score` decimal(5,2) DEFAULT NULL,
  `context7_violations` int NOT NULL DEFAULT '0',
  `auto_fixes_applied` int NOT NULL DEFAULT '0',
  `test_coverage` decimal(5,2) DEFAULT NULL,
  `feature_tags` json DEFAULT NULL,
  `period_start` timestamp NOT NULL,
  `period_end` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `development_velocity_metrics_developer_name_period_start_index` (`developer_name`,`period_start`),
  KEY `development_velocity_metrics_branch_name_period_start_index` (`branch_name`,`period_start`),
  KEY `development_velocity_metrics_period_start_index` (`period_start`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `error_memory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `error_memory` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eski_alt_kategori_yayin_tipi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eski_alt_kategori_yayin_tipi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `alt_kategori_id` bigint unsigned NOT NULL,
  `yayin_tipi_id` bigint unsigned NOT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alt_kat_yayin_tipi_unique` (`alt_kategori_id`,`yayin_tipi_id`),
  KEY `alt_kategori_yayin_tipi_alt_kategori_id_aktiflik_durumu_index` (`alt_kategori_id`,`aktiflik_durumu`),
  KEY `alt_kategori_yayin_tipi_yayin_tipi_id_index` (`yayin_tipi_id`),
  CONSTRAINT `alt_kategori_yayin_tipi_alt_kategori_id_foreign` FOREIGN KEY (`alt_kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alt_kategori_yayin_tipi_yayin_tipi_id_foreign` FOREIGN KEY (`yayin_tipi_id`) REFERENCES `eski_ilan_kategori_yayin_tipleri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eski_ilan_kategori_yayin_tipleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eski_ilan_kategori_yayin_tipleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kategori_id` bigint unsigned NOT NULL,
  `yayin_tipi_id` bigint unsigned DEFAULT NULL,
  `yayin_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Yayın tipi detaylı açıklaması',
  `icon` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Emoji icon (?, ?, ?, etc.)',
  `populer` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Popüler yayın tipi mi?',
  `sira` int DEFAULT NULL COMMENT 'Görüntüleme sırası',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ilan_kategori_yayin_tipleri_kategori_id_yayin_tipi_unique` (`kategori_id`,`yayin_tipi`),
  KEY `ilan_kategori_yayin_tipleri_aktiflik_durumu_index` (`aktiflik_durumu`),
  KEY `idx_populer` (`populer`),
  KEY `idx_sira` (`sira`),
  CONSTRAINT `ilan_kategori_yayin_tipleri_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eski_yayin_tipleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eski_yayin_tipleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0' COMMENT 'Gösterim sırası',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `yayin_tipleri_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eslesmeler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `eslesmeler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `kisi_id` bigint unsigned NOT NULL,
  `talep_id` bigint unsigned DEFAULT NULL,
  `danisman_id` bigint unsigned DEFAULT NULL,
  `skor` int NOT NULL DEFAULT '0',
  `eslesme_durumu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beklemede',
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `eslesme_detaylari` json DEFAULT NULL,
  `eslesme_tarihi` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `eslesmeler_kisi_id_foreign` (`kisi_id`),
  KEY `eslesmeler_eslesme_durumu_index` (`eslesme_durumu`),
  KEY `eslesmeler_skor_index` (`skor`),
  KEY `eslesmeler_ilan_id_kisi_id_index` (`ilan_id`,`kisi_id`),
  KEY `eslesmeler_talep_id_index` (`talep_id`),
  KEY `eslesmeler_danisman_id_index` (`danisman_id`),
  CONSTRAINT `eslesmeler_danisman_id_foreign` FOREIGN KEY (`danisman_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eslesmeler_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eslesmeler_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `eslesmeler_talep_id_foreign` FOREIGN KEY (`talep_id`) REFERENCES `talepler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etiket_kisi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etiket_kisi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `etiket_id` bigint unsigned NOT NULL,
  `kisi_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `etiket_kisi_unique` (`etiket_id`,`kisi_id`),
  KEY `etiket_kisi_etiket_id_index` (`etiket_id`),
  KEY `etiket_kisi_kisi_id_index` (`kisi_id`),
  KEY `etiket_kisi_user_id_index` (`user_id`),
  CONSTRAINT `etiket_kisi_etiket_id_foreign` FOREIGN KEY (`etiket_id`) REFERENCES `etiketler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `etiket_kisi_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `etiket_kisi_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etiketler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etiketler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#3B82F6',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `etiketler_slug_unique` (`slug`),
  KEY `etiketler_aktiflik_durumu_display_order_index` (`aktiflik_durumu`,`display_order`),
  KEY `etiketler_slug_index` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expense_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `aktiflik_durumu` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `expense_items_slug_unique` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feature_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_assignments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `feature_id` bigint unsigned NOT NULL,
  `assignable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `assignable_id` bigint unsigned NOT NULL,
  `main_category_id` bigint unsigned DEFAULT NULL,
  `sub_category_id` bigint unsigned DEFAULT NULL,
  `listing_type_id` bigint unsigned DEFAULT NULL,
  `scope_type` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global',
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `label_override` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Context7: UI label override',
  `field_slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_type` varchar(32) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_visible` tinyint(1) NOT NULL DEFAULT '1',
  `is_inherited` tinyint(1) NOT NULL DEFAULT '0',
  `origin_category_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual' COMMENT 'manual, ai, parent',
  `metadata` json DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `conditional_logic` json DEFAULT NULL,
  `visible_if_json` json DEFAULT NULL,
  `required_if_json` json DEFAULT NULL,
  `enabled_if_json` json DEFAULT NULL,
  `options_json` json DEFAULT NULL,
  `rolled_back_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `group_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_assignment_scoped_unique` (`feature_id`,`assignable_type`,`assignable_id`,`scope_type`),
  KEY `feature_assignments_assignable_type_assignable_id_index` (`assignable_type`,`assignable_id`),
  KEY `feature_assignments_feature_id_assignable_type_index` (`feature_id`,`assignable_type`),
  KEY `feature_assignments_aktiflik_durumu_index` (`aktiflik_durumu`),
  KEY `fa_scope_idx` (`main_category_id`,`sub_category_id`,`listing_type_id`),
  KEY `fa_scope_source_idx` (`scope_type`,`source_type`),
  KEY `fa_rollback_idx` (`rolled_back_at`),
  CONSTRAINT `feature_assignments_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=133 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feature_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feature_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `applies_to` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Emlak türleri: konut, arsa, yazlik, isyeri (virgülle ayrılmış)',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `meta_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `seo_keywords` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feature_categories_slug_unique` (`slug`),
  KEY `feature_categories_aktiflik_durumu_display_order_index` (`aktiflik_durumu`,`display_order`),
  KEY `feature_categories_slug_index` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `features` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lifecycle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active' COMMENT 'active, deprecated, draft, archived',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'boolean',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT 'Aktiflik durumu (Context7: 1=aktif, 0=pasif)',
  `options` json DEFAULT NULL,
  `unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `feature_category_id` bigint unsigned DEFAULT NULL,
  `applies_to` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_filterable` tinyint(1) NOT NULL DEFAULT '1',
  `is_searchable` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0' COMMENT 'Sıralama (Context7 standard)',
  `aktif_mi` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Aktif mi? (Context7 standard)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deprecated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `features_slug_unique` (`slug`),
  KEY `features_aktif_mi_display_order_index` (`aktif_mi`,`display_order`),
  KEY `features_feature_category_id_aktif_mi_index` (`feature_category_id`,`aktif_mi`),
  KEY `features_slug_index` (`slug`),
  KEY `features_lifecycle_index` (`lifecycle`),
  KEY `idx_features_aktiflik_durumu` (`aktiflik_durumu`),
  CONSTRAINT `features_feature_category_id_foreign` FOREIGN KEY (`feature_category_id`) REFERENCES `feature_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feedback_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_results` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned NOT NULL,
  `snapshot_id` bigint unsigned NOT NULL,
  `outcome_id` bigint unsigned NOT NULL,
  `pricing_correct` tinyint(1) DEFAULT NULL,
  `demand_correct` tinyint(1) DEFAULT NULL,
  `opportunity_correct` tinyint(1) DEFAULT NULL,
  `feedback_reason` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `feedback_results_snapshot_id_outcome_id_unique` (`snapshot_id`,`outcome_id`),
  KEY `feedback_results_listing_id_index` (`listing_id`),
  KEY `feedback_results_snapshot_id_index` (`snapshot_id`),
  KEY `feedback_results_outcome_id_index` (`outcome_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `financial_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `reservation_id` bigint unsigned DEFAULT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TR',
  `base_currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `base_amount` decimal(15,2) NOT NULL,
  `display_currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_amount` decimal(15,2) DEFAULT NULL,
  `fx_rate_locked` decimal(15,6) DEFAULT NULL,
  `islem_tipi` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `islem_durumu` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` bigint unsigned DEFAULT NULL,
  `sebep` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kaynak` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `financial_transactions_created_by_foreign` (`created_by`),
  KEY `financial_transactions_property_id_islem_tipi_index` (`property_id`,`islem_tipi`),
  KEY `financial_transactions_reservation_id_islem_durumu_index` (`reservation_id`,`islem_durumu`),
  KEY `financial_transactions_country_code_created_at_index` (`country_code`,`created_at`),
  KEY `idx_fin_property_type` (`property_id`,`islem_tipi`,`islem_durumu`),
  KEY `idx_fin_created` (`created_at`),
  KEY `idx_fin_reservation` (`reservation_id`),
  CONSTRAINT `financial_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `financial_transactions_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `financial_transactions_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `property_reservations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `finansal_islemler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `finansal_islemler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned DEFAULT NULL,
  `kisi_id` bigint unsigned DEFAULT NULL,
  `gorev_id` bigint unsigned DEFAULT NULL,
  `onaylayan_id` bigint unsigned DEFAULT NULL,
  `islem_tipi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'komisyon, odeme, masraf, gelir, gider',
  `miktar` decimal(15,2) NOT NULL,
  `para_birimi` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tarih` date NOT NULL,
  `islem_statusu` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bekliyor' COMMENT 'İşlem durumu (Context7 standard)',
  `onay_tarihi` timestamp NULL DEFAULT NULL,
  `referans_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fatura_no` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `finansal_islemler_gorev_id_foreign` (`gorev_id`),
  KEY `finansal_islemler_onaylayan_id_foreign` (`onaylayan_id`),
  KEY `finansal_islemler_ilan_id_index` (`ilan_id`),
  KEY `finansal_islemler_kisi_id_index` (`kisi_id`),
  KEY `finansal_islemler_islem_statusu_index` (`islem_statusu`),
  KEY `finansal_islemler_islem_tipi_index` (`islem_tipi`),
  KEY `finansal_islemler_tarih_index` (`tarih`),
  CONSTRAINT `finansal_islemler_gorev_id_foreign` FOREIGN KEY (`gorev_id`) REFERENCES `gorevler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finansal_islemler_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finansal_islemler_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `finansal_islemler_onaylayan_id_foreign` FOREIGN KEY (`onaylayan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `follow_up_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `follow_up_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned NOT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `task_type` enum('contact_new_lead','qualify_lead','present_options','re_engage_lost_lead','schedule_viewing','send_documents','follow_up_callback','custom') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `due_date` datetime NOT NULL,
  `priority` enum('low','medium','high') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `gorev_durumu` enum('pending','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `escalated` tinyint(1) NOT NULL DEFAULT '0',
  `escalated_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `follow_up_tasks_lead_id_index` (`lead_id`),
  KEY `follow_up_tasks_assigned_to_index` (`assigned_to`),
  KEY `follow_up_tasks_gorev_durumu_index` (`gorev_durumu`),
  KEY `follow_up_tasks_due_date_index` (`due_date`),
  KEY `follow_up_tasks_priority_index` (`priority`),
  CONSTRAINT `follow_up_tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `follow_up_tasks_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fx_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fx_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `from_currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` decimal(15,6) NOT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `effective_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fx_rates_from_currency_to_currency_effective_at_index` (`from_currency`,`to_currency`,`effective_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gorevler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gorevler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `baslik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `gorev_durumu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beklemede',
  `oncelik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Normal',
  `atanan_user_id` bigint unsigned DEFAULT NULL,
  `olusturan_user_id` bigint unsigned DEFAULT NULL,
  `baslangic_tarihi` date DEFAULT NULL,
  `bitis_tarihi` date DEFAULT NULL,
  `tamamlanma_yuzdesi` int NOT NULL DEFAULT '0',
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `kisi_id` bigint unsigned DEFAULT NULL,
  `proje_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `gorevler_gorev_durumu_oncelik_index` (`gorev_durumu`,`oncelik`),
  KEY `gorevler_atanan_user_id_index` (`atanan_user_id`),
  KEY `gorevler_bitis_tarihi_index` (`bitis_tarihi`),
  KEY `gorevler_kisi_id_foreign` (`kisi_id`),
  KEY `gorevler_proje_id_foreign` (`proje_id`),
  CONSTRAINT `gorevler_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gorevler_proje_id_foreign` FOREIGN KEY (`proje_id`) REFERENCES `projeler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `governance_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `governance_decisions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `finding_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `target` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `recommended_action` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `risk` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `decision` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `karar_durumu` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `karar_veren_id` bigint unsigned DEFAULT NULL,
  `karar_tarihi` timestamp NULL DEFAULT NULL,
  `karar_notu` text COLLATE utf8mb4_unicode_ci,
  `proposal_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `explanation` json DEFAULT NULL,
  `signals` json DEFAULT NULL,
  `confidence` double(3,2) DEFAULT NULL,
  `timeline` json DEFAULT NULL,
  `rollback_snapshot` json DEFAULT NULL,
  `action_result` json DEFAULT NULL COMMENT 'SAB8: {success, changed_fields, error_message, result_summary}',
  `impact_score` smallint DEFAULT NULL COMMENT 'SAB8: -100 (harmful) to +100 (beneficial)',
  `action_completed_at` timestamp NULL DEFAULT NULL COMMENT 'SAB8: When the action was fully completed',
  `feedback_note` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SAB8: Operator feedback after seeing result',
  `override_decision` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `override_reason` text COLLATE utf8mb4_unicode_ci,
  `override_by` bigint unsigned DEFAULT NULL,
  `override_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `governance_decisions_finding_id_unique` (`finding_id`),
  KEY `governance_decisions_karar_durumu_index` (`karar_durumu`),
  KEY `governance_decisions_severity_index` (`severity`),
  KEY `governance_decisions_source_index` (`source`),
  KEY `governance_decisions_karar_durumu_severity_index` (`karar_durumu`,`severity`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `governance_drift_telemetry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `governance_drift_telemetry` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `drift_count` int unsigned NOT NULL DEFAULT '0',
  `ungoverned_count` int unsigned NOT NULL DEFAULT '0',
  `shadow_missing_count` int unsigned NOT NULL DEFAULT '0',
  `compromised_count` int unsigned NOT NULL DEFAULT '0',
  `top_offenders` json DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `governance_drift_telemetry_olusturma_tarihi_index` (`olusturma_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `governance_incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `governance_incidents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SYSTEM',
  `olay_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kaynak` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `snapshot_id` bigint unsigned DEFAULT NULL,
  `imza_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `risk_seviyesi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `governance_incidents_olay_tipi_index` (`olay_tipi`),
  KEY `governance_incidents_risk_seviyesi_index` (`risk_seviyesi`),
  KEY `governance_incidents_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `governance_rollbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `governance_rollbacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `decision_id` bigint unsigned NOT NULL,
  `proposal_filename` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `before_snapshot` json NOT NULL,
  `after_snapshot` json DEFAULT NULL,
  `rollback_reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `rolled_back_by` bigint unsigned NOT NULL,
  `rollback_durumu` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `governance_rollbacks_decision_id_index` (`decision_id`),
  CONSTRAINT `governance_rollbacks_decision_id_foreign` FOREIGN KEY (`decision_id`) REFERENCES `governance_decisions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `governance_suppressions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `governance_suppressions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rule_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scope` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `domain` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `suppressed_by` bigint unsigned NOT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `governance_suppressions_rule_key_index` (`rule_key`),
  KEY `governance_suppressions_aktiflik_durumu_index` (`aktiflik_durumu`),
  KEY `governance_suppressions_source_domain_index` (`source`,`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `governance_tamper_incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `governance_tamper_incidents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version_id` bigint unsigned DEFAULT NULL,
  `version_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `severity` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CRITICAL',
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `context` json DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `governance_tamper_incidents_version_id_index` (`version_id`),
  KEY `governance_tamper_incidents_severity_index` (`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_embeddings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_embeddings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `embedding` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Vector embedding data (JSON array)',
  `model_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nomic-embed-text',
  `dimensions` int NOT NULL DEFAULT '768',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '0=Pasif, 1=Aktif',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ilan_embeddings_ilan_id_index` (`ilan_id`),
  KEY `ilan_embeddings_model_name_index` (`model_name`),
  CONSTRAINT `ilan_embeddings_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_etiketler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_etiketler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `etiket_id` bigint unsigned NOT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `one_cikan` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ilan_etiketler_ilan_id_etiket_id_unique` (`ilan_id`,`etiket_id`),
  KEY `ilan_etiketler_ilan_id_display_order_index` (`ilan_id`,`display_order`),
  KEY `ilan_etiketler_etiket_id_one_cikan_index` (`etiket_id`,`one_cikan`),
  CONSTRAINT `ilan_etiketler_etiket_id_foreign` FOREIGN KEY (`etiket_id`) REFERENCES `etiketler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ilan_etiketler_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_favorileri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_favorileri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `ilan_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ilan_favorileri_user_id_ilan_id_unique` (`user_id`,`ilan_id`),
  KEY `ilan_favorileri_ilan_id_foreign` (`ilan_id`),
  CONSTRAINT `ilan_favorileri_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ilan_favorileri_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_feature`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_feature` (
  `ilan_id` bigint unsigned NOT NULL,
  `feature_id` bigint unsigned NOT NULL,
  `value` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Feature value (for checkbox, number, select)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`ilan_id`,`feature_id`),
  KEY `ilan_feature_feature_id_foreign` (`feature_id`),
  CONSTRAINT `ilan_feature_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ilan_feature_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_fotograflari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_fotograflari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `dosya_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dosya_yolu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `dosya_boyutu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kapak_fotografi` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` int DEFAULT '0',
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ilan_fotograflari_ilan_id_kapak_fotografi_index` (`ilan_id`,`kapak_fotografi`),
  CONSTRAINT `ilan_fotograflari_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_goruntulenme_gunluk`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_goruntulenme_gunluk` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `tarih` date NOT NULL COMMENT 'Görüntülenme tarihi',
  `cihaz` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Cihaz tipi: desktop, mobile, tablet',
  `adet` int NOT NULL DEFAULT '0' COMMENT 'Görüntülenme adedi',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ilan_goruntulenme_gunluk_ilan_id_tarih_index` (`ilan_id`,`tarih`),
  KEY `ilan_goruntulenme_gunluk_tarih_index` (`tarih`),
  CONSTRAINT `ilan_goruntulenme_gunluk_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_kategorileri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_kategorileri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SYSTEM',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parent_id` bigint unsigned DEFAULT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `seviye` int NOT NULL DEFAULT '0',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `custom_slug_parent_unique` (`slug`,`parent_id`),
  KEY `ilan_kategorileri_parent_id_aktiflik_durumu_index` (`parent_id`,`aktiflik_durumu`),
  KEY `ilan_kategorileri_display_order_index` (`display_order`),
  KEY `ilan_kategorileri_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_metinleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_metinleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `baslik` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ton` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'profesyonel',
  `taslak_durumu` tinyint NOT NULL DEFAULT '1',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '0',
  `yapay_zeka_durumu` tinyint NOT NULL DEFAULT '1',
  `kaynak_veriler` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ilan_metinleri_ilan_id_index` (`ilan_id`),
  KEY `ilan_metinleri_ilan_id_aktiflik_durumu_index` (`ilan_id`,`aktiflik_durumu`),
  CONSTRAINT `ilan_metinleri_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_ozellikleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_ozellikleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `ozellik_id` bigint unsigned NOT NULL,
  `deger` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Özellik değeri',
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Özellik açıklaması',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT 'Özellik durum (0=pasif, 1=aktif) - Context7',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ilan_ozellik` (`ilan_id`,`ozellik_id`),
  KEY `ilan_ozellikleri_ozellik_id_foreign` (`ozellik_id`),
  KEY `idx_ilan_ozellikleri_ilan_ozellik` (`ilan_id`,`ozellik_id`),
  KEY `idx_ilan_ozellikleri_aktiflik_durumu` (`aktiflik_durumu`),
  KEY `idx_ilan_ozellikleri_created_at` (`created_at`),
  CONSTRAINT `ilan_ozellikleri_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ilan_ozellikleri_ozellik_id_foreign` FOREIGN KEY (`ozellik_id`) REFERENCES `ozellikler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_price_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_price_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `old_price` decimal(15,2) NOT NULL,
  `new_price` decimal(15,2) NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `change_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `changed_by` bigint unsigned DEFAULT NULL,
  `additional_data` json DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ilan_price_history_ilan_id_index` (`ilan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_resimleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_resimleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `dosya_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Resim dosya adı',
  `dosya_yolu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Resim dosya yolu',
  `dosya_boyutu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Dosya boyutu (bytes)',
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Dosya MIME tipi',
  `sira_no` int NOT NULL DEFAULT '1' COMMENT 'Resim sıra numarası',
  `ana_resim` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Ana resim mi?',
  `alt_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Resim alt metni',
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Resim açıklaması',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Context7: Aktif/Pasif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ilan_resim_sira` (`ilan_id`,`sira_no`),
  KEY `idx_ilan_resimleri_ilan_id` (`ilan_id`),
  KEY `idx_ilan_resimleri_ilan_sira` (`ilan_id`,`sira_no`),
  KEY `idx_ilan_resimleri_ana_resim` (`ana_resim`),
  KEY `idx_ilan_resimleri_aktiflik` (`aktiflik_durumu`),
  KEY `idx_ilan_resimleri_created_at` (`created_at`),
  CONSTRAINT `ilan_resimleri_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_takvim_sync`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_takvim_sync` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `platform` enum('airbnb','booking_com','google_calendar','calendar_dot_com') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'airbnb',
  `external_calendar_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_listing_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_sync_active` tinyint NOT NULL DEFAULT '0',
  `auto_sync` tinyint NOT NULL DEFAULT '1',
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `next_sync_at` timestamp NULL DEFAULT NULL,
  `sync_interval_minutes` int NOT NULL DEFAULT '60',
  `sync_settings` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_secret` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `senkron_durumu` enum('active','paused','failed','disconnected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disconnected',
  `last_error` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_error_at` timestamp NULL DEFAULT NULL,
  `sync_count` int NOT NULL DEFAULT '0',
  `error_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilan_taslaklar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilan_taslaklar` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `site_id` bigint unsigned DEFAULT NULL,
  `ilan_id` bigint unsigned DEFAULT NULL,
  `step` int NOT NULL DEFAULT '1',
  `ana_kategori_id` bigint unsigned DEFAULT NULL,
  `alt_kategori_id` bigint unsigned DEFAULT NULL,
  `yayin_tipi_id` bigint unsigned DEFAULT NULL,
  `baslik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `taslak_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '1: aktif, 0: kapali',
  `version` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ilan_taslaklar_danisman_id_index` (`user_id`),
  KEY `ilan_taslaklar_ana_kategori_id_alt_kategori_id_index` (`ana_kategori_id`,`alt_kategori_id`),
  KEY `ilan_taslaklar_updated_at_index` (`updated_at`),
  KEY `ilan_taslaklar_site_id_foreign` (`site_id`),
  KEY `ilan_taslaklar_ilan_id_foreign` (`ilan_id`),
  KEY `idx_taslak_recovery` (`user_id`,`site_id`,`taslak_durumu`,`updated_at`,`ilan_id`),
  CONSTRAINT `ilan_taslaklar_danisman_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ilan_taslaklar_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ilan_taslaklar_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `sites` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilanlar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilanlar` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `baslik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `fiyat` decimal(15,2) DEFAULT NULL,
  `fiyat_gosterim_modu` enum('exact','starting_from','on_request','hidden') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'exact',
  `baslangic_fiyati` decimal(15,2) DEFAULT NULL,
  `fiyat_notu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_price` decimal(15,2) DEFAULT NULL,
  `operating_expenses_annual` decimal(15,2) DEFAULT NULL,
  `investment_currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `investor_target_roi` decimal(5,2) DEFAULT NULL,
  `para_birimi` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `gunluk_fiyat` decimal(10,2) DEFAULT NULL COMMENT 'Günlük kiralama fiyatı',
  `haftalik_fiyat` decimal(10,2) DEFAULT NULL COMMENT 'Haftalık kiralama fiyatı',
  `aylik_fiyat` decimal(10,2) DEFAULT NULL COMMENT 'Aylık kiralama fiyatı',
  `sezonluk_fiyat` decimal(10,2) DEFAULT NULL COMMENT 'Sezonluk kiralama fiyatı',
  `min_konaklama` int DEFAULT NULL COMMENT 'Minimum konaklama günü',
  `max_misafir` int DEFAULT NULL COMMENT 'Maksimum misafir sayısı',
  `temizlik_ucreti` decimal(10,2) DEFAULT NULL COMMENT 'Temizlik ücreti',
  `sezon_baslangic` date DEFAULT NULL COMMENT 'Sezon başlangıç tarihi',
  `sezon_bitis` date DEFAULT NULL COMMENT 'Sezon bitiş tarihi',
  `elektrik_dahil` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Elektrik dahil mi?',
  `su_dahil` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Su dahil mi?',
  `havuz` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Havuz var mı?',
  `havuz_var` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Havuz var (legacy)',
  `havuz_turu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Havuz türü: Özel, Ortak, Infinity',
  `havuz_boyut` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Havuz boyutu (örn: 8x4m)',
  `havuz_derinlik` decimal(5,2) DEFAULT NULL COMMENT 'Havuz derinliği (m)',
  `yayin_durumu` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'taslak' COMMENT 'Canonical: taslak|beklemede|yayinda|arsiv|pasif',
  `completion_score` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'Context7 Score Split: Zorunlu alanların doluluk yüzdesi (0-100)',
  `rental_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `min_stay_nights` int unsigned NOT NULL DEFAULT '1',
  `max_stay_nights` int unsigned NOT NULL DEFAULT '30',
  `base_guest_count` int NOT NULL DEFAULT '1',
  `extra_guest_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `security_deposit` decimal(10,2) NOT NULL DEFAULT '0.00',
  `booking_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'instant',
  `cancellation_policy` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'flexible',
  `iptal_politikasi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'flexible',
  `checkin_time` time NOT NULL DEFAULT '14:00:00',
  `checkout_time` time NOT NULL DEFAULT '11:00:00',
  `deposit_amount` decimal(12,2) DEFAULT NULL,
  `rental_currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `crm_only` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'CRM dahili ilan (public değil)',
  `firsat_mühru` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Fırsat mühru - Konum skoru > 80 ve fiyat avantajlı',
  `user_id` bigint unsigned DEFAULT NULL,
  `ilan_sahibi_id` bigint unsigned DEFAULT NULL,
  `minimum_stay` int NOT NULL DEFAULT '1',
  `max_guests` int DEFAULT NULL,
  `check_in_time` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '14:00',
  `check_out_time` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '11:00',
  `price_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cleaning_fee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `site_id` bigint unsigned DEFAULT NULL,
  `danisman_id` bigint unsigned DEFAULT NULL,
  `ana_kategori_id` bigint unsigned DEFAULT NULL COMMENT 'Ana kategori ID',
  `alt_kategori_id` bigint unsigned DEFAULT NULL COMMENT 'Alt kategori ID',
  `yayin_tipi_id` bigint unsigned DEFAULT NULL COMMENT 'Yayın tipi ID',
  `il_id` bigint unsigned DEFAULT NULL,
  `ilce_id` bigint unsigned DEFAULT NULL,
  `mahalle_id` bigint unsigned DEFAULT NULL,
  `adres` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ada_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ada numarası',
  `parsel_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Parsel numarası',
  `ada_parsel` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ada/Parsel birleşik (legacy)',
  `imar_statusu` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'İmar statusu: İmarlı, İmarsız, Tarla',
  `kaks` decimal(5,2) DEFAULT NULL COMMENT 'Kat Alanı Kat Sayısı (Floor Area Ratio)',
  `taks` decimal(5,2) DEFAULT NULL COMMENT 'Taban Alanı Kat Sayısı (Building Coverage Ratio)',
  `gabari` decimal(5,2) DEFAULT NULL COMMENT 'Gabari (maksimum bina yüksekliği)',
  `alan_m2` decimal(12,2) DEFAULT NULL COMMENT 'Arsa alanı (m²)',
  `taban_alani` decimal(12,2) DEFAULT NULL COMMENT 'Taban alanı (m²)',
  `yola_cephe` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Yola cephesi var mı?',
  `yola_cephesi` decimal(8,2) DEFAULT NULL COMMENT 'Yola cephe mesafesi (m) - legacy',
  `altyapi_elektrik` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Elektrik altyapısı',
  `altyapi_su` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Su altyapısı',
  `altyapi_dogalgaz` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Doğalgaz altyapısı',
  `elektrik_altyapisi` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Legacy: Elektrik',
  `su_altyapisi` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Legacy: Su',
  `dogalgaz_altyapisi` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Legacy: Doğalgaz',
  `oda_sayisi` int DEFAULT NULL,
  `salon_sayisi` int DEFAULT NULL,
  `banyo_sayisi` int DEFAULT NULL,
  `kat` int DEFAULT NULL,
  `toplam_kat` int DEFAULT NULL,
  `brut_m2` decimal(10,2) DEFAULT NULL,
  `net_m2` decimal(10,2) DEFAULT NULL,
  `bina_yasi` year DEFAULT NULL,
  `isitma` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aidat` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `esyali` tinyint(1) NOT NULL DEFAULT '0',
  `ilan_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `referans_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dosya_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sahibinden_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emlakjet_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hepsiemlak_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zingat_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hurriyetemlak_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `portal_sync_status` json DEFAULT NULL,
  `portal_pricing` json DEFAULT NULL,
  `goruntulenme` int NOT NULL DEFAULT '0',
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `geometry_type` enum('point','polygon') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'point',
  `geometry` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `anahtar_kimde` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anahtar_turu` enum('mal_sahibi','danisman','kapici','emlakci','yonetici','diger') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anahtar_notlari` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `anahtar_ulasilabilirlik` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anahtar_ek_bilgi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isinma_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Doğalgaz, Kombi, Klima, Soba, Merkezi, Yerden Isıtma',
  `site_ozellikleri` json DEFAULT NULL COMMENT 'Güvenlik, Otopark, Havuz, Spor, Sauna, Oyun Alanı, Asansör',
  `isyeri_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ofis, Mağaza, Dükkan, Depo, Fabrika, Atölye, Showroom',
  `kira_bilgisi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Kira bilgileri',
  `ciro_bilgisi` decimal(15,2) DEFAULT NULL COMMENT 'Aylık tahmini ciro',
  `ruhsat_statusu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Var, Yok, Başvuruda',
  `personel_kapasitesi` int DEFAULT NULL COMMENT 'Personel kapasitesi',
  `isyeri_cephesi` int DEFAULT NULL COMMENT 'Cephe uzunluğu (metre)',
  `structured_data` json DEFAULT NULL COMMENT 'Structured data for template-based listings (yazlik_kiralama, etc.)',
  `structured_data_scope` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Template scope: yazlik_kiralama, konut_satilik, etc.',
  `schema_version` int NOT NULL DEFAULT '1' COMMENT 'Schema version for structured_data',
  `approved_at` timestamp NULL DEFAULT NULL COMMENT 'Approval timestamp (mühür)',
  `visibility_score` int unsigned NOT NULL DEFAULT '0',
  `seo_score` int NOT NULL DEFAULT '0',
  `quality_score` int NOT NULL DEFAULT '0',
  `seo_meta` json DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL COMMENT 'User ID who approved',
  `rapor_yolu` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'PDF rapor dosya yolu: storage/mühürlü_raporlar/Y/m/filename.pdf',
  `rapor_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Rapor dosya hash (YALIHAN_REPORT_{ID}_{HASH}.pdf)',
  `rapor_uretildi_at` timestamp NULL DEFAULT NULL COMMENT 'Rapor ne zaman oluşturuldu',
  `rapor_uretildi_by` bigint unsigned DEFAULT NULL COMMENT 'Raporu üreten kullanıcı ID',
  `rapor_gecersiz_mi` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Rapor geçersizleştirildi mi? (Silme yasak, sadece invalidate)',
  `rapor_gecersizlestirildi_at` timestamp NULL DEFAULT NULL COMMENT 'Rapor ne zaman geçersizleştirildi',
  `rapor_locale` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tr' COMMENT 'Rapor dili: tr, en',
  `rapor_surum` int unsigned NOT NULL DEFAULT '1' COMMENT 'Rapor versiyonu (her yeni üretimde +1)',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Context7: canonical active/inactive field',
  `one_cikan` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Context7: canonical featured field',
  `display_order` int NOT NULL DEFAULT '0' COMMENT 'Context7: canonical ordering field',
  `source_locale` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT 'tr',
  `yayin_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'satilik/kiralik',
  `kategori` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'konut/arsa_arazi/villa_isyeri',
  `alt_kategori` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'daire/villa/arsa',
  `il` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'City name (string for import)',
  `ilce` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'District name (string for import)',
  `mahalle` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Neighborhood name (string for import)',
  `external_ref` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'External system reference (e.g., eids:...)',
  `tapu_id` bigint unsigned DEFAULT NULL COMMENT 'FK to tapu_kayitlari',
  `metadata` json DEFAULT NULL COMMENT 'JSON metadata (MySQL json)',
  `kategori_id` bigint unsigned DEFAULT NULL,
  `proje_id` bigint unsigned DEFAULT NULL,
  `ilgili_kisi_id` bigint unsigned DEFAULT NULL,
  `ulke_id` bigint unsigned DEFAULT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TR',
  `premium_ilan` tinyint(1) NOT NULL DEFAULT '0',
  `lansman_fiyati` decimal(15,2) DEFAULT NULL,
  `lansman_bitis_tarihi` timestamp NULL DEFAULT NULL,
  `lansman_kotasi` int DEFAULT NULL,
  `parent_kategori_id` bigint unsigned DEFAULT NULL,
  `kisi_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ilanlar_slug_unique` (`slug`),
  UNIQUE KEY `ilanlar_ilan_no_unique` (`ilan_no`),
  UNIQUE KEY `ilanlar_referans_no_unique` (`referans_no`),
  KEY `ilanlar_yayin_durumu_created_at_index` (`yayin_durumu`,`created_at`),
  KEY `ilanlar_il_id_ilce_id_index` (`il_id`,`ilce_id`),
  KEY `ilanlar_kategori_id_yayin_durumu_index` (`yayin_durumu`),
  KEY `ilanlar_user_id_index` (`user_id`),
  KEY `idx_referans_no` (`referans_no`),
  KEY `idx_sahibinden_id` (`sahibinden_id`),
  KEY `idx_emlakjet_id` (`emlakjet_id`),
  KEY `idx_hepsiemlak_id` (`hepsiemlak_id`),
  KEY `idx_zingat_id` (`zingat_id`),
  KEY `ilanlar_danisman_id_index` (`danisman_id`),
  KEY `idx_ilanlar_ana_kategori` (`ana_kategori_id`),
  KEY `idx_ilanlar_alt_kategori` (`alt_kategori_id`),
  KEY `idx_ilanlar_yayin_tipi` (`yayin_tipi_id`),
  KEY `idx_ilanlar_kategori_combo` (`ana_kategori_id`,`alt_kategori_id`),
  KEY `idx_ilanlar_ada_parsel` (`ada_no`,`parsel_no`),
  KEY `idx_ilanlar_imar_statusu` (`imar_statusu`),
  KEY `idx_ilanlar_min_konaklama` (`min_konaklama`),
  KEY `idx_ilanlar_sezon` (`sezon_baslangic`,`sezon_bitis`),
  KEY `idx_ilanlar_structured_data_scope` (`structured_data_scope`),
  KEY `idx_ilanlar_approved_at` (`approved_at`),
  KEY `ilanlar_firsat_mühru_index` (`firsat_mühru`),
  KEY `idx_ilanlar_rapor_hash` (`rapor_hash`),
  KEY `idx_ilanlar_rapor_uretildi` (`rapor_uretildi_at`),
  KEY `idx_ilanlar_rapor_gecersiz` (`rapor_gecersiz_mi`),
  KEY `idx_ilanlar_rapor_aktif` (`rapor_gecersiz_mi`,`rapor_uretildi_at`),
  KEY `ilanlar_rapor_uretildi_by_foreign` (`rapor_uretildi_by`),
  KEY `ilanlar_external_ref_index` (`external_ref`),
  KEY `idx_ilanlar_yayin` (`yayin_durumu`),
  KEY `idx_ilanlar_il` (`il_id`),
  KEY `idx_ilanlar_ilce` (`ilce_id`),
  KEY `idx_ilanlar_deleted` (`deleted_at`),
  KEY `idx_ilanlar_tarih` (`created_at`),
  KEY `idx_ilanlar_owner` (`user_id`),
  KEY `idx_ilanlar_active_location` (`yayin_durumu`,`il_id`,`ilce_id`),
  KEY `ilanlar_ilan_sahibi_id_foreign` (`ilan_sahibi_id`),
  KEY `ilanlar_site_id_foreign` (`site_id`),
  KEY `ilanlar_visibility_score_index` (`visibility_score`),
  KEY `ilanlar_goruntulenme_index` (`goruntulenme`),
  KEY `ilanlar_updated_at_index` (`updated_at`),
  KEY `idx_ranking_composite` (`yayin_durumu`,`visibility_score`,`updated_at`),
  KEY `ilanlar_yayin_durumu_visibility_score_index` (`yayin_durumu`,`visibility_score`),
  KEY `ilanlar_country_code_index` (`country_code`),
  KEY `idx_ilanlar_country` (`country_code`),
  KEY `idx_ilanlar_currency` (`para_birimi`),
  KEY `idx_ilanlar_aktiflik` (`aktiflik_durumu`),
  KEY `idx_ilanlar_fiyat_gosterim_modu` (`fiyat_gosterim_modu`),
  KEY `ilanlar_created_by_foreign` (`created_by`),
  KEY `ilanlar_updated_by_foreign` (`updated_by`),
  KEY `ilanlar_parent_kategori_id_foreign` (`parent_kategori_id`),
  KEY `ilanlar_kisi_id_foreign` (`kisi_id`),
  CONSTRAINT `ilanlar_alt_kategori_id_foreign` FOREIGN KEY (`alt_kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ilanlar_ana_kategori_id_foreign` FOREIGN KEY (`ana_kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ilanlar_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ilanlar_danisman_id_foreign` FOREIGN KEY (`danisman_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ilanlar_ilan_sahibi_id_foreign` FOREIGN KEY (`ilan_sahibi_id`) REFERENCES `kisiler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ilanlar_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ilanlar_parent_kategori_id_foreign` FOREIGN KEY (`parent_kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ilanlar_rapor_uretildi_by_foreign` FOREIGN KEY (`rapor_uretildi_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ilanlar_site_id_foreign` FOREIGN KEY (`site_id`) REFERENCES `site_apartmanlar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ilanlar_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ilanlar_yayin_tipi_id_foreign` FOREIGN KEY (`yayin_tipi_id`) REFERENCES `eski_ilan_kategori_yayin_tipleri` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_yayin_durumu_canonical` CHECK ((`yayin_durumu` in (_utf8mb4'taslak',_utf8mb4'beklemede',_utf8mb4'yayinda',_utf8mb4'arsiv',_utf8mb4'pasif')))
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ilceler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ilceler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `il_id` bigint unsigned NOT NULL,
  `ilce_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ilce_kodu` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_id` bigint unsigned DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `display_order` int unsigned NOT NULL DEFAULT '0',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ilceler_il_id_ilce_adi_index` (`il_id`,`ilce_adi`),
  KEY `ilceler_il_id_aktiflik_durumu_index` (`il_id`,`aktiflik_durumu`),
  KEY `ilceler_api_id_index` (`api_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `iletim_kayitlari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `iletim_kayitlari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `alici_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'vip_yatirimci, danisman, ilan_sahibi',
  `alici_kimlik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Telefon, email veya user_id',
  `iletim_kanali` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'whatsapp, telegram, email',
  `icerik_sablonu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Kullanılan mesaj şablonu',
  `imzali_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Signed report URL (72 saat)',
  `basarili_mi` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'İletim başarılı mı?',
  `iletim_mührü` timestamp NULL DEFAULT NULL COMMENT 'Başarılı iletim zamanı (Context7: send_at yasak!)',
  `hata_detayi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Hata durumunda detay',
  `metadata` json DEFAULT NULL COMMENT 'API response ve extra bilgi',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ilan_iletim` (`ilan_id`,`iletim_mührü`),
  KEY `idx_alici_tipi` (`alici_tipi`),
  KEY `idx_basarili` (`basarili_mi`),
  KEY `idx_iletim_kanal` (`iletim_kanali`),
  CONSTRAINT `iletim_kayitlari_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `iller`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `iller` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `il_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `plaka_kodu` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `api_id` bigint unsigned DEFAULT NULL,
  `telefon_kodu` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `display_order` int unsigned NOT NULL DEFAULT '0',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Context7: canonical active field',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `iller_plaka_kodu_unique` (`plaka_kodu`),
  KEY `iller_il_adi_index` (`il_adi`),
  KEY `iller_api_id_index` (`api_id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
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
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kategori_yayin_tipi_field_dependencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kategori_yayin_tipi_field_dependencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kategori_slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `yayin_tipi_id` bigint unsigned DEFAULT NULL,
  `yayin_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `field_category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_options` json DEFAULT NULL,
  `field_unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `required` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` int NOT NULL DEFAULT '0',
  `ai_auto_fill` tinyint(1) NOT NULL DEFAULT '0',
  `ai_suggestion` tinyint(1) NOT NULL DEFAULT '0',
  `ai_prompt_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `searchable` tinyint(1) NOT NULL DEFAULT '0',
  `show_in_card` tinyint(1) NOT NULL DEFAULT '0',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_kytfd_unique` (`kategori_slug`,`yayin_tipi`,`field_slug`),
  KEY `idx_kytfd_lookup` (`kategori_slug`,`yayin_tipi`),
  KEY `kategori_yayin_tipi_field_dependencies_kategori_slug_index` (`kategori_slug`),
  KEY `kategori_yayin_tipi_field_dependencies_yayin_tipi_index` (`yayin_tipi`),
  KEY `kategori_yayin_tipi_field_dependencies_field_slug_index` (`field_slug`),
  KEY `kategori_yayin_tipi_field_dependencies_yayin_tipi_id_index` (`yayin_tipi_id`)
) ENGINE=InnoDB AUTO_INCREMENT=59 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kisi_etkilesimler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kisi_etkilesimler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `kisi_id` bigint unsigned NOT NULL,
  `kullanici_id` bigint unsigned NOT NULL,
  `tip` enum('telefon','email','sms','toplanti','whatsapp','not') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `etkilesim_tarihi` timestamp NOT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `iliskili_ilan_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kisi_etkilesimler_kisi_id_etkilesim_tarihi_index` (`kisi_id`,`etkilesim_tarihi`),
  KEY `kisi_etkilesimler_kullanici_id_index` (`kullanici_id`),
  KEY `kisi_etkilesimler_iliskili_ilan_id_foreign` (`iliskili_ilan_id`),
  CONSTRAINT `kisi_etkilesimler_iliskili_ilan_id_foreign` FOREIGN KEY (`iliskili_ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kisi_etkilesimler_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kisi_etkilesimler_kullanici_id_foreign` FOREIGN KEY (`kullanici_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kisiler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kisiler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `danisman_id` bigint unsigned DEFAULT NULL,
  `ad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `soyad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefon_2` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tc_kimlik` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adres` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `il_id` bigint unsigned DEFAULT NULL,
  `ilce_id` bigint unsigned DEFAULT NULL,
  `mahalle_id` bigint unsigned DEFAULT NULL,
  `meslek` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kisi_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Müşteri',
  `crm_surec_asamasi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'potansiyel' COMMENT 'Context7: CRM pipeline stage (Sıcak/Başarılı/vb)',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_contacted_at` timestamp NULL DEFAULT NULL COMMENT 'Son iletişim tarihi (CortexAnalytics için)',
  `user_id` bigint unsigned DEFAULT NULL,
  `ulke_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sesli_onay_verildi` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'Sesli görüşme onayı (Context7 boolean)',
  `referans_kisi_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kisiler_aktiflik_durumu_kisi_tipi_index` (`aktiflik_durumu`,`kisi_tipi`),
  KEY `kisiler_il_id_ilce_id_index` (`il_id`,`ilce_id`),
  KEY `kisiler_user_id_index` (`user_id`),
  KEY `kisiler_email_index` (`email`),
  KEY `kisiler_telefon_index` (`telefon`),
  KEY `kisiler_danisman_id_index` (`danisman_id`),
  KEY `kisiler_crm_surec_asamasi_index` (`crm_surec_asamasi`),
  KEY `kisiler_mahalle_id_index` (`mahalle_id`),
  KEY `idx_kisiler_aktiflik` (`aktiflik_durumu`),
  KEY `idx_kisiler_deleted` (`deleted_at`),
  KEY `idx_kisiler_active_consultant` (`aktiflik_durumu`,`danisman_id`),
  KEY `idx_kisiler_last_contacted` (`last_contacted_at`),
  KEY `kisiler_referans_kisi_id_foreign` (`referans_kisi_id`),
  CONSTRAINT `kisiler_danisman_id_foreign` FOREIGN KEY (`danisman_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `kisiler_referans_kisi_id_foreign` FOREIGN KEY (`referans_kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `komisyonlar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `komisyonlar` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `kisi_id` bigint unsigned NOT NULL,
  `danisman_id` bigint unsigned DEFAULT NULL,
  `satici_danisman_id` bigint unsigned DEFAULT NULL,
  `alici_danisman_id` bigint unsigned DEFAULT NULL,
  `komisyon_tipi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'satis, kiralama, danismanlik',
  `komisyon_orani` decimal(5,2) NOT NULL DEFAULT '0.00',
  `komisyon_tutari` decimal(15,2) NOT NULL DEFAULT '0.00',
  `satici_komisyon_orani` decimal(5,2) DEFAULT NULL,
  `alici_komisyon_orani` decimal(5,2) DEFAULT NULL,
  `satici_komisyon_tutari` decimal(15,2) DEFAULT NULL,
  `alici_komisyon_tutari` decimal(15,2) DEFAULT NULL,
  `ilan_fiyati` decimal(15,2) NOT NULL,
  `para_birimi` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `hesaplama_tarihi` date DEFAULT NULL,
  `odeme_tarihi` date DEFAULT NULL,
  `odeme_statusu` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hesaplandi' COMMENT 'Ödeme durumu (Context7 standard)',
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `komisyonlar_ilan_id_index` (`ilan_id`),
  KEY `komisyonlar_kisi_id_index` (`kisi_id`),
  KEY `komisyonlar_danisman_id_index` (`danisman_id`),
  KEY `komisyonlar_satici_danisman_id_index` (`satici_danisman_id`),
  KEY `komisyonlar_alici_danisman_id_index` (`alici_danisman_id`),
  KEY `komisyonlar_komisyon_tipi_index` (`komisyon_tipi`),
  KEY `komisyonlar_odeme_statusu_index` (`odeme_statusu`),
  KEY `komisyonlar_hesaplama_tarihi_index` (`hesaplama_tarihi`),
  CONSTRAINT `komisyonlar_alici_danisman_id_foreign` FOREIGN KEY (`alici_danisman_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `komisyonlar_danisman_id_foreign` FOREIGN KEY (`danisman_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `komisyonlar_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `komisyonlar_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `komisyonlar_satici_danisman_id_foreign` FOREIGN KEY (`satici_danisman_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `languages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `varsayilan_durumu` tinyint(1) NOT NULL DEFAULT '0',
  `is_rtl` tinyint(1) NOT NULL DEFAULT '0',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `languages_code_unique` (`code`),
  KEY `languages_is_active_index` (`aktiflik_durumu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lead_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned NOT NULL,
  `activity_type` enum('message_received','reply_sent','contacted_via_call','meeting_scheduled','property_shown','offer_made','closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `performed_by` bigint unsigned DEFAULT NULL COMMENT 'FK: users (agent)',
  `activity_date` timestamp NOT NULL,
  `duration_minutes` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_activities_performed_by_foreign` (`performed_by`),
  KEY `lead_activities_lead_id_index` (`lead_id`),
  KEY `lead_activities_activity_type_index` (`activity_type`),
  KEY `lead_activities_activity_date_index` (`activity_date`),
  CONSTRAINT `lead_activities_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_activities_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lead_embeddings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_embeddings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned DEFAULT NULL,
  `kisi_id` bigint unsigned DEFAULT NULL,
  `embedding` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Vector embedding data (JSON array)',
  `model_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nomic-embed-text',
  `dimensions` int NOT NULL DEFAULT '768',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '0=Pasif, 1=Aktif',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_embeddings_lead_id_index` (`lead_id`),
  KEY `lead_embeddings_kisi_id_index` (`kisi_id`),
  KEY `lead_embeddings_model_name_index` (`model_name`),
  CONSTRAINT `lead_embeddings_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lead_embeddings_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lead_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lead_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lead_id` bigint unsigned NOT NULL,
  `message_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_type` enum('incoming','outgoing') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform_message_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Platform-specific message ID',
  `intent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confidence` decimal(3,2) DEFAULT NULL,
  `entities` json DEFAULT NULL,
  `sentiment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'positive, negative, neutral',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_messages_lead_id_index` (`lead_id`),
  KEY `lead_messages_sentiment_index` (`sentiment`),
  KEY `lead_messages_created_at_index` (`created_at`),
  CONSTRAINT `lead_messages_lead_id_foreign` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Customer name (extracted from platform or message)',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `platform_user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'WhatsApp/Instagram/FB user ID',
  `platform_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'WhatsApp phone number',
  `platform_username` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Instagram handle / FB name',
  `interested_location_id` bigint unsigned DEFAULT NULL COMMENT 'FK: locations',
  `interested_property_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Property type: daire, arsa, villa, etc',
  `budget_min` bigint DEFAULT NULL COMMENT 'Min price (TRY)',
  `budget_max` bigint DEFAULT NULL COMMENT 'Max price (TRY)',
  `area_min` int DEFAULT NULL COMMENT 'Min area (m2)',
  `area_max` int DEFAULT NULL COMMENT 'Max area (m2)',
  `rooms` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Room count (3+1, 2+1, etc)',
  `intent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'buy, rent, price_check, info_request, etc',
  `confidence` decimal(3,2) NOT NULL DEFAULT '0.00' COMMENT 'NLP confidence 0.00-1.00',
  `quality_score` tinyint unsigned NOT NULL DEFAULT '0',
  `temperature` enum('cold','warm','hot') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cold',
  `entities` json DEFAULT NULL COMMENT 'Full NLP entities object',
  `first_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Original user message',
  `crm_durumu` tinyint NOT NULL DEFAULT '0' COMMENT '0:Yeni, 1:Ulasildi, 2:Nitelikli, 3:Kayip, 4:Kazanildi',
  `assigned_agent_id` bigint unsigned DEFAULT NULL COMMENT 'FK: users (sales agent)',
  `last_contacted_at` timestamp NULL DEFAULT NULL,
  `follow_up_date` timestamp NULL DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `tags` json DEFAULT NULL COMMENT 'Otomatik/Manuel etiketler (Yatırımcı, Acil vb.)',
  `aktif` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Context7: Active/inactive lead',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ulke_id` bigint unsigned DEFAULT NULL,
  `ilan_id` bigint unsigned DEFAULT NULL,
  `interaction_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'message',
  `sesli_onay_verildi` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'Sesli görüşme onayı (Context7 boolean)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `leads_platform_platform_user_id_unique` (`platform`,`platform_user_id`),
  KEY `leads_crm_durumu_index` (`crm_durumu`),
  KEY `leads_interested_location_id_index` (`interested_location_id`),
  KEY `leads_assigned_agent_id_index` (`assigned_agent_id`),
  KEY `leads_platform_index` (`platform`),
  KEY `leads_user_id_foreign` (`user_id`),
  KEY `leads_ilan_id_index` (`ilan_id`),
  KEY `idx_leads_country_crm` (`crm_durumu`,`aktif`),
  KEY `idx_leads_agent` (`assigned_agent_id`),
  CONSTRAINT `leads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledger_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hesap Adı (örn. Kasa, Gelirler)',
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hesap Tipi: asset, liability, equity, revenue, expense',
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `ulke_id` bigint unsigned DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0' COMMENT 'Sıralama (Context7: o.r.d.e.r → display_order)',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '1: Aktif, 0: Pasif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ledger_accounts_ulke_id_foreign` (`ulke_id`),
  CONSTRAINT `ledger_accounts_ulke_id_foreign` FOREIGN KEY (`ulke_id`) REFERENCES `ulkeler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledger_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger_balances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint unsigned NOT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `total_debit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_credit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `net_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `version` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ledger_balances_account_id_currency_unique` (`account_id`,`currency`),
  CONSTRAINT `ledger_balances_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `ledger_accounts` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledger_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_group_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Atomic işlem grubu ID (Borç/Alacak eşleşmesi)',
  `account_id` bigint unsigned NOT NULL,
  `debit_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Borç (Hesaba giren/Çıkan, hesap tipine göre)',
  `credit_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Alacak',
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fx_rate_locked` decimal(10,6) DEFAULT NULL COMMENT 'İşlem anındaki kur kuru kilidi',
  `base_amount` decimal(15,2) NOT NULL COMMENT 'TRY cinsinden temel tutar',
  `reference_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` bigint unsigned DEFAULT NULL,
  `sebep` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kaynak` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'system',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ledger_entries_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `ledger_entries_created_by_foreign` (`created_by`),
  KEY `ledger_entries_transaction_group_id_index` (`transaction_group_id`),
  KEY `idx_ledger_entries_balance_calc` (`account_id`,`currency`,`created_at`),
  CONSTRAINT `ledger_entries_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `ledger_accounts` (`id`),
  CONSTRAINT `ledger_entries_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledger_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger_transactions` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Atomic işlem grubu ID (transaction_group_id)',
  `idempotency_key` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Idempotency for ensuring single execution',
  `display_order` int NOT NULL DEFAULT '0' COMMENT 'Sıralama',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '0=inactive, 1=active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ledger_transactions_idempotency_key_unique` (`idempotency_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `listing_outcomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listing_outcomes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned NOT NULL,
  `outcome_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `days_to_close` smallint unsigned DEFAULT NULL,
  `final_price` decimal(15,2) DEFAULT NULL,
  `price_changes_count` smallint unsigned NOT NULL DEFAULT '0',
  `lead_count` smallint unsigned NOT NULL DEFAULT '0',
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `listing_outcomes_listing_id_outcome_type_index` (`listing_id`,`outcome_type`),
  KEY `listing_outcomes_listing_id_index` (`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `listing_search_projection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listing_search_projection` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(15,2) NOT NULL,
  `room_count` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `property_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `features` json DEFAULT NULL,
  `portfolio_health` int NOT NULL DEFAULT '0',
  `seo_score` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `listing_search_projection_listing_id_unique` (`listing_id`),
  KEY `listing_search_projection_city_index` (`city`),
  KEY `listing_search_projection_district_index` (`district`),
  KEY `listing_search_projection_price_index` (`price`),
  KEY `listing_search_projection_room_count_index` (`room_count`),
  KEY `listing_search_projection_property_type_index` (`property_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `listing_state_transitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listing_state_transitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `from_state` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_state` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aktan_id` bigint unsigned DEFAULT NULL COMMENT 'İşlemi yapan kullanıcı ID',
  `meta` json DEFAULT NULL COMMENT 'source, reason, ip, vb. bağlamsal veri',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `listing_state_transitions_ilan_id_created_at_index` (`ilan_id`,`created_at`),
  KEY `listing_state_transitions_to_state_created_at_index` (`to_state`,`created_at`),
  KEY `listing_state_transitions_ilan_id_index` (`ilan_id`),
  KEY `listing_state_transitions_aktan_id_index` (`aktan_id`),
  CONSTRAINT `listing_state_transitions_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `listing_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listing_translations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned NOT NULL,
  `locale` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `translated_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `translated_description` text COLLATE utf8mb4_unicode_ci,
  `translated_summary` text COLLATE utf8mb4_unicode_ci,
  `cevirme_durumu` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `translated_by` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ai',
  `review_required` tinyint(1) NOT NULL DEFAULT '0',
  `last_translated_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `listing_translations_listing_id_locale_unique` (`listing_id`,`locale`),
  KEY `listing_translations_locale_index` (`locale`),
  CONSTRAINT `listing_translations_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `listing_velocity_projections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listing_velocity_projections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned NOT NULL,
  `view_count` int NOT NULL DEFAULT '0',
  `favorite_count` int NOT NULL DEFAULT '0',
  `inquiry_count` int NOT NULL DEFAULT '0',
  `share_count` int NOT NULL DEFAULT '0',
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `activity_score` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `listing_velocity_projections_listing_id_unique` (`listing_id`),
  CONSTRAINT `listing_velocity_projections_listing_id_foreign` FOREIGN KEY (`listing_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mahalleler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mahalleler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilce_id` bigint unsigned NOT NULL,
  `mahalle_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mahalle_kodu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_id` bigint unsigned DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `display_order` int unsigned NOT NULL DEFAULT '0',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `posta_kodu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mahalleler_ilce_id_index` (`ilce_id`),
  KEY `mahalleler_mahalle_adi_index` (`mahalle_adi`),
  KEY `mahalleler_api_id_index` (`api_id`),
  CONSTRAINT `mahalleler_ilce_id_foreign` FOREIGN KEY (`ilce_id`) REFERENCES `ilceler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=575 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `market_listing_owner_clusters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `market_listing_owner_clusters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_cluster_id` bigint unsigned NOT NULL,
  `market_listing_id` bigint unsigned NOT NULL,
  `source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'sahibinden, hepsiemlak vs for validation',
  `external_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Platform listing id',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_cluster_listing_unique` (`owner_cluster_id`,`market_listing_id`),
  CONSTRAINT `market_listing_owner_clusters_owner_cluster_id_foreign` FOREIGN KEY (`owner_cluster_id`) REFERENCES `owner_cluster_projections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `market_trend_projections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `market_trend_projections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `district` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `property_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avg_price` decimal(20,2) NOT NULL,
  `median_price` decimal(20,2) NOT NULL,
  `price_change_7d` decimal(5,2) NOT NULL DEFAULT '0.00',
  `price_change_30d` decimal(5,2) NOT NULL DEFAULT '0.00',
  `demand_index` int NOT NULL DEFAULT '50',
  `listing_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_market_location` (`city`,`district`,`property_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `market_trends`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `market_trends` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ulke_id` bigint unsigned DEFAULT NULL COMMENT 'Ülke ID',
  `il_id` bigint unsigned DEFAULT NULL COMMENT 'İl ID',
  `ilce_id` bigint unsigned DEFAULT NULL COMMENT 'İlçe ID',
  `mahalle_id` bigint unsigned DEFAULT NULL COMMENT 'Mahalle ID',
  `kategori_id` bigint unsigned DEFAULT NULL COMMENT 'Kategori ID',
  `ortalama_m2_fiyat` decimal(15,2) NOT NULL COMMENT 'Birim fiyat ortalaması TL/m²',
  `min_m2_fiyat` decimal(15,2) NOT NULL COMMENT 'Minimum birim fiyat TL/m²',
  `max_m2_fiyat` decimal(15,2) NOT NULL COMMENT 'Maksimum birim fiyat TL/m²',
  `std_sapma_m2` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Standart sapma (volatilite)',
  `toplam_sorgu_sayisi` int NOT NULL DEFAULT '0' COMMENT 'Analiz için kullanılan ilan sayısı',
  `satilan_ilan_sayisi` int NOT NULL DEFAULT '0' COMMENT 'Son 30 günde satılan ilan',
  `trend_yonu` enum('yukselme','dusuş','stabil') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stabil' COMMENT 'Fiyat eğilimi: yukselme ↗️, dusuş ↘️, stabil →',
  `aylik_degisim_yuzde` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Son ay % değişim',
  `altı_aylik_degisim_yuzde` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Son 6 ay % değişim',
  `ortalama_satis_suresi_gun` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Ortalama satış süresi (gün)',
  `roi_yuzde` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Beklenen ROI %',
  `sealed_poi_data` json DEFAULT NULL COMMENT 'Mühürlü POI verileri (kalıcı referans)',
  `analiz_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Piyasa analizi yapılma tarihi',
  `son_guncelleme_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Son güncelleme tarihi',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `market_location_unique` (`ulke_id`,`il_id`,`ilce_id`,`mahalle_id`,`kategori_id`),
  KEY `market_trends_ulke_id_il_id_ilce_id_mahalle_id_kategori_id_index` (`ulke_id`,`il_id`,`ilce_id`,`mahalle_id`,`kategori_id`),
  KEY `market_trends_analiz_tarihi_index` (`analiz_tarihi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Pazar zekası: TKGM analizi, birim fiyat eğilimleri, ROI hesaplamaları';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `market_valuation_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `market_valuation_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `location_il` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_ilce` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_mahalle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Konut, Tarla, Arsa vs.',
  `m2` int NOT NULL,
  `median_m2_price` decimal(15,2) NOT NULL,
  `estimated_value` decimal(15,2) NOT NULL,
  `price_range_low` decimal(15,2) NOT NULL,
  `price_range_high` decimal(15,2) NOT NULL,
  `market_trend` decimal(5,2) NOT NULL COMMENT 'Percentage change in median price',
  `liquidity_score` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'LOW, MEDIUM, HIGH',
  `confidence_score` tinyint NOT NULL COMMENT '0-100 score on prediction health',
  `comparable_count` int NOT NULL,
  `is_active` tinyint NOT NULL DEFAULT '1' COMMENT '0=inactive, 1=active (Context7 boolean - PERMANENT STANDARD)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `master_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `master_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `feature_ids` json NOT NULL,
  `metadata` json DEFAULT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_templates_slug_unique` (`slug`),
  KEY `master_templates_aktiflik_durumu_index` (`aktiflik_durumu`),
  KEY `master_templates_display_order_index` (`display_order`),
  KEY `master_templates_created_by_index` (`created_by`),
  CONSTRAINT `master_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `matching_feedbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `matching_feedbacks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `talep_id` bigint unsigned NOT NULL,
  `ilan_id` bigint unsigned NOT NULL,
  `danisman_id` bigint unsigned NOT NULL,
  `feedback_tipi` enum('thumbs_up','thumbs_down','perfect_match','not_relevant') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Danışman feedback türü',
  `match_score` double(8,2) DEFAULT NULL COMMENT 'Eşleşme anındaki genel skor',
  `cortex_score_at_time` int NOT NULL COMMENT 'Feedback anındaki Cortex Score',
  `match_breakdown` json DEFAULT NULL COMMENT 'Skorların detayı (lokasyon, fiyat, vb.)',
  `yayin_durumu_log` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'Context7: yayin_durumu_log',
  `danisman_notu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Danışmanın ek açıklaması',
  `sonuc_olusturuldu` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Bu feedback sonucunda tıklama/görüşme oldu mu?',
  `sonuc_tarihi` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_feedback_per_match` (`talep_id`,`ilan_id`,`danisman_id`),
  KEY `matching_feedbacks_ilan_id_foreign` (`ilan_id`),
  KEY `matching_feedbacks_talep_id_feedback_tipi_index` (`talep_id`,`feedback_tipi`),
  KEY `matching_feedbacks_danisman_id_created_at_index` (`danisman_id`,`created_at`),
  KEY `matching_feedbacks_cortex_score_at_time_index` (`cortex_score_at_time`),
  CONSTRAINT `matching_feedbacks_danisman_id_foreign` FOREIGN KEY (`danisman_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matching_feedbacks_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `matching_feedbacks_talep_id_foreign` FOREIGN KEY (`talep_id`) REFERENCES `talepler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=308 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint unsigned NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `durum` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `opportunities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `opportunities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned DEFAULT NULL,
  `lead_id` bigint unsigned DEFAULT NULL COMMENT 'Lead/Kisi reference',
  `firsat_skoru` double(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Opportunity score (0-100)',
  `skor_detayi` json DEFAULT NULL COMMENT 'Score details JSON',
  `firsat_nedeni` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Opportunity reason/description',
  `firsat_durumu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yeni' COMMENT 'Opportunity phase: yeni,teklif,gorusme,tamamlandi',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Active/inactive state',
  `display_order` int NOT NULL DEFAULT '0' COMMENT 'Display ordering',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `opportunities_ilan_id_foreign` (`ilan_id`),
  KEY `opportunities_firsat_skoru_index` (`firsat_skoru`),
  KEY `opportunities_firsat_durumu_index` (`firsat_durumu`),
  KEY `opportunities_aktiflik_durumu_index` (`aktiflik_durumu`),
  CONSTRAINT `opportunities_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `optimizer_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `optimizer_suggestions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `suggestion_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_rule` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `current_value` text COLLATE utf8mb4_unicode_ci,
  `suggested_value` text COLLATE utf8mb4_unicode_ci,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `confidence` double(8,2) NOT NULL DEFAULT '0.00',
  `evidence` json NOT NULL,
  `oneri_durumu` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` bigint unsigned DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `optimizer_suggestions_oneri_durumu_index` (`oneri_durumu`),
  KEY `optimizer_suggestions_target_rule_index` (`target_rule`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `owner_acquisition_signals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `owner_acquisition_signals` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_cluster_id` bigint unsigned NOT NULL,
  `listing_count` int NOT NULL DEFAULT '0',
  `average_days_on_market` decimal(8,2) NOT NULL DEFAULT '0.00',
  `price_drop_count` int NOT NULL DEFAULT '0',
  `unsold_ratio` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Percentage of unsold listings 0-1',
  `demand_mismatch` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Mismatch with market demand',
  `price_gap_average` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Gap from market average',
  `recorded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `owner_acquisition_signals_owner_cluster_id_recorded_at_index` (`owner_cluster_id`,`recorded_at`),
  CONSTRAINT `owner_acquisition_signals_owner_cluster_id_foreign` FOREIGN KEY (`owner_cluster_id`) REFERENCES `owner_cluster_projections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `owner_cluster_projections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `owner_cluster_projections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_profile_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'UNKNOWN' COMMENT 'INDIVIDUAL_SELLER, INVESTOR, AGENT_LIKE, DEVELOPER, UNKNOWN',
  `listing_count` int NOT NULL DEFAULT '0',
  `average_price` decimal(15,2) NOT NULL DEFAULT '0.00',
  `average_days_online` decimal(8,2) NOT NULL DEFAULT '0.00',
  `price_drop_frequency` int NOT NULL DEFAULT '0',
  `listing_reactivation_pattern` json DEFAULT NULL,
  `owner_tier` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOW_PRIORITY' COMMENT 'PRIME_OWNER_TARGET, HIGH_VALUE_OWNER, MEDIUM_OPPORTUNITY, LOW_PRIORITY',
  `owner_acquisition_score` decimal(5,2) NOT NULL DEFAULT '0.00' COMMENT 'Score between 0-100',
  `cluster_signals` json DEFAULT NULL COMMENT 'Debug/tracing of cluster signals',
  `last_calculated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `owner_report_exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `owner_report_exports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint unsigned NOT NULL,
  `dosya_adi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dosya_yolu` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `format` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `islem_durumu` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `filtreler` json DEFAULT NULL,
  `tamamlanma_tarihi` timestamp NULL DEFAULT NULL,
  `hata_mesaji` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `owner_report_exports_owner_id_index` (`owner_id`),
  CONSTRAINT `owner_report_exports_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `owner_report_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `owner_report_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint unsigned NOT NULL,
  `ilan_id` bigint unsigned DEFAULT NULL,
  `periyot_tipi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periyot_degeri` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `toplam_gelir` decimal(15,2) NOT NULL DEFAULT '0.00',
  `toplam_gider` decimal(15,2) NOT NULL DEFAULT '0.00',
  `net_kar` decimal(15,2) NOT NULL DEFAULT '0.00',
  `doluluk_orani` decimal(5,2) NOT NULL DEFAULT '0.00',
  `rezervasyon_sayisi` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `owner_metrics_unique` (`owner_id`,`ilan_id`,`periyot_tipi`,`periyot_degeri`),
  KEY `owner_report_metrics_owner_id_index` (`owner_id`),
  KEY `owner_report_metrics_ilan_id_index` (`ilan_id`),
  CONSTRAINT `owner_report_metrics_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `owner_report_rows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `owner_report_rows` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint unsigned NOT NULL,
  `ilan_id` bigint unsigned NOT NULL,
  `kayit_tarihi` date NOT NULL,
  `islem_tipi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tutar` decimal(15,2) NOT NULL,
  `para_birimi` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `aciklama` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `owner_report_rows_owner_id_index` (`owner_id`),
  KEY `owner_report_rows_ilan_id_index` (`ilan_id`),
  KEY `owner_report_rows_kayit_tarihi_index` (`kayit_tarihi`),
  CONSTRAINT `owner_report_rows_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`),
  CONSTRAINT `owner_report_rows_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ozellik_kategorileri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ozellik_kategorileri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `parent_id` bigint unsigned DEFAULT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `display_order` int NOT NULL DEFAULT '0',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ozellik_kategorileri_slug_unique` (`slug`),
  KEY `ozellik_kategorileri_parent_id_aktiflik_durumu_index` (`parent_id`,`aktiflik_durumu`),
  KEY `ozellik_kategorileri_display_order_index` (`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ozellikler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ozellikler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori_id` bigint unsigned DEFAULT NULL,
  `veri_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
  `veri_secenekleri` json DEFAULT NULL,
  `birim` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `zorunlu` tinyint(1) NOT NULL DEFAULT '0',
  `arama_filtresi` tinyint(1) NOT NULL DEFAULT '0',
  `ilan_kartinda_goster` tinyint(1) NOT NULL DEFAULT '0',
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ozellikler_slug_unique` (`slug`),
  KEY `ozellikler_aktiflik_durumu_index` (`aktiflik_durumu`),
  KEY `ozellikler_kategori_id_index` (`kategori_id`),
  KEY `ozellikler_display_order_index` (`display_order`),
  CONSTRAINT `ozellikler_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `ozellik_kategorileri` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pipeline_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pipeline_runs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `run_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pipeline_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pipeline_durumu` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
  `mevcut_asama` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_payload` json DEFAULT NULL,
  `normalized_payload` json DEFAULT NULL,
  `final_output` json DEFAULT NULL,
  `karar_aksiyonu` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `karar_gerekcesi` text COLLATE utf8mb4_unicode_ci,
  `total_steps` int unsigned NOT NULL DEFAULT '0',
  `completed_steps` int unsigned NOT NULL DEFAULT '0',
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `triggered_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pipeline_runs_run_uuid_unique` (`run_uuid`),
  KEY `pipeline_runs_triggered_by_foreign` (`triggered_by`),
  KEY `pipeline_runs_pipeline_durumu_created_at_index` (`pipeline_durumu`,`created_at`),
  KEY `pipeline_runs_pipeline_type_index` (`pipeline_type`),
  KEY `pipeline_runs_module_index` (`module`),
  KEY `pipeline_runs_pipeline_durumu_index` (`pipeline_durumu`),
  CONSTRAINT `pipeline_runs_triggered_by_foreign` FOREIGN KEY (`triggered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pipeline_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pipeline_steps` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pipeline_run_id` bigint unsigned NOT NULL,
  `adim_adi` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shard_key` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `agent_adi` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adim_durumu` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `queue_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `input_payload` json DEFAULT NULL,
  `output_payload` json DEFAULT NULL,
  `hata_mesaji` text COLLATE utf8mb4_unicode_ci,
  `meta` json DEFAULT NULL,
  `deneme_sayisi` smallint unsigned NOT NULL DEFAULT '0',
  `duration_ms` int unsigned DEFAULT NULL,
  `worker_node` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pipeline_step_idempotent` (`pipeline_run_id`,`adim_adi`),
  UNIQUE KEY `pipeline_step_shard_idempotent` (`pipeline_run_id`,`adim_adi`,`shard_key`),
  KEY `pipeline_steps_adim_durumu_created_at_index` (`adim_durumu`,`created_at`),
  KEY `pipeline_steps_adim_adi_index` (`adim_adi`),
  CONSTRAINT `pipeline_steps_pipeline_run_id_foreign` FOREIGN KEY (`pipeline_run_id`) REFERENCES `pipeline_runs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `point_of_interests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `point_of_interests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `poi_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'POI adı (İlkokul, Hastane, vb)',
  `poi_turu` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'POI türü: school, hospital, market, etc.',
  `poi_kategorisi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'POI kategorisi: Eğitim, Sağlık, Ticaret, vb.',
  `lat` decimal(10,8) NOT NULL COMMENT 'Enlem',
  `lng` decimal(11,8) NOT NULL COMMENT 'Boylam',
  `google_place_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL COMMENT 'Puan (0.0-5.0)',
  `ek_veri` json DEFAULT NULL COMMENT 'Ek metadata (JSON)',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '0=Pasif, 1=Aktif',
  `display_order` int NOT NULL DEFAULT '0' COMMENT 'Gösterim sırası',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `point_of_interests_google_place_id_unique` (`google_place_id`),
  KEY `point_of_interests_lat_lng_index` (`lat`,`lng`),
  KEY `point_of_interests_poi_turu_index` (`poi_turu`),
  KEY `point_of_interests_aktiflik_durumu_index` (`aktiflik_durumu`),
  KEY `point_of_interests_display_order_index` (`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=195 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prediction_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prediction_snapshots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned NOT NULL,
  `pricing_position` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pricing_score` smallint unsigned NOT NULL DEFAULT '0',
  `demand_score` smallint unsigned NOT NULL DEFAULT '0',
  `demand_label` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `confidence_score` smallint unsigned NOT NULL DEFAULT '0',
  `confidence_label` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opportunity_action` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opportunity_score` smallint unsigned NOT NULL DEFAULT '0',
  `priority_score` smallint unsigned NOT NULL DEFAULT '0',
  `priority_label` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_price` decimal(15,2) DEFAULT NULL,
  `benchmark_price` decimal(15,2) DEFAULT NULL,
  `snapshot_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prediction_snapshots_listing_id_snapshot_at_index` (`listing_id`,`snapshot_at`),
  KEY `prediction_snapshots_listing_id_index` (`listing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proj_agent_performance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proj_agent_performance` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `danisman_id` bigint unsigned NOT NULL,
  `donem` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'YYYY-MM format',
  `toplam_ilan_sayisi` int NOT NULL DEFAULT '0',
  `kapatilan_islem_sayisi` int NOT NULL DEFAULT '0',
  `yeni_kisi_sayisi` int NOT NULL DEFAULT '0',
  `aktivite_sayisi` int NOT NULL DEFAULT '0',
  `basari_puani` decimal(5,2) NOT NULL DEFAULT '0.00',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proj_agent_performance_danisman_id_donem_unique` (`danisman_id`,`donem`),
  KEY `proj_agent_performance_donem_index` (`donem`),
  KEY `proj_agent_performance_basari_puani_index` (`basari_puani`),
  CONSTRAINT `proj_agent_performance_danisman_id_foreign` FOREIGN KEY (`danisman_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proj_dlq`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proj_dlq` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `stack_trace` text COLLATE utf8mb4_unicode_ci,
  `attempts` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proj_dlq_event_id_index` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proj_kpi_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proj_kpi_snapshots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tarih` date NOT NULL,
  `danisman_id` bigint unsigned DEFAULT NULL COMMENT 'Null ise genel şirket KPI',
  `toplam_portfoy_degeri` decimal(20,2) NOT NULL DEFAULT '0.00',
  `aktif_ilan_sayisi` int NOT NULL DEFAULT '0',
  `yeni_talep_sayisi_7_gun` int NOT NULL DEFAULT '0',
  `ortalama_satista_kalma_suresi` int NOT NULL DEFAULT '0' COMMENT 'Gün cinsinden',
  `cevirim_orani` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `proj_kpi_snapshots_tarih_danisman_id_unique` (`tarih`,`danisman_id`),
  KEY `proj_kpi_snapshots_danisman_id_foreign` (`danisman_id`),
  KEY `proj_kpi_snapshots_tarih_index` (`tarih`),
  CONSTRAINT `proj_kpi_snapshots_danisman_id_foreign` FOREIGN KEY (`danisman_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proj_listings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proj_listings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `listing_id` bigint unsigned DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(15,2) DEFAULT NULL,
  `para_birimi_id` bigint unsigned DEFAULT NULL,
  `sahip_id` bigint unsigned DEFAULT NULL,
  `category_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `son_hareket_tarihi` timestamp NULL DEFAULT NULL,
  `ilan_yasi_gun` int NOT NULL DEFAULT '0',
  `bayat_mi` tinyint(1) NOT NULL DEFAULT '0',
  `ilan_id` bigint unsigned NOT NULL,
  `baslik` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `yayin_durumu` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Aktif' COMMENT 'Context7: Taslak|Aktif|Pasif|Beklemede',
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT 'Context7: 0=Pasif, 1=Aktif',
  `fiyat` decimal(15,2) DEFAULT NULL,
  `para_birimi` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `danisman_id` bigint unsigned DEFAULT NULL,
  `kategori_id` bigint unsigned DEFAULT NULL,
  `il_id` bigint unsigned DEFAULT NULL,
  `ilce_id` bigint unsigned DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL COMMENT 'Context7: lat (not latitude)',
  `lng` decimal(11,8) DEFAULT NULL COMMENT 'Context7: lng (not longitude)',
  `goruntulenme_sayisi` int NOT NULL DEFAULT '0',
  `favoriye_alinma_sayisi` int NOT NULL DEFAULT '0',
  `gecen_gun_sayisi` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `proj_listings_ilan_id_foreign` (`ilan_id`),
  KEY `proj_listings_ilce_id_foreign` (`ilce_id`),
  KEY `proj_listings_yayin_durumu_index` (`yayin_durumu`),
  KEY `proj_listings_aktiflik_durumu_index` (`aktiflik_durumu`),
  KEY `proj_listings_danisman_id_index` (`danisman_id`),
  KEY `proj_listings_kategori_id_index` (`kategori_id`),
  KEY `proj_listings_il_id_ilce_id_index` (`il_id`,`ilce_id`),
  KEY `proj_listings_lat_lng_index` (`lat`,`lng`),
  CONSTRAINT `proj_listings_danisman_id_foreign` FOREIGN KEY (`danisman_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proj_listings_il_id_foreign` FOREIGN KEY (`il_id`) REFERENCES `iller` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proj_listings_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `proj_listings_ilce_id_foreign` FOREIGN KEY (`ilce_id`) REFERENCES `ilceler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `proj_listings_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_health_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_health_snapshots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `overall_health_score` decimal(5,2) NOT NULL,
  `context7_compliance_score` decimal(5,2) NOT NULL,
  `code_quality_score` decimal(5,2) NOT NULL,
  `test_coverage_score` decimal(5,2) DEFAULT NULL,
  `performance_score` decimal(5,2) DEFAULT NULL,
  `active_violations` int NOT NULL DEFAULT '0',
  `critical_issues` int NOT NULL DEFAULT '0',
  `total_files` int NOT NULL DEFAULT '0',
  `total_lines` int NOT NULL DEFAULT '0',
  `health_details` json DEFAULT NULL,
  `recommendations` json DEFAULT NULL,
  `snapshot_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_health_snapshots_snapshot_at_index` (`snapshot_at`),
  KEY `project_health_snapshots_overall_health_score_index` (`overall_health_score`),
  KEY `project_health_snapshots_critical_issues_snapshot_at_index` (`critical_issues`,`snapshot_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `projeler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `projeler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `proje_durumu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `oncelik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Orta',
  `baslangic_tarihi` date DEFAULT NULL,
  `bitis_tarihi` date DEFAULT NULL,
  `takim_lideri_id` bigint unsigned DEFAULT NULL,
  `butce` decimal(15,2) DEFAULT NULL,
  `tamamlanma_yuzdesi` int NOT NULL DEFAULT '0',
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `admin_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projeler_slug_unique` (`slug`),
  KEY `projeler_proje_durumu_index` (`proje_durumu`),
  KEY `projeler_oncelik_index` (`oncelik`),
  KEY `projeler_takim_lideri_id_index` (`takim_lideri_id`),
  KEY `projeler_admin_id_foreign` (`admin_id`),
  CONSTRAINT `projeler_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projeler_takim_lideri_id_foreign` FOREIGN KEY (`takim_lideri_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_availabilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_availabilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `date` date NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `block_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_system` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_ref` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reservation_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `property_availabilities_property_id_date_unique` (`property_id`,`date`),
  KEY `property_availabilities_reservation_id_foreign` (`reservation_id`),
  KEY `property_availabilities_property_id_is_available_index` (`property_id`,`is_available`),
  CONSTRAINT `property_availabilities_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `property_availabilities_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `property_reservations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_calendar_feeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_calendar_feeds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'airbnb',
  `ical_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sync_enabled` tinyint(1) NOT NULL DEFAULT '1',
  `sync_frequency_minutes` int unsigned NOT NULL DEFAULT '30',
  `last_synced_at` datetime DEFAULT NULL,
  `last_sync_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_sync_error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `property_calendar_feeds_property_id_provider_unique` (`property_id`,`provider`),
  CONSTRAINT `property_calendar_feeds_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_config_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_config_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version_id` bigint unsigned NOT NULL,
  `islem_tipi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `islem_yapan_id` bigint unsigned DEFAULT NULL,
  `ek_bilgiler` json DEFAULT NULL,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `property_config_audit_logs_version_id_foreign` (`version_id`),
  KEY `property_config_audit_logs_islem_yapan_id_foreign` (`islem_yapan_id`),
  KEY `property_config_audit_logs_islem_tipi_index` (`islem_tipi`),
  KEY `property_config_audit_logs_olusturma_tarihi_index` (`olusturma_tarihi`),
  CONSTRAINT `property_config_audit_logs_islem_yapan_id_foreign` FOREIGN KEY (`islem_yapan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `property_config_audit_logs_version_id_foreign` FOREIGN KEY (`version_id`) REFERENCES `property_config_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_config_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_config_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SYSTEM',
  `version_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA256 signature of the full configuration state',
  `governance_state` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT' COMMENT 'DRAFT, REVIEW, APPROVED, ACTIVE, ARCHIVED',
  `risk_score` int DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'User-friendly description of this version',
  `snapshot_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Full configuration state snapshot (JSON)',
  `signature` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'HMAC or Hash for data integrity verification',
  `is_immutable` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'If true, this version cannot be modified after activation',
  `is_approved_by_dual_control` tinyint NOT NULL DEFAULT '0',
  `created_by` bigint unsigned DEFAULT NULL COMMENT 'User ID who promoted this version',
  `parent_version_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Previous version hash',
  `applied_at` timestamp NULL DEFAULT NULL COMMENT 'When this version became active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `active_flag` tinyint(1) GENERATED ALWAYS AS ((case when (`governance_state` = _utf8mb4'ACTIVE') then 1 else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `property_config_versions_version_hash_unique` (`version_hash`),
  UNIQUE KEY `unique_tenant_active_version` (`tenant_id`,`active_flag`),
  KEY `property_config_versions_version_hash_index` (`version_hash`),
  KEY `property_config_versions_created_by_index` (`created_by`),
  KEY `property_config_versions_parent_version_hash_index` (`parent_version_hash`),
  KEY `property_config_versions_governance_state_index` (`governance_state`),
  KEY `property_config_versions_signature_index` (`signature`),
  KEY `property_config_versions_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `expense_item_id` bigint unsigned NOT NULL,
  `miktar` decimal(15,2) NOT NULL,
  `para_birimi` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `fatura_tarihi` date DEFAULT NULL,
  `donem_tarihi` date DEFAULT NULL,
  `son_odeme_tarihi` date DEFAULT NULL,
  `odeme_tarihi` date DEFAULT NULL,
  `odeme_durumu` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0: Bekliyor, 1: Ödendi, 2: Gecikmede',
  `belge_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned DEFAULT NULL,
  `ulke_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_expenses_ilan_id_foreign` (`ilan_id`),
  KEY `property_expenses_expense_item_id_foreign` (`expense_item_id`),
  KEY `property_expenses_user_id_foreign` (`user_id`),
  CONSTRAINT `property_expenses_expense_item_id_foreign` FOREIGN KEY (`expense_item_id`) REFERENCES `expense_items` (`id`),
  CONSTRAINT `property_expenses_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
  CONSTRAINT `property_expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_growth_projections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_growth_projections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `yearly_growth_rate` decimal(5,4) NOT NULL,
  `projection_years` int NOT NULL DEFAULT '5',
  `projection_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_growth_projections_property_id_aktiflik_durumu_index` (`property_id`,`aktiflik_durumu`),
  CONSTRAINT `property_growth_projections_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_reservations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_reservations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `nights` int unsigned NOT NULL,
  `guest_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guest_phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guest_count` int unsigned DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `reservation_state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `finansal_durum` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `depozito_tutari` decimal(12,2) DEFAULT NULL,
  `depozito_durumu` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `locked_nightly_rate` decimal(12,2) DEFAULT NULL,
  `booking_currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `booking_fx_rate` decimal(15,6) DEFAULT NULL,
  `booking_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TR',
  `total_amount` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `created_by_user_id` bigint unsigned DEFAULT NULL,
  `ulke_id` bigint unsigned DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_reservations_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `property_reservations_property_id_start_date_index` (`property_id`,`start_date`),
  KEY `property_reservations_property_id_reservation_state_index` (`property_id`,`reservation_state`),
  KEY `idx_reservations_property_dates` (`property_id`,`start_date`,`end_date`),
  KEY `idx_reservations_dates` (`start_date`,`end_date`),
  KEY `idx_reservations_finansal` (`finansal_durum`),
  CONSTRAINT `property_reservations_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `property_reservations_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_seasonal_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_seasonal_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `property_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `nightly_rate` int unsigned NOT NULL,
  `weekly_rate` int unsigned DEFAULT NULL,
  `monthly_rate` int unsigned DEFAULT NULL,
  `min_stay_override` int unsigned DEFAULT NULL,
  `season_label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_seasonal_rates_property_id_start_date_end_date_index` (`property_id`,`start_date`,`end_date`),
  CONSTRAINT `property_seasonal_rates_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `property_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `property_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `expense_item_id` bigint unsigned NOT NULL,
  `abone_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sayac_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `servis_saglayici` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sozlesme_tarihi` date DEFAULT NULL,
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `aktiflik_durumu` tinyint unsigned NOT NULL DEFAULT '1',
  `ulke_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `property_subscriptions_ilan_id_foreign` (`ilan_id`),
  KEY `property_subscriptions_expense_item_id_foreign` (`expense_item_id`),
  CONSTRAINT `property_subscriptions_expense_item_id_foreign` FOREIGN KEY (`expense_item_id`) REFERENCES `expense_items` (`id`),
  CONSTRAINT `property_subscriptions_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ref_sequences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ref_sequences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sequence_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique key for sequence type',
  `last_sequence` int NOT NULL DEFAULT '0' COMMENT 'Last used sequence number',
  `year` int NOT NULL COMMENT 'Year for yearly reset',
  `yayin_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Publication type (kiralama/satilik etc.)',
  `lokasyon_kodu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Location code',
  `kategori_kodu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Category code',
  `last_used_at` timestamp NULL DEFAULT NULL COMMENT 'Last time sequence was used',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ref_sequences_sequence_key_unique` (`sequence_key`),
  KEY `ref_sequences_year_index` (`year`),
  KEY `idx_seq_lookup` (`yayin_tipi`,`lokasyon_kodu`,`kategori_kodu`,`year`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rental_ev_kartlari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_ev_kartlari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned DEFAULT NULL COMMENT 'İlişkili ilan — nullable (bağımsız da olabilir)',
  `baslik` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adres` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `su_abone_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `elektrik_abone_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `internet_abone_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `abonelik_su` decimal(10,2) DEFAULT NULL COMMENT 'Aylık tahmini su tutarı',
  `abonelik_elektrik` decimal(10,2) DEFAULT NULL COMMENT 'Aylık tahmini elektrik tutarı',
  `abonelik_dogalgaz` decimal(10,2) DEFAULT NULL COMMENT 'Aylık tahmini doğalgaz tutarı',
  `aidat` decimal(10,2) DEFAULT NULL COMMENT 'Aylık aidat tutarı',
  `depozito_tutari` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Alınan depozito — gelir olarak işlenmez',
  `para_birimi` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `depozito_alinma_tarihi` date DEFAULT NULL,
  `depozito_iade_tarihi` date DEFAULT NULL,
  `aciklama` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notlar` text COLLATE utf8mb4_unicode_ci,
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rental_ev_kartlari_ilan_id_index` (`ilan_id`),
  KEY `rental_ev_kartlari_created_by_index` (`created_by`),
  CONSTRAINT `rental_ev_kartlari_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rental_gelir_kalemleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_gelir_kalemleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ev_karti_id` bigint unsigned NOT NULL,
  `kalem_turu` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0=KIRA, 1=DEPOZITO, 2=EK_GELIR',
  `tutar` decimal(12,2) unsigned NOT NULL,
  `donem_yil` smallint unsigned DEFAULT NULL,
  `donem_ay` tinyint unsigned DEFAULT NULL,
  `odeme_tarihi` date DEFAULT NULL,
  `para_birimi` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `gelir_tarihi` date NOT NULL,
  `gelir_tipi` enum('kira_bedeli','temizlik_ucreti','erken_rezervasyon','diger') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'kira_bedeli',
  `aciklama` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rental_gelir_kalemleri_ev_karti_id_gelir_tarihi_gelir_tipi_index` (`ev_karti_id`,`gelir_tarihi`,`gelir_tipi`),
  KEY `rental_gelir_kalemleri_ev_karti_id_index` (`ev_karti_id`),
  CONSTRAINT `rental_gelir_kalemleri_ev_karti_id_foreign` FOREIGN KEY (`ev_karti_id`) REFERENCES `rental_ev_kartlari` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rental_gider_kalemleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rental_gider_kalemleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ev_karti_id` bigint unsigned NOT NULL,
  `kalem_turu` tinyint unsigned NOT NULL DEFAULT '6' COMMENT '0=ELEKTRIK, 1=SU, 2=TEMIZLIK, 3=HAVUZ, 4=BAHCIVAN, 5=BAKIM, 6=DIGER',
  `tutar` decimal(12,2) unsigned NOT NULL,
  `donem_yil` smallint unsigned DEFAULT NULL,
  `donem_ay` tinyint unsigned DEFAULT NULL,
  `odeme_tarihi` date DEFAULT NULL,
  `para_birimi` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `gider_tarihi` date NOT NULL,
  `gider_kategorisi` enum('komisyon','bakim_onarim','aidat','vergi','temizlik','abonelik','diger') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'diger',
  `odeyen_taraf` enum('mal_sahibi','kiracı','ofis') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mal_sahibi' COMMENT 'Fiilen ödemeyi yapan taraf',
  `maliyeti_tasayan_taraf` enum('mal_sahibi','kiracı','ofis') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mal_sahibi' COMMENT 'Ekonomik maliyeti kim üstleniyor',
  `aciklama` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tedarikci` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rental_gider_kalemleri_ev_karti_id_foreign` (`ev_karti_id`),
  CONSTRAINT `rental_gider_kalemleri_ev_karti_id_foreign` FOREIGN KEY (`ev_karti_id`) REFERENCES `rental_ev_kartlari` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'web',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rule_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rule_definitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SYSTEM',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Descriptive name of the rule',
  `rule_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'FEATURE_ASSIGNMENT, TEMPLATE_OVERRIDE, etc.',
  `rule_config` json NOT NULL COMMENT 'JSON blob defining conditions and actions',
  `priority` int NOT NULL DEFAULT '100' COMMENT 'Lower executes first',
  `version_id` bigint unsigned NOT NULL COMMENT 'Belongs to which config version',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rule_definitions_version_id_priority_index` (`version_id`,`priority`),
  KEY `rule_definitions_rule_type_is_active_index` (`rule_type`,`aktiflik_durumu`),
  KEY `rule_definitions_rule_type_index` (`rule_type`),
  KEY `rule_definitions_version_id_index` (`version_id`),
  KEY `rule_definitions_tenant_id_index` (`tenant_id`),
  CONSTRAINT `rule_definitions_version_id_foreign` FOREIGN KEY (`version_id`) REFERENCES `property_config_versions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saved_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_searches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criteria` json NOT NULL,
  `notification_frequency` enum('instant','daily','off') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'instant',
  `last_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saved_searches_user_id_foreign` (`user_id`),
  CONSTRAINT `saved_searches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `search_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `search_analytics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `query` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unified',
  `filters` json DEFAULT NULL,
  `results_count` int NOT NULL,
  `response_time` double(8,2) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `searched_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `search_analytics_query_searched_at_index` (`query`,`searched_at`),
  KEY `search_analytics_type_success_index` (`type`,`success`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_group_key_index` (`group`,`key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `site_apartmanlar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_apartmanlar` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Site/Apartman adı',
  `tip` enum('site','apartman') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'site',
  `adres` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `il_id` bigint unsigned DEFAULT NULL,
  `ilce_id` bigint unsigned DEFAULT NULL,
  `mahalle_id` bigint unsigned DEFAULT NULL,
  `yonetici_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `yonetici_telefon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `yonetici_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kapici_telefon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `toplam_daire_sayisi` int DEFAULT NULL,
  `kat_sayisi` int DEFAULT NULL,
  `asansor_sayisi` int DEFAULT NULL,
  `otopark_statusu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sosyal_tesisler` json DEFAULT NULL,
  `guvenlik_sistemi` json DEFAULT NULL,
  `aidat_tutari` decimal(10,2) DEFAULT NULL,
  `aidat_para_birimi` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
  `aidat_periyodu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'aylık, 3 aylık, 6 aylık, yıllık',
  `yapim_yili` year DEFAULT NULL,
  `yapi_tarzi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isitma_sistemi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `site_apartmanlar_ilce_id_foreign` (`ilce_id`),
  KEY `site_apartmanlar_mahalle_id_foreign` (`mahalle_id`),
  KEY `site_apartmanlar_name_index` (`name`),
  KEY `site_apartmanlar_il_id_ilce_id_mahalle_id_index` (`il_id`,`ilce_id`,`mahalle_id`),
  CONSTRAINT `site_apartmanlar_il_id_foreign` FOREIGN KEY (`il_id`) REFERENCES `iller` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_apartmanlar_ilce_id_foreign` FOREIGN KEY (`ilce_id`) REFERENCES `ilceler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `site_apartmanlar_mahalle_id_foreign` FOREIGN KEY (`mahalle_id`) REFERENCES `mahalleler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `site_ozellikleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_ozellikleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Özellik adı (örn: Güvenlik, Otopark)',
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'URL-friendly slug',
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'amenity' COMMENT 'Özellik tipi: amenity, security, facility',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Özellik açıklaması',
  `display_order` int NOT NULL DEFAULT '0' COMMENT 'Sıralama',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Context7: Aktif/Pasif',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_ozellikleri_slug_unique` (`slug`),
  KEY `idx_site_ozellikleri_type` (`type`),
  KEY `idx_site_ozellikleri_aktiflik` (`aktiflik_durumu`),
  KEY `idx_site_ozellikleri_display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `blok_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adres` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `il_id` bigint unsigned NOT NULL,
  `ilce_id` bigint unsigned NOT NULL,
  `mahalle_id` bigint unsigned DEFAULT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sites_ilce_id_foreign` (`ilce_id`),
  KEY `sites_mahalle_id_foreign` (`mahalle_id`),
  KEY `sites_created_by_foreign` (`created_by`),
  KEY `sites_il_id_ilce_id_name_index` (`il_id`,`ilce_id`,`name`),
  KEY `sites_aktiflik_durumu_name_index` (`aktiflik_durumu`,`name`),
  KEY `sites_name_index` (`name`),
  CONSTRAINT `sites_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sites_il_id_foreign` FOREIGN KEY (`il_id`) REFERENCES `iller` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sites_ilce_id_foreign` FOREIGN KEY (`ilce_id`) REFERENCES `ilceler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sites_mahalle_id_foreign` FOREIGN KEY (`mahalle_id`) REFERENCES `mahalleler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `takim_uyeleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `takim_uyeleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `lokasyon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `rol` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'danisman',
  `pozisyon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departman` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `performans_skoru` int NOT NULL DEFAULT '0',
  `ise_baslama_tarihi` date DEFAULT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `takim_uyeleri_aktiflik_durumu_departman_index` (`aktiflik_durumu`,`departman`),
  KEY `takim_uyeleri_user_id_index` (`user_id`),
  KEY `takim_uyeleri_performans_skoru_index` (`performans_skoru`),
  KEY `takim_uyeleri_rol_index` (`rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `talep_match_projection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `talep_match_projection` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `talep_id` bigint unsigned NOT NULL,
  `buyer_id` bigint unsigned NOT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `min_price` decimal(15,2) DEFAULT NULL,
  `max_price` decimal(15,2) DEFAULT NULL,
  `room_count` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `features` json DEFAULT NULL,
  `property_type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_intent_level` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `talep_match_projection_talep_id_unique` (`talep_id`),
  KEY `talep_match_projection_buyer_id_index` (`buyer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `talepler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `talepler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `baslik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `kisi_id` bigint unsigned NOT NULL,
  `danisman_id` bigint unsigned DEFAULT NULL,
  `talep_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `emlak_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `min_fiyat` decimal(15,2) DEFAULT NULL,
  `max_fiyat` decimal(15,2) DEFAULT NULL,
  `il_id` bigint unsigned DEFAULT NULL,
  `ilce_id` bigint unsigned DEFAULT NULL,
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `talep_durumu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif',
  `one_cikan` tinyint(1) NOT NULL DEFAULT '0',
  `oncelik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Orta',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `alt_kategori_id` bigint unsigned DEFAULT NULL,
  `mahalle_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `talepler_ilce_id_foreign` (`ilce_id`),
  KEY `talepler_talep_durumu_index` (`talep_durumu`),
  KEY `talepler_talep_tipi_index` (`talep_tipi`),
  KEY `talepler_kisi_id_index` (`kisi_id`),
  KEY `talepler_danisman_id_index` (`danisman_id`),
  KEY `talepler_alt_kategori_id_foreign` (`alt_kategori_id`),
  KEY `talepler_mahalle_id_foreign` (`mahalle_id`),
  KEY `idx_talepler_durumu` (`talep_durumu`),
  KEY `idx_talepler_il` (`il_id`),
  KEY `idx_talepler_deleted` (`deleted_at`),
  KEY `idx_talepler_tarih` (`created_at`),
  CONSTRAINT `talepler_alt_kategori_id_foreign` FOREIGN KEY (`alt_kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE SET NULL,
  CONSTRAINT `talepler_danisman_id_foreign` FOREIGN KEY (`danisman_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `talepler_il_id_foreign` FOREIGN KEY (`il_id`) REFERENCES `iller` (`id`) ON DELETE SET NULL,
  CONSTRAINT `talepler_ilce_id_foreign` FOREIGN KEY (`ilce_id`) REFERENCES `ilceler` (`id`) ON DELETE SET NULL,
  CONSTRAINT `talepler_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE CASCADE,
  CONSTRAINT `talepler_mahalle_id_foreign` FOREIGN KEY (`mahalle_id`) REFERENCES `mahalleler` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telegram_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telegram_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ulke_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `lead_id` bigint unsigned DEFAULT NULL,
  `mesaj_tipi` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mesaj_icerigi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `gonderim_durumu` tinyint unsigned NOT NULL DEFAULT '0',
  `hata_mesaji` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `deneme_sayisi` tinyint unsigned NOT NULL DEFAULT '0',
  `gonderim_zamani` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `telegram_notifications_ulke_id_index` (`ulke_id`),
  KEY `telegram_notifications_user_id_index` (`user_id`),
  KEY `telegram_notifications_lead_id_index` (`lead_id`),
  KEY `telegram_notifications_mesaj_tipi_index` (`mesaj_tipi`),
  KEY `telegram_notifications_gonderim_durumu_index` (`gonderim_durumu`),
  CONSTRAINT `telegram_notifications_ulke_id_foreign` FOREIGN KEY (`ulke_id`) REFERENCES `ulkeler` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries` (
  `sequence` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `family_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `should_display_on_index` tinyint(1) NOT NULL DEFAULT '1',
  `type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`sequence`),
  UNIQUE KEY `telescope_entries_uuid_unique` (`uuid`),
  KEY `telescope_entries_batch_id_index` (`batch_id`),
  KEY `telescope_entries_family_hash_index` (`family_hash`),
  KEY `telescope_entries_created_at_index` (`created_at`),
  KEY `telescope_entries_type_should_display_on_index_index` (`type`,`should_display_on_index`)
) ENGINE=InnoDB AUTO_INCREMENT=426227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_entries_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_entries_tags` (
  `entry_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`entry_uuid`,`tag`),
  KEY `telescope_entries_tags_tag_index` (`tag`),
  CONSTRAINT `telescope_entries_tags_entry_uuid_foreign` FOREIGN KEY (`entry_uuid`) REFERENCES `telescope_entries` (`uuid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `telescope_monitoring`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `telescope_monitoring` (
  `tag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`tag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `template_change_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `template_change_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ups_template_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `aksiyon_tipi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'What changed',
  `entity_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` bigint unsigned DEFAULT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `feature_id` bigint unsigned DEFAULT NULL,
  `eski_degerler` json DEFAULT NULL COMMENT 'Previous values',
  `yeni_degerler` json DEFAULT NULL COMMENT 'New values',
  `versiyon_numarasi` int NOT NULL DEFAULT '0' COMMENT 'Template version',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `yayin_tipi_sablonu_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `template_change_logs_feature_id_foreign` (`feature_id`),
  KEY `tcl_ikyt_ca_index` (`created_at`),
  KEY `tcl_user_ca_index` (`user_id`,`created_at`),
  KEY `template_change_logs_aksiyon_tipi_index` (`aksiyon_tipi`),
  KEY `template_change_logs_ups_template_id_foreign` (`ups_template_id`),
  KEY `template_change_logs_yayin_tipi_sablonu_id_foreign` (`yayin_tipi_sablonu_id`),
  CONSTRAINT `template_change_logs_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE SET NULL,
  CONSTRAINT `template_change_logs_ups_template_id_foreign` FOREIGN KEY (`ups_template_id`) REFERENCES `ups_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `template_change_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `template_change_logs_yayin_tipi_sablonu_id_foreign` FOREIGN KEY (`yayin_tipi_sablonu_id`) REFERENCES `yayin_tipi_sablonlari` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=134 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `template_design_audits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `template_design_audits` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `yayin_tipi_id` bigint unsigned NOT NULL,
  `kategori_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `run_uuid` varchar(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `apply_mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `before_snapshot` json NOT NULL,
  `changes` json NOT NULL,
  `design_payload` json DEFAULT NULL,
  `rolled_back` tinyint(1) NOT NULL DEFAULT '0',
  `rolled_back_at` timestamp NULL DEFAULT NULL,
  `rolled_back_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `template_design_audits_yayin_tipi_id_index` (`yayin_tipi_id`),
  KEY `template_design_audits_user_id_index` (`user_id`),
  KEY `template_design_audits_run_uuid_index` (`run_uuid`),
  CONSTRAINT `template_design_audits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `template_design_audits_yayin_tipi_id_foreign` FOREIGN KEY (`yayin_tipi_id`) REFERENCES `yayin_tipi_sablonlari` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ulkeler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ulkeler` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ulke_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ulke_kodu` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefon_kodu` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `para_birimi` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ulkeler_ulke_kodu_unique` (`ulke_kodu`),
  KEY `ulkeler_ulke_adi_index` (`ulke_adi`),
  KEY `ulkeler_aktiflik_durumu_index` (`aktiflik_durumu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ups_feature_pack_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ups_feature_pack_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `feature_pack_id` bigint unsigned NOT NULL,
  `feature_id` bigint unsigned NOT NULL,
  `display_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pack_feature_unique` (`feature_pack_id`,`feature_id`),
  KEY `ups_feature_pack_items_feature_id_foreign` (`feature_id`),
  KEY `ups_feature_pack_items_feature_pack_id_display_order_index` (`feature_pack_id`,`display_order`),
  CONSTRAINT `ups_feature_pack_items_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ups_feature_pack_items_feature_pack_id_foreign` FOREIGN KEY (`feature_pack_id`) REFERENCES `ups_feature_packs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ups_feature_packs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ups_feature_packs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT 'Aktiflik durumu (Context7: 1=aktif, 0=pasif)',
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ups_feature_packs_slug_unique` (`slug`),
  KEY `ups_feature_packs_status_index` (`status`),
  KEY `ups_feature_packs_display_order_index` (`display_order`),
  KEY `idx_ups_feature_packs_aktiflik_durumu` (`aktiflik_durumu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ups_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ups_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SYSTEM',
  `yayin_tipi_sablonu_id` bigint unsigned NOT NULL,
  `kategori_id` bigint unsigned NOT NULL,
  `yayin_tipi_id` bigint unsigned DEFAULT NULL,
  `template_json` json NOT NULL,
  `template_version` int NOT NULL DEFAULT '1',
  `template_hash` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sealed_at` timestamp NULL DEFAULT NULL,
  `sealed_by_user_id` bigint unsigned DEFAULT NULL,
  `aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
  `active_junction_id` bigint unsigned DEFAULT NULL COMMENT 'NULL=inactive, yayin_tipi_sablonu_id=active. UNIQUE enforces single active.',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ups_templates_active_junction_unique` (`active_junction_id`),
  KEY `ups_template_active_idx` (`yayin_tipi_sablonu_id`,`aktiflik_durumu`),
  KEY `ups_templates_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `device_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique device identifier (UUID)',
  `fcm_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Firebase Cloud Messaging Token',
  `platform` enum('ios','android','web') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ios',
  `last_active_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_devices_device_id_unique` (`device_id`),
  KEY `user_devices_user_id_foreign` (`user_id`),
  CONSTRAINT `user_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baslik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bio` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `instagram_profil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `linkedin_profil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ofis_telefon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ofis_adres` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `whatsapp_numara` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ulke_id` bigint unsigned DEFAULT NULL,
  `role_id` int DEFAULT NULL,
  `departman` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pozisyon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uzmanlik_alanlari` json DEFAULT NULL,
  `diller` json DEFAULT NULL,
  `bolge_uzmanliklari` json DEFAULT NULL,
  `lisans_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deneyim_yili` int DEFAULT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `dogrulanmis_mi` tinyint(1) NOT NULL DEFAULT '0',
  `telegram_chat_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `profile_photo_path` varchar(2048) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `valuation_signal_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `valuation_signal_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `region_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `area_m2` int NOT NULL,
  `estimated_value` decimal(15,2) NOT NULL,
  `confidence_score` double(8,2) NOT NULL,
  `liquidity_score` double(8,2) DEFAULT NULL,
  `trend_percent` double(8,2) DEFAULT NULL,
  `source_engine` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `valuation_signal_logs_region_key_index` (`region_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vip_tercih_matrisi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vip_tercih_matrisi` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vip_kimlik` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique identifier (phone or code)',
  `vip_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'VIP adı',
  `tercih_lokasyonlar` json DEFAULT NULL COMMENT 'İller array: ["Muğla", "İzmir"] veya ilçeler: ["Bodrum", "Çeşme"]',
  `tercih_kategoriler` json DEFAULT NULL COMMENT 'Kategoriler: ["Villa", "Daire", "Arsa"]',
  `min_fiyat` decimal(15,2) DEFAULT NULL COMMENT 'Minimum fiyat',
  `max_fiyat` decimal(15,2) DEFAULT NULL COMMENT 'Maximum fiyat',
  `para_birimi` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY' COMMENT 'TRY, USD, EUR',
  `tercih_kanal` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'whatsapp' COMMENT 'Tercih edilen kanal: whatsapp, telegram, email',
  `telefon` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'WhatsApp/Telegram telefon',
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email adresi',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ekstra notlar',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vip_tercih_matrisi_vip_kimlik_unique` (`vip_kimlik`),
  KEY `idx_aktif` (`aktiflik_durumu`),
  KEY `idx_tercih_kanal` (`tercih_kanal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `yayin_tipi_pivot_atamalari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `yayin_tipi_pivot_atamalari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `yayin_tipi_id` bigint unsigned NOT NULL,
  `alt_kategori_id` bigint unsigned NOT NULL,
  `feature_id` bigint unsigned NOT NULL,
  `zorunlu_mu` tinyint(1) NOT NULL DEFAULT '0',
  `gosterim_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `yayin_tipi_pivot_atamalari_alt_kategori_id_foreign` (`alt_kategori_id`),
  KEY `yayin_tipi_pivot_atamalari_feature_id_foreign` (`feature_id`),
  KEY `yt_ak_idx` (`yayin_tipi_id`,`alt_kategori_id`),
  CONSTRAINT `yayin_tipi_pivot_atamalari_alt_kategori_id_foreign` FOREIGN KEY (`alt_kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE CASCADE,
  CONSTRAINT `yayin_tipi_pivot_atamalari_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE,
  CONSTRAINT `yayin_tipi_pivot_atamalari_yayin_tipi_id_foreign` FOREIGN KEY (`yayin_tipi_id`) REFERENCES `yayin_tipi_sablonlari` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `yayin_tipi_sablonlari`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `yayin_tipi_sablonlari` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SYSTEM',
  `ad` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `varsayilan_ozellikler` json DEFAULT NULL,
  `fiyat_ayarlari` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `ups_template_id` bigint unsigned DEFAULT NULL,
  `kategori_id` bigint unsigned DEFAULT NULL,
  `yayin_tipi_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `yayin_tipi_sablonlari_slug_unique` (`slug`),
  KEY `yayin_tipi_sablonlari_aktiflik_durumu_index` (`aktiflik_durumu`),
  KEY `yayin_tipi_sablonlari_display_order_index` (`display_order`),
  KEY `yayin_tipi_sablonlari_tenant_id_index` (`tenant_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `yayin_tipleri`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `yayin_tipleri` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `yayin_tipleri_name_unique` (`name`),
  UNIQUE KEY `yayin_tipleri_slug_unique_v2` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `yazlik_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `yazlik_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `min_konaklama` int NOT NULL DEFAULT '1' COMMENT 'Minimum konaklama günü',
  `max_misafir` int DEFAULT NULL COMMENT 'Maksimum misafir sayısı',
  `oda_sayisi` int DEFAULT NULL COMMENT 'Oda sayısı',
  `banyo_sayisi` int DEFAULT NULL COMMENT 'Banyo sayısı',
  `yatak_sayisi` int DEFAULT NULL COMMENT 'Yatak sayısı',
  `yatak_turleri` json DEFAULT NULL COMMENT 'Yatak türleri array',
  `carsaf_dahil` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Çarşaf dahil mi',
  `havlu_dahil` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Havlu dahil mi',
  `temizlik_ucreti` decimal(10,2) DEFAULT NULL,
  `havuz` tinyint(1) NOT NULL DEFAULT '0',
  `havuz_turu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `havuz_boyut` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `havuz_derinlik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `havuz_boyut_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Havuz genişlik (m)',
  `havuz_boyut_boy` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Havuz uzunluk (m)',
  `bahce_var` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Bahçe var mı',
  `tv_var` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'TV var mı',
  `barbeku_var` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Barbekü var mı',
  `sezlong_var` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Şezlong var mı',
  `bahce_masasi_var` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Bahçe masası var mı',
  `manzara` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Manzara türü',
  `ozel_isaretler` json DEFAULT NULL COMMENT 'Özel işaretler array',
  `ev_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ev tipi (villa, bungalov, etc.)',
  `ev_konsepti` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ev konsepti',
  `gunluk_fiyat` decimal(10,2) DEFAULT NULL,
  `haftalik_fiyat` decimal(10,2) DEFAULT NULL,
  `aylik_fiyat` decimal(10,2) DEFAULT NULL,
  `sezonluk_fiyat` decimal(10,2) DEFAULT NULL,
  `sezon_baslangic` date DEFAULT NULL,
  `sezon_bitis` date DEFAULT NULL,
  `elektrik_dahil` tinyint(1) NOT NULL DEFAULT '0',
  `internet_dahil` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'İnternet dahil mi',
  `klima_var` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Klima var mı',
  `restoran_mesafe` int DEFAULT NULL COMMENT 'Restoran mesafe (km)',
  `market_mesafe` int DEFAULT NULL COMMENT 'Market mesafe (km)',
  `deniz_mesafe` int DEFAULT NULL COMMENT 'Deniz mesafe (km)',
  `merkez_mesafe` int DEFAULT NULL COMMENT 'Merkez mesafe (km)',
  `su_dahil` tinyint(1) NOT NULL DEFAULT '0',
  `ozel_notlar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `musteri_notlari` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `indirim_notlari` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `indirimli_fiyat` decimal(10,2) DEFAULT NULL,
  `anahtar_kimde` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anahtar_notlari` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sahip_ozel_notlari` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sahip_iletisim_tercihi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eids_onayli` tinyint(1) NOT NULL DEFAULT '0',
  `eids_onay_tarihi` date DEFAULT NULL,
  `eids_belge_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `yazlik_details_ilan_id_unique` (`ilan_id`),
  KEY `yazlik_details_ilan_id_index` (`ilan_id`),
  KEY `yazlik_details_sezon_baslangic_index` (`sezon_baslangic`),
  KEY `yazlik_details_sezon_bitis_index` (`sezon_bitis`),
  CONSTRAINT `yazlik_details_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `yazlik_fiyatlandirma`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `yazlik_fiyatlandirma` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `sezon_tipi` enum('yaz','ara_sezon','kis') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'yaz' COMMENT 'Sezon tipi',
  `baslangic_tarihi` date NOT NULL COMMENT 'Sezon başlangıç tarihi',
  `bitis_tarihi` date NOT NULL COMMENT 'Sezon bitiş tarihi',
  `gunluk_fiyat` decimal(10,2) DEFAULT NULL COMMENT 'Günlük fiyat',
  `haftalik_fiyat` decimal(10,2) DEFAULT NULL COMMENT 'Haftalık fiyat',
  `aylik_fiyat` decimal(10,2) DEFAULT NULL COMMENT 'Aylık fiyat',
  `minimum_konaklama` int NOT NULL DEFAULT '1' COMMENT 'Minimum konaklama günü',
  `maksimum_konaklama` int DEFAULT NULL COMMENT 'Maksimum konaklama günü',
  `ozel_gunler` json DEFAULT NULL COMMENT 'Özel günler ve fiyatları (JSON)',
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_yazlik_fiyat_ilan` (`ilan_id`),
  KEY `idx_yazlik_fiyat_sezon` (`sezon_tipi`),
  KEY `idx_yazlik_fiyat_tarih` (`baslangic_tarihi`,`bitis_tarihi`),
  KEY `idx_yazlik_fiyat_aktiflik_durumu` (`aktiflik_durumu`),
  CONSTRAINT `yazlik_fiyatlandirma_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `yazlik_rezervasyonlar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `yazlik_rezervasyonlar` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ilan_id` bigint unsigned NOT NULL,
  `musteri_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Müşteri adı',
  `musteri_telefon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Müşteri telefonu',
  `musteri_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Müşteri e-posta',
  `check_in` date NOT NULL COMMENT 'Giriş tarihi',
  `check_out` date NOT NULL COMMENT 'Çıkış tarihi',
  `misafir_sayisi` int NOT NULL DEFAULT '1' COMMENT 'Misafir sayısı',
  `cocuk_sayisi` int NOT NULL DEFAULT '0' COMMENT 'Çocuk sayısı',
  `pet_sayisi` int NOT NULL DEFAULT '0' COMMENT 'Evcil hayvan sayısı',
  `ozel_istekler` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Özel istekler',
  `toplam_fiyat` decimal(10,2) NOT NULL COMMENT 'Toplam fiyat',
  `kapora_tutari` decimal(10,2) DEFAULT NULL COMMENT 'Kapora tutarı',
  `rezervasyon_durumu` enum('beklemede','onaylandi','iptal','tamamlandi') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beklemede' COMMENT 'Rezervasyon durumu',
  `iptal_nedeni` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'İptal nedeni',
  `onay_tarihi` timestamp NULL DEFAULT NULL COMMENT 'Onay tarihi',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_yazlik_rez_ilan` (`ilan_id`),
  KEY `idx_yazlik_rez_tarih` (`check_in`,`check_out`),
  KEY `idx_yazlik_rez_durum` (`rezervasyon_durumu`),
  KEY `idx_yazlik_rez_telefon` (`musteri_telefon`),
  KEY `idx_yazlik_rez_email` (`musteri_email`),
  CONSTRAINT `yazlik_rezervasyonlar_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

