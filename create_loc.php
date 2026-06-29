<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$loc = \App\Models\LocationReceiving::firstOrCreate(['name' => 'BARANG BELUM DATANG']);
echo $loc->id;
