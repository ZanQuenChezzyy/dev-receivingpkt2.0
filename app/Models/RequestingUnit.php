<?php

namespace App\Models;

use Database\Factories\RequestingUnitFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestingUnit extends Model
{
    /** @use HasFactory<RequestingUnitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'requisitioner_aliases',
        'pic_name',
        'pic_npk',
        'pic_email',
        'pic_phone',
        'avp_name',
        'avp_title',
        'avp_email',
        'avp_phone',
        'vp_name',
        'vp_title',
        'vp_email',
        'vp_phone',
        'is_pilot',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requisitioner_aliases' => 'array',
            'is_pilot' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function nonStockAgingControls(): HasMany
    {
        return $this->hasMany(NonStockAgingControl::class);
    }

    /**
     * @return list<string>
     */
    public function normalizedAliases(): array
    {
        $aliases = is_array($this->requisitioner_aliases) ? $this->requisitioner_aliases : [];
        $normalized = [];

        foreach ($aliases as $alias) {
            $clean = strtoupper(trim((string) $alias));
            if ($clean !== '') {
                $normalized[] = $clean;
            }
        }

        return array_values(array_unique($normalized));
    }
}
