<?php

use App\Models\LocationReceiving;
use Illuminate\Contracts\Console\Kernel;

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$loc = LocationReceiving::firstOrCreate(['name' => 'BARANG BELUM DATANG']);
echo $loc->id;
