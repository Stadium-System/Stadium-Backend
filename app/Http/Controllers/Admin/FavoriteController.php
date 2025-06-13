<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\FavoriteResource;
use App\Http\Resources\StadiumResource;
use App\Http\Resources\EventResource;
use App\Models\User;
use App\Models\Stadium;
use App\Models\Event;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class FavoriteController extends Controller
{
    /**
     * @group Admin/Favorites
     *
     * Get User's Favorites
     *
     * Retrieves all favorites for a specific user.
     *
     * @authenticated
     *
     * @urlParam user integer required The ID of the user. Example: 1
     * @queryParam type string Filter by type (stadium or event). Example: stadium
     * @queryParam per_page integer Number of items per page. Example: 15
     *
     * @response {
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe"
     *     },
     *     "stats": {
     *       "total_favorites": 10,
     *       "favorite_stadiums_count": 6,
     *       "favorite_events_count": 4
     *     },
     *     "favorites": [
     *       {
     *         "id": 1,
     *         "favoritable_type": "Stadium",
     *         "favoritable": {
     *           "id": 1,
     *           "name": "City Stadium"
     *         },
     *         "created_at": "2025-05-01T10:00:00.000000Z"
     *       }
     *     ]
     *   }
     * }
     */
    public function userFavorites(Request $request, User $user)
    {
        $query = $user->favorites()->with('favoritable');

        if ($request->has('type')) {
            $type = $request->input('type');
            if ($type === 'stadium') {
                $query->where('favoritable_type', Stadium::class);
            } elseif ($type === 'event') {
                $query->where('favoritable_type', Event::class);
            }
        }

        $favorites = $query->latest()->paginate($request->per_page ?? 15);

        $stats = [
            'total_favorites' => $user->favorites()->count(),
            'favorite_stadiums_count' => $user->favorites()->where('favoritable_type', Stadium::class)->count(),
            'favorite_events_count' => $user->favorites()->where('favoritable_type', Event::class)->count(),
        ];

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone_number' => $user->phone_number,
                ],
                'stats' => $stats,
                'favorites' => FavoriteResource::collection($favorites)->response()->getData(true),
            ]
        ]);
    }

    /**
     * @group Admin/Favorites
     *
     * Get Stadium Favorites Stats
     *
     * Get favorite statistics for all stadiums.
     *
     * @authenticated
     *
     * @queryParam filter[user_id] integer Filter by stadium owner ID. Example: 3
     * @queryParam sort string Sort by fields. Example: -favorites_count
     * @queryParam per_page integer Number of items per page. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "City Stadium",
     *       "favorites_count": 25,
     *       "owner": {
     *         "id": 3,
     *         "name": "Stadium Owner"
     *       }
     *     }
     *   ],
     *   "links": { ... },
     *   "meta": { ... }
     * }
     */
    public function stadiumStats(Request $request)
    {
        $stadiums = QueryBuilder::for(Stadium::class)
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
            ])
            ->allowedSorts(['name', 'favorites_count'])
            ->withCount('favorites')
            ->with('user')
            ->paginate($request->per_page ?? 15);

        return $stadiums;
    }

    /**
     * @group Admin/Favorites
     *
     * Get Event Favorites Stats
     *
     * Get favorite statistics for all events.
     *
     * @authenticated
     *
     * @queryParam filter[user_id] integer Filter by event owner ID. Example: 3
     * @queryParam filter[stadium_id] integer Filter by stadium ID. Example: 1
     * @queryParam sort string Sort by fields. Example: -favorites_count
     * @queryParam per_page integer Number of items per page. Example: 15
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Weekend Tournament",
     *       "favorites_count": 15,
     *       "stadium": {
     *         "id": 1,
     *         "name": "City Stadium"
     *       },
     *       "owner": {
     *         "id": 3,
     *         "name": "Stadium Owner"
     *       }
     *     }
     *   ],
     *   "links": { ... },
     *   "meta": { ... }
     * }
     */
    public function eventStats(Request $request)
    {
        $events = QueryBuilder::for(Event::class)
            ->allowedFilters([
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('stadium_id'),
            ])
            ->allowedSorts(['name', 'date', 'favorites_count'])
            ->withCount('favorites')
            ->with(['stadium', 'user'])
            ->paginate($request->per_page ?? 15);

        return $events;
    }

    /**
     * @group Admin/Favorites
     *
     * Get Overall Favorites Statistics
     *
     * Get overall statistics about favorites in the system.
     *
     * @authenticated
     *
     * @response {
     *   "data": {
     *     "total_favorites": 150,
     *     "total_stadium_favorites": 90,
     *     "total_event_favorites": 60,
     *     "users_with_favorites": 45,
     *     "most_favorited_stadium": {
     *       "id": 1,
     *       "name": "City Stadium",
     *       "favorites_count": 25
     *     },
     *     "most_favorited_event": {
     *       "id": 1,
     *       "name": "Weekend Tournament",
     *       "favorites_count": 15
     *     }
     *   }
     * }
     */
    public function overallStats()
    {
        $totalFavorites = Favorite::count();
        $stadiumFavorites = Favorite::where('favoritable_type', Stadium::class)->count();
        $eventFavorites = Favorite::where('favoritable_type', Event::class)->count();
        $usersWithFavorites = Favorite::distinct('user_id')->count('user_id');

        $mostFavoritedStadium = Stadium::withCount('favorites')
            ->orderBy('favorites_count', 'desc')
            ->first();

        $mostFavoritedEvent = Event::withCount('favorites')
            ->orderBy('favorites_count', 'desc')
            ->first();

        return response()->json([
            'data' => [
                'total_favorites' => $totalFavorites,
                'total_stadium_favorites' => $stadiumFavorites,
                'total_event_favorites' => $eventFavorites,
                'users_with_favorites' => $usersWithFavorites,
                'most_favorited_stadium' => $mostFavoritedStadium ? [
                    'id' => $mostFavoritedStadium->id,
                    'name' => $mostFavoritedStadium->name,
                    'favorites_count' => $mostFavoritedStadium->favorites_count,
                ] : null,
                'most_favorited_event' => $mostFavoritedEvent ? [
                    'id' => $mostFavoritedEvent->id,
                    'name' => $mostFavoritedEvent->name,
                    'favorites_count' => $mostFavoritedEvent->favorites_count,
                ] : null,
            ]
        ]);
    }
}