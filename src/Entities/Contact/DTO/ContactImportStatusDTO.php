<?php

namespace SendgridCampaign\Entities\Contact\DTO;

use SendgridCampaign\DTO\BaseDTO;

class ContactImportStatusDTO extends BaseDTO
{
    public string $id = '';
    public string $status = '';
    public string $job_type = '';
    /**
     * @var ContactImportResultDTO
     */
    public ?ContactImportResultDTO $results = null;

    public ?string $started_at = '';
    public ?string $finished_at = '';
}
