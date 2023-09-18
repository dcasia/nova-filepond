<?php

declare(strict_types = 1);

use DigitalCreative\Filepond\Http\Controllers\LoadController;
use DigitalCreative\Filepond\Http\Controllers\ProcessController;
use DigitalCreative\Filepond\Http\Controllers\RevertController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. You're free to add
| as many additional routes to this file as your tool may require.
|
 */

Route::post('/process', ProcessController::class)->name('nova.filepond.process');
Route::delete('/revert', RevertController::class)->name('nova.filepond.revert');
Route::get('/load', LoadController::class)->name('nova.filepond.load');
