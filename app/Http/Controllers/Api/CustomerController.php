<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view customer');

        $query = Customer::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%");
        }

        return response()->json([
            'message' => 'Customers retrieved successfully',
            'data' => $query->get(),
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create customer');

        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'phone' => 'required|string|max:30|unique:customers,phone',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'store_credit_balance' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $customer = Customer::create($validated);

        return response()->json([
            'message' => 'Customer created successfully',
            'data' => $customer,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view customer');

        $customer = Customer::with('sales')->find($id);

        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        return response()->json([
            'message' => 'Customer retrieved successfully',
            'data' => $customer,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAction($request, 'update customer');

        $customer = Customer::find($id);

        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:120',
            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('customers', 'phone')->ignore($customer->id),
            ],
            'email' => 'sometimes|nullable|email|max:100',
            'address' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $customer->update($validated);

        return response()->json([
            'message' => 'Customer updated successfully',
            'data' => $customer->fresh(),
        ], 200);
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizeAction($request, 'update customer');

        $customer = Customer::find($id);

        if (! $customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully',
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
