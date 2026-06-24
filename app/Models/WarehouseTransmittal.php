<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseTransmittal extends Model
{
    use HasFactory;

    protected $fillable = [
        'transmittal_no',
        'warehouse_destination_id',
        'tanggal',
        'created_by',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function destination()
    {
        return $this->belongsTo(WarehouseDestination::class, 'warehouse_destination_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(WarehouseTransmittalItem::class);
    }
}
