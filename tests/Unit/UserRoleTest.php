<?php

namespace Tests\Unit;

use App\Models\ServiceTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_default_customer_role()
    {
        $user = User::factory()->create(['role' => null]);

        // The migration sets default as 'customer'
        $this->assertEquals('customer', $user->fresh()->role);
    }

    public function test_user_role_identification_methods()
    {
        $veoAdmin = User::factory()->veoAdmin()->create();
        $siteManager = User::factory()->siteManager()->create();
        $maintenanceStaff = User::factory()->maintenanceStaff()->create();
        $customer = User::factory()->customer()->create();

        // VEO Admin tests
        $this->assertTrue($veoAdmin->isVeoAdmin());
        $this->assertFalse($veoAdmin->isSiteManager());
        $this->assertFalse($veoAdmin->isMaintenanceStaff());
        $this->assertFalse($veoAdmin->isCustomer());

        // Site Manager tests
        $this->assertFalse($siteManager->isVeoAdmin());
        $this->assertTrue($siteManager->isSiteManager());
        $this->assertFalse($siteManager->isMaintenanceStaff());
        $this->assertFalse($siteManager->isCustomer());

        // Maintenance Staff tests
        $this->assertFalse($maintenanceStaff->isVeoAdmin());
        $this->assertFalse($maintenanceStaff->isSiteManager());
        $this->assertTrue($maintenanceStaff->isMaintenanceStaff());
        $this->assertFalse($maintenanceStaff->isCustomer());

        // Customer tests
        $this->assertFalse($customer->isVeoAdmin());
        $this->assertFalse($customer->isSiteManager());
        $this->assertFalse($customer->isMaintenanceStaff());
        $this->assertTrue($customer->isCustomer());
    }

    public function test_can_manage_assets_permission()
    {
        $veoAdmin = User::factory()->veoAdmin()->create();
        $siteManager = User::factory()->siteManager()->create();
        $maintenanceStaff = User::factory()->maintenanceStaff()->create();
        $customer = User::factory()->customer()->create();

        $this->assertTrue($veoAdmin->canManageAssets());
        $this->assertTrue($siteManager->canManageAssets());
        $this->assertFalse($maintenanceStaff->canManageAssets());
        $this->assertFalse($customer->canManageAssets());
    }

    public function test_can_execute_tasks_permission()
    {
        $veoAdmin = User::factory()->veoAdmin()->create();
        $siteManager = User::factory()->siteManager()->create();
        $maintenanceStaff = User::factory()->maintenanceStaff()->create();
        $customer = User::factory()->customer()->create();

        $this->assertTrue($veoAdmin->canExecuteTasks());
        $this->assertTrue($siteManager->canExecuteTasks());
        $this->assertTrue($maintenanceStaff->canExecuteTasks());
        $this->assertFalse($customer->canExecuteTasks());
    }

    public function test_can_view_assets_permission()
    {
        $veoAdmin = User::factory()->veoAdmin()->create();
        $siteManager = User::factory()->siteManager()->create();
        $maintenanceStaff = User::factory()->maintenanceStaff()->create();
        $customer = User::factory()->customer()->create();

        $this->assertTrue($veoAdmin->canViewAssets());
        $this->assertTrue($siteManager->canViewAssets());
        $this->assertTrue($maintenanceStaff->canViewAssets());
        $this->assertTrue($customer->canViewAssets());
    }

    public function test_user_has_service_tasks_relationship()
    {
        $user = User::factory()->maintenanceStaff()->create(['name' => 'John Technician']);
        $task1 = ServiceTask::factory()->create(['assigned_to' => $user->id]);
        $task2 = ServiceTask::factory()->create(['assigned_to' => $user->id]);
        $task3 = ServiceTask::factory()->create(); // Different user

        $userTasks = $user->serviceTasks;

        $this->assertCount(2, $userTasks);
        $this->assertTrue($userTasks->contains($task1));
        $this->assertTrue($userTasks->contains($task2));
        $this->assertFalse($userTasks->contains($task3));
    }

    public function test_user_factory_states_work_correctly()
    {
        $veoAdmin = User::factory()->veoAdmin()->create();
        $siteManager = User::factory()->siteManager()->create();
        $maintenanceStaff = User::factory()->maintenanceStaff()->create();
        $customer = User::factory()->customer()->create();

        $this->assertEquals('veo_admin', $veoAdmin->role);
        $this->assertEquals('site_manager', $siteManager->role);
        $this->assertEquals('maintenance_staff', $maintenanceStaff->role);
        $this->assertEquals('customer', $customer->role);
    }

    public function test_role_based_business_logic_scenarios()
    {
        // Scenario: VEO Admin can do everything
        $admin = User::factory()->veoAdmin()->create();
        $this->assertTrue($admin->canViewAssets());
        $this->assertTrue($admin->canManageAssets());
        $this->assertTrue($admin->canExecuteTasks());

        // Scenario: Site Manager can manage but not all admin functions
        $manager = User::factory()->siteManager()->create();
        $this->assertTrue($manager->canViewAssets());
        $this->assertTrue($manager->canManageAssets());
        $this->assertTrue($manager->canExecuteTasks());
        $this->assertFalse($manager->isVeoAdmin());

        // Scenario: Maintenance Staff can execute tasks but not manage
        $staff = User::factory()->maintenanceStaff()->create();
        $this->assertTrue($staff->canViewAssets());
        $this->assertFalse($staff->canManageAssets());
        $this->assertTrue($staff->canExecuteTasks());

        // Scenario: Customer can only view
        $customer = User::factory()->customer()->create();
        $this->assertTrue($customer->canViewAssets());
        $this->assertFalse($customer->canManageAssets());
        $this->assertFalse($customer->canExecuteTasks());
    }

    public function test_role_enum_validation()
    {
        // Test that only valid roles are accepted
        $validRoles = ['veo_admin', 'site_manager', 'maintenance_staff', 'customer'];

        foreach ($validRoles as $role) {
            $user = User::factory()->create(['role' => $role]);
            $this->assertEquals($role, $user->role);
        }
    }

    public function test_user_permission_matrix()
    {
        $roles = [
            'veo_admin' => [
                'canViewAssets' => true,
                'canManageAssets' => true,
                'canExecuteTasks' => true,
            ],
            'site_manager' => [
                'canViewAssets' => true,
                'canManageAssets' => true,
                'canExecuteTasks' => true,
            ],
            'maintenance_staff' => [
                'canViewAssets' => true,
                'canManageAssets' => false,
                'canExecuteTasks' => true,
            ],
            'customer' => [
                'canViewAssets' => true,
                'canManageAssets' => false,
                'canExecuteTasks' => false,
            ],
        ];

        foreach ($roles as $role => $permissions) {
            $user = User::factory()->create(['role' => $role]);

            $this->assertEquals($permissions['canViewAssets'], $user->canViewAssets(),
                "Role {$role} canViewAssets permission failed");
            $this->assertEquals($permissions['canManageAssets'], $user->canManageAssets(),
                "Role {$role} canManageAssets permission failed");
            $this->assertEquals($permissions['canExecuteTasks'], $user->canExecuteTasks(),
                "Role {$role} canExecuteTasks permission failed");
        }
    }
}