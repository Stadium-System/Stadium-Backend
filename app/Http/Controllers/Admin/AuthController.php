<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\StoreUserRequest;
use App\Http\Resources\AdminResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @group Admin Authentication
     *
     * Register a new user
     *
     * Creates a new user with the provided details. Only admins can create other admins.
     *
     * @authenticated
     *
     * @bodyParam name string required The name of the user. Example: John Doe
     * @bodyParam phone_number string required The user's phone number. Must start with 2189 and be exactly 12 characters. Example: 218912345678
     * @bodyParam password string required The password for the user. Must be at least 8 characters. Example: securepassword
     * @bodyParam type string required The type of user. Must be either admin or user. Example: admin
     * @bodyParam avatar file The avatar image for the user. Must be a valid image file. Example: avatar.jpg
     * @bodyParam cover file The cover image for the user. Must be a valid image file. Example: cover.jpg
     */
    public function register(StoreUserRequest $request)
    {
        $data = $request->validated();

        // Prevent non-admins from creating admin users
        if ($data['type'] === 'admin' && !Auth::user()->hasRole('admin')) {
            throw ValidationException::withMessages([
                'type' => ['Only admins can create admin users.'],
            ]);
        }

        $user = User::create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'password' => bcrypt($data['password']),
            'avatar' => $data['avatar'] ?? null,
            'cover' => $data['cover'] ?? null,
            'type' => $data['type'],
            'status' => 'active',
        ]);

        // Assign role based on 'type'
        $user->assignRole($data['type']);

        return new UserResource($user);
    }

    /**
     * @group Admin Authentication
     *
     * Login
     *
     * Authenticates an admin using phone number and password.
     *
     * @bodyParam phone_number string required The admin's phone number. Must start with 2189 and be exactly 12 characters. Example: 218912345678
     * @bodyParam password string required The admin's password. Example: password123
     *
     * @response {
     *   "access_token": "token_string",
     *   "token_type": "Bearer"
     * }
     */

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone_number' => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'phone_number' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        if (!$user->hasRole('admin')) {
            throw ValidationException::withMessages([
                'phone_number' => ['User is not an admin.'],
            ]);
        }

        if (($user->status === 'banned')|| ($user->status === 'inactive')) {
            Auth::logout();
            throw ValidationException::withMessages([
                'message' => ['User account is banned.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * @group Admin Authentication
     *
     * Logout
     *
     * Revokes the current admin token.
     *
     * @response 204
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->noContent();
    }
}
