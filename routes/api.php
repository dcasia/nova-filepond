<?php

declare(strict_types = 1);

use DigitalCreative\Filepond\Http\Controllers\LoadController;
use DigitalCreative\Filepond\Http\Controllers\ProcessController;
use DigitalCreative\Filepond\Http\Controllers\RevertController;
use Illuminate\Support\Facades\Route;

Route::get('/load', LoadController::class)->name('nova.filepond.load');
Route::post('/process', ProcessController::class)->name('nova.filepond.process');
Route::delete('/revert', RevertController::class)->name('nova.filepond.revert');
