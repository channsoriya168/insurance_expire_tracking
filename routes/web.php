<?php

use App\Http\Controllers\InsuranceFormController;
use App\Http\Controllers\TelegramWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('signed')->prefix('forms/insurances')->name('forms.insurances.')->group(function () {
    Route::get('/create', [InsuranceFormController::class, 'showCreate'])->name('create');
    Route::post('/create', [InsuranceFormController::class, 'store'])->name('create.store');

    Route::get('/edit', [InsuranceFormController::class, 'showEdit'])->name('edit');
    Route::post('/edit', [InsuranceFormController::class, 'saveEdit'])->name('edit.save');

    Route::get('/delete', [InsuranceFormController::class, 'showDelete'])->name('delete');
    Route::post('/delete', [InsuranceFormController::class, 'destroy'])->name('delete.destroy');

    Route::get('/export', [InsuranceFormController::class, 'showExport'])->name('export');
    Route::post('/export', [InsuranceFormController::class, 'export'])->name('export.download');
});
Route::post('/telegram/webhook', TelegramWebhookController::class)->name('telegram.webhook');
