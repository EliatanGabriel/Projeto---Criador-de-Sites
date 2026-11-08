<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

function panelUser(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

test('guest acessa login dos paineis e e redirecionado para login no painel', function () {
    $this->get('/login/admin')->assertOk()->assertInertia(function ($page) {
        $page->component('auth/login')->where('panel', 'admin');
    });
    $this->get('/login/commercial')->assertOk()->assertInertia(function ($page) {
        $page->component('auth/login')->where('panel', 'commercial');
    });
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/commercial')->assertRedirect('/login');
});

test('owner acessa painel admin e comercial', function () {
    $owner = panelUser('OWNER');

    $this->actingAs($owner)->get('/admin')->assertOk()->assertInertia(fn ($page) => $page->component('admin/dashboard'));
    $this->actingAs($owner)->get('/commercial')->assertOk()->assertInertia(fn ($page) => $page->component('commercial/dashboard'));
    $this->actingAs($owner)->get('/login/admin')->assertRedirect('/admin');
});

test('commercial acessa painel comercial mas nao admin', function () {
    $commercial = panelUser('COMMERCIAL');

    $this->actingAs($commercial)->get('/commercial')->assertOk()->assertInertia(fn ($page) => $page->component('commercial/dashboard'));
    $this->actingAs($commercial)->get('/admin')->assertForbidden();
    $this->actingAs($commercial)->get('/login/admin')->assertRedirect('/commercial');
});

test('developer nao acessa nenhum painel', function () {
    $developer = panelUser('DEVELOPER');

    $this->actingAs($developer)->get('/admin')->assertForbidden();
    $this->actingAs($developer)->get('/commercial')->assertForbidden();
});
