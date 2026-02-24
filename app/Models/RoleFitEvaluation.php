<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RoleFitEvaluation extends Model
{
    use HasUuids;

    protected $table = 'role_fit_evaluations';

    public $timestamps = false;

    protected $fillable = [
        'pool_candidate_id',
        'form_interview_id',
        'applied_role_key',
        'inferred_role_key',
        'role_fit_score',
        'mismatch_level',
        'mismatch_flags',
        'evidence',
        'created_at',
    ];

    protected $casts = [
        'role_fit_score' => 'float',
        'mismatch_flags' => 'array',
        'evidence' => 'array',
        'created_at' => 'datetime',
    ];

    public function isRoleMismatch(): bool
    {
        return $this->mismatch_level === 'strong';
    }
}
