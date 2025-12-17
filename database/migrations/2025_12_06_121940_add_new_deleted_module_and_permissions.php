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
            'name'        => 'deleted_user',
            'display_name' => 'View',
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
        // Remove permission relationship
        // $permissionId = DB::table('permissions')
        //     ->where('name', 'deleted_user')
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
