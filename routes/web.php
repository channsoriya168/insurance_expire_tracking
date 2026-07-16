<?php

use App\Http\Controllers\InsuranceCompanyController;
use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\InsuranceFormController;
use App\Http\Controllers\InsuranceNotificationController;
use App\Http\Controllers\PolicyTypeController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TelegramAuthController;
use App\Http\Controllers\TelegramLaunchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/telegram/launch', TelegramLaunchController::class)->name('telegram.launch');
Route::post('/telegram/auth', TelegramAuthController::class)->name('telegram.auth');

Route::middleware('telegram.chat')->group(function () {
    Route::get('insurances-notifications', [InsuranceNotificationController::class, 'index'])->name('insurances.notifications');
    Route::patch('insurances-notifications/{insurance}/read', [InsuranceNotificationController::class, 'toggleRead'])->name('insurances.notifications.read');
    Route::resource('insurances', InsuranceController::class);

    Route::get('settings', [SettingsController::class, 'index'])->name('settings');

    Route::get('insurance-companies', [InsuranceCompanyController::class, 'index'])->name('insurance-companies.index');
    Route::post('insurance-companies', [InsuranceCompanyController::class, 'store'])->name('insurance-companies.store');
    Route::patch('insurance-companies/{insuranceCompany}', [InsuranceCompanyController::class, 'update'])->name('insurance-companies.update');
    Route::delete('insurance-companies/{insuranceCompany}', [InsuranceCompanyController::class, 'destroy'])->name('insurance-companies.destroy');

    Route::get('policy-types', [PolicyTypeController::class, 'index'])->name('policy-types.index');
    Route::post('policy-types', [PolicyTypeController::class, 'store'])->name('policy-types.store');
    Route::patch('policy-types/{policyType}', [PolicyTypeController::class, 'update'])->name('policy-types.update');
    Route::delete('policy-types/{policyType}', [PolicyTypeController::class, 'destroy'])->name('policy-types.destroy');
});

Route::middleware('signed')->prefix('forms/insurances')->name('forms.insurances.')->group(function () {
    Route::get('/export', [InsuranceFormController::class, 'showExport'])->name('export');
    Route::post('/export', [InsuranceFormController::class, 'export'])->name('export.download');
});
