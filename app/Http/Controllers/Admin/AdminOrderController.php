<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OrdersExport;
use App\Http\Controllers\Controller;
use App\Exceptions\OrderException;
use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\AuditLogService;
use App\Services\OrderService;
use Maatwebsite\Excel\Facades\Excel;

class AdminOrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly AuditLogService $auditLogService
    )
    {
    }

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
        try {
            $oldStatus = $order->status;
            $updatedOrder = $this->orderService->complete($order);
            $this->auditLogService->record('order.status.update', $updatedOrder, [
                'status' => $oldStatus,
            ], [
                'status' => $updatedOrder->status,
            ]);

            return back()->with('success', 'Order marked as completed.');
        } catch (OrderException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function advanceStatus(Order $order)
    {
        try {
            $oldStatus = $order->status;
            $updatedOrder = $this->orderService->advanceStatus($order);
            $this->auditLogService->record('order.status.update', $updatedOrder, [
                'status' => $oldStatus,
            ], [
                'status' => $updatedOrder->status,
            ]);

            return back()->with('success', 'Order status updated.');
        } catch (OrderException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function completeByPickupCode(Request $request)
    {
        $validated = $request->validate([
            'pickup_code' => ['required', 'string', 'max:12'],
        ]);

        try {
            $order = $this->orderService->completeByPickupCode($validated['pickup_code']);
            $this->auditLogService->record('order.pickup.complete', $order, null, [
                'status' => $order->status,
                'pickup_code' => $validated['pickup_code'],
            ]);

            return redirect()->route('admin.orders.show', $order)
                ->with('success', 'Pickup code verified. Order marked as completed.');
        } catch (OrderException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function exportPage()
    {
        return view('admin.orders.export');
    }

    public function refunds(Request $request)
    {
        $query = Transaction::where('type', 'refund')
            ->with(['user', 'bill']);

        if ($request->filled('search_id')) {
            $query->where('bill_id', 'LIKE', "%{$request->search_id}%");
        }

        $refunds = $query->latest()->paginate(15)->withQueryString();

        return view('admin.orders.refunds', compact('refunds'));
    }

    public function export(Request $request) 
    {
        $fileName = 'CoffeePlus_Report_' . now()->format('Ymd') . '.xlsx';
        return Excel::download(new OrdersExport($request), $fileName);
    }
}
