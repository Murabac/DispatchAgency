<?php

use App\Http\Controllers\DocumentPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/pdf/invoices/{invoice}/download', [DocumentPdfController::class, 'downloadInvoice'])
        ->name('pdf.invoice.download');
    Route::get('/pdf/invoices/{invoice}/print', [DocumentPdfController::class, 'printInvoice'])
        ->name('pdf.invoice.print');

    Route::get('/pdf/quotations/{quotation}/download', [DocumentPdfController::class, 'downloadQuotation'])
        ->name('pdf.quotation.download');
    Route::get('/pdf/quotations/{quotation}/print', [DocumentPdfController::class, 'printQuotation'])
        ->name('pdf.quotation.print');

    Route::get('/pdf/receipts/{receipt}/download', [DocumentPdfController::class, 'downloadReceipt'])
        ->name('pdf.receipt.download');
    Route::get('/pdf/receipts/{receipt}/print', [DocumentPdfController::class, 'printReceipt'])
        ->name('pdf.receipt.print');
});
