<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Pages\MemberProfile;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\UserBadgeRenderer;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected Width|string|null $maxWidth = Width::FiveExtraLarge;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_public_profile')
                ->label('Lihat Profil & Aktivitas')
                ->icon(Heroicon::OutlinedUserCircle)
                ->color('gray')
                ->url(fn (): string => MemberProfile::getUrl(['user' => $this->record->getKey()])),
            ViewAction::make(),
            DeleteAction::make()
                ->label(__('user.delete_user'))
                ->icon(Heroicon::Trash)
                ->visible(fn (): bool => $this->record->id !== Auth::id()),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var User $user */
        $user = $this->record;
        $data['developer_badge_style'] = $user->resolvedDeveloperBadgeStyle();

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var User $user */
        $user = $this->record;
        $isEditorDeveloper = (bool) Auth::user()?->hasRole('Developer');
        $chosen = (string) ($data['developer_badge_style'] ?? User::DEVELOPER_BADGE_NONE);

        if ($isEditorDeveloper) {
            if (User::isExclusiveBadgeStyle($chosen)) {
                $willHaveFullAccess = $user->hasRole('Developer')
                    || (bool) ($data['has_exclusive_badge'] ?? $user->hasFullExclusiveBadgeAccess());

                if (! $willHaveFullAccess && ! in_array($chosen, $user->claimedExclusiveBadges(), true)) {
                    $data['unlocked_exclusive_badges'] = array_values(array_unique([
                        ...$user->claimedExclusiveBadges(),
                        $chosen,
                    ]));
                }
            }
        } elseif (! $user->canUseBadgeStyle($chosen)) {
            $data['developer_badge_style'] = User::DEVELOPER_BADGE_NONE;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        UserBadgeRenderer::flushCache();
    }
}
