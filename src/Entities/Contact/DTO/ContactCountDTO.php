<?php

namespace SendgridCampaign\Entities\Contact\DTO;

use SendgridCampaign\DTO\BaseDTO;

class ContactCountDTO extends BaseDTO
{
    public ?int $contact_count = 0;
    public ?int $billable_count = 0;
    public ?ContactBillableBreakdownDTO $billable_breakdown = null;
}
