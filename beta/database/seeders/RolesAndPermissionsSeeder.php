<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Str;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default roles
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => 'Super Administrator',
            'description' => 'Full access to all system features',
            'is_system' => true,
        ]);

        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'Administrative access with some restrictions',
            'is_system' => true,
        ]);

        $managerRole = Role::create([
            'name' => 'manager',
            'display_name' => 'Manager',
            'description' => 'Manage users and content',
            'is_system' => false,
        ]);

        $supportRole = Role::create([
            'name' => 'support',
            'display_name' => 'Support Staff',
            'description' => 'Handle customer support and basic administrative tasks',
            'is_system' => false,
        ]);

        // Create permissions by modules
        $modules = [
            'dashboard' => ['View dashboard'],
            'users' => ['View users', 'Create users', 'Edit users', 'Delete users', 'Manage user roles'],
            'roles' => ['View roles', 'Create roles', 'Edit roles', 'Delete roles', 'Assign permissions'],
            'permissions' => ['View permissions', 'Create permissions', 'Edit permissions', 'Delete permissions'],
            'appointments' => ['View appointments', 'Create appointments', 'Edit appointments', 'Delete appointments', 'Export appointments', 'Manage calendar'],
            'services' => ['View services', 'Create services', 'Edit services', 'Delete services', 'Manage pricing'],
            'payments' => ['View payments', 'Process payments', 'Refund payments', 'Export payments'],
            'payouts' => ['View payouts', 'Approve payouts', 'Reject payouts', 'Process payouts', 'Export payouts'],
            'kyc' => ['View KYC submissions', 'Approve KYC', 'Reject KYC', 'Download KYC documents'],
            'reports' => ['View reports', 'Generate reports', 'Export reports'],
            'settings' => ['View settings', 'Update settings', 'Manage system configuration'],
            'content' => ['Manage blogs', 'Manage FAQs', 'Manage pages', 'Manage media'],
            'notifications' => ['Send notifications', 'Manage notification templates'],
        ];

        $allPermissions = [];

        foreach ($modules as $module => $permissions) {
            foreach ($permissions as $permission) {
                $permissionName = Str::slug($module . ' ' . $permission, '_');
                $permissionObject = Permission::create([
                    'name' => $permissionName,
                    'display_name' => $permission,
                    'module' => $module,
                ]);
                $allPermissions[] = $permissionObject->id;
            }
        }

        // Assign permissions to roles

        // Super Admin gets all permissions
        $superAdminRole->permissions()->sync($allPermissions);

        // Admin gets most permissions except some sensitive ones
        $adminPermissions = Permission::whereNotIn('name', [
            'settings_update_settings',
            'settings_manage_system_configuration',
            'roles_delete_roles',
            'permissions_delete_permissions',
        ])->pluck('id')->toArray();

        $adminRole->permissions()->sync($adminPermissions);

        // Manager gets operational permissions
        $managerPermissions = [
            'dashboard_view_dashboard',
            'users_view_users',
            'appointments_view_appointments',
            'appointments_create_appointments',
            'appointments_edit_appointments',
            'appointments_export_appointments',
            'appointments_manage_calendar',
            'services_view_services',
            'services_manage_pricing',
            'payments_view_payments',
            'payments_process_payments',
            'payments_export_payments',
            'reports_view_reports',
            'reports_generate_reports',
            'reports_export_reports',
            'content_manage_blogs',
            'content_manage_faqs',
            'notifications_send_notifications',
        ];

        $managerPermissionsIds = Permission::whereIn('name', $managerPermissions)->pluck('id')->toArray();
        $managerRole->permissions()->sync($managerPermissionsIds);

        // Support gets basic permissions
        $supportPermissions = [
            'dashboard_view_dashboard',
            'users_view_users',
            'appointments_view_appointments',
            'appointments_create_appointments',
            'services_view_services',
            'payments_view_payments',
            'reports_view_reports',
            'content_manage_faqs',
        ];

        $supportPermissionsIds = Permission::whereIn('name', $supportPermissions)->pluck('id')->toArray();
        $supportRole->permissions()->sync($supportPermissionsIds);

        // Update existing admin users to use the new role system
        $adminUsers = User::where('role', 'admin')->get();
        foreach ($adminUsers as $user) {
            // The first admin user becomes a super admin
            if ($user->id == 1) {
                $user->role_id = $superAdminRole->id;
            } else {
                $user->role_id = $adminRole->id;
            }
            $user->save();
        }

        $this->command->info('Roles and permissions have been created successfully!');
    }
}
