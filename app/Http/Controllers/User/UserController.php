<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\User\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use Spatie\MediaLibrary\MediaCollections\Models\Media;



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
     * @bodyParam avatar_media_id integer optional The media ID for the user's avatar image (obtained from /api/v1/general/temp-uploads/images endpoint). Example: 3
     * @bodyParam cover_media_id integer optional The media ID for the user's cover image (obtained from /api/v1/general/temp-uploads/images endpoint). Example: 4
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

        $user->update([
            'name' => $data['name'] ?? $user->name,
        ]);
        
        // Handle avatar from temp uploads
        if ($request->has('avatar_media_id')) {
            $mediaId = $request->input('avatar_media_id');
            $media = Media::findOrFail($mediaId);
            
            // Clear previous avatar
            $user->clearMediaCollection('avatar');
            
            // Move from temp to user
            $media->move($user, 'avatar');
        }
        
        // Handle cover from temp uploads
        if ($request->has('cover_media_id')) {
            $mediaId = $request->input('cover_media_id');
            $media = Media::findOrFail($mediaId);
            
            // Clear previous cover
            $user->clearMediaCollection('cover');
            
            // Move from temp to user
            $media->move($user, 'cover');
        }
        
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
