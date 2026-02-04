<?php

namespace SendgridCampaign\Entities\Design\DTO;

use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\Entities\Design\Enums\EditorType;

class DesignDTO extends BaseDTO
{
    public ?string $id = null;
    public ?string $updated_at = null;
    public ?string $created_at = null;
    public ?string $thumbnail_url = null;
    public ?EditorType $editor = null;
    public ?string $name = null;
    public ?string $html_content = null;
    public ?bool $generate_plain_content = null;
    public ?string $subject = null;
    /**
     * @var string[]|null
     */
    public ?array $categories = null;

}
