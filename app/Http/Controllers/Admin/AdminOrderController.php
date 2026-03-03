<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\OrderCompletedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class AdminOrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user', 'items.product')->latest()->paginate(10);
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['user', 'items.product']);
        return view('admin.orders.show', compact('order'));
    }

    public function complete(Order $order)
    {
        DB::transaction(function () use ($order) {
            $order->status = 'completed'; 
            $order->save();
            $order->user->notify(new OrderCompletedNotification($order));
        });
        return back()->with('success', 'Order marked as completed.');
    }

    public function exportPage()
    {
        return view('admin.orders.export');
    }

    public function export(Request $request) 
    {
        $fileName = 'CoffeePlus_Report_' . now()->format('Ymd') . '.xlsx';
        return Excel::download(new OrdersExport($request), $fileName);
    }
}
