<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Nama hari/bulan (isoFormat, translatedFormat) mengikuti locale aplikasi
        Carbon::setLocale(config('app.locale'));

        // @rupiah($angka)  ->  Rp 1.250.000
        Blade::directive('rupiah', function ($expression) {
            return "<?php echo 'Rp ' . number_format((float) ({$expression}), 0, ',', '.'); ?>";
        });
    }
}
