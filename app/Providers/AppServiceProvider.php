<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\User;
use App\Observers\OrderObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Contracts\PaymentGatewayInterface::class,
            \App\Services\Payment\Gateways\StripeGateway::class
        );

        $this->app->bind(
            \App\Contracts\PricingServiceInterface::class,
            \App\Services\PricingService::class
        );

        $this->app->bind(
            \App\Contracts\CartServiceInterface::class,
            \App\Services\CartService::class
        );

        $this->app->bind(
            \App\Contracts\FavoriteServiceInterface::class,
            \App\Services\FavoriteService::class
        );

        $this->app->bind(
            \App\Contracts\TangkiServiceInterface::class,
            \App\Services\TangkiService::class
        );

        $this->app->bind(
            \App\Contracts\CheckoutServiceInterface::class,
            \App\Services\CheckoutService::class
        );

        $this->app->singleton(\App\Services\Payment\PaymentHandlerFactory::class, function ($app) {
            return new \App\Services\Payment\PaymentHandlerFactory([
                'refill' => \App\Services\Payment\RefillHandler::class,
                'checkout' => \App\Services\Payment\StripeCheckoutHandler::class,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);

        RateLimiter::for('admin-login', function (Request $request) {
            $email = Str::lower((string) $request->input('email'));

            return Limit::perMinute(5)->by($email.'|'.$request->ip());
        });

        Blade::if('adminCan', function (string $permission): bool {
            $admin = Auth::guard('admin')->user();

            return $admin && $admin->canPerform($permission);
        });

        View::composer('layouts.navigation', function ($view) {
            if (! Auth::check()) {
                return;
            }

            $user = Auth::user();

            // Only regular users have cart items. Admins do not.
            $isRegularUser = $user instanceof User;
            $cartCount = $isRegularUser ? $user->cartItems()->sum('quantity') : 0;

            // Notifications might exist for both, but let's be safe.
            $allNotifications = method_exists($user, 'notifications')
                ? $user->notifications()->latest()->limit(10)->get()
                : collect();

            $unreadCount = method_exists($user, 'unreadNotifications')
                ? $user->unreadNotifications()->count()
                : 0;

            $view->with([
                'cartCount' => $cartCount,
                'navbarNotifications' => $allNotifications,
                'navbarUnreadCount' => $unreadCount,
            ]);
        });
    }
}
