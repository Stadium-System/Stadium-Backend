<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Stadium;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Requests\User\Event\StoreEventRequest;
use App\Http\Requests\User\Event\UpdateEventRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * @group User/Events
     *
     * Event Catalog
     *
     * Retrieves a paginated list of events with filtering options.
     *
     * @queryParam filter[name] string Optional event name filter.
     * @queryParam filter[stadium_id] integer Optional stadium ID filter.
     * @queryParam filter[user_id] integer Optional owner's user ID filter.
     * @queryParam filter[status] string Optional status filter (active, inactive).
     * @queryParam filter[date_from] date Optional minimum date filter.
     * @queryParam filter[date_to] date Optional maximum date filter.
     * @queryParam sort string Optional sort parameter. Prefix with "-" for descending order.
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Weekend Tournament",
     *       "description": "5v5 football tournament",
     *       "date": "2025-06-15T18:00:00.000000Z",
     *       "images": [
     *         {
     *           "id": 1,
     *           "url": "https://example.com/event-image.jpg"
     *         }
     *       ],
     *       "status": "active",
     *       "stadium": {
     *         "id": 1,
     *         "name": "City Stadium"
     *       },
     *       "user": {
     *         "id": 1,
     *         "name": "John Doe"
     *       },
     *       "created_at": "2025-05-01T00:00:00.000000Z",
     *       "updated_at": "2025-05-01T00:00:00.000000Z"
     *     }
     *   ],
     *   "links": { ... },
     *   "meta": { ... }
     * }
     */
    public function index(Request $request)
    {
        $events = QueryBuilder::for(Event::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::exact('stadium_id'),
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('date_from', fn($query, $value) => 
                    $query->where('date', '>=', $value)),
                AllowedFilter::callback('date_to', fn($query, $value) => 
                    $query->where('date', '<=', $value)),
            ])
            ->allowedSorts(['name', 'date', 'created_at', 'updated_at'])
            ->defaultSort('date')
            ->with(['stadium', 'user'])
            ->paginate($request->per_page ?? 15)
            ->appends($request->query());

        return EventResource::collection($events);
    }

    /**
     * @group User/Events
     *
     * Create Event
     *
     * Creates a new event for a stadium owned by the authenticated user.
     *
     * @authenticated
     *
     * @bodyParam name string required The name of the event. Example: Weekend Tournament
     * @bodyParam description string required The description of the event. Example: 5v5 football tournament with prizes
     * @bodyParam date datetime required The date and time of the event. Must be in the future. Example: 2025-06-15 18:00:00
     * @bodyParam stadium_id integer required The ID of the stadium where the event will be held. Must be owned by the user. Example: 1
     * @bodyParam status string The status of the event (active or inactive). Example: active
     * @bodyParam media_ids array Optional array of media IDs for event images. Example: [1, 2]
     * @bodyParam media_ids.* integer The media ID for each image. Example: 1
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "name": "Weekend Tournament",
     *     "description": "5v5 football tournament with prizes",
     *     "date": "2025-06-15T18:00:00.000000Z",
     *     "images": [
     *       {
     *         "id": 1,
     *         "url": "https://your-s3-bucket-url.com/events/1/image1.jpg"
     *       }
     *     ],
     *     "status": "active",
     *     "stadium": {
     *       "id": 1,
     *       "name": "City Stadium"
     *     },
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe"
     *     },
     *     "created_at": "2025-05-01T10:00:00.000000Z",
     *     "updated_at": "2025-05-01T10:00:00.000000Z"
     *   }
     * }
     */
    public function store(StoreEventRequest $request)
    {
        $validatedData = $request->validated();
        
        $user = auth()->user();
        $validatedData['user_id'] = $user->id;
        $validatedData['status'] = $validatedData['status'] ?? 'active';

        $event = Event::create($validatedData);

        // Handle image uploads if media_ids are provided
        if ($request->has('media_ids') && is_array($request->input('media_ids'))) {
            foreach ($request->input('media_ids') as $mediaId) {
                $media = Media::findOrFail($mediaId);
                $media->move($event, 'images');
            }
        }
        
        return new EventResource($event->load(['stadium', 'user']));
    }

    /**
     * @group User/Events
     *
     * Get Event Details
     *
     * Retrieves the details of a specific event.
     *
     * @urlParam id required The ID of the event. Example: 1
     */
    public function show(Event $event)
    {
        return new EventResource($event->load(['stadium', 'user']));
    }

    /**
     * @group User/Events
     *
     * Update Event
     *
     * Updates an existing event. Only the owner of the event can update it.
     *
     * @authenticated
     * @urlParam id required The ID of the event. Example: 1
     *
     * @bodyParam name string The name of the event. Example: Updated Tournament Name
     * @bodyParam description string The description of the event. Example: Updated tournament description
     * @bodyParam date datetime The date and time of the event. Must be in the future. Example: 2025-06-20 19:00:00
     * @bodyParam status string The status of the event (active or inactive). Example: active
     * @bodyParam media_ids array Optional array of media IDs for event images. Example: [3, 4]
     * @bodyParam media_ids.* integer The media ID for each image. Example: 3
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "name": "Updated Tournament Name",
     *     "description": "Updated tournament description",
     *     "date": "2025-06-20T19:00:00.000000Z",
     *     "images": [
     *       {
     *         "id": 3,
     *         "url": "https://your-s3-bucket-url.com/events/1/image3.jpg"
     *       }
     *     ],
     *     "status": "active",
     *     "stadium": {
     *       "id": 1,
     *       "name": "City Stadium"
     *     },
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe"
     *     },
     *     "created_at": "2025-05-01T10:00:00.000000Z",
     *     "updated_at": "2025-05-01T11:30:00.000000Z"
     *   }
     * }
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $event->update($request->validated());

        // Handle image uploads if media_ids are provided
        if ($request->has('media_ids') && is_array($request->input('media_ids'))) {
            foreach ($request->input('media_ids') as $mediaId) {
                $media = Media::findOrFail($mediaId);
                $media->move($event, 'images');
            }
        }
        
        return new EventResource($event->fresh()->load(['stadium', 'user']));
    }

    /**
     * @group User/Events
     *
     * Delete Event
     *
     * Deletes an event. Only the owner of the event can delete it.
     *
     * @authenticated
     * @urlParam id required The ID of the event. Example: 1
     *
     * @response {
     *   "message": "Event deleted successfully"
     * }
     */
    public function destroy(Event $event)
    {
        if ($event->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $event->delete();
        
        return response()->json(['message' => 'Event deleted successfully']);
    }

    /**
     * @group User/Events
     *
     * Remove Event Image
     *
     * Removes an image from an event. Only the owner of the event can remove images.
     *
     * @authenticated
     * @urlParam event integer required The ID of the event. Example: 1
     * @urlParam image integer required The ID of the image to remove. Example: 2
     *
     * @response {
     *   "message": "Image removed successfully"
     * }
     */
    public function removeImage(Event $event, $imageId)
    {
        // Check if user owns the event
        if ($event->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $media = $event->getMedia('images')->where('id', $imageId)->first();
        
        if (!$media) {
            return response()->json(['message' => 'Image not found'], 404);
        }
        
        $media->delete();
        
        return response()->json(['message' => 'Image removed successfully']);
    }
}