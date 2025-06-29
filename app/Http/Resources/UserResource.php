<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'phone_number' => $this->phone_number,
            'type' => $this->type,
            'roles' => $this->getRoleNames(), 

            // Images
            'avatar' => $this->getMedia('avatar')->map(function ($media) {
                return new ImageResource($media);
            })->first(),
            'cover' => $this->getMedia('cover')->map(function ($media) {
                return new ImageResource($media);
            })->first(),

            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
