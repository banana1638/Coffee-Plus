<?php

namespace App\Exports;

use App\Models\Order;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

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

// Sheet-specific export logic.
class OrdersMonthlySheet implements FromQuery, WithChunkReading, WithHeadings, WithMapping, WithTitle
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

    public function query()
    {
        $query = Order::query()
            ->select(['id', 'user_id', 'bill_id', 'final_amount', 'status', 'created_at'])
            ->with('user:id,name');

        if ($this->date) {
            $start = Carbon::parse($this->date)->startOfDay();
            $end = $start->copy()->addDay();

            $query->where('created_at', '>=', $start)
                ->where('created_at', '<', $end);
        } else {
            $start = Carbon::create((int) $this->year, (int) $this->month, 1)->startOfMonth();
            $end = $start->copy()->addMonth();

            $query->where('created_at', '>=', $start)
                ->where('created_at', '<', $end);
        }

        return $query->orderBy('created_at');
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function map($order): array
    {
        return [
            $order->bill_id,
            $order->user?->name,
            $order->final_amount,
            $order->status,
            $order->created_at->format('Y-m-d H:i:s'),
        ];
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
