<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\AuthController;
use App\Models\PosSession;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PosSessionController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeAction($request, 'view inventory'); // or specific POS permission

        $query = PosSession::with(['register.store', 'user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('register_id')) {
            $query->where('register_id', $request->register_id);
        }

        return response()->json([
            'message' => 'POS sessions retrieved successfully',
            'data' => $query->get(),
        ], 200);
    }

    // Open a new register shift
    public function store(Request $request)
    {
        $user = app(AuthController::class)->authenticatedUser($request);
        abort_unless($user, 401, 'Unauthenticated.');

        $validated = $request->validate([
            'register_id' => 'required|integer|exists:registers,id',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Check if user or register already has an active open session
        $existingSession = PosSession::where('register_id', $validated['register_id'])
            ->where('status', 'open')
            ->first();

        if ($existingSession) {
            return response()->json([
                'message' => 'This register already has an active open session.',
            ], 422);
        }

        $session = PosSession::create([
            'register_id' => $validated['register_id'],
            'user_id' => $user->id,
            'opening_balance' => $validated['opening_balance'],
            'status' => 'open',
            'opened_at' => Carbon::now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message' => 'POS session opened successfully',
            'data' => $session->load(['register.store', 'user']),
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $this->authorizeAction($request, 'view inventory');

        $session = PosSession::with(['register.store', 'user', 'sales'])->find($id);

        if (! $session) {
            return response()->json(['message' => 'POS session not found'], 404);
        }

        return response()->json([
            'message' => 'POS session retrieved successfully',
            'data' => $session,
        ], 200);
    }

    // Close a register shift (Reconciliation)
    public function closeSession(Request $request, string $id)
    {
        $session = PosSession::find($id);

        if (! $session) {
            return response()->json(['message' => 'POS session not found'], 404);
        }

        if ($session->status === 'closed') {
            return response()->json(['message' => 'This session is already closed.'], 422);
        }

        $validated = $request->validate([
            'closing_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        // Calculate expected cash balance: Opening Balance + Total Cash Sales recorded in this session
        $expectedCash = $session->opening_balance + $session->cash_sales_total;

        $session->update([
            'closing_balance' => $validated['closing_balance'],
            'expected_cash_balance' => $expectedCash,
            'status' => 'closed',
            'closed_at' => Carbon::now(),
            'notes' => $validated['notes'] ?? $session->notes,
        ]);

        return response()->json([
            'message' => 'POS session closed and reconciled successfully',
            'data' => $session->fresh(['register.store', 'user']),
        ], 200);
    }

    private function authorizeAction(Request $request, string $permission): void
    {
        $user = app(AuthController::class)->authenticatedUser($request);

        abort_unless($user, 401, 'Unauthenticated.');
        abort_unless($user->can($permission), 403, 'You do not have permission to perform this action.');
    }
}
