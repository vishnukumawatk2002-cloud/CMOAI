-- =============================================================================
-- CMO AI — Complete MySQL Database Schema
-- Generated from UI mockup analysis (cmo-01 through cmo-12)
-- Engine: InnoDB | Charset: utf8mb4_unicode_ci
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- PLANS & BILLING
-- -----------------------------------------------------------------------------

CREATE TABLE `plans` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(50) NOT NULL,
    `price_monthly` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `price_yearly` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `max_brands` SMALLINT UNSIGNED NULL COMMENT 'NULL = unlimited',
    `max_social_accounts` SMALLINT UNSIGNED NULL COMMENT 'NULL = unlimited',
    `max_posts_per_month` INT UNSIGNED NULL COMMENT 'NULL = unlimited',
    `bulk_scheduling` TINYINT(1) NOT NULL DEFAULT 0,
    `ai_insights` TINYINT(1) NOT NULL DEFAULT 0,
    `white_label_reports` TINYINT(1) NOT NULL DEFAULT 0,
    `api_access` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `plans_slug_unique` (`slug`),
    KEY `plans_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- USERS & AUTH
-- -----------------------------------------------------------------------------

CREATE TABLE `users` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NULL,
    `google_id` VARCHAR(255) NULL,
    `avatar_url` VARCHAR(500) NULL,
    `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
    `remember_token` VARCHAR(100) NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `users_email_unique` (`email`),
    UNIQUE KEY `users_google_id_unique` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_reset_tokens` (
    `email` VARCHAR(255) NOT NULL,
    `token` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `email_verifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `otp_hash` VARCHAR(255) NOT NULL,
    `expires_at` TIMESTAMP NOT NULL,
    `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `verified_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `email_verifications_user_id_index` (`user_id`),
    KEY `email_verifications_expires_at_index` (`expires_at`),
    CONSTRAINT `email_verifications_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscriptions` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `plan_id` BIGINT UNSIGNED NOT NULL,
    `billing_cycle` ENUM('monthly', 'yearly') NOT NULL DEFAULT 'monthly',
    `status` ENUM('trial', 'active', 'past_due', 'cancelled', 'expired') NOT NULL DEFAULT 'trial',
    `payment_provider` VARCHAR(50) NULL COMMENT 'razorpay, stripe, etc.',
    `provider_subscription_id` VARCHAR(255) NULL,
    `provider_customer_id` VARCHAR(255) NULL,
    `trial_ends_at` TIMESTAMP NULL DEFAULT NULL,
    `starts_at` TIMESTAMP NULL DEFAULT NULL,
    `ends_at` TIMESTAMP NULL DEFAULT NULL,
    `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `subscriptions_user_id_status_index` (`user_id`, `status`),
    KEY `subscriptions_plan_id_index` (`plan_id`),
    KEY `subscriptions_provider_subscription_id_index` (`provider_subscription_id`),
    CONSTRAINT `subscriptions_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `subscriptions_plan_id_foreign`
        FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subscription_usage` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `subscription_id` BIGINT UNSIGNED NOT NULL,
    `year` SMALLINT UNSIGNED NOT NULL,
    `month` TINYINT UNSIGNED NOT NULL COMMENT '1-12',
    `posts_used` INT UNSIGNED NOT NULL DEFAULT 0,
    `brands_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `social_accounts_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `subscription_usage_subscription_year_month_unique` (`subscription_id`, `year`, `month`),
    CONSTRAINT `subscription_usage_subscription_id_foreign`
        FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- BRANDS (WORKSPACES)
-- -----------------------------------------------------------------------------

CREATE TABLE `brands` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Account owner',
    `name` VARCHAR(255) NOT NULL,
    `slug` VARCHAR(255) NOT NULL,
    `website` VARCHAR(500) NULL,
    `industry` VARCHAR(100) NULL,
    `country` VARCHAR(100) NULL DEFAULT 'India',
    `language` VARCHAR(50) NULL DEFAULT 'English',
    `tone` VARCHAR(100) NULL,
    `founded_year` SMALLINT UNSIGNED NULL,
    `short_description` TEXT NULL,
    `logo_path` VARCHAR(500) NULL,
    `setup_step` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=business, 2=assets, 3=social',
    `setup_completed_at` TIMESTAMP NULL DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `brands_user_id_slug_unique` (`user_id`, `slug`),
    KEY `brands_user_id_index` (`user_id`),
    KEY `brands_is_active_index` (`is_active`),
    KEY `brands_deleted_at_index` (`deleted_at`),
    CONSTRAINT `brands_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `brand_members` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `role` ENUM('owner', 'admin', 'editor', 'viewer') NOT NULL DEFAULT 'editor',
    `invited_at` TIMESTAMP NULL DEFAULT NULL,
    `accepted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `brand_members_brand_id_user_id_unique` (`brand_id`, `user_id`),
    KEY `brand_members_user_id_index` (`user_id`),
    CONSTRAINT `brand_members_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
    CONSTRAINT `brand_members_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `brand_voice_settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `tone_style` ENUM('professional', 'casual', 'bold', 'educational', 'witty', 'luxury') NOT NULL DEFAULT 'professional',
    `company_description` TEXT NULL,
    `products_services` TEXT NULL,
    `target_audience` TEXT NULL,
    `keywords` JSON NULL COMMENT 'Array of keywords/hashtags AI should use',
    `avoid_words` JSON NULL COMMENT 'Array of words/phrases to avoid',
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `brand_voice_settings_brand_id_unique` (`brand_id`),
    CONSTRAINT `brand_voice_settings_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `brand_colors` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `label` VARCHAR(50) NOT NULL COMMENT 'primary, accent, dark, card',
    `hex_value` CHAR(7) NOT NULL COMMENT '#RRGGBB',
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `brand_colors_brand_id_index` (`brand_id`),
    CONSTRAINT `brand_colors_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `brand_assets` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `file_path` VARCHAR(500) NOT NULL,
    `disk` VARCHAR(50) NOT NULL DEFAULT 'local',
    `file_type` ENUM('logo', 'image', 'pdf', 'video', 'audio', 'docx', 'website', 'guidelines') NOT NULL,
    `mime_type` VARCHAR(100) NULL,
    `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Bytes',
    `status` ENUM('uploading', 'processing', 'indexed', 'failed') NOT NULL DEFAULT 'uploading',
    `indexed_at` TIMESTAMP NULL DEFAULT NULL,
    `metadata` JSON NULL COMMENT 'pages crawled, dimensions, etc.',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `brand_assets_brand_id_status_index` (`brand_id`, `status`),
    KEY `brand_assets_file_type_index` (`file_type`),
    CONSTRAINT `brand_assets_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `brand_knowledge_bases` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `detected_tone` VARCHAR(255) NULL,
    `detected_audience` VARCHAR(500) NULL,
    `detected_services` TEXT NULL,
    `top_keywords` JSON NULL,
    `training_status` ENUM('idle', 'processing', 'complete', 'failed') NOT NULL DEFAULT 'idle',
    `last_trained_at` TIMESTAMP NULL DEFAULT NULL,
    `training_error` TEXT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `brand_knowledge_bases_brand_id_unique` (`brand_id`),
    KEY `brand_knowledge_bases_training_status_index` (`training_status`),
    CONSTRAINT `brand_knowledge_bases_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- SOCIAL ACCOUNTS & OAUTH
-- -----------------------------------------------------------------------------

CREATE TABLE `social_accounts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `platform` ENUM('facebook', 'instagram', 'linkedin', 'x', 'youtube', 'pinterest', 'threads', 'google_business') NOT NULL,
    `account_name` VARCHAR(255) NOT NULL,
    `account_handle` VARCHAR(255) NULL,
    `account_type` ENUM('page', 'profile', 'channel', 'group', 'business') NOT NULL DEFAULT 'page',
    `external_id` VARCHAR(255) NOT NULL COMMENT 'Platform-native account ID',
    `follower_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `profile_image_url` VARCHAR(500) NULL,
    `status` ENUM('active', 'expired', 'disconnected', 'error') NOT NULL DEFAULT 'active',
    `connected_at` TIMESTAMP NULL DEFAULT NULL,
    `last_synced_at` TIMESTAMP NULL DEFAULT NULL,
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `social_accounts_brand_platform_external_unique` (`brand_id`, `platform`, `external_id`),
    KEY `social_accounts_brand_id_status_index` (`brand_id`, `status`),
    KEY `social_accounts_platform_index` (`platform`),
    CONSTRAINT `social_accounts_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `oauth_tokens` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `social_account_id` BIGINT UNSIGNED NOT NULL,
    `access_token` TEXT NOT NULL COMMENT 'Encrypt at application layer',
    `refresh_token` TEXT NULL COMMENT 'Encrypt at application layer',
    `token_type` VARCHAR(50) NOT NULL DEFAULT 'Bearer',
    `expires_at` TIMESTAMP NULL DEFAULT NULL,
    `scopes` JSON NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `oauth_tokens_social_account_id_unique` (`social_account_id`),
    KEY `oauth_tokens_expires_at_index` (`expires_at`),
    CONSTRAINT `oauth_tokens_social_account_id_foreign`
        FOREIGN KEY (`social_account_id`) REFERENCES `social_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- CONTENT
-- -----------------------------------------------------------------------------

CREATE TABLE `content_folders` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `content_folders_brand_id_slug_unique` (`brand_id`, `slug`),
    CONSTRAINT `content_folders_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_generation_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `content_type` ENUM('post', 'carousel', 'reel_script', 'image_caption', 'hashtags', 'thirty_day_plan', 'thread') NOT NULL,
    `platforms` JSON NOT NULL COMMENT 'Array of platform slugs',
    `prompt` TEXT NOT NULL,
    `status` ENUM('pending', 'processing', 'complete', 'failed') NOT NULL DEFAULT 'pending',
    `error_message` TEXT NULL,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ai_generation_requests_brand_id_status_index` (`brand_id`, `status`),
    KEY `ai_generation_requests_user_id_index` (`user_id`),
    CONSTRAINT `ai_generation_requests_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
    CONSTRAINT `ai_generation_requests_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `content_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `folder_id` BIGINT UNSIGNED NULL,
    `parent_id` BIGINT UNSIGNED NULL COMMENT 'Variation group parent',
    `ai_generation_request_id` BIGINT UNSIGNED NULL,
    `content_type` ENUM('post', 'carousel', 'reel_script', 'image_caption', 'hashtags', 'thirty_day_plan', 'thread') NOT NULL,
    `platform` ENUM('facebook', 'instagram', 'linkedin', 'x', 'youtube', 'pinterest', 'threads', 'google_business', 'multi') NOT NULL,
    `title` VARCHAR(500) NULL,
    `body` LONGTEXT NOT NULL,
    `status` ENUM('draft', 'approved', 'scheduled', 'published', 'failed') NOT NULL DEFAULT 'draft',
    `variation_number` TINYINT UNSIGNED NULL DEFAULT 1,
    `generation_prompt` TEXT NULL,
    `scheduled_at` TIMESTAMP NULL DEFAULT NULL,
    `published_at` TIMESTAMP NULL DEFAULT NULL,
    `external_post_id` VARCHAR(255) NULL,
    `external_post_url` VARCHAR(500) NULL,
    `reach` INT UNSIGNED NULL DEFAULT 0,
    `engagement_rate` DECIMAL(5, 2) NULL,
    `metadata` JSON NULL COMMENT 'Carousel slides, media URLs, etc.',
    `deleted_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `content_items_brand_id_status_index` (`brand_id`, `status`),
    KEY `content_items_brand_id_platform_index` (`brand_id`, `platform`),
    KEY `content_items_folder_id_index` (`folder_id`),
    KEY `content_items_parent_id_index` (`parent_id`),
    KEY `content_items_scheduled_at_index` (`scheduled_at`),
    KEY `content_items_published_at_index` (`published_at`),
    KEY `content_items_ai_generation_request_id_index` (`ai_generation_request_id`),
    FULLTEXT KEY `content_items_body_fulltext` (`title`, `body`),
    CONSTRAINT `content_items_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE,
    CONSTRAINT `content_items_folder_id_foreign`
        FOREIGN KEY (`folder_id`) REFERENCES `content_folders` (`id`) ON DELETE SET NULL,
    CONSTRAINT `content_items_parent_id_foreign`
        FOREIGN KEY (`parent_id`) REFERENCES `content_items` (`id`) ON DELETE SET NULL,
    CONSTRAINT `content_items_ai_generation_request_id_foreign`
        FOREIGN KEY (`ai_generation_request_id`) REFERENCES `ai_generation_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `content_hashtags` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_item_id` BIGINT UNSIGNED NOT NULL,
    `hashtag` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `content_hashtags_item_hashtag_unique` (`content_item_id`, `hashtag`),
    KEY `content_hashtags_hashtag_index` (`hashtag`),
    CONSTRAINT `content_hashtags_content_item_id_foreign`
        FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- SCHEDULING & PUBLISHING
