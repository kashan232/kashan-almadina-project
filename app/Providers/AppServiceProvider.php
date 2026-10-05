<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->email === 'admin@admin.com' ? true : null;
        });

        \Illuminate\Support\Facades\Blade::directive('num', function ($expression) {
            return "<?php echo fmt_num($expression); ?>";
        });

        \Illuminate\Support\Facades\Blade::directive('money', function ($expression) {
            return "<?php echo fmt_money($expression); ?>";
        });

        \Illuminate\Support\Facades\View::composer('admin_panel.layout.header', function ($view) {
            $user = auth()->user();
            $isAdmin = $user && ($user->email === 'admin@admin.com' || ($user->roles && $user->roles->contains('name', 'Admin')) || $user->hasRole('Admin'));
            $pendingCount = 0;
            if ($isAdmin) {
                $pendingCount = \App\Models\StockTransfer::where('status', 'Pending Approval')->count();
            }
            $view->with('pendingStockTransferCount', $pendingCount);
            $view->with('isAdminUser', $isAdmin);
        });
    }
}
