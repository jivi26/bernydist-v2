<?php

use App\Http\Controllers\Api\CatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('catalogo')->group(function () {
    Route::get('/', [CatalogController::class, 'products']);
    Route::get('/{id}', [CatalogController::class, 'show'])->where('id', '[0-9]+');
});

Route::get('/categorias', [CatalogController::class, 'categories']);
Route::get('/divisiones', [CatalogController::class, 'divisions']);
