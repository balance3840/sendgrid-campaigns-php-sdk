<?php

namespace SendgridCampaign\Entities\Contact\DTO;

use SendgridCampaign\DTO\BaseDTO;

class ContactBillableBreakdownDTO extends BaseDTO
{
    public ?int $total = 0;
    /**
     * 
     * @var array<string, int> | null
     */
    public ?array $breakdown = null;
}
