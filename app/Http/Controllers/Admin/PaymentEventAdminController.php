<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentEvent;
use Illuminate\Http\Request;

class PaymentEventAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = PaymentEvent::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('session_id')) {
            $query->where('session_id', 'like', '%' . $request->input('session_id') . '%');
        }

        $paymentEvents = $query->paginate(20)->withQueryString();

        return view('admin.payment-events.index', compact('paymentEvents'));
    }

    public function show(PaymentEvent $paymentEvent)
    {
        $paymentEvent->load('user');

        return view('admin.payment-events.show', compact('paymentEvent'));
    }
}
