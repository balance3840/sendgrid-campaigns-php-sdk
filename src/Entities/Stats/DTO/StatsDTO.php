<?php

namespace SendgridCampaign\Entities\Stats\DTO;

use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\Entities\Stats\Enums\AbPhaseType;

class StatsDTO extends BaseDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $ab_variation = null,
        public ?AbPhaseType $ab_phase = null,
        public ?string $aggregation = null,
        public ?MetricsDTO $stats = null
    ) {
    }
}
