<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\StoreUserRequest;
use App\Http\Requests\Admin\Auth\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class UserController extends Controller
{
    /**
     * @group Admin/Users
     *
     * Get all users
     *
     * Retrieves a paginated list of all users.
     *
     * @authenticated
     *
     * @queryParam filter[name] string Filter by user name. Example: John
     * @queryParam filter[phone_number] string Filter by user phone number. Example: 218925113276
     * @queryParam filter[type] string Filter by user type. Example: admin
     * @queryParam filter[status] string Filter by user status. Example: active
     * @queryParam sort string Sort by fields. Prefix with "-" for descending order. Example: name,-created_at
     */
    public function index(Request $request)
    {
        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::callback('name', function ($query, $value) {
                    $query->whereRaw("LOWER(name) LIKE ?", ["%" . strtolower($value) . "%"]);
                }),
                'phone_number',
                AllowedFilter::callback('status', function ($query, $value) {
                    $allowedStatuses = ['active', 'inactive', 'banned'];
                    if (in_array($value, $allowedStatuses)) {
                        $query->where('status', $value);
                    }
                }),
                AllowedFilter::callback('type', function ($query, $value) {
                    $allowedTypes = ['admin', 'user', 'owner'];
                    if (in_array($value, $allowedTypes)) {
                        $query->where('type', $value);
                    }
                }),

            ])
            ->paginate(10)
            ->appends($request->query());

        return UserResource::collection($users);
    }

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
     * @bodyParam type string required The type of user. Must be either admin or user or owner. Example: admin
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
     * @group Admin/Users
     *
     * Get user by ID
     *
     * Retrieves the details of a specific user by its ID.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the user. Example: 1
     */
    public function show(User $user)
    {
        return new UserResource($user);
    }

    /**
     * @group Admin/Users
     *
     * Update user by ID
     *
     * Updates the details of a specific user by its ID.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the user. Example: 1
     * @bodyParam name string required The updated name of the user. Example: Jane Doe
     * @bodyParam password string The updated password for the user. Must be at least 8 characters. Example: newsecurepassword
     * @bodyParam avatar file The avatar image for the user. Must be a valid image file. Example: avatar.jpg
     * @bodyParam cover file The cover image for the user. Must be a valid image file. Example: cover.jpg
     * @bodyParam type string required The updated type of user. Example: user
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        } else {
            unset($data['password']);
        }
        // Prevent super admins from being downgraded
        if ($user->type === 'super_admin' && $request->filled('type') && $request->type !== 'super_admin') {
            return response()->json(['message' => 'Super admins cannot be downgraded.'], 403);
        }
        
        $user->update($data);

        // Update role based on 'type'
        $user->syncRoles($request->type === 'admin' ? 'admin' : 'user');

        return new UserResource($user->load('addresses'));
    }

    /**
     * @group Admin/Users
     *
     * Delete user by ID
     *
     * Deletes a specific user by its ID.
     * Admins and Super admins cannot delete themselves, and only super admins can delete admins.
     *
     * @authenticated
     *
     * @urlParam id integer required The ID of the user. Example: 1
     */
    public function destroy(User $user)
    {
        $authUser = auth()->user();

        // Prevent admins and super admins from deleting themselves
        if ($authUser->is($user)) {
            return response()->json(['message' => 'Admins and super admins cannot delete themselves.'], 403);
        }

        // Only super admins can delete admins
        if (($user->type === 'admin' || $user->type === 'super_admin') && !$authUser->hasRole('super_admin')) {
            return response()->json(['message' => 'Only super admins can delete admins.'], 403);
        }

        $user->delete();

        return response()->noContent();
    }

    /**
     * @group Admin/Users
     *
     * Ban User
     *
     * Bans a user so they cannot log in. Only super admins and admins are allowed to perform this action.
     * Super admins cannot be banned.
     *
     * @authenticated
     *
     * @urlParam user integer required The ID of the user to ban. Example: 3
     *
     * @response 200 {
     *   "message": "User banned successfully."
     * }
     * @response 403 {
     *   "message": "Unauthorized."
     * }
     * @response 403 {
     *   "message": "Super admins cannot be banned."
     * }
     */
    public function ban(User $user)
    {
        $authUser = auth()->user();

        // Only allow super admins & admins to ban
        if (!$authUser->hasRole(['super_admin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($user->hasRole('super_admin')) {
            return response()->json(['message' => 'Super admins cannot be banned.'], 403);
        }

        // Only super admins can ban other admins
        if ($user->type === 'admin' && !$authUser->hasRole('super_admin')) {
            return response()->json(['message' => 'Only super admins can ban admins.'], 403);
        }

        $user->update([
            'status' => 'banned'
        ]);

        $user->tokens()->delete();
        return response()->json(['message' => 'User banned successfully.']);
    }

    /**
     * @group Admin/Users
     *
     * Unban User
     *
     * Unbans a user, allowing them to log in. Only super admins and admins are allowed to perform this action.
     * Super admins cannot be unbanned.
     *
     * @authenticated
     *
     * @urlParam user integer required The ID of the user to unban. Example: 3
     *
     * @response 200 {
     *   "message": "User unbanned successfully."
     * }
     * @response 403 {
     *   "message": "Unauthorized."
     * }
     * @response 403 {
     *   "message": "Super admins cannot be unbanned."
     * }
     */
    public function unban(User $user)
    {
        $authUser = auth()->user();

        // Only allow super admins & admins to unban
        if (!$authUser->hasRole(['super_admin', 'admin'])) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($user->hasRole('super_admin')) {
            return response()->json(['message' => 'Super admins cannot be unbanned.'], 403);
        }

        // Only super admins can unban other admins
        if ($user->type === 'admin' && !$authUser->hasRole('super_admin')) {
            return response()->json(['message' => 'Only super admins can unban admins.'], 403);
        }

        $user->update([
            'status' => 'active'
        ]);

        return response()->json(['message' => 'User unbanned successfully.']);
    }

}
