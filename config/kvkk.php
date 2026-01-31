<?php

return [

    /*
    |--------------------------------------------------------------------------
    | KVKK Configuration
    |--------------------------------------------------------------------------
    */

    'consent_version' => env('KVKK_CONSENT_VERSION', '1.0'),

    'data_retention_days' => env('DATA_RETENTION_DAYS', 730), // 2 years

    'consent_types' => [
        'kvkk' => [
            'name' => 'KVKK Aydinlatma Metni',
            'required' => true,
        ],
        'video_recording' => [
            'name' => 'Video Kayit Izni',
            'required' => true,
        ],
        'data_processing' => [
            'name' => 'Veri Isleme Izni',
            'required' => true,
        ],
    ],

    'texts' => [
        'kvkk' => "6698 sayili Kisisel Verilerin Korunmasi Kanunu (KVKK) kapsaminda, mulakat surecinde " .
            "kayit altina alinan video ve ses verilerinizin, is basvurunuzun degerlendirilmesi " .
            "amaciyla islenmesine ve saklanmasina onay veriyorum. Bu verilerin sadece yetkilendirilmis " .
            "IK personeli tarafindan erisileceğini ve yasal saklama suresi sonunda silinecegini anliyorum.",

        'video_recording' => "Mulakat surecinde video ve ses kaydinin alinmasina onay veriyorum.",

        'data_processing' => "Kisisel verilerimin is basvuru surecinde islenmesine onay veriyorum.",
    ],

];
