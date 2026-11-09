<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

test('login redireciona por role OWNER admin DEVELOPER developer', function () {
    $eliatan = User::factory()->create(['name' => 'Eliatan Gabriel', 'email' => 'eliatan.gabriel@teste.com', 'password' => bcrypt('password123')]);
    $eliatan->assignRole('OWNER');
    $roger = User::factory()->create(['name' => 'Roger Pereira', 'email' => 'roger.pereira@teste.com', 'password' => bcrypt('password123')]);
    $roger->assignRole('DEVELOPER');
    $commercial = User::factory()->create(['name' => 'Alisson Souza', 'email' => 'alisson@teste.com', 'password' => bcrypt('password123')]);
    $commercial->assignRole('COMMERCIAL');

    $this->post('/login', ['email' => 'eliatan.gabriel@teste.com', 'password' => 'password123'])->assertRedirect('/admin');
    auth()->logout();
    $this->post('/login', ['email' => 'roger.pereira@teste.com', 'password' => 'password123'])->assertRedirect('/developer');
    auth()->logout();
    $this->post('/login', ['email' => 'alisson@teste.com', 'password' => 'password123'])->assertRedirect('/commercial');
    auth()->logout();
    $this->actingAs($eliatan)->get('/')->assertRedirect('/admin');
    $this->actingAs($roger)->get('/')->assertRedirect('/developer');
    $this->actingAs($eliatan)->get('/dashboard')->assertRedirect('/admin');
    $this->actingAs($roger)->get('/dashboard')->assertRedirect('/developer');
    $this->actingAs($commercial)->get('/dashboard')->assertRedirect('/commercial');
});
