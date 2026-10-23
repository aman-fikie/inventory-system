<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\PosSession;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view inventory'); // Or view sales permission

        $query = Sale::with(['customer', 'user', 'posSession.register.store', 'items.productVariant', 'payments']);

        if ($request->has('pos_session_id')) {
            $query->where('pos_session_id', $request->pos_session_id);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        return response()->json([
            'message' => 'Sales retrieved successfully',
            'data' => $query->get(),
        ], 200);
    }

    public function store(Request $request)
    {
        $user = app(AuthController::class)->authenticatedUser($request);
        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can('adjust inventory'), 403, 'You do not have permission to make sales.');

        $validated = $request->validate([
            'pos_session_id' => 'required|integer|exists:pos_sessions,id',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'invoice_number' => 'required|string|max:50|unique:sales,invoice_number',
            'discount_total' => 'nullable|numeric|min:0',
            'tax_total' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',

            // Line items
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',

            // Payments
            'payments' => 'required|array|min:1',
            'payments.*.payment_method' => 'required|string|in:cash,card,mobile_money,store_credit',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.reference_number' => 'nullable|string|max:100',
        ]);

        $sale = DB::transaction(function () use ($validated, $user) {
            $session = PosSession::with('register')->findOrFail($validated['pos_session_id']);

            if ($session->status !== 'open') {
                abort(422, 'Cannot make a sale on a closed POS session.');
            }

            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $itemSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);
                $subtotal += $itemSubtotal;
            }

            $discountTotal = $validated['discount_total'] ?? 0.00;
            $taxTotal = $validated['tax_total'] ?? 0.00;
            $grandTotal = ($subtotal - $discountTotal) + $taxTotal;

            $totalPaid = collect($validated['payments'])->sum('amount');
            if ($totalPaid < $grandTotal) {
                abort(422, 'Total payment amount is less than the grand total.');
            }

            $changeDue = $totalPaid - $grandTotal;

            // Create Sale Header
            $sale = Sale::create([
                'pos_session_id' => $session->id,
                'customer_id' => $validated['customer_id'] ?? null,
                'user_id' => $user->id,
                'invoice_number' => $validated['invoice_number'],
                'status' => 'completed',
                'subtotal' => $subtotal,
                'discount_total' => $discountTotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'amount_paid' => $totalPaid,
                'change_due' => $changeDue,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create Line Items & Deduct Stock from the store's default location
            $storeId = $session->register->store_id;

            foreach ($validated['items'] as $item) {
                $itemSubtotal = ($item['quantity'] * $item['unit_price']) - ($item['discount'] ?? 0);

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? 0.00,
                    'subtotal' => $itemSubtotal,
                ]);

                // Deduct stock balance in the store location
                $stockBalance = StockBalance::whereHas('stockLocation', function ($q) use ($storeId) {
                    $q->where('store_id', $storeId);
                })->where('product_variant_id', $item['product_variant_id'])->first();

                if ($stockBalance) {
                    $stockBalance->decrement('quantity_on_hand', $item['quantity']);
                }
            }

            // Record Payments & Track Cash Sales for session drawer reconciliation
            $cashPaidTotal = 0;
            foreach ($validated['payments'] as $payment) {
                SalePayment::create([
                    'sale_id' => $sale->id,
                    'payment_method' => $payment['payment_method'],
                    'amount' => $payment['amount'],
                    'reference_number' => $payment['reference_number'] ?? null,
                ]);

                if ($payment['payment_method'] === 'cash') {
                    // Exclude the change given back from the cash drawer total increment if needed, 
                    // or increment by the net cash received
                    $cashPaidTotal += ($payment['amount'] - ($payment['payment_method'] === 'cash' ? $changeDue : 0));
                }
            }

            // Update session cash sales total
            if ($cashPaidTotal > 0) {
                $session->increment('cash_sales_total', max(0, $cashPaidTotal));
            }

            return $sale;
        });

        return response()->json([
            'message' => 'Sale completed successfully',
            'data' => $sale->load(['customer', 'user', 'items.productVariant', 'payments']),
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view inventory');

        $sale = Sale::with(['customer', 'user', 'posSession.register.store', 'items.productVariant', 'payments'])->find($id);

        if (! $sale) {
            return response()->json(['message' => 'Sale not found'], 404);
        }

        return response()->json([
            'message' => 'Sale retrieved successfully',
            'data' => $sale,
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
