<?php

namespace SendgridCampaign\Entities\SingleSend\DTO;

use SendgridCampaign\DTO\BaseDTO;
use SendgridCampaign\Entities\SingleSend\Enums\StatusType;

class SingleSendDTO extends BaseDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $name = null,
        public ?int $sender_id = null,
        public ?AbTestDTO $abtest = null,
        public ?StatusType $status = null,
        /** @var string[]|null */
        public ?array $categories = null,
        public ?string $send_at = null,
        public ?bool $is_abtest = null,
        public ?string $updated_at = null,
        public ?string $created_at = null,
        public ?SendToDTO $send_to = null,
        public ?EmailConfigDTO $email_config = null,
        /** @var WarningDTO[]|null */
        public ?array $warnings = null,
    ) {}
}