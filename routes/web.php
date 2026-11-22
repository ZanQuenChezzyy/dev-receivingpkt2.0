<?php

use App\Http\Controllers\Admin\BatchVerifikasiGrsUploadController;
use App\Http\Controllers\Admin\CashierTransmittalPrintController;
use App\Http\Controllers\Admin\ChunkedImportUploadController;
use App\Http\Controllers\Admin\MaterialIssuePrintController;
use App\Http\Controllers\Admin\QCTransmittalPrintController;
use App\Http\Controllers\Admin\TransmittalPrintController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\QRCodeController;
use App\Livewire\Frontend\Home;
use App\Livewire\Frontend\PdNonstockList;
use App\Livewire\Frontend\PublicMaterialIssueForm;
use App\Livewire\Frontend\VendorPoTracking;
use Illuminate\Support\Facades\Route;

Route::get('/lang/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/', Home::class)->name('home');

Route::get('/pengambilan-barang/mir', PublicMaterialIssueForm::class)->name('frontend.mir.create');
Route::get('/list-material', PdNonstockList::class)->name('frontend.list-material');
Route::get('/lacak-po-vendor', VendorPoTracking::class)->name('frontend.vendor.tracking');

Route::get('/admin/material-issues/print-bulk', [MaterialIssuePrintController::class, 'printBulk'])
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.material-issues.print_bulk');

Route::get('/admin/material-issues/{materialIssue:mir_number}/print', [MaterialIssuePrintController::class, 'print'])
    ->where('materialIssue', '.*')
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.material-issues.print');

Route::get('/admin/warehouse-transmittals/{transmittal:transmittal_no}/print', [TransmittalPrintController::class, 'print'])
    ->where('transmittal', '.*')
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.warehouse-transmittals.print');

Route::get('/admin/transmittals/{transmittal:transmittal_no}/print-qc', [QCTransmittalPrintController::class, 'print'])
    ->where('transmittal', '.*')
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.transmittals.print_qc');

Route::get('/admin/warehouse-transmittals/print-bulk', [TransmittalPrintController::class, 'printBulk'])
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.warehouse-transmittals.print_bulk');

Route::get('/admin/print-bulk-do-qr', [QRCodeController::class, 'bulkPrint'])
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.delivery-order-receipts.bulk_print_qr');

Route::get('/admin/delivery-order-receipts/{deliveryOrderReceipt:document_code}/print-qr', [QRCodeController::class, 'print'])
    ->where('deliveryOrderReceipt', '.*')
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.delivery-order-receipts.print_qr');

Route::get('/admin/invoicing-receipts/print-cashier-transmittal', [CashierTransmittalPrintController::class, 'print'])
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.invoicing-receipts.print_cashier_transmittal');

Route::get('/admin/cashier-transmittals/{cashierTransmittal:transmittal_no}/print', [CashierTransmittalPrintController::class, 'printTransmittal'])
    ->where('cashierTransmittal', '.*')
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.cashier-transmittals.print');

Route::post('/admin/invoicing-receipts/import-chunks', [ChunkedImportUploadController::class, 'store'])
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.invoicing-receipts.import_chunks');

Route::post('/admin/invoicing-receipts/upload-verifikasi-grs-ocr', [BatchVerifikasiGrsUploadController::class, 'uploadAndVerify'])
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.invoicing-receipts.upload_verifikasi_grs_ocr');

Route::delete('/admin/invoicing-receipts/import-chunks', [ChunkedImportUploadController::class, 'destroy'])
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.invoicing-receipts.import_chunks.destroy');
