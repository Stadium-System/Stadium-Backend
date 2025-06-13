<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StadiumResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            
            'images' => ImageResource::collection($this->getMedia('images')),

            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            
            'price_per_hour' => $this->price_per_hour,
            'capacity' => $this->capacity,
            'rating' => $this->rating,
            'status' => $this->status,

            'user' => new UserResource($this->whenLoaded('user')),

            'is_favorited' => $this->when(auth()->check(), function () {
                return $this->isFavoritedBy(auth()->user());
            }),
            
            'favorites_count' => $this->when(
                auth()->check() && auth()->user()->hasRole('owner') && auth()->id() === $this->user_id,
                function () {
                    return $this->favorites_count;
                }
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
