<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\UserActivityLogService;
use Filament\Pages\Page;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class MemberProfile extends Page
{
    protected static ?string $slug = 'member-profile';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.member-profile';

    protected Width|string|null $maxWidth = Width::FiveExtraLarge;

    public int $profileUserId;

    public string $category = UserActivityLogService::CATEGORY_ALL;

    public string $search = '';

    public int $limit = 25;

    public static function getRoutePath(Panel $panel): string
    {
        return '/'.static::getSlug($panel).'/{user}';
    }

    public function mount(int|string $user): void
    {
        $resolved = User::query()->with('roles')->findOrFail($user);
        $this->profileUserId = (int) $resolved->getKey();
    }

    public function getTitle(): string|Htmlable
    {
        return __('Profil Pengguna');
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    public function setCategory(string $category): void
    {
        if (array_key_exists($category, UserActivityLogService::categories())) {
            $this->category = $category;
            $this->limit = 25;
        }
    }

    public function updatedSearch(): void
    {
        $this->limit = 25;
    }

    public function loadMore(): void
    {
        $this->limit += 25;
    }

    public function getProfileUserProperty(): User
    {
        return User::query()->with('roles')->findOrFail($this->profileUserId);
    }

    /**
     * @return array{total: int, receiving_grs: int, warehouse_mir: int, invoice_monitoring: int}
     */
    public function getSummaryCountsProperty(): array
    {
        return app(UserActivityLogService::class)->getSummaryCounts($this->profileUser);
    }

    /**
     * @return array{
     *     items: list<array<string, mixed>>,
     *     has_more: bool,
     *     total_fetched: int
     * }
     */
    public function getActivityFeedProperty(): array
    {
        return app(UserActivityLogService::class)->getActivities(
            user: $this->profileUser,
            category: $this->category,
            search: $this->search,
            limit: $this->limit,
        );
    }

    /**
     * @return array<string, string>
     */
    public function getCategoryOptionsProperty(): array
    {
        return UserActivityLogService::categories();
    }
}
