<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\StadiumResource;
use App\Models\Stadium;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Requests\Admin\Stadium\StoreStadiumRequest;
use App\Http\Requests\Admin\Stadium\UpdateStadiumRequest;
use App\Models\User;

class StadiumController extends Controller
{
    /**
     * @group Admin/Stadiums
     *
     * Stadium Management
     *
     * Retrieves a paginated list of all stadiums with filtering options.
     *
     * @authenticated
     *
     * @queryParam filter[name] string Optional stadium name filter.
     * @queryParam filter[location] string Optional stadium location filter.
     * @queryParam filter[min_price_per_hour] float Optional minimum price per hour filter.
     * @queryParam filter[max_price_per_hour] float Optional maximum price per hour filter.
     * @queryParam filter[min_capacity] integer Optional minimum capacity filter.
     * @queryParam filter[max_capacity] integer Optional maximum capacity filter.
     * @queryParam filter[status] string Optional status filter.
     * @queryParam filter[min_rating] float Optional minimum rating filter.
     * @queryParam filter[max_rating] float Optional maximum rating filter.
     * @queryParam filter[user_id] integer Optional owner's user ID filter.
     * @queryParam sort string Optional sort parameter. Prefix with "-" for descending order.
     *
     * @response {
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "City Stadium",
     *       "location": "Downtown",
     *       "latitude": 25.276987,
     *       "longitude": 55.296249,
     *       "price_per_hour": 100.00,
     *       "capacity": 12,
     *       "image": "https://example.com/stadium.jpg",
     *       "description": "Professional stadium with amenities",
     *       "rating": 4.5,
     *       "status": "open",
     *       "created_at": "2023-01-01T00:00:00.000000Z",
     *       "updated_at": "2023-01-01T00:00:00.000000Z",
     *       "user": {
     *         "id": 1,
     *         "name": "John Doe"
     *       },
     *       "images": [
     *         {
     *           "id": 1,
     *           "url": "https://example.com/image1.jpg"
     *         },
     *         {
     *           "id": 2,
     *           "url": "https://example.com/image2.jpg"
     *         }
     *       ]
     *     }
     *   ],
     *   "links": { ... },
     *   "meta": { ... }
     * }
     */
    public function index(Request $request)
    {
        $stadiums = QueryBuilder::for(Stadium::class)
            ->allowedFilters([
                AllowedFilter::partial('name'),
                AllowedFilter::partial('location'),
                AllowedFilter::callback('min_price_per_hour', fn($query, $value) => 
                    $query->where('price_per_hour', '>=', (float)$value)),
                AllowedFilter::callback('max_price_per_hour', fn($query, $value) => 
                    $query->where('price_per_hour', '<=', (float)$value)),
                AllowedFilter::callback('min_capacity', fn($query, $value) => 
                    $query->where('capacity', '>=', (int)$value)),
                AllowedFilter::callback('max_capacity', fn($query, $value) => 
                    $query->where('capacity', '<=', (int)$value)),
                AllowedFilter::exact('status'),
                AllowedFilter::callback('min_rating', fn($query, $value) => 
                    $query->where('rating', '>=', (float)$value)),
                AllowedFilter::callback('max_rating', fn($query, $value) => 
                    $query->where('rating', '<=', (float)$value)),
                AllowedFilter::exact('user_id'),
            ])
            ->allowedSorts(['name', 'price_per_hour', 'capacity', 'rating', 'created_at', 'updated_at', 'status'])
            ->defaultSort('id')
            ->with(['user', 'images'])
            ->paginate($request->per_page ?? 15)
            ->appends($request->query());

        return StadiumResource::collection($stadiums);
    }

    /**
     * @group Admin/Stadiums
     *
     * Create Stadium
     *
     * Creates a new stadium with the provided details.
     *
     * @authenticated
     *
     * @bodyParam name string required The name of the stadium. Example: Olympic Stadium
     * @bodyParam location string required The location of the stadium. Example: Sports City
     * @bodyParam latitude numeric required The latitude coordinates. Example: 25.276987
     * @bodyParam longitude numeric required The longitude coordinates. Example: 55.296249
     * @bodyParam price_per_hour numeric required The hourly rental price. Example: 200.00
     * @bodyParam capacity numeric The capacity of the stadium. Example: 30
     * @bodyParam description string The description of the stadium. Example: Professional stadium with amenities
     * @bodyParam status string The status of the stadium (open or closed). Example: open
     * @bodyParam user_id integer required The ID of the owner. Example: 5
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "name": "Olympic Stadium",
     *     "location": "Sports City",
     *     "latitude": 25.276987,
     *     "longitude": 55.296249,
     *     "price_per_hour": 200.00,
     *     "capacity": 30,
     *     "image": null,
     *     "description": "Professional stadium with amenities",
     *     "rating": 0,
     *     "status": "open",
     *     "created_at": "2025-05-09T10:00:00.000000Z",
     *     "updated_at": "2025-05-09T10:00:00.000000Z",
     *     "user": {
     *       "id": 5,
     *       "name": "Stadium Owner"
     *     }
     *   }
     * }
     */
    public function store(StoreStadiumRequest $request)
    {
        $validatedData = $request->validated();
        $validatedData['user_id'] = $validatedData['user_id'] ?? User::first()->id;

        $stadium = Stadium::create($validatedData);

         // Handle temp upload IDs if provided
        if ($request->filled('temp_upload_ids')) {
            $stadium->imagesFromTempUploads($request->temp_upload_ids, 'stadiums');
        }
        
        return new StadiumResource($stadium->load('user', 'images'));
    }

