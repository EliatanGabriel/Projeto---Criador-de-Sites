<?php
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);
beforeEach(fn() => $this->seed(RolePermissionSeeder::class));
test('developer ve tarefas', function () {
    $dev = User::factory()->create(['name'=>'Roger Pereira']);
    $dev->assignRole('DEVELOPER');
    $proj = Project::factory()->create(['technical_owner_id'=>$dev->id]);
    $t1 = Task::factory()->create(['project_id'=>$proj->id,'assignee_id'=>$dev->id,'status'=>'todo','title'=>'Teste kanban']);
    $t2 = Task::factory()->create(['project_id'=>$proj->id,'assignee_id'=>null,'status'=>'todo','title'=>'Nao atribuida']);
    $r = $this->actingAs($dev)->get('/tasks/my');
    $r->assertOk()->assertInertia(fn($p)=>$p->component('tasks/my-tasks')->has('board')->has('tasks'));
    $this->actingAs($dev)->get('/tasks')->assertOk()->assertInertia(fn($p)=>$p->has('tasks.data', 2));
    $this->actingAs($dev)->get("/tasks/{$t2->id}")->assertOk();
    $this->actingAs($dev)->patch("/tasks/{$t2->id}/status", ['status'=>'in_progress'])->assertRedirect();
    expect($t2->fresh()->status)->toBe('in_progress');
    expect($t2->fresh()->assignee_id)->toBe($dev->id);
});
