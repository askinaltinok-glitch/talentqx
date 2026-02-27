<?php

namespace App\Http\Controllers\Api\CompanyPanel;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->requireRole($request, 'super_admin');

        $users = User::whereNotNull('company_panel_role')
            ->select('id', 'email', 'first_name', 'last_name', 'company_panel_role', 'is_active', 'last_login_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireRole($request, 'super_admin');

        $request->validate([
            'email' => 'required|email|unique:users,email',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'password' => 'required|string|min:8',
            'company_panel_role' => 'required|in:super_admin,sales_rep,accounting',
        ]);

        $user = User::create([
            'email' => Str::lower($request->email),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'password' => $request->password,
            'company_panel_role' => $request->company_panel_role,
            'is_active' => true,
            'is_platform_admin' => true,
        ]);

        return response()->json([
            'success' => true,
            'data' => $user->only('id', 'email', 'first_name', 'last_name', 'company_panel_role', 'is_active'),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $this->requireRole($request, 'super_admin');

        $user = User::whereNotNull('company_panel_role')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $user->only('id', 'email', 'first_name', 'last_name', 'company_panel_role', 'is_active', 'last_login_at', 'created_at'),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $this->requireRole($request, 'super_admin');

        $user = User::whereNotNull('company_panel_role')->findOrFail($id);

        $request->validate([
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'company_panel_role' => 'sometimes|in:super_admin,sales_rep,accounting',
            'is_active' => 'sometimes|boolean',
            'password' => 'sometimes|string|min:8',
        ]);

        $data = $request->only('first_name', 'last_name', 'company_panel_role', 'is_active');
        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'data' => $user->fresh()->only('id', 'email', 'first_name', 'last_name', 'company_panel_role', 'is_active'),
        ]);
    }

    private function requireRole(Request $request, string $role): void
    {
        if ($request->user()->company_panel_role !== $role) {
            abort(403, 'Bu işlem için yetkiniz yok.');
        }
    }
}
