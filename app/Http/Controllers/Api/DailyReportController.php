<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        $orderCount = Order::whereBetween('created_at', [$from, $to])->count();
        $revenue = Order::whereBetween('created_at', [$from, $to])->sum('total_price');

        // === PRODUCTS SOLD ===
        $productsSold = Order::whereBetween('created_at', [$from, $to])
            ->withSum('items', 'qty')
            ->get()
            ->sum('items_sum_qty');

        $stockIn = StockMovement::where('type', 'in')
            ->whereBetween('created_at', [$from, $to])
            ->sum('qty');

        $stockOut = StockMovement::where('type', 'out')
            ->whereBetween('created_at', [$from, $to])
            ->sum('qty');

        return response()->json([
            'range' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
            'data' => [
                'total_orders'  => $orderCount,
                'revenue'       => $revenue,
                'products_sold' => $productsSold,
                'stock_in'      => $stockIn,
                'stock_out'     => $stockOut,
            ]
        ]);
    }

    /**
     * SALES CHART
     */
    public function chart(Request $request)
    {
        [$from, $to] = $this->resolveDateRange($request);

        $data = Order::selectRaw('DATE(created_at) as date, SUM(total_price) as revenue')
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json(['data' => $data]);
    }

    /**
     * SHOW (Pencegah Error jika dipanggil sebagai resource detail)
     */
    public function show(Request $request, string $id)
    {
        if ($id === 'chart') {
            return $this->chart($request);
        }

        return $this->index($request);
    }

    private function resolveDateRange(Request $request): array
    {
        if ($request->filled(['date_from', 'date_to'])) {
            return [
                Carbon::parse($request->date_from)->startOfDay(),
                Carbon::parse($request->date_to)->endOfDay(),
            ];
        }

        return match ($request->get('range', 'daily')) {
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            'yearly'  => [now()->startOfYear(), now()->endOfYear()],
            default   => [now()->startOfDay(), now()->endOfDay()],
        };
    }
}