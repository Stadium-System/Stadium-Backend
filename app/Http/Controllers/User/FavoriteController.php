<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteResource;
use App\Http\Resources\StadiumResource;
use App\Http\Resources\EventResource;
use App\Models\Stadium;
use App\Models\Event;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    /**
     * @group User/Favorites
     *
     * Get My Favorites
     *
     * Retrieves all favorites for the authenticated user.
     *
     * @queryParam type string Filter by type (stadium or event). Example: stadium
     * @queryParam per_page integer Number of items per page. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "favoritable_type": "Stadium",
     *       "favoritable": {
     *         "id": 1,
     *         "name": "City Stadium",
     *         "location": "Downtown",
     *         "price_per_hour": 100.00
     *       },
     *       "created_at": "2025-05-01T10:00:00.000000Z"
     *     },
     *     {
     *       "id": 2,
     *       "favoritable_type": "Event",
     *       "favoritable": {
     *         "id": 1,
     *         "name": "Weekend Tournament",
     *         "date": "2025-06-15T18:00:00.000000Z"
     *       },
     *       "created_at": "2025-05-02T10:00:00.000000Z"
     *     }
     *   ],
     *   "links": { ... },
     *   "meta": { ... }
     * }
     */
    public function index(Request $request)
    {
        $query = auth()->user()->favorites()->with('favoritable');

        if ($request->has('type')) {
            $type = $request->input('type');
            if ($type === 'stadium') {
                $query->where('favoritable_type', Stadium::class);
            } elseif ($type === 'event') {
                $query->where('favoritable_type', Event::class);
            }
        }

        $favorites = $query->latest()->paginate($request->per_page ?? 15);

        return FavoriteResource::collection($favorites);
    }

    /**
     * @group User/Favorites
     *
     * Add Stadium to Favorites
     *
     * Adds a stadium to the authenticated user's favorites.
     *
     * @urlParam stadium integer required The ID of the stadium. Example: 1
     *
     * @response {
     *   "message": "Stadium added to favorites successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "City Stadium",
     *     "is_favorited": true
     *   }
     * }
     *
     * @response 409 {
     *   "message": "Stadium is already in favorites"
     * }
     */
    public function favoriteStadium(Stadium $stadium)
    {
        $user = auth()->user();

        if ($stadium->isFavoritedBy($user)) {
            return response()->json([
                'message' => 'Stadium is already in favorites'
            ], 409);
        }

        $stadium->favoriteBy($user);

        return response()->json([
            'message' => 'Stadium added to favorites successfully',
            'data' => new StadiumResource($stadium)
        ]);
    }

    /**
     * @group User/Favorites
     *
     * Remove Stadium from Favorites
     *
     * Removes a stadium from the authenticated user's favorites.
     *
     * @urlParam stadium integer required The ID of the stadium. Example: 1
     *
     * @response {
     *   "message": "Stadium removed from favorites successfully"
     * }
     *
     * @response 404 {
     *   "message": "Stadium is not in favorites"
     * }
     */
    public function unfavoriteStadium(Stadium $stadium)
    {
        $user = auth()->user();

        if (!$stadium->isFavoritedBy($user)) {
            return response()->json([
                'message' => 'Stadium is not in favorites'
            ], 404);
        }

        $stadium->unfavoriteBy($user);

        return response()->json([
            'message' => 'Stadium removed from favorites successfully'
        ]);
    }

    /**
     * @group User/Favorites
     *
     * Add Event to Favorites
     *
     * Adds an event to the authenticated user's favorites.
     *
     * @urlParam event integer required The ID of the event. Example: 1
     *
     * @response {
     *   "message": "Event added to favorites successfully",
     *   "data": {
     *     "id": 1,
     *     "name": "Weekend Tournament",
     *     "is_favorited": true
     *   }
     * }
     *
     * @response 409 {
     *   "message": "Event is already in favorites"
     * }
     */
    public function favoriteEvent(Event $event)
    {
        $user = auth()->user();

        if ($event->isFavoritedBy($user)) {
            return response()->json([
                'message' => 'Event is already in favorites'
            ], 409);
        }

        $event->favoriteBy($user);

        return response()->json([
            'message' => 'Event added to favorites successfully',
            'data' => new EventResource($event)
        ]);
    }

    /**
     * @group User/Favorites
     *
     * Remove Event from Favorites
     *
     * Removes an event from the authenticated user's favorites.
     *
     * @urlParam event integer required The ID of the event. Example: 1
     *
     * @response {
     *   "message": "Event removed from favorites successfully"
     * }
     *
     * @response 404 {
     *   "message": "Event is not in favorites"
     * }
     */
    public function unfavoriteEvent(Event $event)
    {
        $user = auth()->user();

        if (!$event->isFavoritedBy($user)) {
            return response()->json([
                'message' => 'Event is not in favorites'
            ], 404);
        }

        $event->unfavoriteBy($user);

        return response()->json([
            'message' => 'Event removed from favorites successfully'
        ]);
    }

    /**
     * @group User/Favorites
     *
     * Get My Favorites Stats
     *
     * Get statistics about the authenticated user's favorites.
     *
     * @response {
     *   "data": {
     *     "total_favorites": 10,
     *     "favorite_stadiums_count": 6,
     *     "favorite_events_count": 4
     *   }
     * }
     */
    public function stats()
    {
        $user = auth()->user();

        $stats = [
            'total_favorites' => $user->favorites()->count(),
            'favorite_stadiums_count' => $user->favorites()->where('favoritable_type', Stadium::class)->count(),
            'favorite_events_count' => $user->favorites()->where('favoritable_type', Event::class)->count(),
        ];

        return response()->json(['data' => $stats]);
    }
}