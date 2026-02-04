<?php

namespace SendgridCampaign\Entities\Contact\DTO;

use SendgridCampaign\Entities\Contact\Enums\ContactExportType;

class ContactAllExportsStatusDTO extends ContactExportStatusDTO
{
    public ?ContactExportType $export_type = null;
    public ?array $segments = null;
    public ?array $lists = null;
}
