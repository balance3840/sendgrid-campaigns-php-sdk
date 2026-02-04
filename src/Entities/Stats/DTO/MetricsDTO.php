<?php

namespace SendgridCampaign\Entities\Stats\DTO;

use SendgridCampaign\DTO\BaseDTO;

class MetricsDTO extends BaseDTO
{
    public function __construct(
        public ?int $bounce_drops = null,
        public ?int $bounces = null,
        public ?int $clicks = null,
        public ?int $delivered = null,
        public ?int $invalid_emails = null,
        public ?int $opens = null,
        public ?int $requests = null,
        public ?int $spam_report_drops = null,
        public ?int $unique_clicks = null,
        public ?int $unique_opens = null,
        public ?int $unsubscribes = null,
    ) {
    }
}
