<?php

namespace SendgridCampaign\Entities\CustomField;

use InvalidArgumentException;
use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\CustomField\DTO\CustomFieldDTO;
use SendgridCampaign\Entities\CustomField\DTO\CustomFieldListDTO;
use SendgridCampaign\Entities\CustomField\Enums\CustomFieldType;

class CustomField extends BaseEntity
{

    public const BASE_ENDPOINT = 'marketing/field_definitions';

    public const RESERVED_FIELDS = [
        'first_name',
        'last_name',
        'email',
        'alternate_emails',
        'address_line_1',
        'address_line_2',
        'city',
        'state_province_region',
        'postal_code',
        'country',
        'phone_number',
        'whatsapp',
        'line',
        'facebook',
        'unique_name',
        'list_ids',
        'segment_ids',
        'email_domains',
        'last_clicked',
        'last_opened',
        'last_emailed',
        'singlesend_id',
        'automation_id',
        'created_at',
        'updated_at',
        'contact_id',
        'phone_number_id',
        'external_id',
        'anonymous_id',
    ];

    public function getAll(): CustomFieldListDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT
        );

        return $this->castSingleResponse($response, CustomFieldListDTO::class);
    }

    public function create(string $name, CustomFieldType $fieldType): CustomFieldDTO|BaseErrorDTO
    {
        $this->validateApiKey();
        $this->validateName($name);

        $response =$this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT,
            body: [
                'name' => $name,
                'field_type' => $fieldType->value
            ]
        );

        return $this->castSingleResponse($response, CustomFieldDTO::class);
    }

    public function update(string $id, string $name, ?CustomFieldType $fieldType): CustomFieldDTO|BaseErrorDTO
    {
        $this->validateApiKey();
        $this->validateName($name);

        $body = ['name' => $name];

        if ($fieldType !== null) {
            $body['field_type'] = $fieldType->value;
        }

        $response =$this->sendgridClient->patch(
            endpoint: self::BASE_ENDPOINT . '/' . $id,
            body: $body
        );

        return $this->castSingleResponse($response, CustomFieldDTO::class);
    }

    public function delete(string $id): void
    {
        $this->validateApiKey();

       $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $id
        );
    }

    protected function validateName(string $name): void
    {
        if (empty($name)) {
            throw new InvalidArgumentException('Custom field name cannot be empty.');
        }

        if (preg_match('/^[0-9]/', $name)) {
            throw new InvalidArgumentException(
                'Custom field name cannot begin with a number.'
            );
        }

        if (!preg_match('/^[a-zA-Z_]/', $name)) {
            throw new InvalidArgumentException(
                'Custom field name must begin with a letter (A-Z) or underscore (_).'
            );
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new InvalidArgumentException(
                'Custom field name can only contain alphanumeric characters (A-Z, 0-9) and underscores (_).'
            );
        }

        // Case-insensitive check against reserved fields
        if (in_array(strtolower($name), self::RESERVED_FIELDS, true)) {
            throw new InvalidArgumentException(
                "Custom field name '{$name}' is a reserved field and cannot be used."
            );
        }
    }
}
