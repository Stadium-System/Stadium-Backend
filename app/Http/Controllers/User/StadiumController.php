<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\StadiumResource;
use App\Models\Stadium;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use App\Http\Requests\User\Stadium\StoreStadiumRequest;
use App\Http\Requests\User\Stadium\UpdateStadiumRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;


class StadiumController extends Controller
{
    /**
     * @group User/Stadiums
     *
     * Stadium Catalog
     *
     * Retrieves a paginated list of stadiums with filtering options.
     *
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
     *       "price_per_hour": 100.00,
     *       "capacity": 12,
     *       "image": "https://example.com/stadium.jpg",
     *       "rating": 4.5,
     *       "status": "available",
     *       "created_at": "2023-01-01T00:00:00.000000Z",
     *       "updated_at": "2023-01-01T00:00:00.000000Z",
     *       "user": {
     *         "id": 1,
     *         "name": "John Doe"
     *       }
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
            ->allowedSorts(['name', 'price_per_hour', 'capacity', 'rating', 'created_at', 'updated_at'])
            ->defaultSort('id')
            ->with(['user', 'images'])
            ->paginate($request->per_page ?? 15)
            ->appends($request->query());

        return StadiumResource::collection($stadiums);
    }

    /**
     * @group User/Stadiums
     *
     * Create Stadium
     *
     * Creates a new stadium with the provided details.
     *
     * @authenticated
     *
     * @bodyParam name string required The name of the stadium. Example: Sunset Soccer Field
     * @bodyParam location string required The location of the stadium. Example: Downtown Sports Complex
     * @bodyParam latitude numeric required The latitude coordinates. Example: 25.276987
     * @bodyParam longitude numeric required The longitude coordinates. Example: 55.296249
     * @bodyParam price_per_hour numeric required The hourly rental price. Example: 150.00
     * @bodyParam capacity numeric The capacity of the stadium. Example: 12
     * @bodyParam description string The description of the stadium. Example: Full-size soccer field with floodlights
     * @bodyParam status string The status of the stadium (open or closed). Example: open
    * @bodyParam temp_upload_ids array The TempUpload IDs for the stadium images. Example: [1, 2]
    * @bodyParam temp_upload_ids.* integer The TempUpload ID for each image. Example: 1
    *
    * @response {
    *   "data": {
    *     "id": 1,
    *     "name": "ملعب توسي بارك السداسي",
    *     "location": "ملعب مدرسة شباب الإنتفاضة - مقابل جزيرة الجعب",
    *     "latitude": 25.276987,
    *     "longitude": 55.296249,
    *     "price_per_hour": 150.00,
    *     "capacity": 12,
    *     "images": [
    *       {
    *         "id": 1,
    *         "url": "https://your-s3-bucket-url.com/stadiums/1/image1.jpg"
    *       },
    *       {
    *         "id": 2,
    *         "url": "https://your-s3-bucket-url.com/stadiums/1/image2.jpg"
    *       }
    *     ],
    *     "description": "Full-size soccer field with floodlights",
    *     "rating": 0,
    *     "status": "open",
    *     "created_at": "2025-05-09T10:00:00.000000Z",
    *     "updated_at": "2025-05-09T10:00:00.000000Z",
    *     "user": {
    *       "id": 1,
    *       "name": "John Doe",
    *       "phone_number": "218912345678"
    *     }
    *   }
    * }
    */
    public function store(StoreStadiumRequest $request)
    {
        $validatedData = $request->validated();

        $user = auth()->user();
        $validatedData['user_id'] = $user->id;
        $validatedData['status'] = $validatedData['status'] ?? 'open';

        $stadium = Stadium::create($validatedData);

        // Handle image uploads if media_ids are provided
        if ($request->has('media_ids') && is_array($request->input('media_ids'))) {
            foreach ($request->input('media_ids') as $mediaId) {
                $media = Media::findOrFail($mediaId);
                $media->move($stadium, 'images');
            }
        }
        
        return new StadiumResource($stadium->load('user'));
    }

    /**
     * @group User/Stadiums
     *
     * Get Stadium Details
     *
     * Retrieves the details of a specific stadium.
     *
     * @urlParam id required The ID of the stadium. Example: 1
     *
     */
    public function show(Stadium $stadium)
    {   
        return new StadiumResource($stadium->load('user'));
    }

    /**
     * @group User/Stadiums
     *
     * Update Stadium
     *
     * Updates an existing stadium with the provided details.
     * Only the owner of the stadium can update it.
     *
     * @authenticated
     * @urlParam id required The ID of the stadium. Example: 1
     *
     * @bodyParam name string The name of the stadium. Example: Sunset Soccer Field Elite
     * @bodyParam location string The location of the stadium. Example: Downtown Sports Center
     * @bodyParam latitude numeric The latitude coordinates. Example: 25.276990
     * @bodyParam longitude numeric The longitude coordinates. Example: 55.296260
     * @bodyParam price_per_hour numeric The hourly rental price. Example: 175.00
     * @bodyParam capacity numeric The capacity of the stadium. Example: 24
     * @bodyParam description string The description of the stadium. Example: Premium soccer field with professional lighting
     * @bodyParam status string The status of the stadium (open or closed). Example: open
     *
     * @response {
     *   "data": {
     *     "id": 1,
     *     "name": "Sunset Soccer Field Elite",
     *     "location": "Downtown Sports Center",
     *     "latitude": 25.276990,
     *     "longitude": 55.296260,
     *     "price_per_hour": 175.00,
     *     "capacity": 24,
     *     "image": null,
     *     "description": "Premium soccer field with professional lighting",
     *     "rating": 4.5,
     *     "status": "open",
     *     "created_at": "2025-05-09T10:00:00.000000Z",
     *     "updated_at": "2025-05-09T11:30:00.000000Z",
     *     "user": {
     *       "id": 1,
     *       "name": "John Doe"
     *       "phone_number": "218912345678",

     *     }
     *   }
     * }
     */
    public function update(UpdateStadiumRequest $request, Stadium $stadium)
    {   
        $stadium->update($request->validated());

        // Handle image uploads if media_ids are provided
        if ($request->has('media_ids')) {
            foreach ($request->input('media_ids') as $mediaId) {
                $media = Media::findOrFail($mediaId);
                $media->move($stadium, 'images');
            }
        }
        
        return new StadiumResource($stadium->fresh()->load('user', 'images'));
    }

    /**
     * @group User/Stadiums
     *
     * Delete Stadium
     *
     * Deletes a stadium. Only the owner of the stadium can delete it.
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
        if ($stadium->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $stadium->delete();
        
        return response()->json(['message' => 'Stadium deleted successfully']);
    }

    /**
     * @group User/Stadiums
     *
     * Remove Stadium Image
     *
     * Removes an image from a stadium. Only the owner of the stadium can remove images.
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
        // Check if user owns the stadium
        if ($stadium->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $image = $stadium->images()->findOrFail($imageId);
        
        Storage::disk('s3')->delete($image->url);
        
        $image->delete();
        
        return response()->json(['message' => 'Image removed successfully']);
    }
}
