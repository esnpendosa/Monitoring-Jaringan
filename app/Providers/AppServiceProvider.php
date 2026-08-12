<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasPermission($ability) ?: null;
        });

        config(['app.locale' => 'id']);
        \Carbon\Carbon::setLocale('id');
        setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'id');

        // Safe mock for global_units to prevent querying the non-existent units table
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $view->with('global_units', collect([]));
        });

        // Seed/Upgrade manual payment methods with granular bank/e-wallet options
        try {
            if (\Schema::hasTable('settings')) {
                $manualMethods = \App\Models\Setting::get('manual_payment_methods');
                if (!$manualMethods || $manualMethods === 'Transfer Bank,Cash') {
                    \App\Models\Setting::set(
                        'manual_payment_methods',
                        'Cash, Transfer BRI, Transfer BCA, Transfer BNI, Transfer Mandiri, Transfer DANA, Transfer OVO, Transfer ShopeePay, Transfer Gopay',
                        'payment'
                    );
                }
            }
        } catch (\Exception $e) {
            // Silent fallback during migration/seeding
        }
    }
}