<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('developer pode acessar criacao de sites e hospedagens', function () {
    $dev = User::factory()->create(['name' => 'Roger Pereira', 'email' => 'roger.pereira@teste.com']);
    $dev->assignRole('DEVELOPER');

    $this->actingAs($dev)->get('/sites/create')->assertOk();
    $this->actingAs($dev)->get('/hostings/create')->assertOk();
    $this->actingAs($dev)->get('/sites')->assertOk();
    $this->actingAs($dev)->get('/hostings')->assertOk();
});
