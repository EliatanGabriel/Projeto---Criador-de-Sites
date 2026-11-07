<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemandController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OpportunityController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectTemplateController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
})->name('home');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'can:view-dashboard'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('team', [UserController::class, 'index'])->middleware('can:users.view')->name('team.index');
    Route::get('team/create', [UserController::class, 'create'])->middleware('can:users.create')->name('team.create');
    Route::post('team', [UserController::class, 'store'])->middleware('can:users.create')->name('team.store');
    Route::get('team/{user}/edit', [UserController::class, 'edit'])->middleware('can:users.edit')->name('team.edit');
    Route::put('team/{user}', [UserController::class, 'update'])->middleware('can:users.edit')->name('team.update');
    Route::post('team/{user}/toggle-active', [UserController::class, 'toggleActive'])
        ->middleware('can:users.edit')
        ->name('team.toggle-active');

    Route::get('clients', [ClientController::class, 'index'])->middleware('can:clients.view')->name('clients.index');
    Route::get('clients/create', [ClientController::class, 'create'])->middleware('can:clients.create')->name('clients.create');
    Route::post('clients', [ClientController::class, 'store'])->middleware('can:clients.create')->name('clients.store');
    Route::get('clients/{client}', [ClientController::class, 'show'])->middleware('can:clients.view')->name('clients.show');
    Route::get('clients/{client}/edit', [ClientController::class, 'edit'])->middleware('can:clients.edit')->name('clients.edit');
    Route::put('clients/{client}', [ClientController::class, 'update'])->middleware('can:clients.edit')->name('clients.update');

    Route::get('opportunities', [OpportunityController::class, 'index'])->middleware('can:commercial.view')->name('opportunities.index');
    Route::get('opportunities/create', [OpportunityController::class, 'create'])->middleware('can:commercial.create')->name('opportunities.create');
    Route::post('opportunities', [OpportunityController::class, 'store'])->middleware('can:commercial.create')->name('opportunities.store');
    Route::get('opportunities/{opportunity}', [OpportunityController::class, 'show'])->middleware('can:commercial.view')->name('opportunities.show');
    Route::get('opportunities/{opportunity}/edit', [OpportunityController::class, 'edit'])->middleware('can:commercial.edit')->name('opportunities.edit');
    Route::put('opportunities/{opportunity}', [OpportunityController::class, 'update'])->middleware('can:commercial.edit')->name('opportunities.update');
    Route::patch('opportunities/{opportunity}/status', [OpportunityController::class, 'updateStatus'])
        ->middleware('can:commercial.edit')
        ->name('opportunities.update-status');

    Route::get('demands', [DemandController::class, 'index'])->middleware('can:demands.view')->name('demands.index');
    Route::get('demands/create', [DemandController::class, 'create'])->middleware('can:demands.create')->name('demands.create');
    Route::post('demands', [DemandController::class, 'store'])->middleware('can:demands.create')->name('demands.store');
    Route::get('demands/{demand}', [DemandController::class, 'show'])->middleware('can:demands.view')->name('demands.show');
    Route::get('demands/{demand}/edit', [DemandController::class, 'edit'])->middleware('can:demands.edit')->name('demands.edit');
    Route::put('demands/{demand}', [DemandController::class, 'update'])->middleware('can:demands.edit')->name('demands.update');
    Route::post('demands/{demand}/submit', [DemandController::class, 'submit'])->middleware('can:demands.send')->name('demands.submit');
    Route::post('demands/{demand}/start-review', [DemandController::class, 'startReview'])->middleware('can:demands.analyze')->name('demands.start-review');
    Route::post('demands/{demand}/request-information', [DemandController::class, 'requestInformation'])->middleware('can:demands.analyze')->name('demands.request-information');
    Route::post('demands/{demand}/approve', [DemandController::class, 'approve'])->middleware('can:demands.approve')->name('demands.approve');
    Route::post('demands/{demand}/reject', [DemandController::class, 'reject'])->middleware('can:demands.approve')->name('demands.reject');
    Route::post('demands/{demand}/cancel', [DemandController::class, 'cancel'])->middleware('can:demands.analyze')->name('demands.cancel');
    Route::post('demands/{demand}/attachments', [DemandController::class, 'uploadAttachment'])->middleware('can:demands.edit')->name('demands.attachments.store');
    Route::get('demands/{demand}/attachments/{attachment}/download', [DemandController::class, 'downloadAttachment'])->middleware('can:demands.view')->name('demands.attachments.download');
    Route::post('demands/{demand}/convert', [ProjectController::class, 'convert'])->middleware('can:demands.convert')->name('demands.convert');

    Route::get('projects', [ProjectController::class, 'index'])->middleware('can:projects.view')->name('projects.index');
    Route::get('projects/create', [ProjectController::class, 'create'])->middleware('can:projects.create')->name('projects.create');
    Route::post('projects', [ProjectController::class, 'store'])->middleware('can:projects.create')->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->middleware('can:projects.view')->name('projects.show');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->middleware('can:projects.edit')->name('projects.edit');
    Route::put('projects/{project}', [ProjectController::class, 'update'])->middleware('can:projects.edit')->name('projects.update');
    Route::patch('projects/{project}/status', [ProjectController::class, 'updateStatus'])->middleware('can:projects.edit')->name('projects.update-status');

    Route::get('tasks', [TaskController::class, 'index'])->middleware('can:tasks.view')->name('tasks.index');
    Route::get('tasks/my', [TaskController::class, 'myTasks'])->middleware('can:tasks.view')->name('tasks.my');
    Route::get('tasks/create', [TaskController::class, 'create'])->middleware('can:tasks.create')->name('tasks.create');
    Route::post('tasks', [TaskController::class, 'store'])->middleware('can:tasks.create')->name('tasks.store');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->middleware('can:tasks.view')->name('tasks.show');
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->middleware('can:tasks.edit')->name('tasks.edit');
    Route::put('tasks/{task}', [TaskController::class, 'update'])->middleware('can:tasks.edit')->name('tasks.update');
    Route::post('tasks/{task}/comments', [TaskController::class, 'comment'])->middleware('can:tasks.edit')->name('tasks.comments.store');

    Route::get('templates', [ProjectTemplateController::class, 'index'])->middleware('can:templates.view')->name('templates.index');
    Route::get('templates/create', [ProjectTemplateController::class, 'create'])->middleware('can:templates.manage')->name('templates.create');
    Route::post('templates', [ProjectTemplateController::class, 'store'])->middleware('can:templates.manage')->name('templates.store');
    Route::get('templates/{template}', [ProjectTemplateController::class, 'show'])->middleware('can:templates.view')->name('templates.show');
    Route::get('templates/{template}/edit', [ProjectTemplateController::class, 'edit'])->middleware('can:templates.manage')->name('templates.edit');
    Route::put('templates/{template}', [ProjectTemplateController::class, 'update'])->middleware('can:templates.manage')->name('templates.update');
    Route::post('templates/{template}/toggle-active', [ProjectTemplateController::class, 'toggleActive'])->middleware('can:templates.manage')->name('templates.toggle-active');
    Route::get('templates/{template}/preview', [ProjectTemplateController::class, 'previewTemplate'])->middleware('can:templates.view')->name('templates.preview');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
});
