<?php

use App\Http\Controllers\AccountOpeningController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/account-opening');

Route::controller(AccountOpeningController::class)->prefix('account-opening')->group(function () {
    Route::get('/', 'index')->name('ao.index');
    Route::get('/template-bulk', 'template')->name('ao.template');
    Route::get('/satuan', 'satuan')->name('ao.satuan');

    Route::get('/bulk', 'bulk')->name('ao.bulk');
    Route::post('/bulk/extract', 'extract')->name('ao.extract');
    Route::post('/bulk/validate-row', 'validateRow')->name('ao.validate');
    Route::post('/bulk/submit', 'submit')->name('ao.submit');
    Route::get('/bulk/pdf/{token}', 'pendingPdf')->name('ao.pdf');

    Route::get('/reports', 'reports')->name('ao.reports');
    Route::get('/request/{bulkRequest}', 'show')->name('ao.show');
    Route::post('/request/{bulkRequest}/approve', 'approve')->name('ao.approve');
    Route::post('/request/{bulkRequest}/reject', 'reject')->name('ao.reject');
    Route::get('/request/{bulkRequest}/download', 'download')->name('ao.download');
    Route::get('/item/{item}/pdf', 'itemPdf')->name('ao.item.pdf');
});
