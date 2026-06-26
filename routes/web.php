<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        $user = Auth::user();
        $cliente = $user->cliente;

        return response()->json([
            'cliente_id'    => $user->cliente_id,
            'nombre'        => $cliente?->NOMBRE,
            'clave_cliente' => $cliente?->CLAVE_CLIENTE,
            'estatus'       => $cliente?->ESTATUS,
        ]);
    });
});
