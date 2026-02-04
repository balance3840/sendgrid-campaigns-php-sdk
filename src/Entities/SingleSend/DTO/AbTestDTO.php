<?php

namespace SendgridCampaign\Entities\SingleSend\DTO;

use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\Entities\SingleSend\Enums\AbTestType;
use SendgridCampaign\Entities\SingleSend\Enums\AbTestWinnerCriteria;

class AbTestDTO extends BaseDTO
{
    public ?AbTestType $type = null;
    public ?int $test_percentage = null;
    public ?string $duration = null;
    public ?AbTestWinnerCriteria $winner_criteria = null;
    public ?string $winner_selected_at = null;
    public ?string $winning_template_id = null;
    public ?string $expiration_date = null;
}