<?php

namespace SendgridCampaign\Entities\CustomField\DTO;

use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\Entities\CustomField\Enums\CustomFieldType;

class CustomFieldDTO extends BaseDTO
{
    public string $id = '';
    public string $name = '';
    public ?CustomFieldType $field_type = null;
}
