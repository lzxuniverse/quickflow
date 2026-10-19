<?php

use App\Mcp\Http\Controllers\McpController;
use App\Mcp\Http\Middleware\McpAuthenticate;
use Illuminate\Support\Facades\Route;

Route::match(['get', 'post', 'delete', 'options'], '/mcp', [McpController::class, 'handle'])
    ->middleware(McpAuthenticate::class)
    ->name('mcp.handle');

Route::match(['get', 'post', 'delete', 'options'], '/mcp/tenants/{tenant}', [McpController::class, 'handleTenant'])
    ->middleware(McpAuthenticate::class)
    ->name('mcp.tenant.handle');

Route::match(['get', 'post', 'delete', 'options'], '/mcp/{tenant}', [McpController::class, 'handleTenant'])
    ->middleware(McpAuthenticate::class)
    ->where('tenant', '^(?!tenants$).*')
    ->name('mcp.tenant.short.handle');
