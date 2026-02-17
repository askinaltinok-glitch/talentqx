<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobApplicationFile extends Model
{
    protected $fillable = [
        'job_application_id', 'type', 'path', 'original_name', 'mime', 'size',
    ];

    public function application()
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }
}
