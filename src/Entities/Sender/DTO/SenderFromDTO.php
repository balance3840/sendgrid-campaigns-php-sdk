<?php

namespace SendgridCampaign\Entities\Sender\DTO;

use SendgridCampaign\DTO\BaseDTO;

class SenderFromDTO extends BaseDTO
{
    public function __construct(
        public ?string $email = null,
        public ?string $name = null
    ) {
    }
}