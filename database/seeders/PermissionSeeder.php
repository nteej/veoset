<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for Asset History
        $permissions = [
            // Asset History Permissions
            'view_asset_history',
            'view_own_asset_history',
            'generate_shift_reports',
            'generate_health_reports',
            'download_pdf_reports',
            'simulate_sensor_data',

            // Asset Management Permissions (existing)
            'view_assets',
            'manage_assets',
            'manage_sites',
            'execute_tasks',
            'view_reports',
            'manage_users',
            'admin_access',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $veoAdmin = Role::firstOrCreate(['name' => 'veo_admin']);
        $veoAdmin->givePermissionTo(Permission::all());

        $siteManager = Role::firstOrCreate(['name' => 'site_manager']);
        $siteManager->givePermissionTo([
            'view_asset_history',
            'generate_shift_reports',
            'generate_health_reports',
            'download_pdf_reports',
            'view_assets',
            'manage_assets',
            'execute_tasks',
            'view_reports',
        ]);

        $maintenanceStaff = Role::firstOrCreate(['name' => 'maintenance_staff']);
        $maintenanceStaff->givePermissionTo([
            'view_own_asset_history',
            'generate_shift_reports',
            'download_pdf_reports',
            'view_assets',
            'execute_tasks',
        ]);

        $technician = Role::firstOrCreate(['name' => 'technician']);
        $technician->givePermissionTo([
            'view_own_asset_history',
            'generate_shift_reports',
            'download_pdf_reports',
            'view_assets',
        ]);

        $customer = Role::firstOrCreate(['name' => 'customer']);
        $customer->givePermissionTo([
            'view_assets',
            'view_reports',
        ]);
    }
}