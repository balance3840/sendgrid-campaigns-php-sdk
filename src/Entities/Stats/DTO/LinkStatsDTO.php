<?php

namespace SendgridCampaign\Entities\Stats\DTO;

use SendgridCampaign\DTO\BaseDTO;

class LinkStatsDTO extends BaseDTO
{
    public function __construct(
        public ?string $url = null,
        public ?string $url_location = null,
        public ?string $ab_variation = null,
        public ?string $ab_phase = null,
        public ?int $clicks = null
    ) {
    }
}
