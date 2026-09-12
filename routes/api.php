<?php

use App\Mcp\Http\Controllers\McpController;
use Illuminate\Support\Facades\Route;

Route::post('/mcp', [McpController::class, 'handle'])->name('mcp.handle');
