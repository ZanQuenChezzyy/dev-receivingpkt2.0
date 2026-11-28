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

#[Fillable(['name', 'email', 'password', 'avatar_url', 'cover_photo_url', 'npk', 'is_active', 'left_rail_shortcuts', 'developer_badge_style'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    public const DEVELOPER_BADGE_FLAME = 'flame';

    public const DEVELOPER_BADGE_ELECTRIC = 'electric';

    public const DEVELOPER_BADGE_PKT_COMBO = 'pkt_combo';

    public const DEVELOPER_BADGE_NONE = 'none';

    public const CLASSIC_BADGE_BLUE = 'classic_blue';

    public const CLASSIC_BADGE_EMERALD = 'classic_emerald';

    public const CLASSIC_BADGE_VIOLET = 'classic_violet';

    public const CLASSIC_BADGE_ROSE = 'classic_rose';

    public const CLASSIC_BADGE_SLATE = 'classic_slate';

    /**
     * Daftar role yang berhak memakai & mengatur animasi badge eksklusif.
     *
     * @var list<string>
     */
    public const ANIMATED_BADGE_ROLES = [
        'Developer',
        'AVP Receiving',
    ];

    /**
     * Pilihan gaya animasi badge eksklusif (khusus Developer & AVP Receiving).
     *
     * @return array<string, string>
     */
    public static function exclusiveAnimatedBadgeOptions(): array
    {
        return [
            self::DEVELOPER_BADGE_ELECTRIC => 'Sparkling Lightning',
            self::DEVELOPER_BADGE_FLAME => 'Blazing Ember',
            self::DEVELOPER_BADGE_PKT_COMBO => 'Solar Aurora',
        ];
    }

    /**
     * Pilihan gaya Classic Clean yang dapat dikustomisasi oleh seluruh pengguna.
     *
     * @return array<string, string>
     */
    public static function classicCleanBadgeOptions(): array
    {
        return [
            self::DEVELOPER_BADGE_NONE => 'Classic Amber',
            self::CLASSIC_BADGE_BLUE => 'Classic Cobalt',
            self::CLASSIC_BADGE_EMERALD => 'Classic Emerald',
            self::CLASSIC_BADGE_VIOLET => 'Classic Violet',
            self::CLASSIC_BADGE_ROSE => 'Classic Rose',
            self::CLASSIC_BADGE_SLATE => 'Classic Slate',
        ];
    }

    /**
     * Seluruh pilihan gaya badge (eksklusif + Classic Clean).
     *
     * @return array<string, string>
     */
    public static function developerBadgeOptions(): array
    {
        return [
            ...self::exclusiveAnimatedBadgeOptions(),
            ...self::classicCleanBadgeOptions(),
        ];
    }

    /**
     * Memeriksa apakah sebuah gaya badge termasuk kategori animasi eksklusif.
     */
    public static function isExclusiveBadgeStyle(string $style): bool
    {
        return array_key_exists($style, self::exclusiveAnimatedBadgeOptions());
    }

    /**
     * Memeriksa apakah pengguna memiliki role yang berhak memakai gaya animasi badge eksklusif.
     */
    public function canCustomizeAnimatedBadge(): bool
    {
        return $this->hasAnyRole(self::ANIMATED_BADGE_ROLES);
    }

    /**
     * Daftar opsi gaya badge yang boleh dipilih & disimpan oleh pengguna ini.
     *
     * @return array<string, string>
     */
    public function allowedBadgeOptions(): array
    {
        return $this->canCustomizeAnimatedBadge()
            ? self::developerBadgeOptions()
            : self::classicCleanBadgeOptions();
    }

    /**
     * Mengembalikan nama role prioritas yang memakai animasi badge untuk pengguna ini.
     */
    public function primaryAnimatedBadgeRole(): ?string
    {
        foreach (self::ANIMATED_BADGE_ROLES as $roleName) {
            if ($this->hasRole($roleName)) {
                return $roleName;
            }
        }

        return null;
    }

    /**
     * Mengembalikan gaya badge aktif milik pengguna (mendukung Classic Clean untuk semua user
     * dan animasi eksklusif khusus Developer & AVP Receiving).
     */
    public function resolvedDeveloperBadgeStyle(): string
    {
        $style = trim((string) ($this->developer_badge_style ?? ''));

        if ($this->canCustomizeAnimatedBadge()) {
            if ($style !== '' && array_key_exists($style, self::developerBadgeOptions())) {
                return $style;
            }

            return self::DEVELOPER_BADGE_ELECTRIC;
        }

        if ($style !== '' && array_key_exists($style, self::classicCleanBadgeOptions())) {
            return $style;
        }

        return self::DEVELOPER_BADGE_NONE;
    }

    public function isDeveloperBadgeAnimated(): bool
    {
        return $this->canCustomizeAnimatedBadge()
            && self::isExclusiveBadgeStyle($this->resolvedDeveloperBadgeStyle());
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

    public function getCoverPhotoUrl(): ?string
    {
        return $this->cover_photo_url ? Storage::url($this->cover_photo_url) : null;
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
