<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DbModelController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Model management routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('models', DbModelController::class);
    Route::get('api/field-types', [DbModelController::class, 'getFieldTypes'])->name('api.field-types');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
