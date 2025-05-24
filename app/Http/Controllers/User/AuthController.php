<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Hashing\BcryptHasher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\User\Auth\LoginUserRequest;
use App\Http\Resources\UserResource;
use App\Http\Requests\User\Auth\StoreUserRequest;
use App\Models\Otp;
use App\Models\User;
use App\Services\OtpService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AuthController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * @group User Authentication
     *
     * Register a New User
     *
     * Creates a new user account with the provided details.
     * The phone number must have a valid OTP verification for 'registration' purpose within the last 30 minutes.
     *
     * @bodyParam name string required The name of the user. Example: John Doe
     * @bodyParam phone_number string required The user's phone number. Must start with 2189 and be exactly 12 characters. Example: 218912345678
     * @bodyParam password string required The password for the user. Must be at least 8 characters. Example: securepassword
     * @bodyParam type string required The type of user account (user or owner). Example: user
     * @bodyParam avatar_media_id integer optional The media ID of the user's avatar image. Example: 1
     * @bodyParam cover_media_id integer optional The media ID of the user's cover image. Example: 2
     *
     * @response 201 {
     *   "message": "User registered successfully.",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "phone_number": "218912345678",
     *     "type": "user",
     *     "roles": ["user"],
     *     "avatar": "https://example.com/avatar.jpg",
     *     "cover": "https://example.com/cover.jpg",
     *     "status": "active",
     *     "created_at": "2023-01-01T00:00:00.000000Z",
     *     "updated_at": "2023-01-01T00:00:00.000000Z"
     *   },
     *   "token": "token_string",
     *   "token_type": "Bearer"
     * }
     * @response 422 {
     *   "error": "Phone number not verified or verification expired."
     * }
     */
    public function register(StoreUserRequest $request)
    {
        $data = $request->validated();

        // Check if phone has valid OTP verification for registration
        if (!Otp::hasValidVerification($data['phone_number'], 'registration')) {
            return response()->json(['error' => 'Phone number not verified or verification expired.'], 422);
        }

        // Create the user
        $user = User::create([
            'name' => $data['name'],
            'phone_number' => $data['phone_number'],
            'password' => bcrypt($data['password']),
            'type' => $data['type'],
            'status' => 'active'
        ]);

        // Handle avatar upload from temp media
        if ($request->has('avatar_media_id')) {
            $mediaId = $request->input('avatar_media_id');
            $media = Media::findOrFail($mediaId);
            $media->move($user, 'avatar');
        }
        
        // Handle cover upload from temp media
        if ($request->has('cover_media_id')) {
            $mediaId = $request->input('cover_media_id');
            $media = Media::findOrFail($mediaId);
            $media->move($user, 'cover');
        }

        // Assign role based on type
        $user->assignRole($data['type']);

        // Generate token
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
     * @bodyParam password string required The password for the user. Example: securepassword
     *
     * @response 200 {
     *   "access_token": "token_string",
     *   "token_type": "Bearer",
     *   "user": {
     *     "id": 1,
     *     "name": "John Doe",
     *     "phone_number": "218912345678",
     *     "type": "user",
     *     "roles": ["user"],
     *     "avatar": "https://example.com/avatar.jpg",
     *     "cover": "https://example.com/cover.jpg",
     *     "status": "active",
     *     "created_at": "2023-01-01T00:00:00.000000Z",
     *     "updated_at": "2023-01-01T00:00:00.000000Z"
     *   }
     * }
     * @response 422 {
     *   "message": "The provided credentials are incorrect."
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

        // Check if user is admin (should use admin login)
        if ($user->hasRole('admin')) {
            Auth::logout();
            throw ValidationException::withMessages([
                'message' => ['Please use admin login for administrative access.'],
            ]);
        }

        // Check user status
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
     * @authenticated
     *
     * @response 204
     */
    public function logout()
    {
        Auth::user()->currentAccessToken()->delete();
        return response()->noContent();
    }
}