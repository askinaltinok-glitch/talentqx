<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    protected $fillable = [
        'job_listing_id', 'candidate_id', 'full_name', 'email', 'phone',
        'country_code', 'city', 'role_code', 'department', 'source', 'status',
        'consent_terms', 'consent_contact', 'ip_hash', 'ua_hash',
    ];

    protected $casts = [
        'consent_terms' => 'boolean',
        'consent_contact' => 'boolean',
    ];

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }

    public function files()
    {
        return $this->hasMany(JobApplicationFile::class);
    }
}
