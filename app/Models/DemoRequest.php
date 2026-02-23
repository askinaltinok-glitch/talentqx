<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class DemoRequest extends Model
{
    use HasUuids;

    protected $fillable = [
        'full_name',
        'company',
        'email',
        'country',
        'message',
        'locale',
        'source',
        'ip',
        'user_agent',
    ];
}
