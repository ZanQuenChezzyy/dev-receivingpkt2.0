<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Database\Factories\UserFactory;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'avatar_url', 'npk', 'is_active', 'left_rail_shortcuts', 'developer_badge_style'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const DEVELOPER_BADGE_FLAME = 'flame';

    public const DEVELOPER_BADGE_ELECTRIC = 'electric';

    public const DEVELOPER_BADGE_PKT_COMBO = 'pkt_combo';

    public const DEVELOPER_BADGE_NONE = 'none';

    /**
     * Pilihan gaya animasi badge khusus pengguna dengan role Developer.
     *
     * @return array<string, string>
     */
    public static function developerBadgeOptions(): array
    {
        return [
            self::DEVELOPER_BADGE_ELECTRIC => 'Listrik (Energi Biru PKT)',
            self::DEVELOPER_BADGE_FLAME => 'Api (Bara Oranye PKT)',
            self::DEVELOPER_BADGE_PKT_COMBO => 'Kombinasi PKT (Biru & Oranye)',
            self::DEVELOPER_BADGE_NONE => 'Standar (Tanpa Animasi)',
        ];
    }

    /**
     * Mengembalikan gaya badge Developer aktif milik pengguna.
     */
    public function resolvedDeveloperBadgeStyle(): string
    {
        if (! $this->hasRole('Developer')) {
            return self::DEVELOPER_BADGE_NONE;
        }

        $style = trim((string) ($this->developer_badge_style ?? ''));

        if ($style !== '' && array_key_exists($style, self::developerBadgeOptions())) {
            return $style;
        }

        return self::DEVELOPER_BADGE_ELECTRIC;
    }

    public function isDeveloperBadgeAnimated(): bool
    {
        return $this->hasRole('Developer')
            && $this->resolvedDeveloperBadgeStyle() !== self::DEVELOPER_BADGE_NONE;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'left_rail_shortcuts' => 'array',
        ];
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    public function isOnline(int $minutes = 5): bool
    {
        if (auth()->check() && auth()->id() === $this->id) {
            return true;
        }

        if (Cache::get('user-is-online-'.$this->id, false)) {
            return true;
        }

        if (config('session.driver') === 'database') {
            try {
                $onlineUserIds = Cache::remember('online_user_ids', 30, function () use ($minutes) {
                    return DB::table('sessions')
                        ->whereNotNull('user_id')
                        ->where('last_activity', '>=', now()->subMinutes($minutes)->timestamp)
                        ->pluck('user_id')
                        ->map(fn ($id): int => (int) $id)
                        ->toArray();
                });

                return in_array((int) $this->id, $onlineUserIds, true);
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return (bool) ($this->is_active ?? true) && $this->roles()->exists();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function invoicingReceipts(): HasMany
    {
        return $this->hasMany(InvoicingReceipt::class, 'pic_id');
    }

    public function picMaterialAssignments(): BelongsToMany
    {
        return $this->belongsToMany(PicMaterialAssignment::class, 'pic_material_assignment_user');
    }

    public function assignedPicMaterialAssignments(): HasMany
    {
        return $this->hasMany(PicMaterialAssignment::class, 'user_id');
    }
}
