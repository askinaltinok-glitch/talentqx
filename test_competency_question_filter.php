<?php
/**
 * Competency Question Filtering — Acceptance Tests
 *
 * Verifies:
 *  1. RankToRoleScopeMapper correctly maps all canonical ranks
 *  2. Non-MASTER candidates never receive MASTER-scoped questions
 *  3. MASTER candidates DO receive MASTER + ALL questions
 *  4. scopeForRole('ALL') returns only generic questions
 *  5. Category labels don't leak into interview UI source code
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\CompetencyQuestion;
use App\Services\Competency\RankToRoleScopeMapper;

$pass = 0;
$fail = 0;

function ok(bool $condition, string $label): void
{
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "  ✓ {$label}\n";
    } else {
        $fail++;
        echo "  ✗ FAIL: {$label}\n";
    }
}

echo "=== Competency Question Filtering — Acceptance Tests ===\n\n";

// ──────────────────────────────────────────────
// SECTION 1: RankToRoleScopeMapper — canonical ranks
// ──────────────────────────────────────────────
echo "── Section 1: RankToRoleScopeMapper ──\n";

// Master/Captain → MASTER
ok(RankToRoleScopeMapper::map('MASTER') === 'MASTER', "MASTER → MASTER");
ok(RankToRoleScopeMapper::map('master') === 'MASTER', "master (lowercase) → MASTER");
ok(RankToRoleScopeMapper::map('Captain') === 'MASTER', "Captain → MASTER");
ok(RankToRoleScopeMapper::map('CAPT') === 'MASTER', "CAPT → MASTER");

// Officers → correct scope (NOT MASTER)
ok(RankToRoleScopeMapper::map('C/O') === 'CHIEF_MATE', "C/O → CHIEF_MATE");
ok(RankToRoleScopeMapper::map('chief officer') === 'CHIEF_MATE', "chief officer → CHIEF_MATE");
ok(RankToRoleScopeMapper::map('CHIEF_MATE') === 'CHIEF_MATE', "CHIEF_MATE (direct) → CHIEF_MATE");
ok(RankToRoleScopeMapper::map('2/O') === 'OOW', "2/O → OOW");
ok(RankToRoleScopeMapper::map('3/O') === 'OOW', "3/O → OOW");

// Ratings → correct scope
ok(RankToRoleScopeMapper::map('AB') === 'AB', "AB → AB");
ok(RankToRoleScopeMapper::map('OS') === 'AB', "OS → AB");
ok(RankToRoleScopeMapper::map('BSN') === 'AB', "BSN → AB");

// Engine
ok(RankToRoleScopeMapper::map('C/E') === 'CHIEF_ENG', "C/E → CHIEF_ENG");
ok(RankToRoleScopeMapper::map('2/E') === '2ND_ENG', "2/E → 2ND_ENG");
ok(RankToRoleScopeMapper::map('3/E') === '2ND_ENG', "3/E → 2ND_ENG");
ok(RankToRoleScopeMapper::map('OL') === 'OILER', "OL → OILER");

// Catering
ok(RankToRoleScopeMapper::map('COOK') === 'COOK', "COOK → COOK");
ok(RankToRoleScopeMapper::map('CH.COOK') === 'COOK', "CH.COOK → COOK");
ok(RankToRoleScopeMapper::map('STEWARD') === 'COOK', "STEWARD → COOK");

// Unknown → ALL (fallback)
ok(RankToRoleScopeMapper::map('UNKNOWN_RANK') === 'ALL', "Unknown rank → ALL");
ok(RankToRoleScopeMapper::map('') === 'ALL', "Empty string → ALL");

// NON-NEGOTIABLE: Non-master ranks MUST NOT map to MASTER
$nonMasterRanks = ['C/O', '2/O', '3/O', 'AB', 'OS', 'BSN', 'C/E', '2/E', '3/E', '4/E', 'OL', 'MO', 'COOK', 'STEWARD', 'ETO'];
$masterLeakCount = 0;
foreach ($nonMasterRanks as $rank) {
    $mapped = RankToRoleScopeMapper::map($rank);
    if ($mapped === 'MASTER') {
        $masterLeakCount++;
        echo "    LEAK: {$rank} maps to MASTER!\n";
    }
}
ok($masterLeakCount === 0, "No non-master rank maps to MASTER scope ({$masterLeakCount} leaks)");

// ──────────────────────────────────────────────
// SECTION 2: scopeForRole — query filtering
// ──────────────────────────────────────────────
echo "\n── Section 2: scopeForRole query filtering ──\n";

$allQuestions = CompetencyQuestion::where('is_active', true)->get();
$masterQuestions = CompetencyQuestion::where('is_active', true)->where('role_scope', 'MASTER')->get();
$genericQuestions = CompetencyQuestion::where('is_active', true)->where('role_scope', 'ALL')->get();

echo "    Total active questions: {$allQuestions->count()}\n";
echo "    MASTER-scoped questions: {$masterQuestions->count()}\n";
echo "    ALL-scoped questions: {$genericQuestions->count()}\n";

// scopeForRole('ALL') → only generic questions (NO master)
$forAll = CompetencyQuestion::query()->active()->forRole('ALL')->get();
$masterInAll = $forAll->filter(fn($q) => $q->role_scope === 'MASTER');
ok($masterInAll->count() === 0, "forRole('ALL') returns NO MASTER-scoped questions");
ok($forAll->count() === $genericQuestions->count(), "forRole('ALL') returns exactly the generic questions ({$forAll->count()})");

// scopeForRole('MASTER') → MASTER + ALL questions
$forMaster = CompetencyQuestion::query()->active()->forRole('MASTER')->get();
$expectedMasterCount = $masterQuestions->count() + $genericQuestions->count();
ok($forMaster->count() === $expectedMasterCount, "forRole('MASTER') returns MASTER + ALL questions ({$forMaster->count()} = {$expectedMasterCount})");

// scopeForRole('AB') → AB + ALL questions (no MASTER)
$forAb = CompetencyQuestion::query()->active()->forRole('AB')->get();
$masterInAb = $forAb->filter(fn($q) => $q->role_scope === 'MASTER');
ok($masterInAb->count() === 0, "forRole('AB') returns NO MASTER-scoped questions");
// AB questions = AB-scoped (if any) + ALL-scoped
$abScoped = CompetencyQuestion::where('is_active', true)->where('role_scope', 'AB')->count();
ok($forAb->count() === $abScoped + $genericQuestions->count(), "forRole('AB') returns AB + ALL questions ({$forAb->count()})");

// scopeForRole('COOK') → COOK + ALL questions (no MASTER)
$forCook = CompetencyQuestion::query()->active()->forRole('COOK')->get();
$masterInCook = $forCook->filter(fn($q) => $q->role_scope === 'MASTER');
ok($masterInCook->count() === 0, "forRole('COOK') returns NO MASTER-scoped questions");

// ──────────────────────────────────────────────
// SECTION 3: End-to-end — AB candidate gets NO MASTER questions
// ──────────────────────────────────────────────
echo "\n── Section 3: End-to-end filtering ──\n";

// Simulate what CompetencyScorer does for an AB candidate
$abScope = RankToRoleScopeMapper::map('AB');
$abQuestions = CompetencyQuestion::query()
    ->active()
    ->forRole($abScope)
    ->forVessel('all')
    ->forOperation('both')
    ->with('dimension')
    ->get();

$masterInAbE2e = $abQuestions->filter(fn($q) => $q->role_scope === 'MASTER');
ok($masterInAbE2e->count() === 0, "AB candidate: 0 MASTER questions loaded (got: {$masterInAbE2e->count()})");
ok($abQuestions->count() > 0, "AB candidate: some questions loaded (got: {$abQuestions->count()})");

// Simulate for OILER
$oilerScope = RankToRoleScopeMapper::map('OL');
$oilerQuestions = CompetencyQuestion::query()
    ->active()
    ->forRole($oilerScope)
    ->forVessel('all')
    ->forOperation('both')
    ->get();
$masterInOiler = $oilerQuestions->filter(fn($q) => $q->role_scope === 'MASTER');
ok($masterInOiler->count() === 0, "OILER candidate: 0 MASTER questions loaded");

// Simulate for COOK
$cookScope = RankToRoleScopeMapper::map('COOK');
$cookQuestions = CompetencyQuestion::query()
    ->active()
    ->forRole($cookScope)
    ->forVessel('all')
    ->forOperation('both')
    ->get();
$masterInCookE2e = $cookQuestions->filter(fn($q) => $q->role_scope === 'MASTER');
ok($masterInCookE2e->count() === 0, "COOK candidate: 0 MASTER questions loaded");

// MASTER should get MASTER questions
$masterScope = RankToRoleScopeMapper::map('MASTER');
$masterQs = CompetencyQuestion::query()
    ->active()
    ->forRole($masterScope)
    ->forVessel('all')
    ->forOperation('both')
    ->get();
$masterSpecific = $masterQs->filter(fn($q) => $q->role_scope === 'MASTER');
ok($masterSpecific->count() > 0, "MASTER candidate: MASTER questions included (got: {$masterSpecific->count()})");
ok($masterQs->count() > $abQuestions->count(), "MASTER gets more questions than AB ({$masterQs->count()} > {$abQuestions->count()})");

// ──────────────────────────────────────────────
// SECTION 4: Category label hiding — string scan
// ──────────────────────────────────────────────
echo "\n── Section 4: Category label hiding in interview UI ──\n";

$uiFile = '/www/wwwroot/talentqx-frontend/src/app/interviews/[id]/page.tsx';
$content = file_get_contents($uiFile);

// These category labels MUST NOT appear in the rendered output
$forbiddenLabels = [
    'İletişim',
    'Sorumluluk',
    'Takım Çalışması',
    'Stres Dayanıklılığı',
    'Uyum',
    'Öğrenme Çevikliği',
    'Dürüstlük',
    'Rol Yetkinliği',
];

$leaks = [];
foreach ($forbiddenLabels as $label) {
    if (str_contains($content, $label)) {
        $leaks[] = $label;
    }
}

ok(count($leaks) === 0, "No category labels found in interview UI (" . (count($leaks) > 0 ? implode(', ', $leaks) : "clean") . ")");

// Badge component should not be imported
ok(!str_contains($content, "from \"@/components/ui/badge\""), "Badge component not imported in interview page");
ok(!str_contains($content, '<Badge'), "No <Badge> rendered in interview page");

// Neutral title present
ok(str_contains($content, 'Octopus Maritime'), "Neutral 'Octopus Maritime' title present");
ok(str_contains($content, 'Soru {step}'), "Neutral 'Soru {step}' label present");

// ──────────────────────────────────────────────
// SUMMARY
// ──────────────────────────────────────────────
echo "\n========================================\n";
echo "RESULTS: {$pass} passed, {$fail} failed (total: " . ($pass + $fail) . ")\n";
echo "========================================\n";

exit($fail > 0 ? 1 : 0);
