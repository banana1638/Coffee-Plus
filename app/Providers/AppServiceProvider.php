<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.navigation', function ($view) {
            if (!Auth::check()) {
                return;
            }

            $user = Auth::user();

            // Only regular users have cart items. Admins do not.
            $isRegularUser = $user instanceof User;
            $cartCount = $isRegularUser ? $user->cartItems()->sum('quantity') : 0;

            // Notifications might exist for both, but let's be safe.
            $notificationsQuery = method_exists($user, 'unreadNotifications')
                ? $user->unreadNotifications()
                : null;

            $view->with([
                'cartCount' => $cartCount,
                'navbarNotifications' => $notificationsQuery
                    ? $notificationsQuery->latest()->limit(10)->get()
                    : collect(),
                'navbarUnreadCount' => $notificationsQuery
                    ? (clone $notificationsQuery)->count()
                    : 0,
            ]);
        });
    }
}
