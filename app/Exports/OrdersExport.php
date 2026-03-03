<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OrdersExport implements WithMultipleSheets
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function sheets(): array
    {
        $sheets = [];
        $type = $this->request->get('type');

        if ($type === 'year') {
            $year = $this->request->get('year', date('Y'));
            for ($month = 1; $month <= 12; $month++) {
                $sheets[] = new OrdersMonthlySheet($year, $month);
            }
        } elseif ($type === 'month') {
            $monthInput = $this->request->get('month', date('Y-m'));
            $year = date('Y', strtotime($monthInput));
            $month = date('m', strtotime($monthInput));
            $sheets[] = new OrdersMonthlySheet($year, $month);
        } else {
            // Default to 'date'
            $date = $this->request->get('date', date('Y-m-d'));
            $year = date('Y', strtotime($date));
            $month = date('m', strtotime($date));
            $sheets[] = new OrdersMonthlySheet($year, $month, $date);
        }

        return $sheets;
    }
}

// 内部类：具体的 Sheet 逻辑
class OrdersMonthlySheet implements FromCollection, WithHeadings, WithTitle
{
    private $year;
    private $month;
    private $date;

    public function __construct($year, $month, $date = null)
    {
        $this->year = $year;
        $this->month = $month;
        $this->date = $date;
    }

    public function collection()
    {
        $query = Order::query()->with('user');

        if ($this->date) {
            $query->whereDate('created_at', $this->date);
        } else {
            $query->whereYear('created_at', $this->year)
                ->whereMonth('created_at', $this->month);
        }

        return $query->get()->map(function ($order) {
            return [
                $order->bill_id,
                $order->user->name,
                $order->final_amount,
                $order->status,
                $order->created_at->format('Y-m-d H:i:s'),
            ];
        });
    }

    public function headings(): array
    {
        return ["Order ID", "Customer", "Amount (RM)", "Status", "Timestamp"];
    }

    public function title(): string
    {
        return $this->date ? $this->date : date('F', mktime(0, 0, 0, $this->month, 10));
    }
}
