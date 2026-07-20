<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'group' => 'general',
                'key' => 'app_name',
                'value' => 'CMO AI',
                'type' => 'string',
                'label' => 'Application Name',
                'description' => 'Display name across the platform',
            ],
            [
                'group' => 'general',
                'key' => 'support_email',
                'value' => 'support@cmoai.app',
                'type' => 'string',
                'label' => 'Support Email',
                'description' => 'Contact email for customer support',
            ],
            [
                'group' => 'general',
                'key' => 'maintenance_mode',
                'value' => '0',
                'type' => 'boolean',
                'label' => 'Maintenance Mode',
                'description' => 'Enable to show maintenance page to users',
            ],
            [
                'group' => 'billing',
                'key' => 'trial_days',
                'value' => '14',
                'type' => 'integer',
                'label' => 'Trial Period (Days)',
                'description' => 'Default trial length for new subscriptions',
            ],
            [
                'group' => 'billing',
                'key' => 'currency',
                'value' => 'INR',
                'type' => 'string',
                'label' => 'Default Currency',
                'description' => 'Currency code for billing',
            ],
            [
                'group' => 'notifications',
                'key' => 'email_notifications',
                'value' => '1',
                'type' => 'boolean',
                'label' => 'Email Notifications',
                'description' => 'Send system email notifications to users',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
