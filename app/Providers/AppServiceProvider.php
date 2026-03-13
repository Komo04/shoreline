<?php

namespace App\Providers;

use App\Models\ProdukVarian;
use App\Models\Keranjang;
use App\Observers\ProdukVarianObserver;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('production')) {
            /** @var ConfigRepository $config */
            $config = $this->app->make('config');

            $config->set('sentry.sample_rate', (float) env('SENTRY_SAMPLE_RATE', 0.5));
            $config->set('sentry.traces_sample_rate', (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.02));

            $config->set('sentry.breadcrumbs.cache', filter_var(env('SENTRY_BREADCRUMBS_CACHE_ENABLED', false), FILTER_VALIDATE_BOOL));
            $config->set('sentry.breadcrumbs.sql_queries', filter_var(env('SENTRY_BREADCRUMBS_SQL_QUERIES_ENABLED', false), FILTER_VALIDATE_BOOL));
            $config->set('sentry.breadcrumbs.http_client_requests', filter_var(env('SENTRY_BREADCRUMBS_HTTP_CLIENT_REQUESTS_ENABLED', false), FILTER_VALIDATE_BOOL));

            $config->set('sentry.tracing.queue_job_transactions', filter_var(env('SENTRY_TRACE_QUEUE_ENABLED', false), FILTER_VALIDATE_BOOL));
            $config->set('sentry.tracing.queue_jobs', filter_var(env('SENTRY_TRACE_QUEUE_JOBS_ENABLED', false), FILTER_VALIDATE_BOOL));
            $config->set('sentry.tracing.sql_queries', filter_var(env('SENTRY_TRACE_SQL_QUERIES_ENABLED', false), FILTER_VALIDATE_BOOL));
            $config->set('sentry.tracing.views', filter_var(env('SENTRY_TRACE_VIEWS_ENABLED', false), FILTER_VALIDATE_BOOL));
            $config->set('sentry.tracing.http_client_requests', filter_var(env('SENTRY_TRACE_HTTP_CLIENT_REQUESTS_ENABLED', false), FILTER_VALIDATE_BOOL));
            $config->set('sentry.tracing.cache', filter_var(env('SENTRY_TRACE_CACHE_ENABLED', false), FILTER_VALIDATE_BOOL));
            $config->set('sentry.tracing.notifications', filter_var(env('SENTRY_TRACE_NOTIFICATIONS_ENABLED', false), FILTER_VALIDATE_BOOL));
            $config->set('sentry.tracing.continue_after_response', filter_var(env('SENTRY_TRACE_CONTINUE_AFTER_RESPONSE', false), FILTER_VALIDATE_BOOL));
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ProdukVarian::observe(ProdukVarianObserver::class);

        // Cart count hanya dibutuhkan di navbar web, jangan jalankan query ini untuk semua view.
        View::composer('components.web.navbar', function ($view): void {
            $userId = Auth::id();

            if (! $userId) {
                $view->with('cartCount', 0);
                return;
            }

            $cartCount = Cache::remember(
                "cart_count:user:{$userId}",
                now()->addSeconds(30),
                fn () => (int) Keranjang::where('user_id', $userId)->sum('jumlah_produk')
            );

            $view->with('cartCount', $cartCount);
        });
    }
}
