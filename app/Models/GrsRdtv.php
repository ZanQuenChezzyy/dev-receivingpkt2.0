<?php

namespace App\Models;

use App\Services\BadgeMilestoneService;
use Illuminate\Database\Eloquent\Model;

class GrsRdtv extends Model
{
    protected $fillable = [
        'transaction_date',
        'category',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
    ];

    public function grsRdtvItems()
    {
        return $this->hasMany(GrsRdtvItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    protected static function booted(): void
    {
        static::created(function (GrsRdtv $grsRdtv): void {
            app(BadgeMilestoneService::class)->syncMilestonesForUsers([$grsRdtv->created_by]);
        });

        static::deleting(function (GrsRdtv $grsRdtv) {
            foreach ($grsRdtv->grsRdtvItems()->get() as $item) {
                $item->delete();
            }
        });
    }
}
