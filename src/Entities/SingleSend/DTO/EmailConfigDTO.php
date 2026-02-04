<?php

namespace SendgridCampaign\Entities\SingleSend\DTO;

use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\Entities\Design\Enums\EditorType;

class EmailConfigDTO extends BaseDTO
{
    public function __construct(
        public ?string $subject = null,
        public ?string $html_content = null,
        public ?string $plain_content = null,
        public ?bool $generate_plain_content = null,
        public ?string $design_id = null,
        public ?EditorType $editor = null,
        public ?int $suppression_group_id = null,
        public ?string $custom_unsubscribe_url = null,
        public ?int $sender_id = null,
        public ?string $ip_pool = null
    ) {
    }
}
