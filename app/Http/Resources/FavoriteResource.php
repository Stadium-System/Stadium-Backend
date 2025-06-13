<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Stadium;
use App\Models\Event;


class FavoriteResource extends JsonResource
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
            'favoritable_type' => class_basename($this->favoritable_type),
            'favoritable' => $this->when($this->relationLoaded('favoritable'), function () {
                if ($this->favoritable instanceof Stadium) {
                    return new StadiumResource($this->favoritable);
                } elseif ($this->favoritable instanceof Event) {
                    return new EventResource($this->favoritable);
                }
                return null;
            }),
            'user' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}