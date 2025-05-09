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
            'image' => $this->image,

            'location' => $this->location,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            
            'price_per_hour' => $this->price_per_hour,
            'capacity' => $this->capacity,
            'rating' => $this->rating,
            'status' => $this->status,

            'user' => new UserResource($this->whenLoaded('user')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
          ];
    }
}
