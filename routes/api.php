<?php

use DigitalCreative\Filepond\Http\Controllers\FilepondController;
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

Route::post('/process', [ FilepondController::class, 'process' ])->name('nova.filepond.process');
Route::delete('/revert', [ FilepondController::class, 'revert' ])->name('nova.filepond.revert');
Route::get('/load', [ FilepondController::class, 'load' ])->name('nova.filepond.load');
