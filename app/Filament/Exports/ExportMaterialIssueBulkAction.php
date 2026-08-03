<?php

namespace App\Filament\Exports;

use App\Models\MaterialIssueDetail;
use Filament\Actions\ExportBulkAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class ExportMaterialIssueBulkAction extends ExportBulkAction
{
    public function getIndividuallyAuthorizedSelectedRecords(): EloquentCollection|Collection|LazyCollection
    {
        $selectedRecords = parent::getIndividuallyAuthorizedSelectedRecords();

        $materialIssueIds = $selectedRecords->pluck('id')->all();

        return MaterialIssueDetail::query()
            ->whereIn('material_issue_id', $materialIssueIds)
            ->with([
                'materialIssue.purchaseOrderIssued',
                'materialIssue.deliveryOrderReceipt',
                'materialIssue.createdBy',
                'deliveryOrderReceiptDetail.locationReceiving',
                'deliveryOrderReceiptDetail.warehouseDestination',
            ])
            ->get();
    }
}
