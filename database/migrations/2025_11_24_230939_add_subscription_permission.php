<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Insert permission
        $permissionId = DB::table('permissions')->insertGetId([
            'name'        => 'subscriptions',
            'display_name' => 'Subscriptions',
            'description' => null,
            'module'      => 'subscriptions',
            // 'created_at'  => '2025-05-17 07:01:39',
            // 'updated_at'  => '2025-05-17 07:01:39',
        ]);

        // Assign permission to role
        DB::table('role_permission')->insert([
            'role_id'       => 1,
            'permission_id' => $permissionId
        ]);
    }

    public function down(): void
    {
        // Remove permission relationship
        // $permissionId = DB::table('permissions')
        //     ->where('name', 'subscriptions')
        //     ->value('id');

        // if ($permissionId) {
        //     DB::table('role_permission')
        //         ->where('permission_id', $permissionId)
        //         ->delete();

        //     DB::table('permissions')
        //         ->where('id', $permissionId)
        //         ->delete();
        // }
    }
};
