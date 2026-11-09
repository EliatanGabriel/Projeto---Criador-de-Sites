<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('login redireciona Eliatan Gabriel e Roger Pereira para developer', function () {
    $eliatan = User::factory()->create(['name' => 'Eliatan Gabriel', 'email' => 'eliatan.gabriel@teste.com', 'password' => bcrypt('password123')]);
    $eliatan->assignRole('OWNER');
    $roger = User::factory()->create(['name' => 'Roger Pereira', 'email' => 'roger.pereira@teste.com', 'password' => bcrypt('password123')]);
    $roger->assignRole('DEVELOPER');
    $other = User::factory()->create(['name' => 'Fulano Silva', 'email' => 'fulano@teste.com', 'password' => bcrypt('password123')]);
    $other->assignRole('OWNER');

    $this->post('/login', ['email' => 'eliatan.gabriel@teste.com', 'password' => 'password123'])->assertRedirect('/developer');
    auth()->logout();
    $this->post('/login', ['email' => 'roger.pereira@teste.com', 'password' => 'password123'])->assertRedirect('/developer');
    auth()->logout();
    $this->actingAs($eliatan)->get('/')->assertRedirect('/developer');
    $this->actingAs($roger)->get('/')->assertRedirect('/developer');
    $this->actingAs($eliatan)->get('/dashboard')->assertRedirect('/developer');
    $this->actingAs($roger)->get('/dashboard')->assertRedirect('/developer');
    $this->actingAs($other)->get('/')->assertRedirect('/admin');
});
