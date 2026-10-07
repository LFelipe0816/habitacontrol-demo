<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Requiere `readers` filtrado al usuario actual (ver NoticeController).
class NoticeResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category' => $this->category,
            'title' => $this->title,
            'date' => $this->created_at->translatedFormat('j M'),
            'body' => $this->body,
            'read' => $this->readers->isNotEmpty(),
        ];
    }
}
