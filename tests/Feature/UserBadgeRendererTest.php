<?php

use App\Models\User;
use App\Models\WarehouseDeliveryBatch;
use App\Services\UserBadgeRenderer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('users')) {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('npk')->nullable();
            $table->string('avatar_url')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_delivery_batches')) {
        Schema::create('warehouse_delivery_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_title');
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('total_items')->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('uploaded_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_transmittals')) {
        Schema::create('warehouse_transmittals', function (Blueprint $table) {
            $table->id();
            $table->string('transmittal_no')->unique();
            $table->foreignId('warehouse_destination_id')->nullable();
            $table->date('tanggal');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('warehouse_destinations')) {
        Schema::create('warehouse_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->foreignId('pic_id')->nullable();
            $table->timestamps();
        });
    }
});

test('UserBadgeRenderer renders avatar, name, and NPK correctly for active user', function () {
    $user = User::create([
        'name' => 'Ahmad Fauzi',
        'email' => 'ahmad@pupuk-kaltim.com',
        'npk' => 'PKT-99881',
        'password' => bcrypt('password'),
    ]);

    $badgeHtml = UserBadgeRenderer::render($user);

    expect($badgeHtml)
        ->toContain('Ahmad Fauzi')
        ->toContain('PKT-99881')
        ->toContain('ui-avatars.com')
        ->toContain('rounded-2xl')
        ->toContain('bg-amber-500/10');
});

test('UserBadgeRenderer renders placeholder when user is null', function () {
    $defaultBadge = UserBadgeRenderer::render(null);
    expect($defaultBadge)
        ->toContain('Belum Ada')
        ->toContain('text-slate-400');

    $customBadge = UserBadgeRenderer::render(null, 'Belum Diupload');
    expect($customBadge)
        ->toContain('Belum Diupload');
});

test('UserBadgeRenderer searchQuery filters query by user name or NPK', function () {
    $user1 = User::create([
        'name' => 'Budi Operator',
        'email' => 'budi.op@pupuk-kaltim.com',
        'npk' => 'PKT-11223',
        'password' => bcrypt('password'),
    ]);

    $user2 = User::create([
        'name' => 'Siti Admin',
        'email' => 'siti@pupuk-kaltim.com',
        'npk' => 'PKT-44556',
        'password' => bcrypt('password'),
    ]);

    $batch1 = WarehouseDeliveryBatch::create([
        'batch_title' => 'Batch Budi',
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-05',
        'uploaded_by' => $user1->id,
    ]);

    $batch2 = WarehouseDeliveryBatch::create([
        'batch_title' => 'Batch Siti',
        'start_date' => '2026-11-06',
        'end_date' => '2026-11-10',
        'uploaded_by' => $user2->id,
    ]);

    // Search by name
    $searchName = WarehouseDeliveryBatch::query();
    UserBadgeRenderer::searchQuery($searchName, 'uploader', 'Budi');
    expect($searchName->count())->toBe(1)
        ->and($searchName->first()->id)->toBe($batch1->id);

    // Search by NPK
    $searchNpk = WarehouseDeliveryBatch::query();
    UserBadgeRenderer::searchQuery($searchNpk, 'uploader', '44556');
    expect($searchNpk->count())->toBe(1)
        ->and($searchNpk->first()->id)->toBe($batch2->id);
});
