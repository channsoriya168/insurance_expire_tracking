<?php

use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\InsuranceFormController;
use App\Http\Controllers\InsuranceNotificationController;
use App\Http\Controllers\TelegramAuthController;
use App\Http\Controllers\TelegramLaunchController;
use App\Http\Controllers\ToggleInsuranceNotificationReadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/telegram/launch', TelegramLaunchController::class)->name('telegram.launch');
Route::post('/telegram/auth', TelegramAuthController::class)->name('telegram.auth');

Route::middleware('telegram.chat')->group(function () {
    Route::get('insurances-notifications', InsuranceNotificationController::class)->name('insurances.notifications');
    Route::patch('insurances-notifications/{insurance}/read', ToggleInsuranceNotificationReadController::class)->name('insurances.notifications.read');
    Route::resource('insurances', InsuranceController::class);
});

Route::middleware('signed')->prefix('forms/insurances')->name('forms.insurances.')->group(function () {
    Route::get('/export', [InsuranceFormController::class, 'showExport'])->name('export');
    Route::post('/export', [InsuranceFormController::class, 'export'])->name('export.download');
});
