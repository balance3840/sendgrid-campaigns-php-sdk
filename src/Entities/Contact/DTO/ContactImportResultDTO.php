<?php

namespace SendgridCampaign\Entities\Contact\DTO;

use SendgridCampaign\DTO\BaseDTO;

class ContactImportResultDTO extends BaseDTO
{
    public ?int $requested_count = 0;
    public ?int $created_count = 0;
    public ?int $updated_count = 0;
    public ?int $deleted_count = 0;
    public ?int $errored_count = 0;
    public ?string $errors_url = '';
}
