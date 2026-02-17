<?php

namespace App\Services\Copilot;

/**
 * RedFlagActionService
 *
 * Maps red flags to recommended actions based on configuration.
 * Provides action suggestions without making decisions.
 */
class RedFlagActionService
{
    private array $flagConfig;
    private array $actionConfig;
    private array $riskLevelActions;

    public function __construct()
    {
        $this->flagConfig = config('red_flags.flags', []);
        $this->actionConfig = config('red_flags.actions', []);
        $this->riskLevelActions = config('red_flags.risk_level_actions', []);
    }

    /**
     * Get recommended actions for a set of red flags.
     *
     * @param array $redFlags Array of red flag objects with 'code' key
     * @param string $riskLevel Overall risk level (none, low, medium, high)
     * @return array Structured actions response
     */
    public function getRecommendedActions(array $redFlags, string $riskLevel = 'none'): array
    {
        $actions = [];
        $seenActions = [];

        // Collect actions from each flag
        foreach ($redFlags as $flag) {
            $code = $flag['code'] ?? null;
            if (!$code || !isset($this->flagConfig[$code])) {
                continue;
            }

            $flagDef = $this->flagConfig[$code];
            foreach ($flagDef['actions'] as $actionCode) {
                if (!isset($seenActions[$actionCode])) {
                    $seenActions[$actionCode] = true;
                    $actions[] = $this->buildActionItem($actionCode, $code);
                }
            }
        }

        // Add risk level default actions if no specific actions
        if (empty($actions) && isset($this->riskLevelActions[$riskLevel])) {
            foreach ($this->riskLevelActions[$riskLevel] as $actionCode) {
                if (!isset($seenActions[$actionCode])) {
                    $seenActions[$actionCode] = true;
                    $actions[] = $this->buildActionItem($actionCode, null);
                }
            }
        }

        // Sort by severity (high first)
        usort($actions, function ($a, $b) {
            $severityOrder = ['high' => 0, 'medium' => 1, 'low' => 2];
            return ($severityOrder[$a['severity']] ?? 2) <=> ($severityOrder[$b['severity']] ?? 2);
        });

        return [
            'actions' => $actions,
            'risk_level' => $riskLevel,
            'flag_count' => count($redFlags),
            'has_high_severity' => collect($actions)->contains('severity', 'high'),
        ];
    }

    /**
     * Build a single action item.
     */
    private function buildActionItem(string $actionCode, ?string $sourceFlag): array
    {
        $actionDef = $this->actionConfig[$actionCode] ?? null;

        return [
            'code' => $actionCode,
            'label_key' => $actionDef['label_key'] ?? "actions.{$actionCode}",
            'icon' => $actionDef['icon'] ?? 'information-circle',
            'severity' => $actionDef['severity'] ?? 'medium',
            'source_flag' => $sourceFlag,
        ];
    }

    /**
     * Get flag definition by code.
     */
    public function getFlagDefinition(string $code): ?array
    {
        return $this->flagConfig[$code] ?? null;
    }

    /**
     * Get all flag definitions.
     */
    public function getAllFlags(): array
    {
        return $this->flagConfig;
    }

    /**
     * Get all action definitions.
     */
    public function getAllActions(): array
    {
        return $this->actionConfig;
    }

    /**
     * Enrich red flags with action recommendations.
     *
     * @param array $copilotResponse The parsed Copilot response
     * @return array Enriched response with suggested_actions
     */
    public function enrichWithActions(array $copilotResponse): array
    {
        $redFlags = $copilotResponse['red_flags'] ?? [];
        $riskLevel = $copilotResponse['risk_level'] ?? 'none';

        $actionResult = $this->getRecommendedActions($redFlags, $riskLevel);

        $copilotResponse['suggested_actions'] = $actionResult['actions'];
        $copilotResponse['has_high_severity_action'] = $actionResult['has_high_severity'];

        return $copilotResponse;
    }
}
