<?php

namespace SendgridCampaign\Entities\Contact\DTO;

use SendgridCampaign\DTO\BaseDTO;

class ContactSearchDTO extends BaseDTO
{

    /** @var ContactDTO[] */
    public array $result = [];
    public ?int $contact_count = 0;
}
