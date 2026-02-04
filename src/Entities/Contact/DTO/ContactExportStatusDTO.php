<?php

namespace SendgridCampaign\Entities\Contact\DTO;

use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\Entities\Contact\Enums\ContactExportStatusType;

class ContactExportStatusDTO extends BaseDTO
{
    public ?string $id = null;
    public ?ContactExportStatusType $status = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;
    public ?string $completed_at = null;
    public ?string $expires_at = null;
    /**
     * @var string[]|null
     */
    public ?array $urls = null;
    public ?string $message = null;
    public ?int $contact_count = null;
}
