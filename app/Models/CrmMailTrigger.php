<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CrmMailTrigger extends Model
{
    use HasUuids;

    protected $table = 'crm_mail_triggers';

    protected $fillable = [
        'name', 'trigger_event', 'conditions', 'action_type', 'action_config', 'active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'action_config' => 'array',
        'active' => 'boolean',
    ];

    // Trigger events
    public const EVENT_NEW_COMPANY = 'new_company';
    public const EVENT_REPLY_RECEIVED = 'reply_received';
    public const EVENT_NO_REPLY = 'no_reply';
    public const EVENT_DEAL_STAGE_CHANGED = 'deal_stage_changed';
    public const EVENT_LEAD_CREATED = 'lead_created';

    public const EVENTS = [
        self::EVENT_NEW_COMPANY, self::EVENT_REPLY_RECEIVED,
        self::EVENT_NO_REPLY, self::EVENT_DEAL_STAGE_CHANGED,
        self::EVENT_LEAD_CREATED,
    ];

    // Action types
    public const ACTION_ENROLL_SEQUENCE = 'enroll_sequence';
    public const ACTION_SEND_TEMPLATE = 'send_template';
    public const ACTION_GENERATE_AI_REPLY = 'generate_ai_reply';
    public const ACTION_ADVANCE_LEAD_STAGE = 'advance_lead_stage';
    public const ACTION_CREATE_DEAL = 'create_deal';
    public const ACTION_ADVANCE_DEAL_STAGE = 'advance_deal_stage';

    public const ACTION_TYPES = [
        self::ACTION_ENROLL_SEQUENCE,
        self::ACTION_SEND_TEMPLATE,
        self::ACTION_GENERATE_AI_REPLY,
        self::ACTION_ADVANCE_LEAD_STAGE,
        self::ACTION_CREATE_DEAL,
        self::ACTION_ADVANCE_DEAL_STAGE,
    ];

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->where('trigger_event', $event);
    }
}
