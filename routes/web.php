<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [LoginController::class, 'showForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// SPA catch-all: Vue Router maneja todas las rutas del frontend
// El login sigue siendo Blade puro (fuera del SPA)
Route::get('/{any?}', function () {
    return view('spa');
})->where('any', '^(?!api|login|logout).*$');
