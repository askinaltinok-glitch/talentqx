<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CrmEmailTemplate extends Model
{
    use HasUuids;

    protected $table = 'crm_email_templates';

    protected $fillable = [
        'key', 'industry_code', 'language',
        'subject', 'body_html', 'body_text', 'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForIndustry($query, string $code)
    {
        return $query->where('industry_code', $code);
    }

    public function scopeForLanguage($query, string $lang)
    {
        return $query->where('language', $lang);
    }

    public static function findTemplate(string $key, string $industry = 'general', string $language = 'en'): ?self
    {
        return self::where('key', $key)
            ->where('industry_code', $industry)
            ->where('language', $language)
            ->where('active', true)
            ->first();
    }

    public function render(array $vars): array
    {
        $subject = $this->subject;
        $html = $this->body_html;
        $text = $this->body_text;

        foreach ($vars as $key => $value) {
            $placeholder = '{' . $key . '}';
            $subject = str_replace($placeholder, $value, $subject);
            $html = str_replace($placeholder, $value, $html);
            $text = str_replace($placeholder, $value, $text);
        }

        return ['subject' => $subject, 'body_html' => $html, 'body_text' => $text];
    }
}
