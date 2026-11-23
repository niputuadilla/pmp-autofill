<?php

namespace App\Providers;

use App\Services\ApExtractor;
use App\Services\Dukcapil\DukcapilClient;
use App\Services\Dukcapil\FileDukcapilClient;
use App\Services\Dukcapil\HttpDukcapilClient;
use App\Services\ClaudeExtractor;
use App\Services\MockExtractor;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ApExtractor::class, fn () => config('pmp.extractor') === 'claude'
            ? new ClaudeExtractor()
            : new MockExtractor());

        $this->app->bind(DukcapilClient::class, fn () => config('pmp.dukcapil.driver') === 'http'
            ? new HttpDukcapilClient()
            : new FileDukcapilClient());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
