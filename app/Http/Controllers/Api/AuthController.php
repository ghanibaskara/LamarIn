<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Autentikasi & Manajemen Token JWT"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/auth/register",
     *     tags={"Auth"},
     *     summary="Registrasi user baru (penyedia atau pelamar)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Budi Santoso"),
     *             @OA\Property(property="email", type="string", format="email", example="budi@example.com"),
     *             @OA\Property(property="password", type="string", minLength=8, example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123"),
     *             @OA\Property(property="role", type="string", enum={"penyedia","pelamar"}, example="pelamar"),
     *             @OA\Property(property="nama_perusahaan", type="string", nullable=true, example="PT Maju Bersama"),
     *             @OA\Property(property="telepon", type="string", nullable=true, example="081234567890")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User berhasil didaftarkan"),
     *     @OA\Response(response=422, description="Validasi gagal")
     * )
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'        => ['required', 'string', 'min:8', 'confirmed'],
            'role'            => ['sometimes', 'in:penyedia,pelamar'],
            'nama_perusahaan' => ['required_if:role,penyedia', 'nullable', 'string', 'max:255'],
            'telepon' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'] ?? 'pelamar',
            'nama_perusahaan' => $validated['nama_perusahaan'] ?? null,
            'telepon' => $validated['telepon'] ?? null,
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
            'token' => $this->respondWithToken($token),
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     summary="Login dan dapatkan JWT token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="budi@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login berhasil"),
     *     @OA\Response(response=401, description="Kredensial tidak valid")
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials.',
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful.',
            'user' => auth('api')->user(),
            'token' => $this->respondWithToken($token),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/auth/me",
     *     tags={"Auth"},
     *     summary="Melihat data user yang sedang login",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Data user berhasil diambil"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Data user berhasil diambil.',
            'data'    => auth('api')->user(),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Auth"},
     *     summary="Logout dan invalidate JWT token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logout berhasil"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        if (!auth('api')->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        auth('api')->logout();

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/refresh",
     *     tags={"Auth"},
     *     summary="Refresh JWT token yang masih aktif",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Token berhasil di-refresh"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        if (!auth('api')->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return response()->json([
            'message' => 'Token refreshed successfully.',
            'token' => $this->respondWithToken(auth('api')->refresh()),
        ]);
    }

    protected function respondWithToken(string $token): array
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ];
    }
}
