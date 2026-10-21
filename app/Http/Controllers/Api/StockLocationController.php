<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\StockLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StockLocationController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view stores');

        $query = StockLocation::with('store');

        // Optional filter by store
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        return response()->json([
            'message' => 'Stock locations retrieved successfully',
            'data' => $query->get(),
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create stores');

        $validated = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'code' => 'required|string|max:30|unique:stock_locations,code',
            'name' => 'required|string|max:120',
            'location_type' => 'required|string|in:shop_floor,warehouse,storage_room',
            'is_active' => 'sometimes|boolean',
        ]);

        $stockLocation = StockLocation::create($validated);

        return response()->json([
            'message' => 'Stock location created successfully',
            'data' => $stockLocation->load('store'),
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view stores');

        $stockLocation = StockLocation::with(['store', 'stockBalances.productVariant'])->find($id);

        if (! $stockLocation) {
            return response()->json(['message' => 'Stock location not found'], 404);
        }

        return response()->json([
            'message' => 'Stock location retrieved successfully',
            'data' => $stockLocation,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAction($request, 'update stores');

        $stockLocation = StockLocation::find($id);

        if (! $stockLocation) {
            return response()->json(['message' => 'Stock location not found'], 404);
        }

        $validated = $request->validate([
            'store_id' => 'sometimes|required|integer|exists:stores,id',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('stock_locations', 'code')->ignore($stockLocation->id),
            ],
            'name' => 'sometimes|required|string|max:120',
            'location_type' => 'sometimes|required|string|in:shop_floor,warehouse,storage_room',
            'is_active' => 'sometimes|boolean',
        ]);

        $stockLocation->update($validated);

        return response()->json([
            'message' => 'Stock location updated successfully',
            'data' => $stockLocation->fresh(['store']),
        ], 200);
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizeAction($request, 'update stores');

        $stockLocation = StockLocation::find($id);

        if (! $stockLocation) {
            return response()->json(['message' => 'Stock location not found'], 404);
        }

        $stockLocation->delete();

        return response()->json([
            'message' => 'Stock location deleted successfully',
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
