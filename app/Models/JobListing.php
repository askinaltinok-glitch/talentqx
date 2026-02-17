<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
    protected $table = 'job_listings';

    protected $fillable = [
        'industry_code', 'title', 'slug', 'company_name', 'location', 'employment_type',
        'description', 'requirements', 'is_published', 'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function applications()
    {
        return $this->hasMany(JobApplication::class, 'job_listing_id');
    }
}
