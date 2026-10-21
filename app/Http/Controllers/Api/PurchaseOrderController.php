<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view purchases');

        $query = PurchaseOrder::with(['supplier', 'store', 'items.productVariant']);

        // Optional filters
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'message' => 'Purchase orders retrieved successfully',
            'data' => $query->get(),
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create purchases');

        $validated = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'store_id' => 'required|integer|exists:stores,id',
            'po_number' => 'required|string|max:50|unique:purchase_orders,po_number',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'discount_total' => 'nullable|numeric|min:0',
            'tax_total' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',

            // Line items validation
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.001',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        // Wrap in a database transaction to ensure data integrity
        $purchaseOrder = DB::transaction(function () use ($validated) {
            $subtotal = 0;

            // Calculate items subtotals first
            foreach ($validated['items'] as $itemData) {
                $subtotal += $itemData['quantity_ordered'] * $itemData['unit_cost'];
            }

            $discountTotal = $validated['discount_total'] ?? 0.00;
            $taxTotal = $validated['tax_total'] ?? 0.00;
            $grandTotal = ($subtotal - $discountTotal) + $taxTotal;

            // Create the main Purchase Order header
            $po = PurchaseOrder::create([
                'supplier_id' => $validated['supplier_id'],
                'store_id' => $validated['store_id'],
                'po_number' => $validated['po_number'],
                'status' => 'pending',
                'order_date' => $validated['order_date'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'discount_total' => $discountTotal,
                'grand_total' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create each line item
            foreach ($validated['items'] as $itemData) {
                $itemSubtotal = $itemData['quantity_ordered'] * $itemData['unit_cost'];

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'product_variant_id' => $itemData['product_variant_id'],
                    'quantity_ordered' => $itemData['quantity_ordered'],
                    'quantity_received' => 0.000,
                    'unit_cost' => $itemData['unit_cost'],
                    'subtotal' => $itemSubtotal,
                ]);
            }

            return $po;
        });

        return response()->json([
            'message' => 'Purchase order created successfully',
            'data' => $purchaseOrder->load(['supplier', 'store', 'items.productVariant']),
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view purchases');

        $purchaseOrder = PurchaseOrder::with(['supplier', 'store', 'items.productVariant', 'goodsReceipts'])->find($id);

        if (! $purchaseOrder) {
            return response()->json(['message' => 'Purchase order not found'], 404);
        }

        return response()->json([
            'message' => 'Purchase order retrieved successfully',
            'data' => $purchaseOrder,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAction($request, 'create purchases');

        $purchaseOrder = PurchaseOrder::find($id);

        if (! $purchaseOrder) {
            return response()->json(['message' => 'Purchase order not found'], 404);
        }

        // Only allow updates if the PO is still pending
        if ($purchaseOrder->status !== 'pending') {
            return response()->json([
                'message' => 'Only purchase orders with pending status can be modified.'
            ], 422);
        }

        $validated = $request->validate([
            'expected_delivery_date' => 'sometimes|nullable|date',
            'status' => 'sometimes|string|in:pending,approved,cancelled',
            'notes' => 'sometimes|nullable|string',
        ]);

        $purchaseOrder->update($validated);

        return response()->json([
            'message' => 'Purchase order updated successfully',
            'data' => $purchaseOrder->fresh(['supplier', 'store', 'items.productVariant']),
        ], 200);
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizeAction($request, 'approve purchases'); // Restricted to managers/admins

        $purchaseOrder = PurchaseOrder::find($id);

        if (! $purchaseOrder) {
            return response()->json(['message' => 'Purchase order not found'], 404);
        }

        if ($purchaseOrder->status === 'received') {
            return response()->json([
                'message' => 'Cannot delete a purchase order that has already been received.'
            ], 422);
        }

        $purchaseOrder->delete();

        return response()->json([
            'message' => 'Purchase order deleted successfully',
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
