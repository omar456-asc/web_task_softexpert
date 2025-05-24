<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Task;
use Laravel\Sanctum\Sanctum;

class TaskApiTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();
        \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'Manager',
            'guard_name' => 'web',
        ]);
        \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'User',
            'guard_name' => 'web',
        ]);
    }

    public function test_manager_can_create_task()
    {
        $manager = User::factory()->create();
        $manager->assignRole('Manager');
        Sanctum::actingAs($manager);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'assignee_id' => $manager->id,
            'due_date' => now()->addDays(7)->toDateString(),
        ]);
        $response->assertStatus(201);
    }

    public function test_user_cannot_create_task()
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tasks', [
            'title' => 'Test Task',
            'description' => 'This is a test task',
            'assignee_id' => $user->id,
            'due_date' => now()->addDays(7)->toDateString(),
        ]);
        $response->assertStatus(403);
    }

    public function test_user_can_update_own_task_status()
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        $task = Task::factory()->create(['assignee_id' => $user->id]);
        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/tasks/{$task->id}/status", [
            'status' => 'completed',
        ]);
        $response->assertStatus(200);
    }

    public function test_user_can_list_own_tasks()
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        $otherUser = User::factory()->create();
        $task1 = Task::factory()->create(['assignee_id' => $user->id, 'title' => 'User Task 1']);
        $task2 = Task::factory()->create(['assignee_id' => $user->id, 'title' => 'User Task 2']);
        $otherTask = Task::factory()->create(['assignee_id' => $otherUser->id, 'title' => 'Other User Task']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/tasks');
        $response->assertStatus(200);
        $response->assertJsonFragment(['title' => 'User Task 1']);
        $response->assertJsonFragment(['title' => 'User Task 2']);
        $response->assertJsonMissing(['title' => 'Other User Task']);
    }

    public function test_task_cannot_be_completed_until_dependencies_are_completed()
    {
        $user = User::factory()->create();
        $user->assignRole('User');
        Sanctum::actingAs($user);
        // Create two tasks: one is the dependency, one is the main task
        $dependency = Task::factory()->create([
            'assignee_id' => $user->id,
            'status' => 'pending',
        ]);
        $mainTask = Task::factory()->create([
            'assignee_id' => $user->id,
            'status' => 'pending',
        ]);
        // Attach dependency
        $mainTask->dependencies()->attach($dependency->id);
        // Try to complete the main task (should fail)
        $response = $this->patchJson("/api/tasks/{$mainTask->id}/status", [
            'status' => 'completed',
        ]);
        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'All dependencies must be completed first.']);
        // Complete the dependency
        $dependency->status = 'completed';
        $dependency->save();
        // Try to complete the main task again (should succeed)
        $response = $this->patchJson("/api/tasks/{$mainTask->id}/status", [
            'status' => 'completed',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('completed', $mainTask->fresh()->status);
    }
}
