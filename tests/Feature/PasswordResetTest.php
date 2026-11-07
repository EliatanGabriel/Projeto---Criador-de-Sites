<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    Notification::fake();
});

test('usuário pode solicitar recuperação de senha', function () {
    $user = User::factory()->create(['email' => 'recuperar@example.com']);

    $response = $this->post('/forgot-password', ['email' => 'recuperar@example.com']);

    $response->assertSessionHasNoErrors();
    Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class);
});

test('token de recuperação permite redefinir a senha', function () {
    $user = User::factory()->create(['email' => 'reset@example.com']);
    $token = Password::createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => 'reset@example.com',
        'password' => 'NovaSenha@123',
        'password_confirmation' => 'NovaSenha@123',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertTrue(Hash::check('NovaSenha@123', $user->fresh()->password));
});

test('token inválido não permite redefinir', function () {
    $user = User::factory()->create(['email' => 'invalido@example.com']);

    $response = $this->post('/reset-password', [
        'token' => 'token-invalido',
        'email' => 'invalido@example.com',
        'password' => 'NovaSenha@123',
        'password_confirmation' => 'NovaSenha@123',
    ]);

    $response->assertSessionHasErrors('email');
});
