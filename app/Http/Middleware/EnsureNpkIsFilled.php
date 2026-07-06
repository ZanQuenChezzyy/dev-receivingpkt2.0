<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNpkIsFilled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Cek apakah user sudah login dan NPK-nya kosong
        if ($user && empty($user->npk)) {
            // Jangan redirect jika sedang berada di halaman profil atau proses logout
            if (!$request->routeIs('filament.admin.auth.profile') && !$request->routeIs('filament.admin.auth.logout')) {
                // Kecualikan request Livewire agar profil bisa disimpan
                if (!$request->is('livewire/*')) {
                    Notification::make()
                        ->warning()
                        ->title('Perhatian')
                        ->body('Harap Masukkan NPK sebelum melanjutkan.')
                        ->send();

                    return redirect(filament()->getProfileUrl());
                }
            }
        }

        return $next($request);
    }
}
