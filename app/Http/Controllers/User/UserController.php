<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\User\UpdateUserRequest;
use App\Http\Resources\UserResource;


class UserController extends Controller
{
    /**
     * @group User/Users
     *
     * Get Authenticated User
     *
     * Retrieves the currently authenticated user's details.
     *
     * @response {
     *  "id": 1,
     *  "name": "John Doe",
     *  "phone_number": "218925113276",
     *  "type": "user",
     *  "roles": ["user"],
     *  "avatar": "https://example.com/avatar.jpg",
     *  "cover": "https://example.com/cover.jpg",
     *  "status": "active",
     *  "created_at": "2023-01-01T00:00:00.000000Z",
     *  "updated_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function getMe(){
        $user = auth()->user();
        return  new UserResource($user);
    }

    /**
     * @group User/Users 
     *
     * Update Authenticated User
     *
     * Updates the currently authenticated user's details.
     *
     * @bodyParam name string optional The name of the user. Example: John Doe
     * @bodyParam avatar file optional The avatar image for the user. Must be a valid image file. Example: avatar.jpg
     * @bodyParam cover file optional The cover image for the user. Must be a valid image file. Example: cover.jpg
     *
     * @response {
     *  "id": 1,
     *  "name": "John Doe",
     *  "phone_number": "218925113276",
     *  "type": "user",
     *  "roles": ["user"],
     *  "avatar": "https://example.com/avatar.jpg",
     *  "cover": "https://example.com/cover.jpg",
     *  "status": "active",
     *  "created_at": "2023-01-01T00:00:00.000000Z",
     *  "updated_at": "2023-01-01T00:00:00.000000Z"
     * }
     */
    public function update(UpdateUserRequest $request)
    {
        $data = $request->validated();

        $user = auth()->user();
        $user->update($data);
        
        return new UserResource($user);
    }

    /**
     * @group User/Users
     *
     * Delete Authenticated User
     *
     * Deletes the currently authenticated user's account and revokes their access token.
     *
     * @response {
     *  "message": "User deleted successfully."
     * }
     */
    public function destroy()
    {
        $user = auth()->user();
        $user->currentAccessToken()->delete();

        $user->delete();
        return response()->json(['message' => 'User deleted successfully.']);
    }
}
