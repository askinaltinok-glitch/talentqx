<?php

namespace App\Services\Research;

use App\Models\ResearchRun;

interface ResearchAgentInterface
{
    public function run(ResearchRun $run): void;

    public function getName(): string;
}