    /**
     * @group Admin/Stadiums
     *
     * Get Stadium Details
     *
     * Retrieves the details of a specific stadium.
     *
     * @authenticated
     * @urlParam id required The ID of the stadium. Example: 1
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "name": "Olympic Stadium",
     *     "location": "Sports City",
     *     "latitude": 25.276987,
     *     "longitude": 55.296249,
     *     "price_per_hour": 200.00,
     *     "capacity": 30,
     *     "image": null,
     *     "description": "Professional stadium with amenities",
     *     "rating": 4.2,
     *     "status": "open",
     *     "created_at": "2025-05-09T10:00:00.000000Z",
     *     "updated_at": "2025-05-09T10:00:00.000000Z",
     *     "user": {
     *       "id": 5,
     *       "name": "Stadium Owner"
     *     }
     *   }
     * }
     */
    public function show(Stadium $stadium)
    {
        return new StadiumResource($stadium->load('user', 'images'));
    }

    /**
     * @group Admin/Stadiums
     *
     * Update Stadium
     *
     * Updates an existing stadium with the provided details.
     *
     * @authenticated
     * @urlParam id required The ID of the stadium. Example: 1
     *
     * @bodyParam name string The name of the stadium. Example: Olympic Stadium Premium
     * @bodyParam location string The location of the stadium. Example: Sports City Center
     * @bodyParam latitude numeric The latitude coordinates. Example: 25.276990
     * @bodyParam longitude numeric The longitude coordinates. Example: 55.296260
     * @bodyParam price_per_hour numeric The hourly rental price. Example: 225.00
     * @bodyParam capacity numeric The capacity of the stadium. Example: 35
     * @bodyParam description string The description of the stadium. Example: Premium stadium with VIP facilities
     * @bodyParam status string The status of the stadium (open or closed). Example: open
     * @bodyParam rating numeric The rating of the stadium (0-5). Example: 4.8
     * @bodyParam user_id integer The ID of the owner. Example: 7
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "name": "Olympic Stadium Premium",
     *     "location": "Sports City Center",
     *     "latitude": 25.276990,
     *     "longitude": 55.296260,
     *     "price_per_hour": 225.00,
     *     "capacity": 35,
     *     "image": null,
     *     "description": "Premium stadium with VIP facilities",
     *     "rating": 4.8,
     *     "status": "open",
     *     "created_at": "2025-05-09T10:00:00.000000Z",
     *     "updated_at": "2025-05-09T11:30:00.000000Z",
     *     "user": {
     *       "id": 7,
     *       "name": "New Stadium Owner"
     *     }
     *   }
     * }
     */
    public function update(UpdateStadiumRequest $request, Stadium $stadium)
    {
        $stadium->update($request->validated());

         // Handle temp upload IDs if provided
        if ($request->filled('temp_upload_ids')) {
            $stadium->imagesFromTempUploads($request->temp_upload_ids, 'stadiums');
        }

        return new StadiumResource($stadium->fresh()->load('user', 'images'));
    }

    /**
     * @group Admin/Stadiums
     *
     * Delete Stadium
     *
     * Deletes a stadium.
     *
     * @authenticated
     * @urlParam id required The ID of the stadium. Example: 1
     *
     * @response {
     *   "message": "Stadium deleted successfully"
     * }
     */
    public function destroy(Stadium $stadium)
    {
        $stadium->delete();
        
        return response()->json(['message' => 'Stadium deleted successfully']);
    }

    /**
     * @group Admin/Stadiums
     *
     * Remove Stadium Image
     *
     * Removes an image from a stadium.
     *
     * @authenticated
     * @urlParam stadium integer required The ID of the stadium. Example: 1
     * @urlParam image integer required The ID of the image to remove. Example: 2
     *
     * @response {
     *   "message": "Image removed successfully"
     * }
     */
    public function removeImage(Stadium $stadium, $imageId)
    {
        $image = $stadium->images()->find($imageId);
        
        if (!$image) {
            return response()->json(['message' => 'Image not found'], 404);
        }
        
        // Delete the image from S3
        Storage::disk('s3')->delete($image->url);
        
        // Delete the image record
        $image->delete();
        
        return response()->json(['message' => 'Image removed successfully']);
    }
}
