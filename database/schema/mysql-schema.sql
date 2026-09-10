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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `user_id` bigint unsigned NULL COMMENT 'User who owns this photo',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  UNIQUE KEY `feature_assignment_scoped_unique` (`feature_id`,`assignable_type`,`assignable_id`,`scope_type`,`main_category_id`,`listing_type_id`),
  KEY `feature_assignments_assignable_type_assignable_id_index` (`assignable_type`,`assignable_id`),
  KEY `feature_assignments_feature_id_assignable_type_index` (`feature_id`,`assignable_type`),
  KEY `feature_assignments_aktiflik_durumu_index` (`aktiflik_durumu`),
  KEY `fa_scope_idx` (`main_category_id`,`sub_category_id`,`listing_type_id`),
  KEY `fa_scope_source_idx` (`scope_type`,`source_type`),
  KEY `fa_rollback_idx` (`rolled_back_at`),
  CONSTRAINT `feature_assignments_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `ai_saglayici` varchar(50) NULL COMMENT 'AI provider name',
  `ai_modeli` varchar(80) NULL COMMENT 'AI model version',
  `ai_dogrulama_durumu` varchar(20) NULL COMMENT 'ai_confirmed|ai_pending|ai_rejected',
  `ai_hata_sebebi` text NULL COMMENT 'AI validation failure reason',
  `ai_inceleme_gerekli` tinyint(1) NULL COMMENT 'Needs human review flag',
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
  `tenant_id` bigint unsigned NULL COMMENT 'Multi-tenant isolation key',
  `source_event` varchar(255) NULL COMMENT 'AI: event that triggered this task',
  `source_module` varchar(255) NULL COMMENT 'AI: source module name',
  `ai_confidence_score` float NULL COMMENT 'AI confidence 0-1',
  `ai_reasoning` text NULL COMMENT 'AI reasoning/explanation',
  `ai_model_version` varchar(80) NULL COMMENT 'AI model that generated this',
  `assigned_at` timestamp NULL COMMENT 'When task was assigned',
  `started_at` timestamp NULL COMMENT 'When task work began',
  `completed_at` timestamp NULL COMMENT 'When task was completed',
  `cancel_reason` varchar(255) NULL COMMENT 'Cancellation reason',
  INDEX `action_center_queue_idx` (`gorev_durumu`,`oncelik`,`bitis_tarihi`),
  INDEX `gorevler_tenant_id_idx` (`tenant_id`),
  INDEX `gorevler_source_event_idx` (`source_event`),
  INDEX `gorevler_ilan_id_idx` (`kisi_id`),
  INDEX `gorevler_reservation_id_idx` (`proje_id`),
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
  `tenant_id` bigint unsigned NULL COMMENT 'Multi-tenant isolation (KRONIK-1)',
  `current_hash` char(64) NULL COMMENT 'Content hash for integrity',
  `prev_hash` char(64) NULL COMMENT 'Previous decision hash (chain)',
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `governance_decisions_finding_id_unique` (`finding_id`),
  KEY `governance_decisions_karar_durumu_index` (`karar_durumu`),
  KEY `governance_decisions_severity_index` (`severity`),
  KEY `governance_decisions_source_index` (`source`),
  KEY `governance_decisions_karar_durumu_severity_index` (`karar_durumu`,`severity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Context7: boolean state',
  INDEX `idx_ilan_favorileri_aktiflik` (`aktiflik_durumu`),
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `token_access` text NULL COMMENT 'OAuth access token',
  `token_refresh` text NULL COMMENT 'OAuth refresh token',
  `token_expires_at` timestamp NULL COMMENT 'Token expiry time',
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
  `category_id` bigint unsigned NULL COMMENT 'Category ID for template mapping',
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
  `tenant_id` bigint unsigned DEFAULT NULL COMMENT 'Multi-tenant: tenant isolation key — KRONIK-1 SSOT fix',
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
  KEY `ilanlar_tenant_id_index` (`tenant_id`),
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `eposta` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Context7: Turkish canonical email',
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
  `son_etkilesim_tarihi` timestamp NULL DEFAULT NULL COMMENT 'Context7: Turkish canonical last contact',
  `user_id` bigint unsigned DEFAULT NULL,
  `ulke_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `sesli_onay_verildi` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'Sesli görüşme onayı (Context7 boolean)',
  `tenant_id` bigint unsigned NULL COMMENT 'Multi-tenant isolation key',
  `vergi_kimlik_no` varchar(20) NULL COMMENT 'Tax ID for legal entities',
  `kurum_unvani` varchar(255) NULL COMMENT 'Company name / legal title',
  `mersis_no` varchar(20) NULL COMMENT 'Trade Registry number',
  `sicil_no` varchar(20) NULL COMMENT 'Trade registry serial number',
  `referans_kisi_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kisiler_aktiflik_durumu_kisi_tipi_index` (`aktiflik_durumu`,`kisi_tipi`),
  KEY `kisiler_il_id_ilce_id_index` (`il_id`,`ilce_id`),
  KEY `kisiler_user_id_index` (`user_id`),
  KEY `kisiler_email_index` (`eposta`),
  KEY `kisiler_telefon_index` (`telefon`),
  KEY `kisiler_danisman_id_index` (`danisman_id`),
  KEY `kisiler_crm_surec_asamasi_index` (`crm_surec_asamasi`),
  KEY `kisiler_mahalle_id_index` (`mahalle_id`),
  KEY `idx_kisiler_aktiflik` (`aktiflik_durumu`),
  KEY `idx_kisiler_deleted` (`deleted_at`),
  KEY `idx_kisiler_active_consultant` (`aktiflik_durumu`,`danisman_id`),
  KEY `idx_kisiler_last_contacted` (`son_etkilesim_tarihi`),
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
  `tip` varchar(30) NULL COMMENT 'Asset|Liability|Revenue|Expense',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `external_reservation_id` varchar(255) NULL COMMENT 'Channel-specific reservation ID',
  `external_channel` varchar(50) NULL COMMENT 'airbnb|booking_com|direct|etc',
  `checked_in_at` timestamp NULL COMMENT 'Guest check-in time',
  `checked_out_at` timestamp NULL COMMENT 'Guest check-out time',
  `completed_at` timestamp NULL COMMENT 'Reservation completed timestamp',
  `checkin_window_opened_at` timestamp NULL COMMENT 'Checkin window open notification sent',
  `arrival_time_estimated` time NULL COMMENT 'Estimated arrival time',
  `arrival_notes` text NULL COMMENT 'Guest arrival notes',
  `channel_fee_amount` decimal(12,2) NULL COMMENT 'Channel fee amount',
  `channel_fee_currency` varchar(3) NULL COMMENT 'Channel fee currency',
  `channel_fee_rate` decimal(5,4) NULL COMMENT 'Channel fee percentage',
  `channel_fee_source` varchar(50) NULL COMMENT 'airbnb|booking|etc',
  `channel_fee_bearer` varchar(20) NULL COMMENT 'host|guest|platform',
  `channel_fee_captured_at` timestamp NULL COMMENT 'When fee was captured',
  `channel_fee_is_verified` tinyint(1) NULL COMMENT 'Fee verification status',
  `override_of_id` bigint unsigned NULL COMMENT 'Original reservation this overrides',
  `override_authorized_by` bigint unsigned NULL COMMENT 'Who authorized the override',
  `override_occurred_at` timestamp NULL COMMENT 'When override happened',
  INDEX `idx_ext_reservation_channel_tenant` (`external_channel`,`tenant_id`),
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
  `rate` decimal(12,2) NULL COMMENT 'Seasonal rate override',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `filters` json NULL COMMENT 'Search filter criteria (JSON)',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `ana_kategori_id` bigint unsigned DEFAULT NULL COMMENT 'FK → ilan_kategorileri (Context7 canonical)',
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
  `max_metrekare` int NULL COMMENT 'Max square meters filter',
  `min_metrekare` int NULL COMMENT 'Min square meters filter',
  `metadata` json NULL COMMENT 'Extended search metadata',
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
  CONSTRAINT `talepler_ana_kategori_id_foreign` FOREIGN KEY (`ana_kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE SET NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  `device_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mobile device push token (nullable - P0 schema fix)',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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

-- ============================================================
-- MISSING TABLES: from yalihanai_clone ground truth (2026-09-09)
-- 77 tables not present in original schema dump
-- ============================================================

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2018_08_08_100000_create_telescope_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2025_01_03_000001_create_market_trends_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_10_07_214858_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_10_07_214921_add_missing_columns_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_10_07_220521_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_10_07_220551_create_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_10_08_132426_update_sites_status_column',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_10_08_133526_add_soft_deletes_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_10_10_071029_add_last_activity_at_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_10_10_073304_create_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_10_10_073503_create_ilan_kategorileri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_10_10_073545_create_ilceler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_10_10_073545_create_iller_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_10_10_073826_create_kisiler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_10_10_160010_create_gorevler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_10_10_160010_create_ozellik_kategorileri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_10_10_160010_create_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_10_10_160010_create_takim_uyeleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_10_10_160011_create_ulkeler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_10_10_174618_add_seviye_to_ilan_kategorileri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_10_10_174808_create_ilan_kategori_yayin_tipleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2025_10_10_175050_create_ozellikler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2025_10_10_175050_create_projeler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_10_10_203000_create_talepler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_10_10_210000_create_eslesmeler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_10_11_151632_create_error_memory_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_10_11_180000_add_referans_system_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_10_11_215700_add_anahtar_management_fields_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_10_12_180000_create_mahalleler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_10_13_204351_create_site_apartmanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_10_15_160340_create_feature_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_10_15_170751_create_etiketler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_10_15_172758_create_features_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_10_15_213116_add_danisman_id_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_10_15_213509_add_danisman_id_to_kisiler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_10_15_220340_create_search_analytics_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_10_15_220402_create_design_token_usage_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_10_15_220437_create_ai_category_analytics_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_10_16_220234_create_sites_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_10_19_223916_add_new_category_fields_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_10_19_224458_create_ilan_ozellikleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_10_19_224501_create_ilan_resimleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_10_22_072529_add_arsa_fields_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_10_22_072548_add_yazlik_fields_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_10_22_072600_create_yazlik_fiyatlandirma_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_10_22_072601_create_yazlik_rezervasyonlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_10_22_142744_add_villa_isyeri_fields_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_10_22_145330_add_para_birimi_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_10_22_152435_create_ilan_fotograflari_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_10_22_170000_create_anahtar_yonetimi_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_10_22_203233_add_tip_column_to_site_apartmanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_10_23_121215_create_site_ozellikleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_10_23_143000_add_yayin_tipi_details_to_ilan_kategori_yayin_tipleri',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_10_26_065836_create_ilan_feature_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_10_26_115934_add_applies_to_to_feature_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_10_26_160410_add_applies_to_to_features_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_10_27_085026_create_ilan_etiketler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_10_27_090614_create_ilan_takvim_sync_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_10_27_101503_remove_legacy_category_fields_from_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_10_27_101837_create_yazlik_details_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_10_27_150000_update_yazlik_details_airbnb_fields',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_10_28_083829_create_yayin_tipleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_10_29_170932_create_alt_kategori_yayin_tipi_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_11_05_000001_create_feature_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_11_05_133340_create_dashboard_widgets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_11_08_152018_create_etiket_kisi_pivot_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_11_23_140000_create_analytics_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_11_25_create_kisi_etkilesimler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_11_26_011602_create_finansal_islemler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_11_26_011602_create_komisyonlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_12_20_140001_create_ups_feature_packs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_12_20_140002_create_ups_feature_pack_items_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_12_27_011316_create_ilan_favorileri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_12_27_205630_create_ilan_metinleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_01_03_142718_create_ilan_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_01_09_164316_create_template_change_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_01_11_000000_create_category_feature_whitelist_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_01_12_000001_create_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_01_12_000002_create_kategori_yayin_tipi_field_dependencies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_01_12_083349_create_leads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_01_12_084213_create_lead_messages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_01_12_084214_create_lead_activities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_01_12_141712_create_analytics_dashboard_filters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_01_12_141712_create_analytics_dashboard_metrics_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_01_12_141712_create_analytics_reports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_01_12_181952_add_danisman_profile_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_01_12_182348_add_professional_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_01_12_200110_create_ilan_embeddings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_01_12_231330_create_danisman_chat_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_01_12_add_scoring_to_leads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_01_12_create_follow_up_tasks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_01_13_000000_create_lead_embeddings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_01_13_065706_add_lifecycle_to_features_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_01_13_073229_create_permission_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_01_13_080000_add_lokasyon_to_takim_uyeleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_01_13_081140_create_ilan_goruntulenme_gunluk_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_01_13_081844_add_crm_only_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_01_13_090439_create_opportunities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_01_13_120000_create_advisor_photos_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_01_13_150000_fix_yazlik_kiralama_alt_kategoriler',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_01_13_151140_create_ref_sequences_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_01_13_153538_rename_aktif_mi_to_aktiflik_durumu_in_yazlik_fiyatlandirma_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_01_13_202429_add_structured_data_fields_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_01_14_124250_add_firsat_muhru_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_01_14_130754_add_rapor_columns_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_01_14_153939_create_iletim_kayitlari_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_01_14_153939_create_vip_tercih_matrisi_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_01_16_210453_create_ai_feature_usages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_01_17_081936_create_ai_ogrenme_sinyalleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_01_17_081937_create_ai_esik_profilleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_01_17_081937_create_ai_saglayici_profilleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_01_17_082633_add_explainability_v2_to_ai_feature_usages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_01_17_091133_add_cost_guard_fields_to_ai_feature_usages_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_01_17_092147_create_ai_optimization_runs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_01_17_092147_create_ai_threshold_overrides_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2026_01_17_093641_create_ai_provider_decisions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2026_01_17_093641_create_ai_provider_profiles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_01_17_095715_add_debug_metadata_to_ai_provider_decisions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2026_01_17_103000_create_ai_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2026_01_17_103114_create_ai_archive_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2026_01_17_103246_add_performance_indexes_to_ai_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2026_01_17_104352_create_ai_abuse_signals_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2026_01_17_114000_create_ai_experiments_and_enhanced_tracking',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2026_01_17_120632_create_ilan_price_history_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2026_01_17_121315_add_tenant_id_to_ai_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2026_01_17_121316_add_tenant_id_to_ai_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2026_01_17_121328_create_ai_tenant_quotas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2026_01_17_121328_create_ai_tenant_settings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_01_18_191418_add_crm_surec_asamasi_to_kisiler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_01_19_122000_create_ai_wallet_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_01_19_163500_create_ai_pricing_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_01_20_162348_add_canonical_fields_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2026_01_21_000000_fix_ups_template_schema',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2026_01_21_070000_create_ilan_taslaklar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2026_01_21_075039_refine_ilan_taslaklar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2026_01_21_185800_sync_yayin_tipleri_and_pois',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2026_01_21_200440_add_yayin_tipi_id_to_field_dependencies',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2026_01_23_085834_add_granular_feature_categories',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2026_01_24_093224_add_source_type_and_metadata_to_feature_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2026_01_24_100000_create_master_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2026_01_25_111031_add_aktiflik_durumu_to_feature_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2026_01_25_133708_enhance_template_change_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_01_25_175200_fix_template_change_logs_enum_violation',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2026_01_25_231849_create_user_devices_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2026_01_25_233247_create_saved_searches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2026_01_25_233713_add_profile_photo_path_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2026_01_25_235331_add_mobile_fields_to_leads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2026_01_26_190710_rename_sira_to_display_order_in_ilan_fotograflari_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2026_01_31_000001_add_mahalle_id_to_kisiler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2026_01_31_193249_add_mahalle_id_to_kisiler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2026_01_31_210000_update_talepler_schema_sync',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2026_01_31_220000_add_context7_location_columns',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2026_02_01_000001_add_performance_indexes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2026_02_01_000002_create_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2026_02_01_000003_add_event_column_to_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2026_02_01_000004_add_batch_uuid_column_to_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2026_02_01_103800_add_lifecycle_timestamps_to_features_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2026_02_01_104800_change_aksiyon_tipi_to_string',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2026_02_01_105500_rename_status_to_durum_in_notifications',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2026_02_01_111059_add_last_contacted_at_to_kisiler_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2026_02_01_143700_add_aktiflik_durumu_to_features_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2026_02_01_143800_add_aktiflik_durumu_to_ups_feature_packs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2026_02_01_143900_add_name_to_site_apartmanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2026_02_03_075642_convert_to_yayin_tipi_based_template_system',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2026_02_06_193621_add_ilan_sahibi_and_site_to_ilanlar',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2026_02_06_203000_standardize_yayin_tipleri_schema',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2026_02_07_000000_add_model_to_ai_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2026_02_08_000001_create_ai_telemetry_hourly_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2026_02_08_000002_add_telemetry_index_to_ai_logs',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2026_02_08_000003_create_ai_call_analyses_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2026_02_08_000004_create_ai_lead_scores_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2026_02_08_000005_add_tags_to_leads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2026_02_08_104131_add_win_probability_to_ai_lead_scores_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2026_02_08_121000_backup_ilan_kategori_yayin_tipleri_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2026_02_08_150000_soft_delete_invalid_project_types',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2026_02_09_070537_create_ups_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2026_02_09_070538_add_ups_template_id_to_ilan_kategori_yayin_tipleri',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2026_02_09_072948_make_yayin_tipi_id_nullable_in_ups_templates',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2026_02_09_074948_fix_template_change_logs_fk_and_add_ups_template_id',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2026_02_09_185445_add_visibility_score_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2026_02_09_195202_create_ai_prompt_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2026_02_09_203459_add_indexes_and_violation_flag_to_ai_prompt_logs',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2026_02_09_221000_change_visibility_score_to_medium_int',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2026_02_10_093000_rename_status_code_in_ai_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2026_02_11_113919_add_ranking_indexes_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2026_02_11_205442_add_visibility_and_seo_columns_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2026_02_12_000000_migrate_template_change_logs_to_v2',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2026_02_12_000001_add_missing_seo_columns_to_ilanlar_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2026_02_13_000000_add_missing_columns_to_yayin_tipi_sablonlari',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2026_02_13_000000_add_missing_season_fields_to_ilanlar',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2026_02_16_142426_create_property_config_versions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2026_02_16_142509_create_rule_definitions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2026_02_16_143332_add_active_version_constraint_to_property_config_versions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2026_02_16_143919_enforce_single_active_version_constraint',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2026_02_16_145742_create_property_engine_shadow_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2026_02_16_201055_add_snapshot_to_property_config_versions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2026_02_17_000000_harmonize_ai_logs_schema',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2026_02_17_005201_add_role_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2026_02_17_010000_create_property_config_audit_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2026_02_17_020000_drop_property_engine_shadow_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2026_02_17_081020_create_governance_incidents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2026_02_17_091142_add_dual_approval_to_property_config_versions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2026_02_17_091533_add_tenant_id_to_governance_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2026_02_17_091623_add_tenant_id_to_templates_and_categories',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2026_02_17_091752_add_tenant_id_to_ups_templates',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2026_02_17_100000_create_governance_telemetry_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2026_02_18_010000_scope_active_version_to_tenant',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2026_02_18_020000_add_risk_score_to_property_config_versions',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2026_02_18_030000_create_yayin_tipi_pivot_atamalari_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2026_02_20_183422_drop_ilan_templates_table_and_cleanup_legacy_fks',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2026_02_21_000001_add_active_junction_anchor_to_ups_templates',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2026_02_22_000000_add_correlation_id_to_ai_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2026_02_23_000000_create_matching_feedbacks_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2026_02_25_213028_add_rental_fields_to_ilanlar_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2026_02_25_213030_create_property_reservations_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2026_02_25_213031_create_property_availabilities_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2026_02_25_213032_create_property_calendar_feeds_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2026_02_26_053902_create_property_seasonal_rates_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2026_02_26_055212_create_country_financial_rules_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2026_02_26_055212_create_financial_transactions_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2026_02_26_055212_create_fx_rates_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2026_02_26_055213_add_financial_state_to_property_reservations_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2026_02_26_060445_add_investor_fields_to_ilanlar_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2026_02_26_060445_create_property_growth_projections_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2026_02_26_060652_add_country_code_to_ilanlar_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2026_02_26_061716_create_currencies_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2026_02_26_061716_create_languages_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2026_02_26_100000_enterprise_performance_hardening',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2026_02_26_080224_create_financial_expansion_tables',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2026_02_26_080515_add_voice_consent_to_crm_tables',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2026_02_26_080602_add_ulke_id_to_users_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2026_02_26_080734_add_ulke_id_to_crm_and_finance_tables',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2026_02_26_080927_add_ulke_id_to_reservation_and_subscription_tables',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2026_02_26_083630_create_telegram_notifications_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2026_02_26_110000_standardize_settings_naming_v2',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2026_02_26_084817_rename_setting_status_keys_to_durumu_v2',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2026_02_26_123342_create_ledger_accounts_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2026_02_26_123342_create_ledger_entries_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2026_02_26_190551_create_ledger_transactions_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2026_02_26_192529_create_ledger_balance_projections_view',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2026_02_26_193408_drop_ledger_balance_projections_view',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2026_02_26_193609_add_composite_index_to_ledger_entries',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2026_02_26_193409_create_ledger_balances_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2026_02_26_222131_add_unique_indexes_to_kisiler_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2026_02_27_061536_create_cqrs_projection_tables',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2026_02_27_094834_create_projection_dlq_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2026_02_27_211444_create_rental_ev_kartlari_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2026_02_27_211444_create_rental_gelir_kalemleri_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2026_03_28_210441_create_jobs_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2026_03_28_212704_add_geometry_fields_to_ilanlar_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2026_03_29_091756_backfill_features_from_ozellikler',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2026_03_29_000001_create_pipeline_runs_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2026_03_29_000002_create_pipeline_steps_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2026_03_29_161556_create_job_batches_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2026_03_29_170000_add_shard_key_and_meta_to_pipeline_steps_table',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2026_03_30_000001_create_template_design_audits_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2026_03_31_060000_extend_feature_assignments_scoped_columns',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2026_03_31_070000_update_feature_assignments_unique_index_for_scopes',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (263,'2026_03_31_140000_add_dependency_columns_to_feature_assignments',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (264,'2026_04_01_100000_create_ai_field_suggestions_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2026_04_01_100001_create_ai_suggestion_actions_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2026_03_31_165157_create_copilot_action_logs_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2026_03_31_180000_add_fiyat_gosterim_modu_to_ilanlar_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2026_04_03_202452_add_rol_to_takim_uyeleri_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2026_02_27_211444_create_rental_gider_kalemleri_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2026_02_27_212013_create_listing_state_transitions_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2026_02_27_215514_normalize_yayin_durumu_canonical_state',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2026_02_27_223436_add_completion_score_to_ilanlar_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2026_02_27_225123_add_minimum_tracking_to_ilanlar_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2026_02_27_230158_align_rental_tables_to_phase17c_spec',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2026_03_03_085901_rename_status_to_aktiflik_durumu_in_danisman_chat_sessions',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2026_03_03_162000_create_owner_report_projections',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2026_03_04_064607_rename_forbidden_columns_to_aktiflik_durumu',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2026_03_06_080253_fix_ghost_foreign_keys_for_sab_v16',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2026_03_06_090149_create_analytics_read_models',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2026_03_06_154056_create_ai_opportunity_logs_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2026_03_06_180000_create_listing_search_projection_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (282,'2026_03_06_181000_create_ai_query_logs_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (283,'2026_03_06_223810_create_listing_translations_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (284,'2026_03_06_223812_create_ai_translation_logs_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (285,'2026_03_06_223813_add_source_locale_to_ilanlar_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (286,'2026_03_07_000001_sync_proj_listings_ghost_fields',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (287,'2026_03_07_000002_rename_proj_listings_ghost_fields',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (288,'2026_03_07_050956_create_buyer_match_logs_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (289,'2026_03_07_050957_create_buyer_intent_projection_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (290,'2026_03_07_050957_create_buyer_match_snapshots_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (291,'2026_03_07_050957_create_talep_match_projection_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (292,'2026_03_07_153001_create_deal_prediction_logs_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (293,'2026_03_07_153002_create_deal_prediction_snapshots_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (294,'2026_03_07_153003_create_deal_projections_tables',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (295,'2026_03_07_201626_fix_listing_translations_fields',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (296,'2026_03_07_205300_fix_kpi_snapshot_danisman_nullable',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (297,'2026_03_08_163500_add_missing_columns_to_listing_search_projection',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (298,'2026_03_08_205730_create_owner_discovery_projections',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (299,'2026_03_08_211522_create_market_valuation_reports_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (300,'2026_03_09_180000_create_ai_production_telemetry_tables',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (301,'2026_03_28_100001_create_prediction_snapshots_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2026_03_28_100002_create_listing_outcomes_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2026_03_28_100003_create_feedback_results_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2026_06_15_000001_create_governance_decisions_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (305,'2026_04_04_000001_sab3_decision_safety_layer',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (306,'2026_04_04_000002_sab4_multi_agent_orchestration',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (307,'2026_04_03_231337_add_sab8_action_result_to_governance_decisions',43);
CREATE TABLE `access_credentials` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`ilan_id` bigint unsigned NOT NULL,
`credential_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`credential_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`credential_location` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`is_active` tinyint(1) NOT NULL DEFAULT '1',
`requires_reset` tinyint(1) NOT NULL DEFAULT '0',
`last_reset_at` date DEFAULT NULL,
`expires_at` date DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `access_credentials_tenant_id_ilan_id_is_active_index` (`tenant_id`,`ilan_id`,`is_active`),
KEY `access_credentials_ilan_id_foreign` (`ilan_id`),
CONSTRAINT `access_credentials_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
CONSTRAINT `access_credentials_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

CREATE TABLE `admin_activity_events` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`entity_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'reservation, calendar, ...',
`entity_id` bigint unsigned NOT NULL,
`action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'create, confirm, cancel, close_calendar, ...',
`source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'admin, telegram, system',
`summary` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`context` json DEFAULT NULL,
`user_id` bigint unsigned DEFAULT NULL,
`telegram_user_id` bigint unsigned DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `admin_activity_events_user_id_foreign` (`user_id`),
KEY `admin_act_entity_idx` (`entity_type`,`entity_id`),
KEY `admin_act_action_source_idx` (`action`,`source`),
KEY `admin_act_created_idx` (`created_at`),
CONSTRAINT `admin_activity_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

CREATE TABLE `admin_notifications` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`user_id` bigint unsigned NOT NULL,
`channel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'reservation, calendar, system',
`event` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'reservation_created, reservation_cancelled, ...',
`title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`payload` json DEFAULT NULL,
`is_read` tinyint(1) NOT NULL DEFAULT '0',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `admin_notif_user_read_idx` (`user_id`,`is_read`),
KEY `admin_notif_user_created_idx` (`user_id`,`created_at`),
CONSTRAINT `admin_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

