<?php

namespace SendgridCampaign\Entities\Job\DTO;

use SendgridCampaign\DTO\BaseDTO;

class JobDTO extends BaseDTO
{
    public ?string $job_id = null;
    public ?string $id = null;
}