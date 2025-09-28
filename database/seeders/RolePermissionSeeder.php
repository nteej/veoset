<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Asset permissions
            'view assets',
            'create assets',
            'edit assets',
            'delete assets',
            'manage asset status',
            'manage mqtt devices',

            // Asset History permissions
            'view asset history',
            'create asset history',
            'edit asset history',
            'delete asset history',
            'generate reports',

            // MQTT Device permissions
            'view mqtt devices',
            'create mqtt devices',
            'edit mqtt devices',
            'delete mqtt devices',
            'send mqtt commands',
            'configure mqtt devices',

            // General permissions
            'record performance',
            'end shift',
            'view all assets',
            'view real time data',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // VEO Admin - Full access to everything
        $veoAdmin = Role::firstOrCreate(['name' => 'veo_admin']);
        $veoAdmin->givePermissionTo(Permission::all());

        // Site Manager - Manage assets and staff within their sites
        $siteManager = Role::firstOrCreate(['name' => 'site_manager']);
        $siteManager->givePermissionTo([
            'view assets',
            'create assets',
            'edit assets',
            'manage asset status',
            'view asset history',
            'create asset history',
            'edit asset history',
            'generate reports',
            'view mqtt devices',
            'create mqtt devices',
            'edit mqtt devices',
            'send mqtt commands',
            'configure mqtt devices',
            'record performance',
            'end shift',
            'view real time data',
        ]);

        // Maintenance Staff - Execute tasks and record data
        $maintenanceStaff = Role::firstOrCreate(['name' => 'maintenance_staff']);
        $maintenanceStaff->givePermissionTo([
            'view assets',
            'manage asset status',
            'view asset history',
            'create asset history',
            'edit asset history',
            'view mqtt devices',
            'send mqtt commands',
            'record performance',
            'end shift',
            'view real time data',
        ]);

        // Customer - View only access to their assets
        $customer = Role::firstOrCreate(['name' => 'customer']);
        $customer->givePermissionTo([
            'view assets',
            'view asset history',
            'view mqtt devices',
            'view real time data',
        ]);

        // Assign roles to existing users if they exist
        $adminUser = User::where('email', 'admin@veoset.com')->first();
        if ($adminUser && !$adminUser->hasAnyRole(['veo_admin'])) {
            $adminUser->assignRole('veo_admin');
            $this->command->info("Assigned veo_admin role to admin@veoset.com");
        }

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created roles: veo_admin, site_manager, maintenance_staff, customer');
        $this->command->info('Created ' . count($permissions) . ' permissions');
    }
}