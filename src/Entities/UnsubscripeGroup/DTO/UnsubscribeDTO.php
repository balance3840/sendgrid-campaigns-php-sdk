<?php

namespace SendgridCampaign\Entities\UnsubscripeGroup\DTO;

use SendgridCampaign\DTO\BaseDTO;

class UnsubscribeDTO extends BaseDTO
{
    public ?int $id = null;
    public ?string $name = null;
    public ?string $description = null;

    public ?bool $is_default = null;
    public ?int $unsubscribes = null;
}
