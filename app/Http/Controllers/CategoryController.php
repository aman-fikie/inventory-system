<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AuthController;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * GET /api/categories
     * Admin, manager, and sales can view categories.
     */
    public function index(Request $request)
    {
        $this->authorizeCategoryAction($request, 'view categories');

        $categories = Category::all();

        return response()->json([
            'message' => 'Categories retrieved successfully',
            'data' => $categories,
        ], 200);
    }

    /**
     * POST /api/categories
     * Only admin and manager can create categories.
     */
    public function store(Request $request)
    {
        $this->authorizeCategoryAction($request, 'create categories');

        $validated = $request->validate([
            'parent_id' => 'nullable|integer|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    /**
     * GET /api/categories/{id}
     * Admin, manager, and sales can view one category.
     */
    public function show(Request $request, string $id)
    {
        $this->authorizeCategoryAction($request, 'view categories');

        $category = Category::find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Category retrieved successfully',
            'data' => $category,
        ], 200);
    }

    /**
     * PUT/PATCH /api/categories/{id}
     * Only admin and manager can update categories.
     */
    public function update(Request $request, string $id)
    {
        $this->authorizeCategoryAction($request, 'update categories');

        $category = Category::find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        $validated = $request->validate([
            'parent_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:categories,id',
                Rule::notIn([$category->id]),
            ],
            'name' => 'sometimes|required|string|max:255',
            'slug' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')
                    ->ignore($category->id),
            ],
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category->fresh(),
        ], 200);
    }

    /**
     * DELETE /api/categories/{id}
     * Only admin and manager can delete categories.
     */
    public function destroy(Request $request, string $id)
    {
        $this->authorizeCategoryAction($request, 'delete categories');

        $category = Category::find($id);

        if (! $category) {
            return response()->json([
                'message' => 'Category not found',
            ], 404);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ], 200);
    }

    /**
     * Authenticate the Bearer token and check the user's permission.
     * This intentionally does not use middleware.
     */
    private function authorizeCategoryAction(
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
