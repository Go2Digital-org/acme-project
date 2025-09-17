<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Laravel\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;
use Modules\User\Infrastructure\Laravel\Models\User;

final class AuthController extends Controller
{
    /**
     * Convert Laravel validation errors to API Platform format.
     *
     * @param  array<string, array<string>>  $errors
     * @return array<array<string, string>>
     */
    private function formatValidationErrors(array $errors): array
    {
        $violations = [];
        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $violations[] = [
                    'propertyPath' => $field,
                    'message' => $message,
                    'code' => '',
                ];
            }
        }

        return $violations;
    }

    /**
     * Handle user login.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email' => ['required', 'string', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'violations' => $this->formatValidationErrors($e->errors()),
            ], 422);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return ApiResponse::unauthorized('The provided credentials are incorrect.');
        }

        // Revoke existing tokens for security
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'organization_id' => $user->organization_id,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Successfully authenticated.',
        ]);
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'organization_id' => ['nullable', 'integer', 'exists:organizations,id'],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'violations' => $this->formatValidationErrors($e->errors()),
            ], 422);
        }

        // Create new user
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ];

        if (isset($data['organization_id'])) {
            $userData['organization_id'] = $data['organization_id'];
        }

        $user = User::create($userData);

        // Generate API token
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'organization_id' => $user->organization_id,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
            'message' => 'Employee registered successfully.',
        ], 201);
    }

    /**
     * Get authenticated user profile.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return ApiResponse::unauthorized('User not authenticated.');
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'organization_id' => $user->organization_id,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
            'message' => 'User profile retrieved successfully.',
        ]);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user !== null) {
            $currentToken = $user->currentAccessToken();
            if ($currentToken) {
                $currentToken->delete();
            }
            // Also revoke all tokens for extra security
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Successfully logged out.',
        ]);
    }
}
