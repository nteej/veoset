<?php

namespace Tests\Unit;

use App\Models\Asset;
use App\Models\ServiceTask;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_task_can_be_created()
    {
        $site = Site::factory()->create();
        $asset = Asset::factory()->create(['site_id' => $site->id]);
        $user = User::factory()->create();

        $task = ServiceTask::create([
            'asset_id' => $asset->id,
            'assigned_to' => $user->id,
            'title' => 'Routine Maintenance',
            'description' => 'Perform routine maintenance check',
            'scheduled_date' => now()->addDays(7),
        ]);

        $task = $task->fresh(); // Refresh from database to get defaults
        $this->assertInstanceOf(ServiceTask::class, $task);
        $this->assertEquals('Routine Maintenance', $task->title);
        $this->assertEquals('preventive', $task->type); // Default
        $this->assertEquals('medium', $task->priority); // Default
        $this->assertEquals('pending', $task->status); // Default
        $this->assertFalse($task->requires_shutdown); // Default
    }

    public function test_service_task_belongs_to_asset_and_user()
    {
        $site = Site::factory()->create();
        $asset = Asset::factory()->create(['site_id' => $site->id, 'name' => 'Test Generator']);
        $user = User::factory()->create(['name' => 'John Technician']);

        $task = ServiceTask::factory()->create([
            'asset_id' => $asset->id,
            'assigned_to' => $user->id,
        ]);

        $this->assertEquals('Test Generator', $task->asset->name);
        $this->assertEquals('John Technician', $task->assignedUser->name);
    }

    public function test_service_task_scopes()
    {
        $site = Site::factory()->create();
        $asset = Asset::factory()->create(['site_id' => $site->id]);

        ServiceTask::factory()->create(['asset_id' => $asset->id, 'status' => 'pending']);
        ServiceTask::factory()->create(['asset_id' => $asset->id, 'status' => 'in_progress']);
        ServiceTask::factory()->create(['asset_id' => $asset->id, 'status' => 'completed']);
        ServiceTask::factory()->create(['asset_id' => $asset->id, 'priority' => 'high']);
        ServiceTask::factory()->create(['asset_id' => $asset->id, 'type' => 'emergency']);
        ServiceTask::factory()->create(['asset_id' => $asset->id, 'requires_shutdown' => true]);

        $this->assertCount(1, ServiceTask::pending()->get());
        $this->assertCount(1, ServiceTask::inProgress()->get());
        $this->assertCount(1, ServiceTask::completed()->get());
        $this->assertCount(1, ServiceTask::byPriority('high')->get());
        $this->assertCount(1, ServiceTask::byType('emergency')->get());
        $this->assertCount(1, ServiceTask::requiresShutdown()->get());
    }

    public function test_service_task_overdue_scope()
    {
        $site = Site::factory()->create();
        $asset = Asset::factory()->create(['site_id' => $site->id]);

        // Overdue pending task
        ServiceTask::factory()->create([
            'asset_id' => $asset->id,
            'status' => 'pending',
            'scheduled_date' => now()->subDays(5),
        ]);

        // Overdue in-progress task
        ServiceTask::factory()->create([
            'asset_id' => $asset->id,
            'status' => 'in_progress',
            'scheduled_date' => now()->subDays(2),
        ]);

        // Future task (not overdue)
        ServiceTask::factory()->create([
            'asset_id' => $asset->id,
            'status' => 'pending',
            'scheduled_date' => now()->addDays(5),
        ]);

        // Completed task (not overdue even if past due date)
        ServiceTask::factory()->create([
            'asset_id' => $asset->id,
            'status' => 'completed',
            'scheduled_date' => now()->subDays(10),
        ]);

        $overdueTasks = ServiceTask::overdue()->get();

        $this->assertCount(2, $overdueTasks);
    }

    public function test_service_task_status_methods()
    {
        $pendingTask = ServiceTask::factory()->make(['status' => 'pending']);
        $inProgressTask = ServiceTask::factory()->make(['status' => 'in_progress']);
        $completedTask = ServiceTask::factory()->make(['status' => 'completed']);

        $this->assertTrue($pendingTask->isPending());
        $this->assertFalse($pendingTask->isInProgress());
        $this->assertFalse($pendingTask->isCompleted());

        $this->assertFalse($inProgressTask->isPending());
        $this->assertTrue($inProgressTask->isInProgress());
        $this->assertFalse($inProgressTask->isCompleted());

        $this->assertFalse($completedTask->isPending());
        $this->assertFalse($completedTask->isInProgress());
        $this->assertTrue($completedTask->isCompleted());
    }

    public function test_service_task_is_overdue_method()
    {
        $overdueTask = ServiceTask::factory()->make([
            'status' => 'pending',
            'scheduled_date' => now()->subDays(5),
        ]);

        $futureTask = ServiceTask::factory()->make([
            'status' => 'pending',
            'scheduled_date' => now()->addDays(5),
        ]);

        $completedTask = ServiceTask::factory()->make([
            'status' => 'completed',
            'scheduled_date' => now()->subDays(5),
        ]);

        $this->assertTrue($overdueTask->isOverdue());
        $this->assertFalse($futureTask->isOverdue());
        $this->assertFalse($completedTask->isOverdue());
    }

    public function test_service_task_is_critical_method()
    {
        $criticalTask = ServiceTask::factory()->make(['priority' => 'critical']);
        $normalTask = ServiceTask::factory()->make(['priority' => 'medium']);

        $this->assertTrue($criticalTask->isCritical());
        $this->assertFalse($normalTask->isCritical());
    }

    public function test_service_task_start_method()
    {
        $task = ServiceTask::factory()->create(['status' => 'pending']);

        $result = $task->start();

        $this->assertTrue($result);
        $this->assertEquals('in_progress', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->started_at);
    }

    public function test_service_task_start_method_fails_if_not_pending()
    {
        $task = ServiceTask::factory()->create(['status' => 'in_progress']);

        $result = $task->start();

        $this->assertFalse($result);
        $this->assertEquals('in_progress', $task->fresh()->status);
    }

    public function test_service_task_complete_method()
    {
        $task = ServiceTask::factory()->create([
            'status' => 'in_progress',
            'started_at' => now()->subHours(4),
        ]);

        $result = $task->complete('Task completed successfully');

        $this->assertTrue($result);
        $this->assertEquals('completed', $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);
        $this->assertEquals('Task completed successfully', $task->fresh()->completion_notes);
        $this->assertEquals(4, $task->fresh()->actual_duration_hours);
    }

    public function test_service_task_complete_method_fails_if_not_in_progress()
    {
        $task = ServiceTask::factory()->create(['status' => 'pending']);

        $result = $task->complete('Should not complete');

        $this->assertFalse($result);
        $this->assertEquals('pending', $task->fresh()->status);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_service_task_duration_attribute()
    {
        // Completed task
        $completedTask = ServiceTask::factory()->create([
            'started_at' => now()->subHours(6),
            'completed_at' => now()->subHours(2),
        ]);

        // In-progress task
        $inProgressTask = ServiceTask::factory()->create([
            'started_at' => now()->subHours(3),
            'completed_at' => null,
        ]);

        // Not started task
        $notStartedTask = ServiceTask::factory()->create([
            'started_at' => null,
            'completed_at' => null,
        ]);

        $this->assertEquals(4, $completedTask->duration);
        $this->assertEquals(3, $inProgressTask->duration);
        $this->assertNull($notStartedTask->duration);
    }

    public function test_service_task_json_fields_are_cast_properly()
    {
        $task = ServiceTask::factory()->create([
            'required_tools' => ['wrench', 'multimeter'],
            'required_materials' => ['oil_filter', 'gaskets'],
            'safety_requirements' => [
                'ppe_required' => ['hard_hat', 'gloves'],
                'lockout_procedures' => true,
            ],
        ]);

        $this->assertIsArray($task->required_tools);
        $this->assertIsArray($task->required_materials);
        $this->assertIsArray($task->safety_requirements);

        $this->assertContains('wrench', $task->required_tools);
        $this->assertContains('oil_filter', $task->required_materials);
        $this->assertTrue($task->safety_requirements['lockout_procedures']);
    }

    public function test_service_task_datetime_fields_are_cast_properly()
    {
        $task = ServiceTask::factory()->create([
            'scheduled_date' => '2024-12-15 10:00:00',
            'started_at' => '2024-12-15 10:30:00',
            'completed_at' => '2024-12-15 14:30:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $task->scheduled_date);
        $this->assertInstanceOf(\Carbon\Carbon::class, $task->started_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $task->completed_at);
    }

    public function test_service_task_factory_creates_valid_task()
    {
        $task = ServiceTask::factory()->create();

        $this->assertInstanceOf(ServiceTask::class, $task);
        $this->assertInstanceOf(Asset::class, $task->asset);
        $this->assertInstanceOf(User::class, $task->assignedUser);
        $this->assertNotEmpty($task->title);
        $this->assertNotEmpty($task->description);
        $this->assertContains($task->type, ['preventive', 'corrective', 'predictive', 'emergency']);
        $this->assertContains($task->priority, ['low', 'medium', 'high', 'critical']);
        $this->assertContains($task->status, ['pending', 'in_progress', 'completed', 'cancelled', 'on_hold']);
        $this->assertIsBool($task->requires_shutdown);
    }

    public function test_deleting_asset_cascades_to_service_tasks()
    {
        $site = Site::factory()->create();
        $asset = Asset::factory()->create(['site_id' => $site->id]);
        $task = ServiceTask::factory()->create(['asset_id' => $asset->id]);

        $this->assertDatabaseHas('service_tasks', ['id' => $task->id]);

        $asset->delete();

        $this->assertDatabaseMissing('service_tasks', ['id' => $task->id]);
    }

    public function test_deleting_user_sets_assigned_to_null()
    {
        $user = User::factory()->create();
        $task = ServiceTask::factory()->create(['assigned_to' => $user->id]);

        $this->assertEquals($user->id, $task->fresh()->assigned_to);

        $user->delete();

        $this->assertNull($task->fresh()->assigned_to);
    }
}