<?php

namespace Tests\Unit\Services\Maritime;

use App\Models\CandidateContract;
use App\Services\Ais\VesselTypeNormalizer;
use Tests\TestCase;

/**
 * Guard test: LNG command class must NEVER appear without explicit LNG experience.
 *
 * Regression test for potential LNG inference bug — ensures:
 * 1. VesselTypeNormalizer only maps to LNG_LPG for explicit LNG keywords
 * 2. Generic tanker / bulk / container text never resolves to LNG
 * 3. Unknown vessel types default to VESSEL_OTHER, never LNG_LPG
 */
class VesselTypeInferenceTest extends TestCase
{
    /**
     * Explicit LNG keywords should normalize to LNG_LPG.
     */
    public function test_explicit_lng_keywords_map_to_lng_lpg(): void
    {
        $normalizer = new VesselTypeNormalizer();

        $lngKeywords = ['lng', 'lpg', 'lng carrier', 'lpg carrier', 'gas carrier', 'lng/lpg'];

        foreach ($lngKeywords as $keyword) {
            $result = $normalizer->normalize($keyword);
            $this->assertEquals(
                CandidateContract::VESSEL_LNG_LPG,
                $result,
                "Keyword '{$keyword}' should map to VESSEL_LNG_LPG"
            );
        }
    }

    /**
     * Non-LNG vessel types must NEVER resolve to LNG_LPG.
     */
    public function test_non_lng_vessels_never_resolve_to_lng(): void
    {
        $normalizer = new VesselTypeNormalizer();

        $nonLngTypes = [
            'tanker',
            'bulk carrier',
            'container ship',
            'cargo vessel',
            'product tanker',
            'chemical tanker',
            'offshore',
            'tug',
            'passenger',
            'ferry',
            'cruise',
            'ro-ro',
            'general cargo',
            'reefer',
        ];

        foreach ($nonLngTypes as $type) {
            $result = $normalizer->normalize($type);
            $this->assertNotEquals(
                CandidateContract::VESSEL_LNG_LPG,
                $result,
                "Non-LNG type '{$type}' must NOT resolve to VESSEL_LNG_LPG, got: {$result}"
            );
        }
    }

    /**
     * Unknown / garbage vessel type strings must default to VESSEL_OTHER.
     */
    public function test_unknown_vessel_types_default_to_other(): void
    {
        $normalizer = new VesselTypeNormalizer();

        $unknownTypes = [
            'xyzboat',
            'random_vessel_123',
            '',
            'some unknown type',
        ];

        foreach ($unknownTypes as $type) {
            $result = $normalizer->normalize($type);
            $this->assertEquals(
                CandidateContract::VESSEL_OTHER,
                $result,
                "Unknown type '{$type}' should default to VESSEL_OTHER, got: {$result}"
            );
        }
    }
}
