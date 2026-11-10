<?php

use App\Models\User;

test('user model has is_active in fillable and boolean cast', function () {
    $user = new User;

    expect($user->getFillable())->toContain('is_active')
        ->and($user->getCasts()['is_active'])->toBe('boolean');
});

test('canAccessPanel returns false when is_active is false', function () {
    $user = new User;
    $user->is_active = false;
    $panel = filament()->getPanel('admin');

    expect($user->canAccessPanel($panel))->toBeFalse();
});

test('active scope adds where is_active equals true condition', function () {
    $query = User::query()->active();
    $sql = $query->toSql();

    expect($sql)->toContain('is_active')
        ->and($query->getBindings())->toContain(true);
});
