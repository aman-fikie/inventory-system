<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view products');

        // Filter by product_id if passed as a query parameter (e.g., /api/product-variants?product_id=5)
        $query = ProductVariant::with(['product', 'stockBalances']);

        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        return response()->json([
            'message' => 'Product variants retrieved successfully',
            'data' => $query->get(),
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create products');

        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'sku' => 'required|string|max:80|unique:product_variants,sku',
            'barcode' => 'nullable|string|max:100|unique:product_variants,barcode',
            'variant_name' => 'required|string|max:120',
            'attributes' => 'nullable|array', // e.g. ["color" => "Black", "storage" => "256GB"]
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'wholesale_price' => 'nullable|numeric|min:0',
            'minimum_selling_price' => 'nullable|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $variant = ProductVariant::create($validated);

        return response()->json([
            'message' => 'Product variant created successfully',
            'data' => $variant->load('product'),
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view products');

        $variant = ProductVariant::with(['product', 'stockBalances.stockLocation'])->find($id);

        if (! $variant) {
            return response()->json(['message' => 'Product variant not found'], 404);
        }

        return response()->json([
            'message' => 'Product variant retrieved successfully',
            'data' => $variant,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAction($request, 'update products');

        $variant = ProductVariant::find($id);

        if (! $variant) {
            return response()->json(['message' => 'Product variant not found'], 404);
        }

        $validated = $request->validate([
            'product_id' => 'sometimes|required|integer|exists:products,id',
            'sku' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('product_variants', 'sku')->ignore($variant->id),
            ],
            'barcode' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('product_variants', 'barcode')->ignore($variant->id),
            ],
            'variant_name' => 'sometimes|required|string|max:120',
            'attributes' => 'sometimes|nullable|array',
            'cost_price' => 'sometimes|required|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'wholesale_price' => 'sometimes|nullable|numeric|min:0',
            'minimum_selling_price' => 'sometimes|nullable|numeric|min:0',
            'weight' => 'sometimes|nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $variant->update($validated);

        return response()->json([
            'message' => 'Product variant updated successfully',
            'data' => $variant->fresh('product'),
        ], 200);
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizeAction($request, 'delete products');

        $variant = ProductVariant::find($id);

        if (! $variant) {
            return response()->json(['message' => 'Product variant not found'], 404);
        }

        $variant->delete();

        return response()->json([
            'message' => 'Product variant deleted successfully',
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
