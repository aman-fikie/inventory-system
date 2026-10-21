<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view brands');

        $brands = Brand::withCount('products')->get();

        return response()->json([
            'message' => 'Brands retrieved successfully',
            'data' => $brands,
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create brands');

        $validated = $request->validate([
            'name' => 'required|string|max:120|unique:brands,name',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $brand = Brand::create($validated);

        return response()->json([
            'message' => 'Brand created successfully',
            'data' => $brand,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view brands');

        $brand = Brand::with('products')->find($id);

        if (! $brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        return response()->json([
            'message' => 'Brand retrieved successfully',
            'data' => $brand,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAction($request, 'update brands');

        $brand = Brand::find($id);

        if (! $brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:120',
                Rule::unique('brands', 'name')->ignore($brand->id),
            ],
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $brand->update($validated);

        return response()->json([
            'message' => 'Brand updated successfully',
            'data' => $brand->fresh(),
        ], 200);
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizeAction($request, 'delete brands');

        $brand = Brand::find($id);

        if (! $brand) {
            return response()->json(['message' => 'Brand not found'], 404);
        }

        $brand->delete();

        return response()->json([
            'message' => 'Brand deleted successfully',
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
