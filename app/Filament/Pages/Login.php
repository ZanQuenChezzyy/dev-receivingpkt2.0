<?php

namespace App\Filament\Pages;

use App\Models\User;
use Caresome\FilamentAuthDesigner\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('email')
                    ->label('Email')
                    ->placeholder('Masukkan Email')
                    ->email()
                    ->required()
                    ->autocomplete()
                    ->autofocus()
                    ->extraInputAttributes(['tabindex' => 1]),

                TextInput::make('password')
                    ->label('Kata Sandi')
                    ->placeholder('Masukkan Kata Sandi')
                    ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()" tabindex="3"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
                    ->password()
                    ->revealable(filament()->arePasswordsRevealable())
                    ->autocomplete('current-password')
                    ->required()
                    ->extraInputAttributes(['tabindex' => 2]),
            ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return [
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => true,
        ];
    }

    protected function throwFailureValidationException(): never
    {
        $data = $this->form->getState();
        $user = User::where('email', $data['email'] ?? null)->first();

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([
                'data.email' => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.',
            ]);
        }

        parent::throwFailureValidationException();
    }
}
