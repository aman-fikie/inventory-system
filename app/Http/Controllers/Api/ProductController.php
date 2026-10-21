<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    /**
     * GET /api/products
     * View all products.
     */
    public function index(Request $request)
    {
        $this->authorizeProductAction($request, 'view products');

        $products = Product::with(['category', 'brand', 'baseUnit', 'variants'])->get();

        return response()->json([
            'message' => 'Products retrieved successfully',
            'data' => $products,
        ], 200);
    }

    /**
     * POST /api/products
     * Create a new product.
     */
    public function store(Request $request)
    {
        $this->authorizeProductAction($request, 'create products');

        $validated = $request->validate([
            'category_id' => 'nullable|integer|exists:categories,id',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'base_unit_id' => 'required|integer|exists:units,id',
            'product_code' => 'required|string|max:50|unique:products,product_code',
            'name' => 'required|string|max:180',
            'slug' => 'nullable|string|max:200|unique:products,slug',
            'description' => 'nullable|string',
            'status' => 'sometimes|string|in:draft,active,inactive,archived',
            'track_inventory' => 'sometimes|boolean',
            'is_sellable' => 'sometimes|boolean',
        ]);

        // Automatically generate a slug from the name if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $validated['status'] = $validated['status'] ?? 'draft';

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product->load(['category', 'brand', 'baseUnit']),
        ], 201);
    }

    /**
     * GET /api/products/{id}
     * View a single product.
     */
    public function show(Request $request, string $id)
    {
        $this->authorizeProductAction($request, 'view products');

        $product = Product::with(['category', 'brand', 'baseUnit', 'variants', 'images'])->find($id);

        if (! $product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Product retrieved successfully',
            'data' => $product,
        ], 200);
    }

    /**
     * PUT/PATCH /api/products/{id}
     * Update a product.
     */
    public function update(Request $request, string $id)
    {
        $this->authorizeProductAction($request, 'update products');

        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        $validated = $request->validate([
            'category_id' => 'sometimes|nullable|integer|exists:categories,id',
            'brand_id' => 'sometimes|nullable|integer|exists:brands,id',
            'base_unit_id' => 'sometimes|required|integer|exists:units,id',
            'product_code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'product_code')->ignore($product->id),
            ],
            'name' => 'sometimes|required|string|max:180',
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:200',
                Rule::unique('products', 'slug')->ignore($product->id),
            ],
            'description' => 'sometimes|nullable|string',
            'status' => 'sometimes|string|in:draft,active,inactive,archived',
            'track_inventory' => 'sometimes|boolean',
            'is_sellable' => 'sometimes|boolean',
        ]);

        if (isset($validated['name']) && empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'data' => $product->fresh(['category', 'brand', 'baseUnit']),
        ], 200);
    }

    /**
     * DELETE /api/products/{id}
     * Delete a product.
     */
    public function destroy(Request $request, string $id)
    {
        $this->authorizeProductAction($request, 'delete products');

        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'message' => 'Product not found',
            ], 404);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully',
        ], 200);
    }

    /**
     * Authenticate the Bearer token and check the user's permission.
     */
    private function authorizeProductAction(
        Request $request,
        string $permission
    ): void {
        $user = app(AuthController::class)
            ->authenticatedUser($request);

        abort_unless(
            $user,
            401,
            'Unauthenticated.'
        );

        abort_unless(
            $user->can($permission),
            403,
            'You do not have permission to perform this action.'
        );
    }
}
