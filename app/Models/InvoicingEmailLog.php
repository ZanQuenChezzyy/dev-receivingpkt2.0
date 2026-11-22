<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoicing_receipt_id',
    'sent_by',
    'jenis',
    'trigger_source',
    'recipients',
    'original_recipients',
    'cc_recipients',
    'subject',
    'body_html',
    'attachments',
    'status',
    'zimbra_message_id',
    'error_message',
    'sent_at',
])]
class InvoicingEmailLog extends Model
{
    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'original_recipients' => 'array',
            'cc_recipients' => 'array',
            'attachments' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function invoicingReceipt(): BelongsTo
    {
        return $this->belongsTo(InvoicingReceipt::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
