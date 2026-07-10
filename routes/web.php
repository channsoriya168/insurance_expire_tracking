<?php

use App\Http\Controllers\InsuranceController;
use App\Http\Controllers\InsuranceFormController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('telegram.chat')->group(function () {
    Route::resource('insurances', InsuranceController::class)->except('show');
});

Route::middleware('signed')->prefix('forms/insurances')->name('forms.insurances.')->group(function () {
    Route::get('/export', [InsuranceFormController::class, 'showExport'])->name('export');
    Route::post('/export', [InsuranceFormController::class, 'export'])->name('export.download');
});
