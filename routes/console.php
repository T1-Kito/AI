<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Services\TozPieProductSync;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('tozpie:sync-products', function (TozPieProductSync $sync) {
    $count = $sync->sync();

    $this->info("Da dong bo {$count} san pham tu TozPie.");
})->purpose('Sync products, stock and safe prices from TozPie');
