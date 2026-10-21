<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view stores');

        $stores = Store::with('stockLocations')->get();

        return response()->json([
            'message' => 'Stores retrieved successfully',
            'data' => $stores,
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create stores');

        $validated = $request->validate([
            'code' => 'required|string|max:30|unique:stores,code',
            'name' => 'required|string|max:120',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $store = Store::create($validated);

        return response()->json([
            'message' => 'Store created successfully',
            'data' => $store,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view stores');

        $store = Store::with('stockLocations.stockBalances')->find($id);

        if (! $store) {
            return response()->json(['message' => 'Store not found'], 404);
        }

        return response()->json([
            'message' => 'Store retrieved successfully',
            'data' => $store,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAction($request, 'update stores');

        $store = Store::find($id);

        if (! $store) {
            return response()->json(['message' => 'Store not found'], 404);
        }

        $validated = $request->validate([
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('stores', 'code')->ignore($store->id),
            ],
            'name' => 'sometimes|required|string|max:120',
            'phone' => 'sometimes|nullable|string|max:30',
            'email' => 'sometimes|nullable|email|max:100',
            'address' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $store->update($validated);

        return response()->json([
            'message' => 'Store updated successfully',
            'data' => $store->fresh(),
        ], 200);
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizeAction($request, 'update stores');

        $store = Store::find($id);

        if (! $store) {
            return response()->json(['message' => 'Store not found'], 404);
        }

        $store->delete();

        return response()->json([
            'message' => 'Store deleted successfully',
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
