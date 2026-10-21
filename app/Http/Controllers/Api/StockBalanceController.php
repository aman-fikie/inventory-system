<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\StockBalance;
use Illuminate\Http\Request;

class StockBalanceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view inventory');

        $query = StockBalance::with([
            'stockLocation.store',
            'productVariant.product',
        ]);

        if ($request->has('stock_location_id')) {
            $query->where('stock_location_id', $request->stock_location_id);
        }

        if ($request->has('product_variant_id')) {
            $query->where('product_variant_id', $request->product_variant_id);
        }

        return response()->json([
            'message' => 'Stock balances retrieved successfully',
            'data' => $query->get(),
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'adjust inventory');

        $validated = $request->validate([
            'stock_location_id' => 'required|integer|exists:stock_locations,id',
            'product_variant_id' => 'required|integer|exists:product_variants,id',
            'quantity_on_hand' => 'required|numeric|min:0',
            'quantity_reserved' => 'sometimes|numeric|min:0',
            'reorder_level' => 'sometimes|numeric|min:0',
        ]);

        $balance = StockBalance::where('stock_location_id', $validated['stock_location_id'])
            ->where('product_variant_id', $validated['product_variant_id'])
            ->first();

        if ($balance) {
            return response()->json([
                'message' => 'Stock balance already exists for this location and variant. Use update/adjust instead.',
            ], 422);
        }

        $balance = StockBalance::create($validated);

        return response()->json([
            'message' => 'Stock balance initialized successfully',
            'data' => $balance->load(['stockLocation', 'productVariant']),
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view inventory');

        $balance = StockBalance::with([
            'stockLocation.store',
            'productVariant.product',
        ])->find($id);

        if (! $balance) {
            return response()->json(['message' => 'Stock balance record not found'], 404);
        }

        return response()->json([
            'message' => 'Stock balance retrieved successfully',
            'data' => $balance,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAction($request, 'adjust inventory');

        $balance = StockBalance::find($id);

        if (! $balance) {
            return response()->json(['message' => 'Stock balance record not found'], 404);
        }

        $validated = $request->validate([
            'quantity_on_hand' => 'sometimes|required|numeric|min:0',
            'quantity_reserved' => 'sometimes|numeric|min:0',
            'reorder_level' => 'sometimes|numeric|min:0',
        ]);

        $balance->update($validated);

        return response()->json([
            'message' => 'Stock balance updated successfully',
            'data' => $balance->fresh(['stockLocation', 'productVariant']),
        ], 200);
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizeAction($request, 'adjust inventory');

        $balance = StockBalance::find($id);

        if (! $balance) {
            return response()->json(['message' => 'Stock balance record not found'], 404);
        }

        $balance->delete();

        return response()->json([
            'message' => 'Stock balance record deleted successfully',
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
