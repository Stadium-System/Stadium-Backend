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
     * @bodyParam first_name string required The first name of the user. Example: John
     * @bodyParam last_name string required The last name of the user. Example: Doe
     * @bodyParam phone_number string required The user's phone number. Must start with 2189 and be exactly 12 characters. Example: 218912345678
     * @bodyParam password string required The password for the user. Must be at least 8 characters. Example: securepassword
     */
    public function register(StoreUserRequest $request)
    {
        $data = $request->validated();

        // Create user
        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone_number' => $data['phone_number'],
            'password' => bcrypt($data['password']),
            'type' => 'user',
        ]);

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

        if ($user->role == 'admin' ) {
            Auth::logout();
            throw ValidationException::withMessages([
                'message' => ['Unauthorized access for this user type.'],
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
