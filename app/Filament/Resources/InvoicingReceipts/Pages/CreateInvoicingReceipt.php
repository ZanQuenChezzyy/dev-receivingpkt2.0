<?php

namespace App\Filament\Resources\InvoicingReceipts\Pages;

use App\Filament\Resources\InvoicingReceipts\InvoicingReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoicingReceipt extends CreateRecord
{
    protected static string $resource = InvoicingReceiptResource::class;
}
