<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseDestination extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'pic_id'];

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }
}
