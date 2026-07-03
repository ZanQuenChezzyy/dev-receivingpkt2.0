<?php

namespace App\Livewire\Frontend;

use App\Models\DeliveryOrderReceiptDetail;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Daftar Material PD & Non-Stock')]
class PdNonstockList extends Component
{
    use WithPagination;

    public $activeTab = 'PD';

    public $search = '';

    public $layout = 'grid'; // 'grid' or 'table'

    protected $queryString = [
        'activeTab' => ['except' => 'PD'],
        'search' => ['except' => ''],
        'layout' => ['except' => 'grid'],
    ];

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function getItemsProperty()
    {
        $query = DeliveryOrderReceiptDetail::with(['deliveryOrderReceipt', 'purchaseOrderIssued', 'locationReceiving'])
            ->where('mrp_type', $this->activeTab === 'PD' ? 'PD' : 'NONSTOCK')
            ->where(function ($q) {
                if ($this->activeTab === 'PD') {
                    $q->where('material_type', 'ZSP');
                } else {
                    $q->where('material_type', 'ZSP')
                        ->orWhereNull('material_type');
                }
            })
            ->whereDoesntHave('deliveryOrderReceipt', function ($q) {
                $q->where('status', 'RDTV');
            })
            ->whereRaw('quantity > (SELECT COALESCE(SUM(diserahkan), 0) FROM material_issue_details WHERE material_issue_details.delivery_order_receipt_detail_id = delivery_order_receipt_details.id)');

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->whereHas('purchaseOrderIssued', function ($qPo) {
                    $qPo->where('purchase_order_no', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%')
                        ->orWhere('material_code', 'like', '%' . $this->search . '%')
                        ->orWhere('requisitioner', 'like', '%' . $this->search . '%');
                });
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(12);
    }

    public function render()
    {
        return view('livewire.frontend.pd-nonstock-list')->layout('components.layouts.frontend');
    }
}