CREATE TABLE `ai_credit_balances` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`available_credits` int NOT NULL DEFAULT '0',
`used_credits` int NOT NULL DEFAULT '0',
`monthly_limit` int NOT NULL DEFAULT '0',
`last_reset_at` datetime DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `ai_credit_balances_tenant_id_unique` (`tenant_id`),
CONSTRAINT `ai_credit_balances_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`);

CREATE TABLE `ai_description_drafts` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ilan_id` bigint unsigned NOT NULL,
`user_id` bigint unsigned DEFAULT NULL,
`draft_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AI tarafından üretilen taslak içerik',
`original_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Orijinal aciklama (reject durumunda geri yükleme için)',
`durum` enum('taslak','onayli','uygulandi','reddedildi') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'taslak' COMMENT 'taslak=review bekliyor, onayli=owner onayladı, uygulandi=persist edildi, reddedildi=owner reddetti',
`provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Kullanılan AI provider (deepseek, openai, ollama)',
`model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Kullanılan model',
`metadata` json DEFAULT NULL COMMENT 'Token usage, latency vs',
`approved_by` bigint unsigned DEFAULT NULL,
`approved_at` timestamp NULL DEFAULT NULL,
`applied_at` timestamp NULL DEFAULT NULL COMMENT 'aciklama alanına yazıldığı tarih',
`rejected_by` bigint unsigned DEFAULT NULL,
`rejected_at` timestamp NULL DEFAULT NULL,
`rejection_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Owner reddetme notu',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `ai_description_drafts_user_id_foreign` (`user_id`),
KEY `ai_description_drafts_approved_by_foreign` (`approved_by`),
KEY `ai_description_drafts_rejected_by_foreign` (`rejected_by`),
KEY `ai_description_drafts_ilan_id_durum_index` (`ilan_id`,`durum`),
KEY `ai_description_drafts_ilan_id_created_at_index` (`ilan_id`,`created_at`),
CONSTRAINT `ai_description_drafts_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
CONSTRAINT `ai_description_drafts_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
CONSTRAINT `ai_description_drafts_rejected_by_foreign` FOREIGN KEY (`rejected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
CONSTRAINT `ai_description_drafts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

CREATE TABLE `ai_security_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`event_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'prompt_injection, sql_injection, spam_detected, high_anomaly_score, etc.',
`user_id` bigint unsigned NOT NULL COMMENT 'User who triggered the security event',
`context` json NOT NULL COMMENT 'Event-specific data (input, reason, pattern, etc.)',
`previous_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SHA-256 hash of previous log entry (null for first entry)',
`current_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA-256 hash of this entry (id + event_type + user_id + context + previous_hash + created_at)',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `ai_security_logs_current_hash_unique` (`current_hash`),
KEY `idx_user_event_time` (`user_id`,`event_type`,`created_at`),
KEY `ai_security_logs_event_type_index` (`event_type`),
KEY `ai_security_logs_user_id_index` (`user_id`),
CONSTRAINT `ai_security_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

CREATE TABLE `ai_storages` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`storage_key` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Benzersiz depolama anahtarı',
`data` json NOT NULL COMMENT 'AI veri payload (pattern, result, cache)',
`type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'pattern, result, cache, ...',
`context` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Anahtar prefix context''i',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `ai_storages_storage_key_unique` (`storage_key`),
KEY `ai_storage_type_idx` (`type`),
KEY `ai_storage_context_idx` (`context`);

CREATE TABLE `ai_telemetry` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL COMMENT 'Tenant ID for data isolation',
`provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'AI provider: openai, deepseek, anthropic',
`model_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Model name: gpt-4, deepseek-chat, etc.',
`feature` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Feature key: vision_analysis, smart_fields, etc.',
`response_time_ms` int unsigned NOT NULL COMMENT 'Response time in milliseconds',
`tokens_used` int unsigned NOT NULL DEFAULT '0' COMMENT 'Total tokens consumed',
`prompt_tokens` int unsigned NOT NULL DEFAULT '0' COMMENT 'Prompt tokens',
`completion_tokens` int unsigned NOT NULL DEFAULT '0' COMMENT 'Completion tokens',
`cost_usd` decimal(10,8) NOT NULL DEFAULT '0.00000000' COMMENT 'Cost in USD',
`prompt_metadata` json DEFAULT NULL COMMENT 'Prompt metadata (sanitized)',
`response_metadata` json DEFAULT NULL COMMENT 'Response metadata',
`aktiflik_kodu` smallint unsigned NOT NULL DEFAULT '200' COMMENT 'HTTP status code (200, 429, 500, etc.)',
`hata_mesaji` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Error message if aktiflik_kodu >= 400',
`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_tenant_created` (`tenant_id`,`created_at`),
KEY `idx_provider_created` (`provider`,`created_at`),
KEY `idx_feature_created` (`feature`,`created_at`),
KEY `idx_latency_created` (`response_time_ms`,`created_at`),
KEY `idx_status_created` (`aktiflik_kodu`,`created_at`),
KEY `ai_telemetry_tenant_id_index` (`tenant_id`),
KEY `ai_telemetry_provider_index` (`provider`),
KEY `ai_telemetry_feature_index` (`feature`),
KEY `ai_telemetry_response_time_ms_index` (`response_time_ms`),
KEY `ai_telemetry_aktiflik_kodu_index` (`aktiflik_kodu`),
KEY `ai_telemetry_created_at_index` (`created_at`);

CREATE TABLE `belgeler` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`user_id` bigint unsigned NOT NULL,
`ilan_id` bigint unsigned DEFAULT NULL,
`baslik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`dosya_yolu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`dosya_tipi` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`belge_turu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'diger',
`boyut_kb` int NOT NULL DEFAULT '0',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `belgeler_tenant_id_foreign` (`tenant_id`),
KEY `belgeler_user_id_foreign` (`user_id`),
KEY `belgeler_ilan_id_foreign` (`ilan_id`),
CONSTRAINT `belgeler_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE SET NULL,
CONSTRAINT `belgeler_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
CONSTRAINT `belgeler_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

CREATE TABLE `billing_ledger_entries` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`islem_turu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`islem_tutari` decimal(10,2) NOT NULL,
`currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
`reference_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`reference_id` bigint unsigned DEFAULT NULL,
`metadata` json DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
  `amount` decimal(15,2) NULL COMMENT 'Ledger entry amount',
  `type` varchar(30) NULL COMMENT 'credit|debit|adjustment',
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `billing_ledger_entries_tenant_id_foreign` (`tenant_id`),
CONSTRAINT `billing_ledger_entries_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`);

CREATE TABLE `bonuses` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned DEFAULT NULL,
`agent_id` bigint unsigned NOT NULL,
`target_month` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`prim_tutari` decimal(15,2) NOT NULL,
`odendi_mi` tinyint(1) NOT NULL DEFAULT '0',
`odeme_tarihi` timestamp NULL DEFAULT NULL,
`bonus_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'performance',
`reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`created_at` timestamp NULL DEFAULT NULL,
  `amount` decimal(15,2) NULL COMMENT 'Bonus amount',
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `unique_bonus_per_agent_month` (`agent_id`,`target_month`),
KEY `bonuses_agent_id_index` (`agent_id`),
KEY `idx_bonuses_tenant_id` (`tenant_id`),
CONSTRAINT `fk_bonuses_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

CREATE TABLE `channel_sync_executions` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`property_id` bigint unsigned NOT NULL,
`reservation_id` bigint unsigned DEFAULT NULL,
`channel` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`operation` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`block_reason` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`date_range_start` date NOT NULL,
`date_range_end` date NOT NULL,
`target_availability` tinyint(1) NOT NULL,
`synced_dates` json DEFAULT NULL,
`conflicts` json DEFAULT NULL,
`idempotency_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`correlation_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dispatched',
`attempts` tinyint unsigned NOT NULL DEFAULT '0',
`synced_count` int unsigned DEFAULT NULL,
`error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`processed_at` timestamp NULL DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `channel_sync_executions_tenant_key_channel_unique` (`tenant_id`,`idempotency_key`,`channel`),
KEY `channel_sync_executions_tenant_id_property_id_index` (`tenant_id`,`property_id`),
KEY `channel_sync_executions_property_id_status_index` (`property_id`,`status`),
KEY `channel_sync_executions_tenant_id_reservation_id_index` (`tenant_id`,`reservation_id`),
KEY `channel_sync_executions_correlation_id_index` (`correlation_id`),
KEY `channel_sync_executions_processed_at_index` (`processed_at`),
KEY `channel_sync_executions_tenant_channel_idx` (`tenant_id`,`channel`);

CREATE TABLE `commissions` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned DEFAULT NULL,
`ilan_id` bigint unsigned NOT NULL,
`agent_id` bigint unsigned NOT NULL,
`sale_price` decimal(15,2) NOT NULL,
`commission_rate` decimal(5,2) NOT NULL,
`total_commission` decimal(15,2) NOT NULL,
`office_share_percentage` decimal(5,2) NOT NULL,
`agent_share_percentage` decimal(5,2) NOT NULL,
`ofis_tutari` decimal(15,2) NOT NULL,
`danisman_tutari` decimal(15,2) NOT NULL,
`payment_state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
`payout_date` date DEFAULT NULL,
`calculated_by` bigint unsigned DEFAULT NULL,
`paid_by` bigint unsigned DEFAULT NULL,
`invoice_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
  `agent_amount` decimal(15,2) NULL COMMENT 'Agent commission portion',
  `office_amount` decimal(15,2) NULL COMMENT 'Office commission portion',
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `unique_commission_tenant_listing_agent` (`tenant_id`,`ilan_id`,`agent_id`),
KEY `commissions_ilan_id_index` (`ilan_id`),
KEY `commissions_agent_id_index` (`agent_id`),
KEY `idx_commissions_tenant_id` (`tenant_id`),
CONSTRAINT `fk_commissions_tenant_id` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

CREATE TABLE `communications` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned DEFAULT NULL,
`communicable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`communicable_id` bigint unsigned DEFAULT NULL,
`reservation_id` bigint unsigned DEFAULT NULL,
`channel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'telegram, whatsapp, instagram, email, web',
`source_mailbox` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email gelen mailbox: yalihanemlak.com.tr | gmail.com/yalihanemlak | channex | telegram | web',
`message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`subject` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Email konusu',
`sender_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`sender_phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`sender_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`platform` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'airbnb|booking.com|direct|unknown',
`gmail_labels` json DEFAULT NULL COMMENT 'Gmail label IDs: INBOX, UNREAD, IMPORTANT vb.',
`sender_instagram` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`sender_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Kanal bazlı unique sender ID',
`external_message_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`ai_analysis` json DEFAULT NULL COMMENT 'AI analiz sonuçları',
`severity` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'P0|P1|P2 — deterministic PHP policy',
`ai_extracted_data` json DEFAULT NULL COMMENT 'LLM: intent, language, source_platform, guest_name, reservation_ref, sentiment, is_urgent',
`reply_durumu` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bekliyor' COMMENT 'bekliyor, cevaplandi, arşivlendi',
`replied_at` timestamp NULL DEFAULT NULL,
`resolved_at` timestamp NULL DEFAULT NULL,
`resolved_by` bigint unsigned DEFAULT NULL,
`created_by` bigint unsigned DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `comm_tenant_msgid_unique` (`tenant_id`,`external_message_id`),
KEY `communications_communicable_type_communicable_id_index` (`communicable_type`,`communicable_id`),
KEY `communications_created_by_foreign` (`created_by`),
KEY `comm_channel_reply_idx` (`channel`,`reply_durumu`),
KEY `comm_sender_id_idx` (`sender_id`),
KEY `comm_tenant_id_idx` (`tenant_id`),
KEY `comm_reservation_idx` (`reservation_id`),
KEY `communications_resolved_by_foreign` (`resolved_by`),
CONSTRAINT `communications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
CONSTRAINT `communications_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `property_reservations` (`id`) ON DELETE SET NULL,
CONSTRAINT `communications_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
CONSTRAINT `communications_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL;

CREATE TABLE `config_options` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`option_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Config anahtarı, örn: oda_sayisi_options',
`option_type` enum('simple','associative','object_array','nested') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'simple',
`option_value` json NOT NULL COMMENT 'JSON formatında config değeri',
`kategori_id` bigint unsigned DEFAULT NULL,
`yayin_tipi_id` bigint unsigned DEFAULT NULL,
`label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Yönetim paneli etiket',
`description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`icon` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
`display_order` int NOT NULL DEFAULT '0',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `config_options_kategori_id_foreign` (`kategori_id`),
KEY `config_options_yayin_tipi_id_foreign` (`yayin_tipi_id`),
KEY `config_opt_key_kat_yay_idx` (`option_key`,`kategori_id`,`yayin_tipi_id`),
KEY `config_opt_aktif_order_idx` (`aktiflik_durumu`,`display_order`),
CONSTRAINT `config_options_kategori_id_foreign` FOREIGN KEY (`kategori_id`) REFERENCES `ilan_kategorileri` (`id`) ON DELETE SET NULL,
CONSTRAINT `config_options_yayin_tipi_id_foreign` FOREIGN KEY (`yayin_tipi_id`) REFERENCES `yayin_tipi_sablonlari` (`id`) ON DELETE SET NULL;

CREATE TABLE `cortex_neural_connections` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ulke_id` bigint unsigned DEFAULT NULL,
`source_module` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`target_module` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`connection_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'direct',
`connection_strength` decimal(8,2) NOT NULL DEFAULT '0.00',
`interaction_count` int unsigned NOT NULL DEFAULT '0',
`success_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
`avg_performance` decimal(8,2) NOT NULL DEFAULT '0.00',
`learned_patterns` json DEFAULT NULL,
`usage_context` json DEFAULT NULL,
`first_interaction_at` timestamp NULL DEFAULT NULL,
`last_interaction_at` timestamp NULL DEFAULT NULL,
`aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `cortex_neural_connections_source_module_target_module_index` (`source_module`,`target_module`),
KEY `cortex_neural_connections_connection_type_aktiflik_durumu_index` (`connection_type`,`aktiflik_durumu`),
KEY `cortex_neural_connections_ulke_id_index` (`ulke_id`),
KEY `cortex_neural_connections_aktiflik_durumu_index` (`aktiflik_durumu`);

CREATE TABLE `danisman_yorumlar` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`danisman_id` bigint unsigned NOT NULL,
`musteri_adi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`yorum` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`rating` tinyint unsigned NOT NULL DEFAULT '5',
`onay_durumu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `danisman_yorumlar_danisman_id_index` (`danisman_id`),
KEY `danisman_yorumlar_onay_durumu_index` (`onay_durumu`),
CONSTRAINT `danisman_yorumlar_danisman_id_foreign` FOREIGN KEY (`danisman_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

CREATE TABLE `demirbas_kategorileri` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
`display_order` int NOT NULL DEFAULT '0',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `demirbas_kategorileri_slug_unique` (`slug`),
KEY `dk_aktif_order_idx` (`aktiflik_durumu`,`display_order`);

CREATE TABLE `demirbaslar` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ulke_id` bigint unsigned DEFAULT NULL,
`name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`brand` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`kategori_id` bigint unsigned DEFAULT NULL,
`ilan_kategori_id` bigint unsigned DEFAULT NULL,
`yayin_tipi_id` bigint unsigned DEFAULT NULL,
`aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
`display_order` int unsigned NOT NULL DEFAULT '0',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `demirbaslar_slug_unique` (`slug`),
KEY `demirbaslar_ulke_id_index` (`ulke_id`),
KEY `demirbaslar_kategori_id_index` (`kategori_id`),
KEY `demirbaslar_ilan_kategori_id_index` (`ilan_kategori_id`),
KEY `demirbaslar_yayin_tipi_id_index` (`yayin_tipi_id`),
KEY `demirbaslar_aktiflik_durumu_index` (`aktiflik_durumu`);

CREATE TABLE `etki_alani_olaylari` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`aggregate_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Domain aggregate class (Lead, Ilan, Kisi)',
`aggregate_id` bigint unsigned NOT NULL COMMENT 'Aggregate root entity ID',
`event_type` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Fully qualified event class name',
`sequence_number` int unsigned NOT NULL COMMENT 'Monotonic sequence for ordered replay',
`payload` json NOT NULL COMMENT 'Serialized event data',
`encrypted_payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'GDPR-compliant encrypted sensitive data',
`user_id` bigint unsigned DEFAULT NULL COMMENT 'User who triggered the event',
`ip_adresi` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
UNIQUE KEY `uniq_aggregate_sequence` (`aggregate_type`,`aggregate_id`,`sequence_number`),
KEY `idx_tenant_aggregate` (`tenant_id`,`aggregate_type`,`aggregate_id`),
KEY `idx_aggregate_sequence` (`aggregate_type`,`aggregate_id`,`sequence_number`),
KEY `etki_alani_olaylari_event_type_index` (`event_type`),
KEY `etki_alani_olaylari_created_at_index` (`created_at`),
KEY `etki_alani_olaylari_user_id_foreign` (`user_id`),
KEY `etki_alani_olaylari_tenant_id_index` (`tenant_id`),
CONSTRAINT `etki_alani_olaylari_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
CONSTRAINT `etki_alani_olaylari_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

CREATE TABLE `etki_alani_olaylari_hatali` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`olay_turu` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Event type that failed',
`kaynak_kimligi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Source aggregate identifier',
`olay_verisi` json NOT NULL COMMENT 'Original event data',
`hata_mesaji` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Exception message',
`stack_trace` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Full stack trace for forensic analysis',
`islem_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '1: İncelemede, 2: Yeniden Oynatıldı, 3: Arşivlendi',
`olusturulma_zamani` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`islenme_zamani` timestamp NULL DEFAULT NULL COMMENT 'When manually replayed',
PRIMARY KEY (`id`),
KEY `idx_tenant_event_type` (`tenant_id`,`olay_turu`),
KEY `etki_alani_olaylari_hatali_islem_durumu_index` (`islem_durumu`),
KEY `etki_alani_olaylari_hatali_olusturulma_zamani_index` (`olusturulma_zamani`),
KEY `etki_alani_olaylari_hatali_tenant_id_index` (`tenant_id`),
CONSTRAINT `etki_alani_olaylari_hatali_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT;

CREATE TABLE `feature_category_translations` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`feature_category_id` bigint unsigned NOT NULL,
`locale` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'tr, en, de, ...',
`name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `fct_category_locale_unique` (`feature_category_id`,`locale`),
CONSTRAINT `feature_category_translations_feature_category_id_foreign` FOREIGN KEY (`feature_category_id`) REFERENCES `feature_categories` (`id`) ON DELETE CASCADE;

CREATE TABLE `feature_flags` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`is_enabled` tinyint(1) NOT NULL DEFAULT '0',
`rules` json DEFAULT NULL,
`description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `feature_flags_key_unique` (`key`);

CREATE TABLE `feature_translations` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`feature_id` bigint unsigned NOT NULL,
`locale` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'tr, en, de, ...',
`name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `ft_feature_locale_unique` (`feature_id`,`locale`),
CONSTRAINT `feature_translations_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE;

CREATE TABLE `feature_values` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`valuable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`valuable_id` bigint unsigned NOT NULL,
`feature_id` bigint unsigned NOT NULL,
`value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Ham değer; typed_value accessor parse eder',
`value_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'string, integer, boolean, json',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `feature_values_valuable_type_valuable_id_index` (`valuable_type`,`valuable_id`),
KEY `feature_values_feature_id_foreign` (`feature_id`),
KEY `fv_valuable_feature_idx` (`valuable_type`,`valuable_id`,`feature_id`),
CONSTRAINT `feature_values_feature_id_foreign` FOREIGN KEY (`feature_id`) REFERENCES `features` (`id`) ON DELETE CASCADE;

CREATE TABLE `financial_settings` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned DEFAULT NULL,
`default_commission_rate` decimal(5,2) NOT NULL DEFAULT '3.00',
`min_commission_rate` decimal(5,2) NOT NULL DEFAULT '1.00',
`max_commission_rate` decimal(5,2) NOT NULL DEFAULT '10.00',
`office_share` decimal(5,2) NOT NULL DEFAULT '50.00',
`agent_share` decimal(5,2) NOT NULL DEFAULT '50.00',
`payment_delay_days` int NOT NULL DEFAULT '30',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `financial_settings_tenant_id_foreign` (`tenant_id`),
CONSTRAINT `financial_settings_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`);

CREATE TABLE `governance_alerts` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`data` json DEFAULT NULL,
`severity` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
`acknowledged` tinyint(1) NOT NULL DEFAULT '0',
`acknowledged_at` timestamp NULL DEFAULT NULL,
`acknowledged_by` bigint unsigned DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `governance_alerts_tip_created_at_index` (`tip`,`created_at`),
KEY `governance_alerts_tip_index` (`tip`),
KEY `governance_alerts_acknowledged_index` (`acknowledged`),
KEY `governance_alerts_acknowledged_by_foreign` (`acknowledged_by`),
CONSTRAINT `governance_alerts_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

CREATE TABLE `governance_audit_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`entity_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'İlgili model sınıfı (örn. App\\Models\\PropertyConfigVersion)',
`entity_id` bigint unsigned NOT NULL COMMENT 'Etkilenen modelin ID''si',
`action_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Örn. DRAFT_CREATED, PROMOTED',
`from_state` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Önceki durumu',
`to_state` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Yeni durumu',
`actor_id` bigint unsigned DEFAULT NULL COMMENT 'İşlemi yapan User ID, sistem işlemleri için NULL',
`ulke_id` bigint unsigned DEFAULT NULL,
`correlation_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'İzlenebilirlik için istek/process ID',
`reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Görevin nedeni veya açıklaması',
`payload_snapshot` json DEFAULT NULL COMMENT 'Değişiklik anındaki metadata / diff base',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_gov_audit_entity` (`entity_type`,`entity_id`),
KEY `idx_gov_audit_corr_id` (`correlation_id`),
KEY `idx_gov_audit_actor_id` (`actor_id`),
KEY `idx_gov_audit_created_at` (`created_at`),
KEY `governance_audit_logs_ulke_id_index` (`ulke_id`),
CONSTRAINT `governance_audit_logs_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
CONSTRAINT `governance_audit_logs_ulke_id_foreign` FOREIGN KEY (`ulke_id`) REFERENCES `ulkeler` (`id`) ON DELETE SET NULL;

CREATE TABLE `governance_events` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`metric` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`tags` json DEFAULT NULL,
`tenant_id` bigint unsigned DEFAULT NULL,
`operation_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`severity` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
`is_violation` tinyint(1) NOT NULL DEFAULT '0',
`occurred_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `governance_events_is_violation_occurred_at_index` (`is_violation`,`occurred_at`),
KEY `governance_events_tenant_id_is_violation_index` (`tenant_id`,`is_violation`),
KEY `governance_events_metric_index` (`metric`),
KEY `governance_events_tenant_id_index` (`tenant_id`),
KEY `governance_events_is_violation_index` (`is_violation`),
KEY `governance_events_occurred_at_index` (`occurred_at`);

CREATE TABLE `guest_messages` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`tenant_id` bigint unsigned DEFAULT NULL,
`ilan_id` bigint unsigned DEFAULT NULL,
`channel` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'whatsapp',
`sender_phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`sender_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`external_message_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`message_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`message_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'text',
`routing_decision` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`reservation_id` bigint unsigned DEFAULT NULL,
`intent` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`confidence` decimal(4,3) DEFAULT NULL,
`required_fact_keys` json DEFAULT NULL,
`response_mode` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`response_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`gorev_id` bigint unsigned DEFAULT NULL,
`escalated` tinyint(1) NOT NULL DEFAULT '0',
`escalation_reason` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `guest_messages_external_message_id_unique` (`external_message_id`),
KEY `guest_messages_tenant_id_index` (`tenant_id`),
KEY `guest_messages_ilan_id_index` (`ilan_id`),
KEY `guest_messages_sender_phone_index` (`sender_phone`),
KEY `guest_messages_routing_decision_index` (`routing_decision`),
KEY `guest_messages_reservation_id_index` (`reservation_id`),
KEY `guest_messages_intent_index` (`intent`),
KEY `guest_messages_response_mode_index` (`response_mode`),
KEY `guest_messages_gorev_id_index` (`gorev_id`),
KEY `guest_messages_escalated_index` (`escalated`),
CONSTRAINT `guest_messages_gorev_id_foreign` FOREIGN KEY (`gorev_id`) REFERENCES `gorevler` (`id`) ON DELETE SET NULL,
CONSTRAINT `guest_messages_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `property_reservations` (`id`) ON DELETE SET NULL;

CREATE TABLE `hermes_analytics` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`event_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`tenant_id` bigint unsigned DEFAULT NULL,
`date` date NOT NULL,
`total_count` int unsigned NOT NULL DEFAULT '0',
`success_count` int unsigned NOT NULL DEFAULT '0',
`failure_count` int unsigned NOT NULL DEFAULT '0',
`avg_duration_ms` double(8,2) NOT NULL DEFAULT '0.00',
`metadata` json DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `hermes_analytics_event_name_tenant_id_date_index` (`event_name`,`tenant_id`,`date`),
KEY `hermes_analytics_tenant_id_date_index` (`tenant_id`,`date`),
KEY `hermes_analytics_event_name_index` (`event_name`),
KEY `hermes_analytics_tenant_id_index` (`tenant_id`),
KEY `hermes_analytics_date_index` (`date`);

CREATE TABLE `hermes_event_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`event_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`event_class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`payload` json NOT NULL,
`tenant_id` bigint unsigned DEFAULT NULL,
`occurred_at` timestamp NOT NULL,
`processed_at` timestamp NULL DEFAULT NULL,
`status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
`handler_results` json DEFAULT NULL,
`error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `hermes_event_logs_event_name_tenant_id_index` (`event_name`,`tenant_id`),
KEY `hermes_event_logs_status_occurred_at_index` (`status`,`occurred_at`),
KEY `hermes_event_logs_event_name_index` (`event_name`),
KEY `hermes_event_logs_tenant_id_index` (`tenant_id`),
KEY `hermes_event_logs_status_index` (`status`);

CREATE TABLE `ilan_arsa_details` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ilan_id` bigint unsigned NOT NULL,
`ada_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`parsel_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`imar_durumu` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`kaks` decimal(5,2) DEFAULT NULL,
`taks` decimal(5,2) DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `ilan_arsa_details_ilan_id_unique` (`ilan_id`),
CONSTRAINT `ilan_arsa_details_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE;

CREATE TABLE `ilan_calendar_feeds` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ilan_id` bigint unsigned NOT NULL,
`token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
`created_by_user_id` bigint unsigned DEFAULT NULL,
`revoked_at` timestamp NULL DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `ilan_calendar_feeds_token_unique` (`token`),
KEY `ilan_calendar_feeds_created_by_user_id_foreign` (`created_by_user_id`),
KEY `ilan_calendar_feeds_ilan_id_aktiflik_durumu_index` (`ilan_id`,`aktiflik_durumu`),
CONSTRAINT `ilan_calendar_feeds_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
CONSTRAINT `ilan_calendar_feeds_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE;

CREATE TABLE `ilan_demirbas` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ilan_id` bigint unsigned NOT NULL,
`demirbas_id` bigint unsigned NOT NULL,
`brand` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`quantity` int unsigned NOT NULL DEFAULT '1',
`notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`display_order` int unsigned NOT NULL DEFAULT '0',
`aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `ilan_demirbas_ilan_id_demirbas_id_unique` (`ilan_id`,`demirbas_id`),
KEY `ilan_demirbas_ilan_id_index` (`ilan_id`),
KEY `ilan_demirbas_demirbas_id_index` (`demirbas_id`);

CREATE TABLE `ilan_no_sequences` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tip_kodu` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'STL, KRL, YZL, GNL',
`kategori_kodu` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'DRE, VLA, ARS, ISY, KNT, TCR',
`yil` int NOT NULL COMMENT '2024, 2025, ...',
`son_sira` int NOT NULL DEFAULT '0' COMMENT 'Son kullanılan sıra numarası',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `idx_sequence_unique` (`tip_kodu`,`kategori_kodu`,`yil`),
KEY `idx_sequence_lookup` (`tip_kodu`,`kategori_kodu`,`yil`);

CREATE TABLE `ilan_notlari` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ulke_id` bigint unsigned DEFAULT NULL,
`ilan_id` bigint unsigned NOT NULL,
`user_id` bigint unsigned DEFAULT NULL,
`not_icerigi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`not_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'genel',
`onemli_mi` tinyint(1) NOT NULL DEFAULT '0',
`is_ai_generated` tinyint(1) NOT NULL DEFAULT '0',
`channel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `ilan_notlari_ilan_id_not_tipi_index` (`ilan_id`,`not_tipi`),
KEY `ilan_notlari_ilan_id_is_ai_generated_index` (`ilan_id`,`is_ai_generated`),
KEY `ilan_notlari_ulke_id_index` (`ulke_id`),
KEY `ilan_notlari_ilan_id_index` (`ilan_id`),
KEY `ilan_notlari_user_id_index` (`user_id`),
KEY `ilan_notlari_not_tipi_index` (`not_tipi`),
KEY `ilan_notlari_is_ai_generated_index` (`is_ai_generated`);

CREATE TABLE `ilan_ticari_details` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ilan_id` bigint unsigned NOT NULL,
`isyeri_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`kira_bilgisi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`kira_getirisi` decimal(10,2) DEFAULT NULL,
`kat_adedi` int DEFAULT NULL,
`ofis_adedi` int DEFAULT NULL,
`asansor_var` tinyint(1) NOT NULL DEFAULT '0',
`depo_var` tinyint(1) NOT NULL DEFAULT '0',
`otopark_var` tinyint(1) NOT NULL DEFAULT '0',
`ek_bilgiler` json DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `ilan_ticari_details_ilan_id_unique` (`ilan_id`),
CONSTRAINT `ilan_ticari_details_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE;

CREATE TABLE `ilan_turizm_details` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ilan_id` bigint unsigned NOT NULL,
`check_in_saati` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`check_out_saati` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`min_konaklama` int DEFAULT NULL,
`max_misafir` int DEFAULT NULL,
`gunluk_fiyat` decimal(12,2) DEFAULT NULL,
`temizlik_ucreti` decimal(10,2) DEFAULT NULL,
`havuz_var` tinyint(1) NOT NULL DEFAULT '0',
`sezon_baslangic` date DEFAULT NULL,
`sezon_bitis` date DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `ilan_turizm_details_ilan_id_unique` (`ilan_id`),
CONSTRAINT `ilan_turizm_details_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE;

CREATE TABLE `ilan_videolari` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ilan_id` bigint unsigned NOT NULL,
`video_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`video_tipi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'youtube',
`display_order` int NOT NULL DEFAULT '0',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `ilan_videolari_ilan_id_foreign` (`ilan_id`),
CONSTRAINT `ilan_videolari_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE;

CREATE TABLE `ilanlar_read_model` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`ulke_id` bigint unsigned DEFAULT NULL,
`ilan_id` bigint unsigned NOT NULL COMMENT 'FK to ilanlar.id',
`son_islenen_sira_numarasi` int unsigned NOT NULL DEFAULT '0' COMMENT 'Last processed event sequence number for idempotency',
`baslik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`aciklama` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`yayin_durumu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'draft|published|archived',
`aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '1=active, 0=inactive',
`one_cikan` tinyint NOT NULL DEFAULT '0' COMMENT '1=featured, 0=normal',
`kapak_resmi` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Primary image URL',
`ana_kategori_id` bigint unsigned DEFAULT NULL COMMENT 'FK to kategoriler (main type)',
`alt_kategori_id` bigint unsigned DEFAULT NULL COMMENT 'FK to kategoriler (sub category)',
`il` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Province name',
`ilce` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'District name',
`mahalle` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Neighborhood',
`lat` decimal(10,7) DEFAULT NULL COMMENT 'Latitude',
`lng` decimal(10,7) DEFAULT NULL COMMENT 'Longitude',
`fiyat` decimal(15,2) DEFAULT NULL COMMENT 'Price in TRY',
`doviz_birimi` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY' COMMENT 'Currency code',
`oda_sayisi` smallint unsigned DEFAULT NULL COMMENT 'Number of rooms',
`banyo_sayisi` smallint unsigned DEFAULT NULL COMMENT 'Number of bathrooms',
`brut_alan_m2` int unsigned DEFAULT NULL COMMENT 'Gross area in m²',
`net_alan_m2` int unsigned DEFAULT NULL COMMENT 'Net area in m²',
`bina_yasi` smallint unsigned DEFAULT NULL COMMENT 'Building age in years',
`bulundugu_kat` smallint unsigned DEFAULT NULL COMMENT 'Floor number',
`sahip_id` bigint unsigned DEFAULT NULL COMMENT 'FK to kisiler (owner)',
`sorumlu_danisman_id` bigint unsigned DEFAULT NULL COMMENT 'FK to users (assigned agent)',
`display_order` int unsigned NOT NULL DEFAULT '0' COMMENT 'Sort order for listings',
`slug` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SEO-friendly URL slug',
`goruntulenme_sayisi` int unsigned NOT NULL DEFAULT '0' COMMENT 'View count',
`favori_sayisi` int unsigned NOT NULL DEFAULT '0' COMMENT 'Favorite count',
`iletisim_sayisi` int unsigned NOT NULL DEFAULT '0' COMMENT 'Contact count',
`ilan_olusturulma_tarihi` timestamp NULL DEFAULT NULL COMMENT 'Original property creation date',
`son_guncelleme_tarihi` timestamp NULL DEFAULT NULL COMMENT 'Last property update date',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `ilanlar_read_model_ilan_id_unique` (`ilan_id`),
UNIQUE KEY `ilanlar_read_model_slug_unique` (`slug`),
KEY `idx_ilanlar_tenant_status` (`tenant_id`,`yayin_durumu`,`aktiflik_durumu`),
KEY `idx_tenant_location_price` (`tenant_id`,`il`,`fiyat`),
KEY `idx_tenant_category_price` (`tenant_id`,`ana_kategori_id`,`fiyat`),
KEY `idx_tenant_featured` (`tenant_id`,`one_cikan`,`display_order`),
KEY `idx_agent_status` (`sorumlu_danisman_id`,`yayin_durumu`),
KEY `ilanlar_read_model_tenant_id_index` (`tenant_id`),
KEY `ilanlar_read_model_ulke_id_index` (`ulke_id`),
KEY `ilanlar_read_model_yayin_durumu_index` (`yayin_durumu`),
KEY `ilanlar_read_model_aktiflik_durumu_index` (`aktiflik_durumu`),
KEY `ilanlar_read_model_one_cikan_index` (`one_cikan`),
KEY `ilanlar_read_model_ana_kategori_id_index` (`ana_kategori_id`),
KEY `ilanlar_read_model_alt_kategori_id_index` (`alt_kategori_id`),
KEY `ilanlar_read_model_il_index` (`il`),
KEY `ilanlar_read_model_ilce_index` (`ilce`),
KEY `ilanlar_read_model_lat_index` (`lat`),
KEY `ilanlar_read_model_lng_index` (`lng`),
KEY `ilanlar_read_model_fiyat_index` (`fiyat`),
KEY `ilanlar_read_model_brut_alan_m2_index` (`brut_alan_m2`),
KEY `ilanlar_read_model_sahip_id_index` (`sahip_id`),
KEY `ilanlar_read_model_sorumlu_danisman_id_index` (`sorumlu_danisman_id`),
KEY `ilanlar_read_model_display_order_index` (`display_order`),
CONSTRAINT `ilanlar_read_model_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
CONSTRAINT `ilanlar_read_model_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

CREATE TABLE `kisiler_read_model` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`uuid` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`ad_soyad` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`telefon_numarasi` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`eposta_adresi` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`musteri_segmenti` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Standart',
`iletisim_tercihleri` json DEFAULT NULL,
`kimlik_dogrulama_durumu` tinyint(1) NOT NULL DEFAULT '0',
`aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
`son_islenen_sira_numarasi` bigint unsigned NOT NULL DEFAULT '0',
`olusturulma_zamani` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`degistirilme_zamani` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `kisiler_read_model_uuid_unique` (`uuid`),
KEY `idx_tenant_aktif_segment` (`tenant_id`,`aktiflik_durumu`,`musteri_segmenti`),
KEY `kisiler_read_model_uuid_index` (`uuid`),
KEY `kisiler_read_model_telefon_numarasi_index` (`telefon_numarasi`),
CONSTRAINT `kisiler_read_model_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT;

CREATE TABLE `leads_read_model` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`ulke_id` bigint unsigned DEFAULT NULL COMMENT 'Country isolation (HasCountryScope)',
`uuid` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Aggregate root identifier',
`platform` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'whatsapp, facebook, web, etc.',
`platform_user_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'External platform user ID',
`message_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`crm_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '1: Yeni, 2: Aranacak, 3: Görüşüldü, etc.',
`assigned_to` bigint unsigned DEFAULT NULL COMMENT 'Assigned advisor user_id',
`contact_attempts` int unsigned NOT NULL DEFAULT '0',
`last_contact_at` timestamp NULL DEFAULT NULL,
`converted_at` timestamp NULL DEFAULT NULL,
`aktiflik_durumu` tinyint NOT NULL DEFAULT '1',
`son_islenen_sira_numarasi` int unsigned NOT NULL DEFAULT '0' COMMENT 'Last processed sequence number for idempotency',
`olusturulma_zamani` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`degistirilme_zamani` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `leads_read_model_uuid_unique` (`uuid`),
KEY `idx_tenant_status` (`tenant_id`,`crm_durumu`),
KEY `idx_tenant_advisor` (`tenant_id`,`assigned_to`),
KEY `idx_platform_user` (`platform`,`platform_user_id`),
KEY `leads_read_model_olusturulma_zamani_index` (`olusturulma_zamani`),
KEY `leads_read_model_tenant_id_index` (`tenant_id`),
KEY `leads_read_model_ulke_id_index` (`ulke_id`),
KEY `leads_read_model_crm_durumu_index` (`crm_durumu`),
KEY `leads_read_model_assigned_to_index` (`assigned_to`),
KEY `leads_read_model_aktiflik_durumu_index` (`aktiflik_durumu`),
CONSTRAINT `leads_read_model_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
CONSTRAINT `leads_read_model_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

CREATE TABLE `mesajlar` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`gonderen_id` bigint unsigned NOT NULL,
`alici_id` bigint unsigned NOT NULL,
`ilan_id` bigint unsigned DEFAULT NULL,
`icerik` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`okundu_mu` tinyint(1) NOT NULL DEFAULT '0',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `mesajlar_tenant_id_foreign` (`tenant_id`),
KEY `mesajlar_gonderen_id_foreign` (`gonderen_id`),
KEY `mesajlar_alici_id_foreign` (`alici_id`),
KEY `mesajlar_ilan_id_foreign` (`ilan_id`),
CONSTRAINT `mesajlar_alici_id_foreign` FOREIGN KEY (`alici_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
CONSTRAINT `mesajlar_gonderen_id_foreign` FOREIGN KEY (`gonderen_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
CONSTRAINT `mesajlar_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE SET NULL,
CONSTRAINT `mesajlar_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

CREATE TABLE `notification_templates` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`channel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`provider_template_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`language` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tr',
`display_order` int NOT NULL DEFAULT '0' COMMENT 'Sıralama (Context7: order → display_order)',
`aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '0=inactive, 1=active (Context7 canonical)',
`metadata` json DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `notification_templates_key_channel_language_unique` (`key`,`channel`,`language`),
KEY `notification_templates_key_index` (`key`),
KEY `notification_templates_channel_index` (`channel`);

CREATE TABLE `oauth_tokens` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`service` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'gmail, google-drive, vs.',
`tenant_id` bigint unsigned NOT NULL,
`encrypted_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Crypt::encryptString ile sifreli',
`token_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'refresh_token',
`expires_at` timestamp NULL DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `oauth_tokens_service_tenant_unique` (`service`,`tenant_id`),
KEY `oauth_tokens_tenant_id_index` (`tenant_id`),
CONSTRAINT `oauth_tokens_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

CREATE TABLE `openclaw_audit_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`event_type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`agent_source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`agent_scope` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`correlation_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`token_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`route` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`http_method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`http_durum_kodu` smallint unsigned DEFAULT NULL,
`ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`payload_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`payload_size` int unsigned DEFAULT NULL,
`duration_ms` double(8,2) DEFAULT NULL,
`basarili` tinyint(1) NOT NULL DEFAULT '0',
`rejection_reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`service_class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`service_method` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`metadata` json DEFAULT NULL,
`olusturma_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
PRIMARY KEY (`id`),
KEY `openclaw_audit_logs_correlation_id_index` (`correlation_id`),
KEY `openclaw_audit_logs_event_type_olusturma_tarihi_index` (`event_type`,`olusturma_tarihi`),
KEY `openclaw_audit_logs_agent_source_olusturma_tarihi_index` (`agent_source`,`olusturma_tarihi`),
KEY `openclaw_audit_logs_basarili_olusturma_tarihi_index` (`basarili`,`olusturma_tarihi`),
KEY `openclaw_audit_logs_token_hash_olusturma_tarihi_index` (`token_hash`,`olusturma_tarihi`);

CREATE TABLE `outbound_notifications` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`channel` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`recipient` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`template_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`payload_data` json DEFAULT NULL,
`gonderim_durumu` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
`deneme_sayisi` int NOT NULL DEFAULT '0',
`hata_mesaji` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`gonderim_tarihi` datetime DEFAULT NULL,
`son_deneme_tarihi` datetime DEFAULT NULL,
`basarisiz_olma_tarihi` timestamp NULL DEFAULT NULL,
`provider_response` json DEFAULT NULL,
`display_order` int NOT NULL DEFAULT '0' COMMENT 'Sıralama (Context7: order → display_order)',
`aktiflik_durumu` tinyint NOT NULL DEFAULT '1' COMMENT '0=inactive, 1=active (Context7 canonical)',
`created_at` timestamp NULL DEFAULT NULL,
  `error_message` text NULL COMMENT 'Last send error',
  `last_attempt_at` timestamp NULL COMMENT 'Last delivery attempt',
  `retry_count` int NULL COMMENT 'Retry counter',
  `sent_at` timestamp NULL COMMENT 'When message was sent',
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `outbound_notifications_channel_index` (`channel`),
KEY `outbound_notifications_recipient_index` (`recipient`),
KEY `outbound_notifications_template_key_index` (`template_key`),
KEY `outbound_notifications_gonderim_durumu_index` (`gonderim_durumu`);

CREATE TABLE `outbox_entries` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`event_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`payload` json NOT NULL,
`yayin_durumu` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
`attempts` int NOT NULL DEFAULT '0',
`error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`idempotency_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`processed_at` timestamp NULL DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `outbox_entries_idempotency_key_unique` (`idempotency_key`),
KEY `outbox_entries_event_key_index` (`event_key`),
KEY `outbox_entries_yayin_durumu_index` (`yayin_durumu`);

CREATE TABLE `owner_login_tokens` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL COMMENT 'RULE-T1: zorunlu tenant izolasyonu',
`user_id` bigint unsigned NOT NULL,
`token_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`giris_kanali` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
`gecerlilik_bitis` timestamp NULL DEFAULT NULL,
`kullanildi` tinyint(1) NOT NULL DEFAULT '0',
`kullanilan_ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `owner_login_tokens_token_hash_unique` (`token_hash`),
KEY `owner_login_tokens_tenant_id_foreign` (`tenant_id`),
KEY `owner_login_tokens_user_id_kullanildi_index` (`user_id`,`kullanildi`),
KEY `owner_login_tokens_gecerlilik_bitis_index` (`gecerlilik_bitis`),
CONSTRAINT `owner_login_tokens_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
CONSTRAINT `owner_login_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

CREATE TABLE `payments` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL COMMENT 'RULE-T1: zorunlu tenant izolasyonu',
`ulke_id` bigint unsigned DEFAULT NULL,
`reservation_id` bigint unsigned NOT NULL,
`amount` decimal(15,2) NOT NULL,
`currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
`payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'mock' COMMENT 'kart|eft|havale|nakit|mock — sağlayıcı entegrasyonu yok',
`status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending' COMMENT 'pending | paid | failed (TransactionStatus)',
`reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Banka referansı / makbuz no',
`notes` text COLLATE utf8mb4_unicode_ci,
`idempotency_key` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Aynı ödemenin iki kez kaydedilmesini önler',
`recorded_by` bigint unsigned NOT NULL,
`verified_by` bigint unsigned DEFAULT NULL,
`verified_at` timestamp NULL DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `payments_idempotency_key_unique` (`idempotency_key`),
KEY `payments_reservation_id_foreign` (`reservation_id`),
KEY `payments_recorded_by_foreign` (`recorded_by`),
KEY `payments_verified_by_foreign` (`verified_by`),
KEY `payments_tenant_id_reservation_id_index` (`tenant_id`,`reservation_id`),
KEY `payments_tenant_id_status_index` (`tenant_id`,`status`),
KEY `payments_ulke_id_index` (`ulke_id`),
CONSTRAINT `payments_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`),
CONSTRAINT `payments_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `property_reservations` (`id`) ON DELETE CASCADE,
CONSTRAINT `payments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
CONSTRAINT `payments_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

CREATE TABLE `plans` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`price` decimal(10,2) NOT NULL DEFAULT '0.00',
`currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
`billing_cycle_days` int NOT NULL DEFAULT '30',
`features` json DEFAULT NULL,
`is_active` tinyint(1) NOT NULL DEFAULT '1',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `plans_slug_unique` (`slug`);

CREATE TABLE `portfolio_drive_workspaces` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ilan_id` bigint unsigned NOT NULL,
`tenant_id` bigint unsigned DEFAULT NULL,
`drive_folder_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`drive_folder_url` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`workspace_status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'creating',
`lifecycle_state` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'workspace_created',
`state_changed_at` timestamp NULL DEFAULT NULL,
`workspace_created_at` timestamp NULL DEFAULT NULL,
`ai_completion_percent` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '0-100: AI workforce pipeline completion percentage',
`ai_completion_flags` json DEFAULT NULL COMMENT '{"photo_agent": true, "description_agent": false, ...}',
`root_folder_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`portfolio_no` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`subfolders_json` json DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`drive_webhook_channel_json` json DEFAULT NULL COMMENT 'Google Drive push notification channel metadata',
`metadata_json` json DEFAULT NULL COMMENT 'Generic metadata: tracked Drive files, KPI sheets, sync state',
PRIMARY KEY (`id`),
UNIQUE KEY `portfolio_drive_workspaces_drive_folder_id_unique` (`drive_folder_id`),
KEY `portfolio_drive_workspaces_ilan_id_tenant_id_index` (`ilan_id`,`tenant_id`),
KEY `portfolio_drive_workspaces_workspace_status_tenant_id_index` (`workspace_status`,`tenant_id`),
KEY `portfolio_drive_workspaces_portfolio_no_tenant_id_index` (`portfolio_no`,`tenant_id`),
KEY `portfolio_drive_workspaces_ilan_id_index` (`ilan_id`),
KEY `portfolio_drive_workspaces_tenant_id_index` (`tenant_id`),
KEY `portfolio_drive_workspaces_workspace_status_index` (`workspace_status`),
KEY `portfolio_drive_workspaces_portfolio_no_index` (`portfolio_no`),
KEY `idx_workspace_lifecycle_state` (`lifecycle_state`);

CREATE TABLE `proj_activity_stream` (
`id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`occurred_at` timestamp NULL DEFAULT NULL,
`actor_id` bigint unsigned DEFAULT NULL,
`listing_id` bigint unsigned DEFAULT NULL,
`type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`);

CREATE TABLE `proj_event_offsets` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`projector_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`event_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`processed_at` timestamp NULL DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`);

CREATE TABLE `properties` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`tenant_id` bigint unsigned NOT NULL,
`workspace_id` bigint unsigned DEFAULT NULL COMMENT 'Link to PropertyWorkspace',
`canonical_reference` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Deterministic identifier for legacy mapping',
`lifecycle_state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT' COMMENT 'DRAFT | ACTIVE | ARCHIVED',
`aktiflik_durumu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT' COMMENT 'Alias for lifecycle_state (Context7 compatibility)',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
`property_identity_json` json DEFAULT NULL COMMENT 'Structured identity: { site: {name, block}, building: {name, entrance}, unit: {floor, no} }',
PRIMARY KEY (`id`),
UNIQUE KEY `properties_uuid_unique` (`uuid`),
UNIQUE KEY `properties_canonical_reference_unique` (`canonical_reference`),
KEY `properties_tenant_state_idx` (`tenant_id`,`lifecycle_state`),
KEY `properties_tenant_canonical_idx` (`tenant_id`,`canonical_reference`);

CREATE TABLE `property_access_assets` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`property_id` bigint unsigned NOT NULL,
`varlik_tipi` enum('KEY','SITE_CARD','GARAGE_REMOTE','SMART_LOCK','ALARM_CODE','STORAGE_KEY') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type of access asset',
`tanimlayici_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Key number, card UID — SENSITIVE, hidden by default',
`tanim` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Human-readable description',
`durum` enum('AKTIF','KAYIP','DEAKTIVE','IPTAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
`olusturan_id` bigint unsigned NOT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `property_access_assets_olusturan_id_foreign` (`olusturan_id`),
KEY `idx_paa_property_status` (`property_id`,`durum`),
KEY `idx_paa_tenant` (`tenant_id`),
CONSTRAINT `property_access_assets_olusturan_id_foreign` FOREIGN KEY (`olusturan_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
CONSTRAINT `property_access_assets_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE RESTRICT,
CONSTRAINT `property_access_assets_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT;

CREATE TABLE `property_documents` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`property_id` bigint unsigned NOT NULL,
`dokuman_tipi` enum('TITLE_DEED','MANAGEMENT_AGREEMENT','OWNER_AUTHORIZATION','ID_DOCUMENT','COMPANY_DOCUMENT','INSURANCE','OCCUPANCY_PERMIT','ZONING','UTILITY_SUBSCRIPTION','KEY_RECEIPT') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Document classification',
`dosya_yolu` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Points to existing media storage — no duplicate storage',
`referans_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Title deed number, policy number, etc.',
`yayin_tarihi` date DEFAULT NULL COMMENT 'Issue date',
`son_gecerlilik_tarihi` date DEFAULT NULL COMMENT 'Expiry date',
`durum` enum('AKTIF','SURESI_DOLMUS','IPTAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AKTIF',
`notu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`olusturan_id` bigint unsigned NOT NULL,
`idempotency_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `property_documents_idempotency_key_unique` (`idempotency_key`),
KEY `property_documents_olusturan_id_foreign` (`olusturan_id`),
KEY `idx_pd_property_status` (`property_id`,`durum`),
KEY `idx_pd_tenant` (`tenant_id`),
KEY `idx_pd_expiry` (`son_gecerlilik_tarihi`,`durum`),
CONSTRAINT `property_documents_olusturan_id_foreign` FOREIGN KEY (`olusturan_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
CONSTRAINT `property_documents_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE RESTRICT,
CONSTRAINT `property_documents_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT;

CREATE TABLE `property_engine_shadow_events` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ulke_id` bigint unsigned DEFAULT NULL,
`mode` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'shadow',
`env` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'production',
`context_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`v2_signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`v3_signature` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`match` tinyint(1) NOT NULL DEFAULT '0',
`error_v2` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`error_v3` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`latency_ms_v2` int unsigned DEFAULT NULL,
`latency_ms_v3` int unsigned DEFAULT NULL,
`rule_count_v2` int unsigned DEFAULT NULL,
`rule_count_v3` int unsigned DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `property_engine_shadow_events_match_created_at_index` (`match`,`created_at`),
KEY `property_engine_shadow_events_mode_env_created_at_index` (`mode`,`env`,`created_at`),
KEY `property_engine_shadow_events_ulke_id_index` (`ulke_id`),
KEY `property_engine_shadow_events_context_hash_index` (`context_hash`),
KEY `property_engine_shadow_events_match_index` (`match`);

CREATE TABLE `property_key_custodies` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`asset_id` bigint unsigned NOT NULL,
`kisi_id` bigint unsigned DEFAULT NULL COMMENT 'Nullable: IADE records have no holder',
`islem_tipi` enum('TESLIM','IADE','KAYIP_BILDIRIM','YENILEME') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`islem_tarihi` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
`notu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`olusturan_id` bigint unsigned NOT NULL,
`idempotency_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `property_key_custodies_v2_idempotency_key_unique` (`idempotency_key`),
KEY `property_key_custodies_v2_kisi_id_foreign` (`kisi_id`),
KEY `property_key_custodies_v2_olusturan_id_foreign` (`olusturan_id`),
KEY `idx_pkc_asset_history_v2` (`asset_id`,`islem_tipi`,`islem_tarihi`),
KEY `idx_pkc_tenant_v2` (`tenant_id`),
CONSTRAINT `property_key_custodies_v2_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `property_access_assets` (`id`) ON DELETE RESTRICT,
CONSTRAINT `property_key_custodies_v2_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE SET NULL,
CONSTRAINT `property_key_custodies_v2_olusturan_id_foreign` FOREIGN KEY (`olusturan_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
CONSTRAINT `property_key_custodies_v2_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT;

CREATE TABLE `property_ownerships` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`property_id` bigint unsigned NOT NULL,
`kisi_id` bigint unsigned NOT NULL,
`pay_orani` decimal(6,4) NOT NULL DEFAULT '1.0000' COMMENT 'Ownership share: 0.0001–1.0000',
`sahiplik_tipi` enum('OWNER','BENEFICIAL_OWNER','JOINT_OWNER','REPRESENTATIVE') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OWNER',
`yetkili_temsilci_id` bigint unsigned DEFAULT NULL,
`baslangic_tarihi` date NOT NULL,
`bitis_tarihi` date DEFAULT NULL COMMENT 'null = currently active',
`atama_kaynagi` enum('MANUAL','CONTRACT','INHERITANCE','COURT','TKGM') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MANUAL',
`atama_notu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`idempotency_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `property_ownerships_idempotency_key_unique` (`idempotency_key`),
KEY `property_ownerships_kisi_id_foreign` (`kisi_id`),
KEY `property_ownerships_yetkili_temsilci_id_foreign` (`yetkili_temsilci_id`),
KEY `idx_po_property_active` (`property_id`,`bitis_tarihi`),
KEY `idx_po_property_historical` (`property_id`,`baslangic_tarihi`,`bitis_tarihi`),
KEY `idx_po_tenant` (`tenant_id`),
CONSTRAINT `property_ownerships_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE RESTRICT,
CONSTRAINT `property_ownerships_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE RESTRICT,
CONSTRAINT `property_ownerships_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT,
CONSTRAINT `property_ownerships_yetkili_temsilci_id_foreign` FOREIGN KEY (`yetkili_temsilci_id`) REFERENCES `kisiler` (`id`) ON DELETE SET NULL;

CREATE TABLE `property_readiness` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`reservation_id` bigint unsigned NOT NULL,
`ilan_id` bigint unsigned NOT NULL,
`property_clean` tinyint(1) NOT NULL DEFAULT '0',
`access_credential_ready` tinyint(1) NOT NULL DEFAULT '0',
`guest_contact_ready` tinyint(1) NOT NULL DEFAULT '0',
`amenity_check_complete` tinyint(1) NOT NULL DEFAULT '0',
`welcome_kit_prepared` tinyint(1) NOT NULL DEFAULT '0',
`is_ready` tinyint(1) NOT NULL DEFAULT '0',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `property_readiness_reservation_unique` (`reservation_id`),
KEY `property_readiness_tenant_id_reservation_id_index` (`tenant_id`,`reservation_id`),
KEY `property_readiness_tenant_id_ilan_id_index` (`tenant_id`,`ilan_id`),
KEY `property_readiness_ilan_id_foreign` (`ilan_id`),
CONSTRAINT `property_readiness_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
CONSTRAINT `property_readiness_reservation_id_foreign` FOREIGN KEY (`reservation_id`) REFERENCES `property_reservations` (`id`) ON DELETE CASCADE,
CONSTRAINT `property_readiness_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

CREATE TABLE `property_representatives` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`property_id` bigint unsigned NOT NULL,
`kisi_id` bigint unsigned NOT NULL,
`temsil_yetu_tipi` enum('FULL','FINANCIAL','OPERATIONAL','LEGAL') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Representative authority type',
`baslangic_tarihi` date NOT NULL,
`bitis_tarihi` date DEFAULT NULL,
`notu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`idempotency_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `property_representatives_idempotency_key_unique` (`idempotency_key`),
KEY `property_representatives_kisi_id_foreign` (`kisi_id`),
KEY `idx_pr_property_active` (`property_id`,`bitis_tarihi`),
KEY `idx_pr_tenant` (`tenant_id`),
KEY `idx_pr_type_active` (`property_id`,`temsil_yetu_tipi`,`bitis_tarihi`),
CONSTRAINT `property_representatives_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE RESTRICT,
CONSTRAINT `property_representatives_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE RESTRICT,
CONSTRAINT `property_representatives_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT;

CREATE TABLE `property_workspaces` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`property_id` bigint unsigned DEFAULT NULL,
`workspace_uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`intent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`template_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'workspace_created',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `property_workspaces_workspace_uuid_unique` (`workspace_uuid`),
UNIQUE KEY `property_workspaces_property_id_unique` (`property_id`),
KEY `property_workspaces_tenant_id_ilan_id_index` (`tenant_id`),
KEY `property_workspaces_tenant_id_state_index` (`tenant_id`,`state`),
KEY `property_workspaces_tenant_id_index` (`tenant_id`);

CREATE TABLE `provider_settlements` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`provider` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`external_settlement_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`external_reservation_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`reservation_id` bigint unsigned DEFAULT NULL,
`gross_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
`channel_fee_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
`net_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
`currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
`payout_type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`payout_status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`bank_transfer_reference` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`payout_date` date DEFAULT NULL,
`value_date` date DEFAULT NULL,
`raw_payload` json DEFAULT NULL,
`raw_source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'api',
`settlement_status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
`allocated_to_id` bigint unsigned DEFAULT NULL,
`idempotency_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `provider_settlements_idempotency_key_unique` (`idempotency_key`),
KEY `ps_tp_ext_idx` (`tenant_id`,`provider`,`external_settlement_id`),
KEY `ps_t_rsv_idx` (`tenant_id`,`reservation_id`),
KEY `ps_t_status_idx` (`tenant_id`,`settlement_status`),
KEY `provider_settlements_tenant_id_index` (`tenant_id`),
KEY `provider_settlements_provider_index` (`provider`),
KEY `provider_settlements_reservation_id_index` (`reservation_id`);

CREATE TABLE `settlement_allocations` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`provider_settlement_id` bigint unsigned NOT NULL,
`reservation_id` bigint unsigned NOT NULL,
`reconciliation_execution_id` bigint unsigned DEFAULT NULL,
`gross_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
`channel_fee_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
`net_amount` decimal(15,4) NOT NULL DEFAULT '0.0000',
`currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
`allocation_status` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `settlement_allocations_provider_settlement_id_foreign` (`provider_settlement_id`),
CONSTRAINT `settlement_allocations_provider_settlement_id_foreign` FOREIGN KEY (`provider_settlement_id`) REFERENCES `provider_settlements` (`id`) ON DELETE CASCADE;

CREATE TABLE `subscriptions` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`plan_id` bigint unsigned NOT NULL,
`status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'trialing',
`trial_ends_at` datetime DEFAULT NULL,
`starts_at` datetime NOT NULL,
`ends_at` datetime DEFAULT NULL,
`canceled_at` datetime DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `subscriptions_tenant_id_foreign` (`tenant_id`),
KEY `subscriptions_plan_id_foreign` (`plan_id`),
CONSTRAINT `subscriptions_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`),
CONSTRAINT `subscriptions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

CREATE TABLE `system_learning_transactions` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ulke_id` bigint unsigned DEFAULT NULL,
`transaction_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`module` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`related_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`related_id` bigint unsigned DEFAULT NULL,
`input_data` json DEFAULT NULL,
`output_data` json DEFAULT NULL,
`context` json DEFAULT NULL,
`success` tinyint(1) NOT NULL DEFAULT '1',
`performance_score` decimal(5,2) DEFAULT NULL,
`execution_time_ms` int unsigned DEFAULT NULL,
`learned_patterns` json DEFAULT NULL,
`user_id` bigint unsigned DEFAULT NULL,
`ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`executed_at` timestamp NULL DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `system_learning_transactions_module_action_success_index` (`module`,`action`,`success`),
KEY `system_learning_transactions_related_type_related_id_index` (`related_type`,`related_id`),
KEY `system_learning_transactions_ulke_id_index` (`ulke_id`),
KEY `system_learning_transactions_transaction_type_index` (`transaction_type`),
KEY `system_learning_transactions_module_index` (`module`),
KEY `system_learning_transactions_related_type_index` (`related_type`),
KEY `system_learning_transactions_related_id_index` (`related_id`),
KEY `system_learning_transactions_success_index` (`success`),
KEY `system_learning_transactions_user_id_index` (`user_id`),
KEY `system_learning_transactions_executed_at_index` (`executed_at`);

CREATE TABLE `teklifler` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL,
`ilan_id` bigint unsigned NOT NULL,
`kisi_id` bigint unsigned NOT NULL,
`teklif_tutari` decimal(15,2) NOT NULL,
`para_birimi` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
`teklif_durumu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'beklemede',
`mesaj` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`gecerlilik_tarihi` datetime DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `teklifler_tenant_id_foreign` (`tenant_id`),
KEY `teklifler_ilan_id_foreign` (`ilan_id`),
KEY `teklifler_kisi_id_foreign` (`kisi_id`),
CONSTRAINT `teklifler_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE CASCADE,
CONSTRAINT `teklifler_kisi_id_foreign` FOREIGN KEY (`kisi_id`) REFERENCES `kisiler` (`id`) ON DELETE CASCADE,
CONSTRAINT `teklifler_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

CREATE TABLE `template_audit_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ulke_id` bigint unsigned DEFAULT NULL,
`auditable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`auditable_id` bigint unsigned NOT NULL,
`event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`old_value` json DEFAULT NULL,
`new_value` json DEFAULT NULL,
`user_id` bigint unsigned DEFAULT NULL,
`ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `template_audit_logs_auditable_type_auditable_id_event_index` (`auditable_type`,`auditable_id`,`event`),
KEY `template_audit_logs_ulke_id_index` (`ulke_id`),
KEY `template_audit_logs_auditable_type_index` (`auditable_type`),
KEY `template_audit_logs_auditable_id_index` (`auditable_id`),
KEY `template_audit_logs_event_index` (`event`),
KEY `template_audit_logs_user_id_index` (`user_id`);

CREATE TABLE `tenants` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`aktiflik_durumu` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
`status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
`uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`is_active` tinyint(1) NOT NULL DEFAULT '1',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
`durum` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
PRIMARY KEY (`id`),
UNIQUE KEY `tenants_domain_unique` (`domain`);

CREATE TABLE `test_entities` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`payload` json DEFAULT NULL,
`published_payload` json DEFAULT NULL,
`governance_state` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
`ulke_id` bigint unsigned DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`);

CREATE TABLE `tkgm_learning_patterns` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`pattern_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`il_id` bigint unsigned DEFAULT NULL,
`ilce_id` bigint unsigned DEFAULT NULL,
`mahalle_id` bigint unsigned DEFAULT NULL,
`pattern_data` json DEFAULT NULL,
`sample_count` int unsigned NOT NULL DEFAULT '0',
`confidence_level` decimal(5,2) NOT NULL DEFAULT '0.00',
`last_calculated_at` timestamp NULL DEFAULT NULL,
`last_updated_at` timestamp NULL DEFAULT NULL,
`prediction_count` int unsigned NOT NULL DEFAULT '0',
`prediction_accuracy` decimal(5,2) DEFAULT NULL,
`successful_predictions` int unsigned NOT NULL DEFAULT '0',
`pattern_aktiflik_durumu` tinyint(1) NOT NULL DEFAULT '1',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `tkgm_learning_patterns_pattern_type_il_id_ilce_id_index` (`pattern_type`,`il_id`,`ilce_id`),
KEY `idx_tkgm_patterns_conf_active` (`confidence_level`,`pattern_aktiflik_durumu`),
KEY `tkgm_learning_patterns_pattern_type_index` (`pattern_type`),
KEY `tkgm_learning_patterns_il_id_index` (`il_id`),
KEY `tkgm_learning_patterns_ilce_id_index` (`ilce_id`),
KEY `tkgm_learning_patterns_mahalle_id_index` (`mahalle_id`),
KEY `tkgm_learning_patterns_pattern_aktiflik_durumu_index` (`pattern_aktiflik_durumu`);

CREATE TABLE `tkgm_queries` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`ada` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`parsel` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`il_id` bigint unsigned DEFAULT NULL,
`ilce_id` bigint unsigned DEFAULT NULL,
`mahalle_id` bigint unsigned DEFAULT NULL,
`alan_m2` decimal(12,2) DEFAULT NULL,
`kaks` decimal(5,2) DEFAULT NULL,
`taks` int unsigned DEFAULT NULL,
`nitelik` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`gabari` int unsigned DEFAULT NULL,
`ilan_id` bigint unsigned DEFAULT NULL,
`satis_fiyati` decimal(15,2) DEFAULT NULL,
`satis_tarihi` date DEFAULT NULL,
`satis_suresi_gun` int unsigned DEFAULT NULL,
`query_source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`user_id` bigint unsigned DEFAULT NULL,
`queried_at` timestamp NULL DEFAULT NULL,
`tkgm_raw_data` json DEFAULT NULL,
`islem_durumu` tinyint(1) NOT NULL DEFAULT '1',
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `tkgm_queries_ada_parsel_il_id_index` (`ada`,`parsel`,`il_id`),
KEY `tkgm_queries_il_id_ilce_id_mahalle_id_index` (`il_id`,`ilce_id`,`mahalle_id`),
KEY `tkgm_queries_il_id_index` (`il_id`),
KEY `tkgm_queries_ilce_id_index` (`ilce_id`),
KEY `tkgm_queries_mahalle_id_index` (`mahalle_id`),
KEY `tkgm_queries_ilan_id_index` (`ilan_id`),
KEY `tkgm_queries_query_source_index` (`query_source`),
KEY `tkgm_queries_user_id_index` (`user_id`),
KEY `tkgm_queries_queried_at_index` (`queried_at`),
KEY `tkgm_queries_islem_durumu_index` (`islem_durumu`);

CREATE TABLE `transactions` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`tenant_id` bigint unsigned NOT NULL COMMENT 'RULE-T1: zorunlu tenant izolasyonu',
`ilan_id` bigint unsigned DEFAULT NULL,
`islem_turu` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`islem_tutari` decimal(15,2) NOT NULL,
`currency` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRY',
`payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`payment_date` date DEFAULT NULL,
`description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`receipt_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`bank_reference` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`is_verified` tinyint(1) NOT NULL DEFAULT '0',
`recorded_by` bigint unsigned NOT NULL,
`verified_by` bigint unsigned DEFAULT NULL,
`verified_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `transactions_tenant_id_foreign` (`tenant_id`),
KEY `transactions_ilan_id_foreign` (`ilan_id`),
KEY `transactions_recorded_by_foreign` (`recorded_by`),
KEY `transactions_verified_by_foreign` (`verified_by`),
CONSTRAINT `transactions_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE SET NULL,
CONSTRAINT `transactions_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`),
CONSTRAINT `transactions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE,
CONSTRAINT `transactions_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

