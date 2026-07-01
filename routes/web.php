<?php

use App\Http\Controllers\Admin\MaterialIssuePrintController;
use App\Http\Controllers\Admin\TransmittalPrintController;
use App\Http\Controllers\QRCodeController;
use App\Livewire\Frontend\Home;
use App\Livewire\Frontend\PdNonstockList;
use App\Livewire\Frontend\PublicMaterialIssueForm;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');

Route::get('/pengambilan-barang/mir', PublicMaterialIssueForm::class)->name('frontend.mir.create');
Route::get('/list-material', PdNonstockList::class)->name('frontend.list-material');

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

Route::get('/admin/transmittals/{transmittal:transmittal_no}/print-qc', [\App\Http\Controllers\Admin\QCTransmittalPrintController::class, 'print'])
    ->where('transmittal', '.*')
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.transmittals.print_qc');

Route::get('/admin/warehouse-transmittals/print-bulk', [TransmittalPrintController::class, 'printBulk'])
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.warehouse-transmittals.print_bulk');

Route::get('/admin/print-bulk-do-qr', [QRCodeController::class, 'bulkPrint'])
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.delivery-order-receipts.bulk_print_qr');

Route::get('/admin/delivery-order-receipts/{deliveryOrderReceipt}/print-qr', [QRCodeController::class, 'print'])
    ->middleware(['web', 'auth'])
    ->name('filament.admin.resources.delivery-order-receipts.print_qr');
