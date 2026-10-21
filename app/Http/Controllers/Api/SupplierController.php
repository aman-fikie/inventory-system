<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\Supplier;
use Illuminate\Http\Request;


class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view purchases');

        $suppliers = Supplier::withCount('purchaseOrders')->get();

        return response()->json([
            'message' => 'Suppliers retrieved successfully',
            'data' => $suppliers,
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create purchases');

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'opening_balance' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['current_balance'] = $validated['opening_balance'] ?? 0.00;

        $supplier = Supplier::create($validated);

        return response()->json([
            'message' => 'Supplier created successfully',
            'data' => $supplier,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view purchases');

        $supplier = Supplier::with(['purchaseOrders', 'supplierPayments'])->find($id);

        if (! $supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        return response()->json([
            'message' => 'Supplier retrieved successfully',
            'data' => $supplier,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAction($request, 'create purchases');

        $supplier = Supplier::find($id);

        if (! $supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:120',
            'contact_person' => 'sometimes|nullable|string|max:100',
            'phone' => 'sometimes|nullable|string|max:30',
            'email' => 'sometimes|nullable|email|max:100',
            'address' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $supplier->update($validated);

        return response()->json([
            'message' => 'Supplier updated successfully',
            'data' => $supplier->fresh(),
        ], 200);
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizeAction($request, 'approve purchases'); // Restrict deletion to managers/admins

        $supplier = Supplier::find($id);

        if (! $supplier) {
            return response()->json(['message' => 'Supplier not found'], 404);
        }

        $supplier->delete();

        return response()->json([
            'message' => 'Supplier deleted successfully',
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
