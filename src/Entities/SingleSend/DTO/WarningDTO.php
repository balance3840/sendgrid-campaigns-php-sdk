<?php

namespace SendgridCampaign\Entities\SingleSend\DTO;

use SendgridCampaign\DTO\BaseDTO;

class WarningDTO extends BaseDTO
{
    public ?string $field = null;
    public ?string $message = null;
    public ?string $warning_id = null;
}
