<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'role'            => ['sometimes', 'in:penyedia,pelamar'],
            'nama_perusahaan' => ['required_if:role,penyedia', 'nullable', 'string', 'max:255'],
            'telepon'         => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'password'        => $validated['password'],
            'role'            => $validated['role'] ?? 'pelamar',
            'nama_perusahaan' => $validated['nama_perusahaan'] ?? null,
            'telepon'         => $validated['telepon'] ?? null,
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'message' => 'User registered successfully.',
            'user'    => $user,
            'token'   => $this->respondWithToken($token),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful.',
            'user'    => auth('api')->user(),
            'token'   => $this->respondWithToken($token),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Data user berhasil diambil.',
            'data'    => auth('api')->user(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        if (! auth('api')->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        auth('api')->logout();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        if (! auth('api')->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'message' => 'Token refreshed successfully.',
            'token'   => $this->respondWithToken(auth('api')->refresh()),
        ]);
    }

    protected function respondWithToken(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ];
    }
}
