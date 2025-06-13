<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
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
            'date' => $this->date,
            
            'images' => ImageResource::collection($this->getMedia('images')),
            
            'status' => $this->status,
            
            'stadium' => new StadiumResource($this->whenLoaded('stadium')),
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