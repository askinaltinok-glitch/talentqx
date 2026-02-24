<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$interview = App\Models\FormInterview::find('a1199b85-bc52-4adb-be7e-4796cc831f50');
$answers = $interview->answers()->get();

foreach ($answers as $a) {
    echo "=== [{$a->competency}] (slot {$a->slot_index}) ===\n";
    echo $a->answer_text . "\n\n";
}
