<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
})->name('home');

// Redirect após login (Fortify usa config fortify.home)
Route::get('/dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'can:view-dashboard'])->name('dashboard');

Route::post('/logout-redirect', function () {
    return redirect('/login');
})->name('logout.redirect');
