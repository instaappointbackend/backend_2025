<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Insert permission
        $permissionId = DB::table('permissions')->insertGetId([
            'name'        => 'export_vendors',
            'display_name' => 'Export Vendors',
            'description' => null,
            'module'      => 'users',
            // 'created_at'  => '2025-05-17 07:01:39',
            // 'updated_at'  => '2025-05-17 07:01:39',
        ]);

        // Assign permission to role
        DB::table('role_permission')->insert([
            'role_id'       => 1,
            'permission_id' => $permissionId
        ]);

        // Insert permission
        $permissionId = DB::table('permissions')->insertGetId([
            'name'        => 'export_customers',
            'display_name' => 'Export Customers',
            'description' => null,
            'module'      => 'users',
            // 'created_at'  => '2025-05-17 07:01:39',
            // 'updated_at'  => '2025-05-17 07:01:39',
        ]);

        // Assign permission to role
        DB::table('role_permission')->insert([
            'role_id'       => 1,
            'permission_id' => $permissionId
        ]);


        // Insert permission
        $permissionId = DB::table('permissions')->insertGetId([
            'name'        => 'export_deleted_vendors',
            'display_name' => 'Export Deleted Vendors',
            'description' => null,
            'module'      => 'Deleted User',
            // 'created_at'  => '2025-05-17 07:01:39',
            // 'updated_at'  => '2025-05-17 07:01:39',
        ]);

        // Assign permission to role
        DB::table('role_permission')->insert([
            'role_id'       => 1,
            'permission_id' => $permissionId
        ]);


        // Insert permission
        $permissionId = DB::table('permissions')->insertGetId([
            'name'        => 'export_deleted_customers',
            'display_name' => 'Export Deleted customers',
            'description' => null,
            'module'      => 'Deleted User',
            // 'created_at'  => '2025-05-17 07:01:39',
            // 'updated_at'  => '2025-05-17 07:01:39',
        ]);

        // Assign permission to role
        DB::table('role_permission')->insert([
            'role_id'       => 1,
            'permission_id' => $permissionId
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
