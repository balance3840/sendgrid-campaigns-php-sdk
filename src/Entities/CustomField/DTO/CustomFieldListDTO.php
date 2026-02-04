<?php

namespace SendgridCampaign\Entities\CustomField\DTO;

use SendgridCampaign\DTO\BaseDTO;

class CustomFieldListDTO extends BaseDTO
{
    /**
     * @var CustomFieldDTO[]
     */
    public array $custom_fields = [];
    
    /**
     * @var CustomFieldDTO[]
     */
    public array $reserved_fields = [];
}
