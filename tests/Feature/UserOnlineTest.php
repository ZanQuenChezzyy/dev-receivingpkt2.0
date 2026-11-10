<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

test('it returns true when user is the authenticated user', function () {
    $user = User::factory()->make(['id' => 999]);

    Auth::login($user);

    expect($user->isOnline())->toBeTrue();
});

test('it returns false when user is not online and has no session', function () {
    $user = User::factory()->make(['id' => 888]);

    Auth::logout();

    expect($user->isOnline())->toBeFalse();
});

test('it returns true when user is marked online in cache', function () {
    $user = User::factory()->make(['id' => 777]);

    Auth::logout();
    Cache::put('user-is-online-777', true, 60);

    expect($user->isOnline())->toBeTrue();
});
