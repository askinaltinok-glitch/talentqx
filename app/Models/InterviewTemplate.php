<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InterviewTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'version',
        'language',
        'position_code',
        'title',
        'template_json',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get template as array (decode JSON)
     */
    public function getTemplateAttribute(): array
    {
        return json_decode($this->template_json, true) ?? [];
    }
}
