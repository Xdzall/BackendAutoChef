<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StepResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // 'this' merujuk pada satu instance dari model Step
        return [
            'urutan' => $this->step_number,
            'instruksi' => $this->instruction,
        ];
    }
}