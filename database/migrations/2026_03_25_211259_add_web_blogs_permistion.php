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
        $permissionId = DB::table('permissions')->insertGetId([
            'name' => 'content_manage_web_blogs',
            'display_name' => 'Manage Web blogs',
            'description' => null,
            'module' => 'content',
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Assign permission to role
        DB::table('role_permission')->insert([
            'role_id' => 1,
            'permission_id' => $permissionId,
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
