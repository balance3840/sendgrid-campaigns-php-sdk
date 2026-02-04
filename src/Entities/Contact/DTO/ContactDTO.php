<?php

namespace SendgridCampaign\Entities\Contact\DTO;

use SendgridCampaign\DTO\BaseDTO;

class ContactDTO extends BaseDTO
{
    public function __construct(
        public string $id = '',
        public ?string $email = null,
        public string $first_name = '',
        public string $last_name = '',
        public ?string $external_id = null,
        public string $address_line_1 = '',
        public string $address_line_2 = '',
        public string $city = '',
        public string $country = '',
        public string $postal_code = '',
        public string $state_province_region = '',
        public string $phone_number = '',
        public ?string $phone_number_id = null,
        public ?string $anonymous_id = null,
        public string $whatsapp = '',
        public string $line = '',
        public string $facebook = '',
        public string $unique_name = '',
        /** @var string[] */
        public ?array $list_ids = null,
        /** @var string[]|null */
        public ?array $alternate_emails = null,
        /** @var array<string, mixed> */
        public ?array $custom_fields = null,
        public string $created_at = '',
        public string $updated_at = '',
    ) {}
}