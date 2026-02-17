<?php

namespace App\Models;

use App\Exceptions\ImmutableRecordException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormInterviewAnswer extends Model
{
    use HasUuids;

    protected $fillable = [
        'form_interview_id',
        'slot',
        'competency',
        'answer_text',
        'score',
    ];

    protected $casts = [
        'slot' => 'integer',
        'score' => 'integer',
    ];

    protected static function booted(): void
    {
        // Prevent updates to answers of completed interviews
        static::updating(function (FormInterviewAnswer $answer) {
            $interview = $answer->formInterview;

            if ($interview && $interview->isCompleted()) {
                throw new ImmutableRecordException(
                    "Cannot modify answer for completed interview",
                    $answer->id,
                    ['form_interview_id' => $answer->form_interview_id]
                );
            }
        });

        // Prevent deletion of answers from completed interviews
        static::deleting(function (FormInterviewAnswer $answer) {
            $interview = $answer->formInterview;

            if ($interview && $interview->isCompleted()) {
                throw new ImmutableRecordException(
                    "Cannot delete answer from completed interview",
                    $answer->id,
                    ['form_interview_id' => $answer->form_interview_id]
                );
            }
        });
    }

    public function formInterview(): BelongsTo
    {
        return $this->belongsTo(FormInterview::class);
    }
}
