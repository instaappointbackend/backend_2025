<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BlogResponse extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'thumbnail' => $this->attachment_type == 'video' ? asset('images/video.png') : asset('images/picture.png'),
            'attachment' => $this->attachment ? asset('storage/'.$this->attachment) : null,
            'attachment_type' => $this->attachment_type,
            'status' => $this->status,
            'author' => $this->user->name ?? 'Unknown',
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}
