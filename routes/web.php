<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Property\Controllers\PropertyController;

use App\Mcp\Http\Controllers\McpController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/mcp', [McpController::class, 'handle'])->name('mcp.web');

Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');


