<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\Sale;
use App\Models\StockBalance;
use App\Models\PosSession;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // 1. Sales Summary & Revenue Report
    public function salesReport(Request $request)
    {
        $this->authorizeAction($request, 'view reports');

        $query = Sale::with(['user', 'posSession.register.store', 'customer']);

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        if ($request->has('store_id')) {
            $storeId = $request->store_id;
            $query->whereHas('posSession.register', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });
        }

        $sales = $query->get();

        $summary = [
            'total_sales_count' => $sales->count(),
            'total_revenue' => $sales->sum('grand_total'),
            'total_discount' => $sales->sum('discount_total'),
            'total_tax' => $sales->sum('tax_total'),
            'sales' => $sales,
        ];

        return response()->json([
            'message' => 'Sales report retrieved successfully',
            'data' => $summary,
        ], 200);
    }

    // 2. Low Stock Inventory Alert Report
    public function lowStockReport(Request $request)
    {
        $this->authorizeAction($request, 'view reports');

        $query = StockBalance::with(['stockLocation.store', 'productVariant.product'])
            ->whereColumn('quantity_on_hand', '<=', 'reorder_level');

        if ($request->has('store_id')) {
            $storeId = $request->store_id;
            $query->whereHas('stockLocation', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });
        }

        return response()->json([
            'message' => 'Low stock report retrieved successfully',
            'data' => $query->get(),
        ], 200);
    }

    // 3. Cash Drawer / Shift Reconciliation Report
    public function cashReconciliationReport(Request $request)
    {
        $this->authorizeAction($request, 'view reports');

        $query = PosSession::with(['register.store', 'user'])
            ->where('status', 'closed');

        if ($request->has('register_id')) {
            $query->where('register_id', $request->register_id);
        }

        $sessions = $query->get()->map(function ($session) {
            $discrepancy = $session->closing_balance - $session->expected_cash_balance;
            return [
                'session_id' => $session->id,
                'register' => $session->register->name ?? 'N/A',
                'store' => $session->register->store->name ?? 'N/A',
                'cashier' => $session->user->name ?? 'N/A',
                'opening_balance' => $session->opening_balance,
                'cash_sales_total' => $session->cash_sales_total,
                'expected_cash_balance' => $session->expected_cash_balance,
                'closing_balance' => $session->closing_balance,
                'discrepancy' => $discrepancy, // Positive = overage, Negative = shortage
                'opened_at' => $session->opened_at,
                'closed_at' => $session->closed_at,
                'notes' => $session->notes,
            ];
        });

        return response()->json([
            'message' => 'Cash drawer reconciliation report retrieved successfully',
            'data' => $sessions,
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
