<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Property\Controllers\PropertyController;

use App\Mcp\Http\Controllers\McpController;
use App\Mcp\Http\Middleware\McpAuthenticate;

Route::get('/', function () {
    return view('welcome');
});

Route::match(['get', 'post', 'delete', 'options'], '/mcp', [McpController::class, 'handle'])
    ->middleware(McpAuthenticate::class)
    ->name('mcp.web');

Route::match(['get', 'post', 'delete', 'options'], '/mcp/tenants/{tenant}', [McpController::class, 'handleTenant'])
    ->middleware(McpAuthenticate::class)
    ->name('mcp.tenant.web');

Route::match(['get', 'post', 'delete', 'options'], '/mcp/{tenant}', [McpController::class, 'handleTenant'])
    ->middleware(McpAuthenticate::class)
    ->where('tenant', '^(?!tenants$).*')
    ->name('mcp.tenant.short.web');

Route::get('/properties', [PropertyController::class, 'index'])->name('properties.index');
Route::get('/properties/stats', [PropertyController::class, 'stats'])->name('properties.stats');
Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('properties.show');
Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('properties.edit');
Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('properties.update');
Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])->name('properties.destroy');
