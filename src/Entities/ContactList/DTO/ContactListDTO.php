<?php

namespace SendgridCampaign\Entities\ContactList\Dto;

use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\Entities\Contact\DTO\ContactDTO;

class ContactListDTO extends BaseDTO
{
    public ?string $id = null;
    public ?string $name = null;
    public int $contact_count = 0;
    
    /** @var ContactDTO[] */
    public array $contact_sample = [];
}