-- -----------------------------------------------------------------------------

CREATE TABLE `scheduled_posts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_item_id` BIGINT UNSIGNED NOT NULL,
    `social_account_id` BIGINT UNSIGNED NOT NULL,
    `scheduled_at` TIMESTAMP NOT NULL,
    `status` ENUM('pending', 'publishing', 'published', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    `published_at` TIMESTAMP NULL DEFAULT NULL,
    `external_post_id` VARCHAR(255) NULL,
    `external_post_url` VARCHAR(500) NULL,
    `failure_reason` TEXT NULL,
    `retry_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `last_attempt_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `scheduled_posts_scheduled_at_status_index` (`scheduled_at`, `status`),
    KEY `scheduled_posts_content_item_id_index` (`content_item_id`),
    KEY `scheduled_posts_social_account_id_index` (`social_account_id`),
    KEY `scheduled_posts_status_index` (`status`),
    CONSTRAINT `scheduled_posts_content_item_id_foreign`
        FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`) ON DELETE CASCADE,
    CONSTRAINT `scheduled_posts_social_account_id_foreign`
        FOREIGN KEY (`social_account_id`) REFERENCES `social_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `publish_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `scheduled_post_id` BIGINT UNSIGNED NOT NULL,
    `event` ENUM('queued', 'started', 'success', 'failed', 'retry', 'cancelled') NOT NULL,
    `message` TEXT NULL,
    `payload` JSON NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `publish_logs_scheduled_post_id_index` (`scheduled_post_id`),
    KEY `publish_logs_event_index` (`event`),
    KEY `publish_logs_created_at_index` (`created_at`),
    CONSTRAINT `publish_logs_scheduled_post_id_foreign`
        FOREIGN KEY (`scheduled_post_id`) REFERENCES `scheduled_posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- ANALYTICS
-- -----------------------------------------------------------------------------

CREATE TABLE `analytics_snapshots` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `snapshot_date` DATE NOT NULL,
    `total_reach` BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `engagement_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `link_clicks` INT UNSIGNED NOT NULL DEFAULT 0,
    `followers_gained` INT NOT NULL DEFAULT 0,
    `posts_published` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `analytics_snapshots_brand_date_unique` (`brand_id`, `snapshot_date`),
    KEY `analytics_snapshots_snapshot_date_index` (`snapshot_date`),
    CONSTRAINT `analytics_snapshots_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_analytics` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `content_item_id` BIGINT UNSIGNED NOT NULL,
    `social_account_id` BIGINT UNSIGNED NOT NULL,
    `reach` INT UNSIGNED NOT NULL DEFAULT 0,
    `impressions` INT UNSIGNED NOT NULL DEFAULT 0,
    `engagement` INT UNSIGNED NOT NULL DEFAULT 0,
    `clicks` INT UNSIGNED NOT NULL DEFAULT 0,
    `likes` INT UNSIGNED NOT NULL DEFAULT 0,
    `comments` INT UNSIGNED NOT NULL DEFAULT 0,
    `shares` INT UNSIGNED NOT NULL DEFAULT 0,
    `saves` INT UNSIGNED NOT NULL DEFAULT 0,
    `engagement_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `recorded_at` TIMESTAMP NOT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `post_analytics_content_item_id_index` (`content_item_id`),
    KEY `post_analytics_social_account_id_recorded_at_index` (`social_account_id`, `recorded_at`),
    KEY `post_analytics_recorded_at_index` (`recorded_at`),
    CONSTRAINT `post_analytics_content_item_id_foreign`
        FOREIGN KEY (`content_item_id`) REFERENCES `content_items` (`id`) ON DELETE CASCADE,
    CONSTRAINT `post_analytics_social_account_id_foreign`
        FOREIGN KEY (`social_account_id`) REFERENCES `social_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `social_account_daily_stats` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `social_account_id` BIGINT UNSIGNED NOT NULL,
    `stat_date` DATE NOT NULL,
    `posts_published` INT UNSIGNED NOT NULL DEFAULT 0,
    `reach` INT UNSIGNED NOT NULL DEFAULT 0,
    `engagement_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `followers` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `social_account_daily_stats_account_date_unique` (`social_account_id`, `stat_date`),
    KEY `social_account_daily_stats_stat_date_index` (`stat_date`),
    CONSTRAINT `social_account_daily_stats_social_account_id_foreign`
        FOREIGN KEY (`social_account_id`) REFERENCES `social_accounts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- AI
-- -----------------------------------------------------------------------------

CREATE TABLE `ai_insights` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NOT NULL,
    `insight_type` ENUM('posting_time', 'platform', 'content_type', 'recommendation', 'warning') NOT NULL,
    `title` VARCHAR(255) NULL,
    `message` TEXT NOT NULL,
    `metadata` JSON NULL COMMENT 'best_days, times, platform stats',
    `valid_from` DATE NULL,
    `valid_until` DATE NULL,
    `is_dismissed` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ai_insights_brand_id_is_dismissed_index` (`brand_id`, `is_dismissed`),
    KEY `ai_insights_insight_type_index` (`insight_type`),
    CONSTRAINT `ai_insights_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `ai_suggested_prompts` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brand_id` BIGINT UNSIGNED NULL COMMENT 'NULL = global prompt',
    `content_type` ENUM('post', 'carousel', 'reel_script', 'image_caption', 'hashtags', 'thirty_day_plan', 'thread') NULL,
    `platform` ENUM('facebook', 'instagram', 'linkedin', 'x', 'youtube', 'pinterest', 'threads', 'google_business') NULL,
    `label` VARCHAR(255) NOT NULL,
    `prompt_text` TEXT NOT NULL,
    `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `ai_suggested_prompts_brand_id_is_active_index` (`brand_id`, `is_active`),
    CONSTRAINT `ai_suggested_prompts_brand_id_foreign`
        FOREIGN KEY (`brand_id`) REFERENCES `brands` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- NOTIFICATIONS
-- -----------------------------------------------------------------------------

CREATE TABLE `notifications` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `type` VARCHAR(100) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `data` JSON NULL,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NULL DEFAULT NULL,
    `updated_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `notifications_user_id_read_at_index` (`user_id`, `read_at`),
    KEY `notifications_type_index` (`type`),
    CONSTRAINT `notifications_user_id_foreign`
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
