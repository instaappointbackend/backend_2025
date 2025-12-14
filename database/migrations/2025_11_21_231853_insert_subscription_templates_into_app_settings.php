<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $rows = [
            [
                'key' => 'subscription_success_template_id',
                'value' => '1707176284183829579',
            ],
            [
                'key' => 'subscription_template',
                'value' => 'Congratulations! Your subscription in INSTA APPOINT is now active enjoy all the benefits! https://www.instaappoint.in/',
            ],
            [
                'key' => 'subscription_expired_template_id',
                'value' => '1707176284556963222',
            ],
            [
                'key' => 'subscription_expired_template',
                'value' => 'Your INSTA APPOINT subscription is about to expire on [24 nov 2025] Please renew it to continue enjoying our services. https://www.instaappoint.in/',
            ],
        ];

        foreach ($rows as $row) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => $row['key']],
                [
                    'value' => $row['value'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        DB::table('app_settings')
            ->whereIn('key', [
                'subscription_success_template_id',
                'subscription_template',
                'subscription_expired_template_id',
                'subscription_expired_template',
            ])
            ->delete();
    }
};
