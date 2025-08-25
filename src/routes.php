<?php

use Illuminate\Support\Facades\Route;
use Giobi\LaravelBackupDb\Http\Controllers\BackupController;

Route::get('/', [BackupController::class, 'index'])->name('laravel-backup-db.index');
Route::post('/restore', [BackupController::class, 'restore'])->name('laravel-backup-db.restore');