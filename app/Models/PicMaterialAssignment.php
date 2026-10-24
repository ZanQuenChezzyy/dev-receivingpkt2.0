<?php

namespace App\Models;

use Database\Factories\PicMaterialAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable([
    'material_type',
    'assignment_mode',
    'user_id',
    'notes',
])]
class PicMaterialAssignment extends Model
{
    /** @use HasFactory<PicMaterialAssignmentFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pic_material_assignment_user');
    }
}
