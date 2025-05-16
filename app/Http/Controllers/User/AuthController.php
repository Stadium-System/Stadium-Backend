<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\User\Auth\LoginUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Requests\User\Auth\StoreUserRequest;
use App\Models\User;
use App\Models\Otp;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Helpers\MediaHelper;


class AuthController extends Controller
{

    /**
     * @group User Authentication
     *
     * Register a New User
     *
     * Completes the registration for a new user with the provided details,
     * assigns the default 'user' role, and returns an access token.
     *
     * @bodyParam name string required The  name of the user. Example: John Doe
     * @bodyParam phone_number string required The user's phone number. Must start with 2189 and be exactly 12 characters. Example: 218912345678
     * @bodyParam password string required The password for the user. Must be at least 8 characters. Example: securepassword
     * @bodyParam avatar_media_id integer The media ID of the user's avatar image (obtained from /api/v1/general/temp-uploads/images endpoint). Example: 1
     * @bodyParam cover_media_id integer The media ID of the user's cover image (obtained from /api/v1/general/temp-uploads/images endpoint). Example: 2
     */
    public function register(StoreUserRequest $request)
    {
        $data = $request->validated();

        // Find the user by phone number
        $user = User::where('phone_number', $data['phone_number'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'phone_number' => ['This phone number is not associated with an OTP request.'],
            ]);
        }

        // Ensure the phone number has been verified via OTP
        if (!Otp::where('phone_number', $data['phone_number'])->where('is_verified', true)->exists()) {
            throw ValidationException::withMessages([
                'phone_number' => ['Phone number is not verified.'],
            ]);
        }

        // Ensure the user is still inactive before updating
        if ($user->status !== 'inactive') {
            throw ValidationException::withMessages([
                'phone_number' => ['This phone number is already registered and active. Please log in.'],
            ]);
        }

        $user->update([
            'name' => $data['name'],
            'avatar' => $data['avatar'] ?? null,
            'cover' => $data['cover'] ?? null,
            'type' => 'user',
            'status' => 'active'
        ]);

        // For avatar
        if ($request->has('avatar_media_id')) {
            MediaHelper::attachMedia($user, [$request->input('avatar_media_id')], 'avatar', true);
        }

        // For cover
        if ($request->has('cover_media_id')) {
            MediaHelper::attachMedia($user, [$request->input('cover_media_id')], 'cover', true);
        }

        $user->assignRole('user');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => new UserResource($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201); 
    }


    /**
     * @group User Authentication
     *
     * Login (by Phone Number)
     *
     * Authenticates a user using phone number and password.
     *
     * @bodyParam phone_number string required The user's phone number. Must start with 2189 and be exactly 12 characters. Example: 218912345678
     * @bodyParam password string required The password for the user. Must be at least 8 characters. Example: securepassword
     *
     * @response {
     *   "access_token": "token_string",
     *   "token_type": "Bearer"
     * }
     */
    public function login(LoginUserRequest $request)
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'message' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();

        if ($user->role == 'admin') {
            Auth::logout();
            throw ValidationException::withMessages([
                'message' => ['Unauthorized access for this user type.'],
            ]);
        }

        if ($user->status === 'inactive') {
            Auth::logout();
            throw ValidationException::withMessages([
                'message' => ['User account is not active.'],
            ]);
        }

        if ($user->status === 'banned') {
            Auth::logout();
            throw ValidationException::withMessages([
                'message' => ['User account is banned.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * @group User Authentication
     *
     * Logout
     *
     * Revokes the current user token.
     *
     * @response 204
     */
    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();
        return response()->noContent();
    }
}
