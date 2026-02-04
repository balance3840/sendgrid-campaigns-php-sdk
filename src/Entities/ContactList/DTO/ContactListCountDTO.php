<?php

namespace SendgridCampaign\Entities\ContactList\DTO;

use SendgridCampaign\DTO\BaseDTO;

class ContactListCountDTO extends BaseDTO
{
    public int $contact_count = 0;
    public int $billable_count = 0;
}
