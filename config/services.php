<?php

return [

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
    ],

    'youtube' => [
        'client_id' => env('YOUTUBE_CLIENT_ID', env('GOOGLE_CLIENT_ID')),
        'client_secret' => env('YOUTUBE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET')),
        'redirect' => env('YOUTUBE_REDIRECT_URI', env('APP_URL').'/onboarding/social/youtube/callback'),
        'ffmpeg_path' => env('FFMPEG_PATH'),
        'scopes' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('YOUTUBE_SCOPES', 'https://www.googleapis.com/auth/youtube.upload,https://www.googleapis.com/auth/youtube.readonly,https://www.googleapis.com/auth/userinfo.profile'))
        ))),
    ],

    'snapchat' => [
        'client_id' => env('SNAPCHAT_CLIENT_ID'),
        'client_secret' => env('SNAPCHAT_CLIENT_SECRET'),
        'redirect' => env('SNAPCHAT_REDIRECT_URI', env('APP_URL').'/onboarding/social/snapchat/callback'),
        'scopes' => array_values(array_filter(array_map(
            'trim',
            explode(' ', env('SNAPCHAT_SCOPES', 'snapchat-profile-api'))
        ))),
    ],

    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI', env('APP_URL').'/onboarding/social/facebook/callback'),
        // Facebook Login for Business configuration ID (recommended for Page permissions).
        'login_config_id' => env('FACEBOOK_LOGIN_CONFIG_ID'),
        // Comma-separated scopes — only used if login_config_id is empty (legacy apps).
        'scopes' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('FACEBOOK_SCOPES','public_profile,email,pages_show_list,pages_read_engagement,pages_manage_posts,pages_manage_metadata,business_management,instagram_basic,instagram_content_publish'))
        ))),
    ],

    'x' => [
        'client_id' => env('X_CLIENT_ID'),
        'client_secret' => env('X_CLIENT_SECRET'),
        'redirect' => env('X_REDIRECT_URI', env('APP_URL').'/onboarding/social/x/callback'),
        'scopes' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('X_SCOPES', 'tweet.read,tweet.write,users.read,offline.access,media.write'))
        ))),
    ],

    'linkedin-openid' => [
        'client_id' => env('LINKEDIN_CLIENT_ID'),
        'client_secret' => env('LINKEDIN_CLIENT_SECRET'),
        'redirect' => env('LINKEDIN_REDIRECT_URI', env('APP_URL').'/onboarding/social/linkedin/callback'),
        'scopes' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('LINKEDIN_SCOPES', 'openid,profile,email,w_member_social'))
        ))),
        'api_version' => env('LINKEDIN_API_VERSION', '202601'),
    ],

    'publish' => [
        'public_url' => env('APP_PUBLIC_URL'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_MODEL', 'google/gemini-2.5-flash'),
        'fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('OPENROUTER_FALLBACK_MODELS', 'openrouter/free,meta-llama/llama-3.3-70b-instruct:free'))
        ))),
        'referer' => env('OPENROUTER_REFERER', env('APP_URL')),
        'title' => env('OPENROUTER_TITLE', env('APP_NAME', 'CMO AI')),
        'image_model' => env('OPENROUTER_IMAGE_MODEL', 'black-forest-labs/flux.2-pro'),
        'image_fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('OPENROUTER_IMAGE_FALLBACK_MODELS', 'google/gemini-2.5-flash-image-preview'))
        ))),
        'image_aspect_ratio' => env('OPENROUTER_IMAGE_ASPECT_RATIO', '1:1'),
        'carousel_slides' => (int) env('OPENROUTER_CAROUSEL_SLIDES', 4),
        'video_model' => env('OPENROUTER_VIDEO_MODEL', 'google/veo-3.1-lite'),
        'video_fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('OPENROUTER_VIDEO_FALLBACK_MODELS', 'google/veo-3.1'))
        ))),
        'video_duration' => (int) env('OPENROUTER_VIDEO_DURATION', 8),
        'video_aspect_ratio' => env('OPENROUTER_VIDEO_ASPECT_RATIO', '9:16'),
        'video_resolution' => env('OPENROUTER_VIDEO_RESOLUTION', '720p'),
        'video_generate_audio' => env('OPENROUTER_VIDEO_GENERATE_AUDIO', true),
        'video_poll_attempts' => (int) env('OPENROUTER_VIDEO_POLL_ATTEMPTS', 120),
        'video_poll_interval' => (int) env('OPENROUTER_VIDEO_POLL_INTERVAL', 5),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    ],

    'bluesminds' => [
        'api_key' => env('BLUESMINDS_API_KEY'),
        'base_url' => env('BLUESMINDS_BASE_URL', 'https://api.bluesminds.com/v1'),
        'model' => env('BLUESMINDS_MODEL', 'gpt-4o-mini'),
        'fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('BLUESMINDS_FALLBACK_MODELS', 'gpt-4o,gpt-5.4'))
        ))),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'fallback_models' => array_values(array_filter(array_map(
            'trim',
            explode(',', env('GEMINI_FALLBACK_MODELS', 'gemini-2.0-flash'))
        ))),
    ],

    'payu' => [
        'merchant_key' => env('PAYU_MERCHANT_KEY'),
        'merchant_salt' => env('PAYU_MERCHANT_SALT'),
        // Optional: older Salt (V1) if reverse-hash fails with Salt V2.
        'merchant_salt_v1' => env('PAYU_MERCHANT_SALT_V1'),
        'base_url' => env('PAYU_BASE_URL', 'https://test.payu.in/_payment'),
    ],

];
