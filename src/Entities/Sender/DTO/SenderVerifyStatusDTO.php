<?php

namespace SendgridCampaign\Entities\Sender\DTO;

use SendgridCampaign\DTO\BaseDTO;

class SenderVerifyStatusDTO extends BaseDTO
{
    public function __construct(
        public ?bool $status = null,
        public mixed $reason = null
    ) {
    }
}
