<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view units');

        $units = Unit::all();

        return response()->json([
            'message' => 'Units retrieved successfully',
            'data' => $units,
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create units');

        $validated = $request->validate([
            'name' => 'required|string|max:80|unique:units,name',
            'code' => 'required|string|max:30|unique:units,code',
            'is_active' => 'sometimes|boolean',
        ]);

        $unit = Unit::create($validated);

        return response()->json([
            'message' => 'Unit created successfully',
            'data' => $unit,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view units');

        $unit = Unit::find($id);

        if (! $unit) {
            return response()->json(['message' => 'Unit not found'], 404);
        }

        return response()->json([
            'message' => 'Unit retrieved successfully',
            'data' => $unit,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAction($request, 'update units');

        $unit = Unit::find($id);

        if (! $unit) {
            return response()->json(['message' => 'Unit not found'], 404);
        }

        $validated = $request->validate([
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:80',
                Rule::unique('units', 'name')->ignore($unit->id),
            ],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('units', 'code')->ignore($unit->id),
            ],
            'is_active' => 'sometimes|boolean',
        ]);

        $unit->update($validated);

        return response()->json([
            'message' => 'Unit updated successfully',
            'data' => $unit->fresh(),
        ], 200);
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizeAction($request, 'delete units');

        $unit = Unit::find($id);

        if (! $unit) {
            return response()->json(['message' => 'Unit not found'], 404);
        }

        $unit->delete();

        return response()->json([
            'message' => 'Unit deleted successfully',
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
