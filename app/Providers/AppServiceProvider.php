<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentView;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
    public function panel(\Filament\Panel $panel): \Filament\Panel
{
    FilamentView::registerRenderHook(
        'panels::head.end',
        fn () => \Illuminate\Support\Facades\Vite::withEntryPoints(['resources/js/app.js'])->toHtml()
    );

    return $panel;
}
}
