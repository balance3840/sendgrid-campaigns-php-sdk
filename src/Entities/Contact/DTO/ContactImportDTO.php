<?php

namespace SendgridCampaign\Entities\Contact\DTO;

use SendgridCampaign\DTO\BaseDTO;

class ContactImportDTO extends BaseDTO
{
    public string $job_id = '';
    public string $upload_uri = '';
    /** @var array{header: string, value: string}[] */
    public array $upload_headers = [];
}
