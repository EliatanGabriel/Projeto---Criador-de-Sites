<?php
use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use App\Models\Website;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
uses(RefreshDatabase::class);
beforeEach(fn() => $this->seed(RolePermissionSeeder::class));
test('developer acessa index alertas sem 500', function () {
    $dev = User::factory()->create(['name'=>'Roger Pereira']);
    $dev->assignRole('DEVELOPER');
    $client = Client::factory()->create();
    $project = Project::factory()->create(['client_id'=>$client->id]);
    $project->members()->create(['user_id'=>$dev->id,'role'=>'developer']);
    $site = Website::factory()->create(['client_id'=>$client->id]);
    $site->websiteProjects()->create(['project_id'=>$project->id,'relation_type'=>'origin']);
    $site->domains()->create(['domain'=>'a.test','expires_at'=>now()->addDays(5)->toDateString(),'status'=>'ativo']);
    (new \App\Domain\Websites\Actions\CheckExpirationsAction())(\Carbon\Carbon::now());
    $this->actingAs($dev)->get('/alerts')->assertOk();
    $this->actingAs($dev)->get('/alerts')->assertInertia(fn($p)=>$p->component('alerts/index'));
});
