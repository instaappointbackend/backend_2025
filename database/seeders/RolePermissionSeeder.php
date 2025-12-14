<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Dashboard
            ['name' => 'dashboard_view_dashboard', 'display_name' => 'View Dashboard', 'description' => 'Access to admin dashboard', 'module' => 'dashboard'],
            
            // Users
            ['name' => 'users_view_users', 'display_name' => 'View Users', 'description' => 'View user list and details', 'module' => 'users'],
            ['name' => 'users_create_users', 'display_name' => 'Create Users', 'description' => 'Create new users', 'module' => 'users'],
            ['name' => 'users_edit_users', 'display_name' => 'Edit Users', 'description' => 'Edit user information', 'module' => 'users'],
            ['name' => 'users_delete_users', 'display_name' => 'Delete Users', 'description' => 'Delete users', 'module' => 'users'],
            
            // Roles
            ['name' => 'roles_view_roles', 'display_name' => 'View Roles', 'description' => 'View roles and permissions', 'module' => 'roles'],
            ['name' => 'roles_create_roles', 'display_name' => 'Create Roles', 'description' => 'Create new roles', 'module' => 'roles'],
            ['name' => 'roles_edit_roles', 'display_name' => 'Edit Roles', 'description' => 'Edit role permissions', 'module' => 'roles'],
            ['name' => 'roles_delete_roles', 'display_name' => 'Delete Roles', 'description' => 'Delete roles', 'module' => 'roles'],
            
            // Permissions
            ['name' => 'permissions_view_permissions', 'display_name' => 'View Permissions', 'description' => 'View all permissions', 'module' => 'permissions'],
            ['name' => 'permissions_create_permissions', 'display_name' => 'Create Permissions', 'description' => 'Create new permissions', 'module' => 'permissions'],
            ['name' => 'permissions_edit_permissions', 'display_name' => 'Edit Permissions', 'description' => 'Edit permissions', 'module' => 'permissions'],
            ['name' => 'permissions_delete_permissions', 'display_name' => 'Delete Permissions', 'description' => 'Delete permissions', 'module' => 'permissions'],
            
            // Appointments
            ['name' => 'appointments_view_appointments', 'display_name' => 'View Appointments', 'description' => 'View appointment list', 'module' => 'appointments'],
            ['name' => 'appointments_create_appointments', 'display_name' => 'Create Appointments', 'description' => 'Create new appointments', 'module' => 'appointments'],
            ['name' => 'appointments_edit_appointments', 'display_name' => 'Edit Appointments', 'description' => 'Edit appointments', 'module' => 'appointments'],
            ['name' => 'appointments_delete_appointments', 'display_name' => 'Delete Appointments', 'description' => 'Delete appointments', 'module' => 'appointments'],
            ['name' => 'appointments_manage_calendar', 'display_name' => 'Manage Calendar', 'description' => 'Access calendar view', 'module' => 'appointments'],
            
            // Services
            ['name' => 'services_view_services', 'display_name' => 'View Services', 'description' => 'View service list', 'module' => 'services'],
            ['name' => 'services_create_services', 'display_name' => 'Create Services', 'description' => 'Create new services', 'module' => 'services'],
            ['name' => 'services_edit_services', 'display_name' => 'Edit Services', 'description' => 'Edit services', 'module' => 'services'],
            ['name' => 'services_delete_services', 'display_name' => 'Delete Services', 'description' => 'Delete services', 'module' => 'services'],
            
            // KYC
            ['name' => 'kyc_view_kyc_submissions', 'display_name' => 'View KYC', 'description' => 'View KYC submissions', 'module' => 'kyc'],
            ['name' => 'kyc_approve_kyc', 'display_name' => 'Approve KYC', 'description' => 'Approve KYC submissions', 'module' => 'kyc'],
            ['name' => 'kyc_reject_kyc', 'display_name' => 'Reject KYC', 'description' => 'Reject KYC submissions', 'module' => 'kyc'],
            ['name' => 'kyc_create_kyc', 'display_name' => 'Create KYC', 'description' => 'Create KYC records', 'module' => 'kyc'],
            ['name' => 'kyc_edit_kyc', 'display_name' => 'Edit KYC', 'description' => 'Edit KYC records', 'module' => 'kyc'],
            ['name' => 'kyc_delete_kyc', 'display_name' => 'Delete KYC', 'description' => 'Delete KYC records', 'module' => 'kyc'],
            
            // Content Management
            ['name' => 'content_manage_blogs', 'display_name' => 'Manage Blogs', 'description' => 'Create, edit, delete blogs', 'module' => 'content'],
            ['name' => 'content_manage_faqs', 'display_name' => 'Manage FAQs', 'description' => 'Create, edit, delete FAQs', 'module' => 'content'],
            ['name' => 'content_manage_pages', 'display_name' => 'Manage Pages', 'description' => 'Create, edit, delete pages', 'module' => 'content'],
            
            // Business Categories
            ['name' => 'business_categories_view', 'display_name' => 'View Categories', 'description' => 'View business categories', 'module' => 'business_categories'],
            ['name' => 'business_categories_create', 'display_name' => 'Create Categories', 'description' => 'Create business categories', 'module' => 'business_categories'],
            ['name' => 'business_categories_edit', 'display_name' => 'Edit Categories', 'description' => 'Edit business categories', 'module' => 'business_categories'],
            ['name' => 'business_categories_delete', 'display_name' => 'Delete Categories', 'description' => 'Delete business categories', 'module' => 'business_categories'],
            
            // Settings
            ['name' => 'settings_view_settings', 'display_name' => 'View Settings', 'description' => 'View system settings', 'module' => 'settings'],
            ['name' => 'settings_update_settings', 'display_name' => 'Update Settings', 'description' => 'Update system settings', 'module' => 'settings'],
            
            // Newsletters
            ['name' => 'newsletters_view_newsletters', 'display_name' => 'View Newsletters', 'description' => 'View newsletter subscribers', 'module' => 'newsletters'],
            ['name' => 'newsletters_manage_newsletters', 'display_name' => 'Manage Newsletters', 'description' => 'Manage newsletter subscribers', 'module' => 'newsletters'],
            ['name' => 'newsletters_export_newsletters', 'display_name' => 'Export Newsletters', 'description' => 'Export newsletter data', 'module' => 'newsletters'],
            
            // Contacts
            ['name' => 'contacts_view_contacts', 'display_name' => 'View Contacts', 'description' => 'View contact messages', 'module' => 'contacts'],
            
            // Offers
            ['name' => 'offers_view_offers', 'display_name' => 'View Offers', 'description' => 'View offers', 'module' => 'offers'],
            ['name' => 'offers_create_offers', 'display_name' => 'Create Offers', 'description' => 'Create new offers', 'module' => 'offers'],
            ['name' => 'offers_edit_offers', 'display_name' => 'Edit Offers', 'description' => 'Edit offers', 'module' => 'offers'],
            ['name' => 'offers_delete_offers', 'display_name' => 'Delete Offers', 'description' => 'Delete offers', 'module' => 'offers'],
            
            // Payouts
            ['name' => 'payouts_view_payouts', 'display_name' => 'View Payouts', 'description' => 'View payout requests', 'module' => 'payouts'],
            ['name' => 'payouts_process_payouts', 'display_name' => 'Process Payouts', 'description' => 'Process payout requests', 'module' => 'payouts'],
            ['name' => 'payouts_export_payouts', 'display_name' => 'Export Payouts', 'description' => 'Export payout data', 'module' => 'payouts'],
            
            // Payments
            ['name' => 'payments_view_payments', 'display_name' => 'View Payments', 'description' => 'View payment records', 'module' => 'payments'],
            ['name' => 'payments_process_payments', 'display_name' => 'Process Payments', 'description' => 'Process payment records', 'module' => 'payments'],
            ['name' => 'payments_export_payments', 'display_name' => 'Export Payments', 'description' => 'Export payment data', 'module' => 'payments'],
            
            // Reports
            ['name' => 'reports_view_reports', 'display_name' => 'View Reports', 'description' => 'View system reports', 'module' => 'reports'],
            ['name' => 'reports_export_reports', 'display_name' => 'Export Reports', 'description' => 'Export report data', 'module' => 'reports'],
            
            // Refunds
            ['name' => 'refunds_view_refunds', 'display_name' => 'View Refunds', 'description' => 'View refund requests and details', 'module' => 'refunds'],
            ['name' => 'refunds_process_refunds', 'display_name' => 'Process Refunds', 'description' => 'Process pending refund requests', 'module' => 'refunds'],
            ['name' => 'refunds_export_refunds', 'display_name' => 'Export Refunds', 'description' => 'Export refund data and generate receipts', 'module' => 'refunds'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        // Create roles
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            [
                'display_name' => 'Super Administrator',
                'description' => 'Full system access with all permissions',
                'is_system' => true
            ]
        );

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => 'Administrator',
                'description' => 'Administrative access with most permissions',
                'is_system' => true
            ]
        );

        $managerRole = Role::firstOrCreate(
            ['name' => 'manager'],
            [
                'display_name' => 'Manager',
                'description' => 'Management access with limited permissions',
                'is_system' => false
            ]
        );

        $editorRole = Role::firstOrCreate(
            ['name' => 'editor'],
            [
                'display_name' => 'Content Editor',
                'description' => 'Content management access',
                'is_system' => false
            ]
        );

        // Assign all permissions to super admin
        $allPermissions = Permission::all();
        $superAdminRole->permissions()->sync($allPermissions->pluck('id'));

        // Assign most permissions to admin (exclude super admin specific ones)
        $adminPermissions = Permission::whereNotIn('name', [
            'roles_delete_roles',
            'permissions_delete_permissions',
            'users_delete_users'
        ])->get();
        $adminRole->permissions()->sync($adminPermissions->pluck('id'));

        // Assign limited permissions to manager
        $managerPermissions = Permission::whereIn('module', [
            'dashboard',
            'appointments',
            'services',
            'users',
            'reports',
            'refunds'
        ])->whereNotIn('name', [
            'users_delete_users',
            'services_delete_services',
            'refunds_process_refunds'
        ])->get();
        $managerRole->permissions()->sync($managerPermissions->pluck('id'));

        // Assign content permissions to editor
        $editorPermissions = Permission::whereIn('module', [
            'dashboard',
            'content',
            'business_categories'
        ])->get();
        $editorRole->permissions()->sync($editorPermissions->pluck('id'));

        $this->command->info('Roles and permissions seeded successfully!');
    }
}