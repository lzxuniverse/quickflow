<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Property\Controllers\PropertyController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');


