<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard for Staff.
     * Focus: Pending orders and quick actions.
     */
    public function index()
    {
        if (auth()->guard('admin')->user()->canPerform('report.view')) {
            return redirect()->route('admin.owner.dashboard');
        }

        $pendingOrders = Order::where('status', 'pending')
            ->with(['user', 'items.product'])
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.dashboard', compact('pendingOrders'));
    }

    /**
     * Show the owner dashboard.
     * Focus: Sales analytics and revenue.
     */
    public function ownerDashboard()
    {
        if (! auth()->guard('admin')->user()->canPerform('report.view')) {
            return redirect()->route('admin.dashboard')->with('error', 'Unauthorized access.');
        }

        $todayStart = Carbon::today();
        $tomorrowStart = $todayStart->copy()->addDay();
        $monthStart = Carbon::now()->startOfMonth();
        $nextMonthStart = $monthStart->copy()->addMonth();

        $analytics = Cache::remember('admin_owner_dashboard_analytics', 60, function () use ($todayStart, $tomorrowStart, $monthStart, $nextMonthStart) {
            $totalRevenueCents = (int) Order::where('status', 'completed')->sum('final_amount_cents');
            $revenueTodayCents = (int) Order::where('status', 'completed')
                ->where('updated_at', '>=', $todayStart)
                ->where('updated_at', '<', $tomorrowStart)
                ->sum('final_amount_cents');
            $revenueThisMonthCents = (int) Order::where('status', 'completed')
                ->where('updated_at', '>=', $monthStart)
                ->where('updated_at', '<', $nextMonthStart)
                ->sum('final_amount_cents');

            return [
                'totalRevenue' => $totalRevenueCents / 100,
                'revenueToday' => $revenueTodayCents / 100,
                'revenueThisMonth' => $revenueThisMonthCents / 100,
                'totalOrders' => Order::count(),
                'totalUsers' => User::count(),
                'salesData' => Order::where('status', 'completed')
                    ->where('updated_at', '>=', Carbon::now()->subDays(7))
                    ->select(DB::raw('DATE(updated_at) as date'), DB::raw('SUM(final_amount_cents) / 100 as total'))
                    ->groupBy('date')
                    ->orderBy('date', 'ASC')
                    ->get(),
                'topProducts' => DB::table('order_items')
                    ->join('products', 'order_items.product_id', '=', 'products.id')
                    ->select('products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
                    ->groupBy('products.id', 'products.name')
                    ->orderBy('total_sold', 'DESC')
                    ->limit(5)
                    ->get(),
            ];
        });

        extract($analytics);

        return view('admin.owner_dashboard', compact(
            'totalRevenue',
            'revenueToday',
            'revenueThisMonth',
            'totalOrders',
            'totalUsers',
            'salesData',
            'topProducts'
        ));
    }
}
