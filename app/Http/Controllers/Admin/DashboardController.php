<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard for Staff.
     * Focus: Pending orders and quick actions.
     */
    public function index()
    {
        if (auth()->guard('admin')->user()->isOwner()) {
            return redirect()->route('admin.owner.dashboard');
        }

        $pendingOrders = Order::where('status', 'pending')
            ->with(['user', 'items.product'])
            ->oldest()
            ->get();

        return view('admin.dashboard', compact('pendingOrders'));
    }

    /**
     * Show the owner dashboard.
     * Focus: Sales analytics and revenue.
     */
    public function ownerDashboard()
    {
        if (!auth()->guard('admin')->user()->isOwner()) {
            return redirect()->route('admin.dashboard')->with('error', 'Unauthorized access.');
        }

        // Stats
        $totalRevenue = Order::where('status', 'completed')->sum('final_amount');
        $revenueToday = Order::where('status', 'completed')
            ->whereDate('updated_at', Carbon::today())
            ->sum('final_amount');
        $revenueThisMonth = Order::where('status', 'completed')
            ->whereMonth('updated_at', Carbon::now()->month)
            ->sum('final_amount');

        $totalOrders = Order::count();
        $totalUsers = User::count();

        // Chart Data (Last 7 Days)
        $salesData = Order::where('status', 'completed')
            ->where('updated_at', '>=', Carbon::now()->subDays(7))
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('SUM(final_amount) as total'))
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        // Top Products
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.name', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'DESC')
            ->limit(5)
            ->get();

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