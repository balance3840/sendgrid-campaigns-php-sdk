<?php

namespace SendgridCampaign\Entities\Sender\DTO;

use SendgridCampaign\DTO\BaseDTO;

class SenderDTO extends BaseDTO
{
    public function __construct(
        public ?string $id = null,
        public ?string $nickname = null,
        public ?SenderFromDTO $from = null,
        public ?string $from_email = null,
        public ?string $from_name = null,
        public ?string $reply_to_name = null,
        public SenderReplyToDTO|string|null $reply_to = null,
        public ?string $address = null,
        public ?string $address_2 = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $zip = null,
        public ?string $country = null,
        public SenderVerifyStatusDTO|bool|null $verified = null,
        public ?bool $locked = null,
        public ?int $created_at = null,
        public ?int $updated_at = null
    ) {
    }
}
