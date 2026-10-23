<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\Register;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view stores');

        $query = Register::with('store');

        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        return response()->json([
            'message' => 'Registers retrieved successfully',
            'data' => $query->get(),
        ], 200);
    }

    public function store(Request $request)
    {
        $this->authorizeAction($request, 'create stores');

        $validated = $request->validate([
            'store_id' => 'required|integer|exists:stores,id',
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:registers,code',
            'is_active' => 'sometimes|boolean',
        ]);

        $register = Register::create($validated);

        return response()->json([
            'message' => 'Register created successfully',
            'data' => $register->load('store'),
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view stores');

        $register = Register::with(['store', 'posSessions'])->find($id);

        if (! $register) {
            return response()->json(['message' => 'Register not found'], 404);
        }

        return response()->json([
            'message' => 'Register retrieved successfully',
            'data' => $register,
        ], 200);
    }

    public function update(Request $request, string $id)
    {
        $this->authorizeAction($request, 'update stores');

        $register = Register::find($id);

        if (! $register) {
            return response()->json(['message' => 'Register not found'], 404);
        }

        $validated = $request->validate([
            'store_id' => 'sometimes|required|integer|exists:stores,id',
            'name' => 'sometimes|required|string|max:100',
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('registers', 'code')->ignore($register->id),
            ],
            'is_active' => 'sometimes|boolean',
        ]);

        $register->update($validated);

        return response()->json([
            'message' => 'Register updated successfully',
            'data' => $register->fresh(['store']),
        ], 200);
    }

    public function destroy(Request $request, string $id)
    {
        $this->authorizeAction($request, 'update stores');

        $register = Register::find($id);

        if (! $register) {
            return response()->json(['message' => 'Register not found'], 404);
        }

        $register->delete();

        return response()->json([
            'message' => 'Register deleted successfully',
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
