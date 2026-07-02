<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', fn () => view('spa'))->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');
Route::post('/recuperar-password', [ForgotPasswordController::class, 'send'])->name('password.recover');

// SPA catch-all — Vue Router maneja el resto de rutas frontend
Route::get('/{any?}', fn () => view('spa'))->where('any', '^(?!api|logout).*$');
