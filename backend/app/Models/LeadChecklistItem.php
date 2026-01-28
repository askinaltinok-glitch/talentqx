<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadChecklistItem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lead_id',
        'stage',
        'item',
        'is_completed',
        'completed_at',
        'completed_by',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // Default checklist items
    public const DEFAULT_CHECKLIST = [
        'discovery' => [
            'Firma büyüklüğü ve yapısı öğrenildi',
            'Mevcut işe alım süreci anlaşıldı',
            'Acı noktaları belirlendi',
            'Bütçe ve karar verici netleştirildi',
            'Rekabet analizi yapıldı',
        ],
        'demo' => [
            'Pozisyon şablonu hazırlandı',
            'Demo ortamı kuruldu',
            'Demo sunumu yapıldı',
            'Sorular yanıtlandı',
            'Pilot teklifi gönderildi',
        ],
        'pilot' => [
            'Pilot sözleşmesi imzalandı',
            'Hesap açıldı',
            'Eğitim verildi',
            'İlk değerlendirmeler yapıldı',
            'Haftalık takip yapıldı',
            'Pilot sonuç raporu hazırlandı',
        ],
        'closing' => [
            'Pilot değerlendirme toplantısı yapıldı',
            'Tam teklif gönderildi',
            'Pazarlık tamamlandı',
            'Sözleşme imzalandı',
        ],
    ];

    // Relationships
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // Methods
    public function markCompleted(?string $userId = null): void
    {
        $this->is_completed = true;
        $this->completed_at = now();
        $this->completed_by = $userId;
        $this->save();
    }

    public static function createDefaultForLead(Lead $lead): void
    {
        foreach (self::DEFAULT_CHECKLIST as $stage => $items) {
            foreach ($items as $item) {
                self::create([
                    'lead_id' => $lead->id,
                    'stage' => $stage,
                    'item' => $item,
                ]);
            }
        }
    }
}
