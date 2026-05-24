<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $plainToken = bin2hex(random_bytes(32));

        $user = User::query()->create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'api_token_hash' => hash('sha256', $plainToken),
            'is_admin' => false,
            'role' => 'user',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Register berhasil.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
                'role' => $user->role ?: ((bool) $user->is_admin ? 'admin' : 'user'),
            ],
            'token' => $plainToken,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = trim((string) ($validated['login'] ?? $validated['email'] ?? ''));
        if ($login === '') {
            return response()->json([
                'success' => false,
                'message' => 'Email atau username wajib diisi.',
            ], 422);
        }

        $user = User::query()
            ->where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if ($user === null || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email/username atau password tidak valid.',
            ], 401);
        }

        $plainToken = bin2hex(random_bytes(32));
        $user->api_token_hash = hash('sha256', $plainToken);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token' => $plainToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
                'role' => $user->role ?: ((bool) $user->is_admin ? 'admin' : 'user'),
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
                'role' => $user->role ?: ((bool) $user->is_admin ? 'admin' : 'user'),
            ],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'different:current_password'],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak valid.',
            ], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diperbarui.',
        ]);
    }

    public function adminCreateUser(Request $request): JsonResponse
    {
        $guard = $this->ensureAdmin($request);
        if ($guard !== null) {
            return $guard;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'in:user,manager,admin'],
        ]);

        $role = (string) ($validated['role'] ?? 'user');

        $user = User::query()->create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $role,
            'is_admin' => $role === 'admin',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat.',
            'user' => $this->transformUser($user),
        ], 201);
    }

    public function adminUpdateUser(Request $request, int $userId): JsonResponse
    {
        $guard = $this->ensureAdmin($request);
        if ($guard !== null) {
            return $guard;
        }

        $user = User::query()->find($userId);
        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', 'in:user,manager,admin'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->role = $validated['role'];
        $user->is_admin = $validated['role'] === 'admin';

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diperbarui.',
            'user' => $this->transformUser($user),
        ]);
    }

    private function ensureAdmin(Request $request): ?JsonResponse
    {
        $user = $request->user();
        $role = (string) ($user->role ?? '');

        if (!$user || (!$user->is_admin && $role !== 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden. Admin only.',
            ], 403);
        }

        return null;
    }

    private function transformUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'role' => $user->role ?: ((bool) $user->is_admin ? 'admin' : 'user'),
            'created_at' => optional($user->created_at)?->toISOString(),
        ];
    }
}