CREATE TABLE `workforce_execution_logs` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`hermes_event_log_id` bigint unsigned DEFAULT NULL,
`ilan_id` bigint unsigned DEFAULT NULL,
`tenant_id` bigint unsigned DEFAULT NULL,
`chain_id` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`agent_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`agent_class` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`event_received` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`event_chain_step` tinyint unsigned NOT NULL DEFAULT '0',
`input_payload` json DEFAULT NULL,
`output_payload` json DEFAULT NULL,
`status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
`error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`started_at` timestamp NULL DEFAULT NULL,
`completed_at` timestamp NULL DEFAULT NULL,
`duration_ms` double(8,2) DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `workforce_execution_logs_chain_id_event_chain_step_index` (`chain_id`,`event_chain_step`),
KEY `workforce_execution_logs_ilan_id_tenant_id_index` (`ilan_id`,`tenant_id`),
KEY `workforce_execution_logs_agent_name_status_index` (`agent_name`,`status`),
KEY `workforce_execution_logs_status_created_at_index` (`status`,`created_at`),
KEY `workforce_execution_logs_hermes_event_log_id_index` (`hermes_event_log_id`),
KEY `workforce_execution_logs_ilan_id_index` (`ilan_id`),
KEY `workforce_execution_logs_tenant_id_index` (`tenant_id`),
KEY `workforce_execution_logs_chain_id_index` (`chain_id`),
KEY `workforce_execution_logs_agent_name_index` (`agent_name`),
KEY `workforce_execution_logs_status_index` (`status`);

CREATE TABLE `workspace_executions` (
`id` bigint unsigned NOT NULL AUTO_INCREMENT,
`workspace_id` bigint unsigned NOT NULL,
`ilan_id` bigint unsigned DEFAULT NULL,
`tenant_id` bigint unsigned DEFAULT NULL,
`execution_type` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`execution_label` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
`chain_id` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`state` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'queued',
`queued_at` timestamp NULL DEFAULT NULL,
`started_at` timestamp NULL DEFAULT NULL,
`completed_at` timestamp NULL DEFAULT NULL,
`duration_ms` int unsigned DEFAULT NULL,
`attempt_number` int unsigned NOT NULL DEFAULT '1',
`max_attempts` int unsigned NOT NULL DEFAULT '3',
`retry_count` int unsigned NOT NULL DEFAULT '0',
`backoff_intervals` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`original_execution_id` bigint unsigned DEFAULT NULL,
`failure_reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
`failure_context` json DEFAULT NULL,
`input_payload` json DEFAULT NULL,
`output_result` json DEFAULT NULL,
`progress_pct` int unsigned DEFAULT NULL,
`queue_name` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'workspace',
`job_id` varchar(80) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`timeout_seconds` int unsigned NOT NULL DEFAULT '300',
`triggered_by` varchar(40) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
`triggered_by_user_id` bigint unsigned DEFAULT NULL,
`created_at` timestamp NULL DEFAULT NULL,
`updated_at` timestamp NULL DEFAULT NULL,
`deleted_at` timestamp NULL DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `workspace_executions_triggered_by_user_id_foreign` (`triggered_by_user_id`),
KEY `workspace_executions_workspace_id_state_index` (`workspace_id`,`state`),
KEY `ws_exec_wsid_type_created_at_idx` (`workspace_id`,`execution_type`,`created_at`),
KEY `workspace_executions_state_created_at_index` (`state`,`created_at`),
KEY `workspace_executions_chain_id_created_at_index` (`chain_id`,`created_at`),
KEY `workspace_executions_ilan_id_created_at_index` (`ilan_id`,`created_at`),
KEY `workspace_executions_tenant_id_index` (`tenant_id`),
KEY `workspace_executions_chain_id_index` (`chain_id`),
KEY `workspace_executions_state_index` (`state`),
KEY `workspace_executions_original_execution_id_index` (`original_execution_id`),
KEY `workspace_executions_job_id_index` (`job_id`),
CONSTRAINT `workspace_executions_ilan_id_foreign` FOREIGN KEY (`ilan_id`) REFERENCES `ilanlar` (`id`) ON DELETE SET NULL,
CONSTRAINT `workspace_executions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE RESTRICT,
CONSTRAINT `workspace_executions_triggered_by_user_id_foreign` FOREIGN KEY (`triggered_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
CONSTRAINT `workspace_executions_workspace_id_foreign` FOREIGN KEY (`workspace_id`) REFERENCES `portfolio_drive_workspaces` (`id`) ON DELETE CASCADE;


/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

