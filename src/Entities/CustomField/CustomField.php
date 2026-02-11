<?php

namespace SendgridCampaign\Entities\CustomField;

use InvalidArgumentException;
use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\CustomField\DTO\CustomFieldDTO;
use SendgridCampaign\Entities\CustomField\DTO\CustomFieldListDTO;
use SendgridCampaign\Entities\CustomField\Enums\CustomFieldType;

/**
 * SendGrid Custom Field Management Entity
 * 
 * This class manages custom fields for contacts in SendGrid Marketing Campaigns.
 * Custom fields allow you to store additional data about your contacts beyond
 * the standard fields (email, first_name, last_name, etc.).
 * 
 * Use cases for custom fields:
 * - Store customer preferences (e.g., 'preferred_language')
 * - Track customer data (e.g., 'purchase_date', 'membership_level')
 * - Enable advanced segmentation (e.g., segment by 'country' or 'industry')
 * - Personalize email content (e.g., 'company_name', 'product_interest')
 * 
 * Field types:
 * - Text: Strings up to 32,768 characters
 * - Number: Integer or decimal values
 * - Date: Date values in ISO 8601 format
 * 
 * Limits:
 * - Maximum 120 custom fields per account
 * - Field names: 1-100 characters, alphanumeric and underscores only
 * 
 * @package SendgridCampaign\Entities\CustomField
 * @see https://docs.sendgrid.com/api-reference/custom-fields
 * 
 * @example
 * $customField = new CustomField('your-api-key');
 * 
 * // Create a custom field
 * $field = $customField->create('membership_level', CustomFieldType::TEXT);
 * 
 * // List all custom fields
 * $fields = $customField->getAll();
 */
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

    /**
     * Retrieves all custom fields from your account.
     * 
     * Returns both reserved (system) fields and your custom fields.
     * Reserved fields are the standard SendGrid fields (email, first_name, etc.)
     * and cannot be deleted.
     * 
     * @return CustomFieldListDTO|BaseErrorDTO Returns a DTO containing:
     *         - custom_fields: Array of your custom field definitions
     *         - reserved_fields: Array of system field definitions
     *         Or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/custom-fields/get-all-field-definitions
     * 
     * @example
     * $fields = $entity->getAll();
     * if (!$fields instanceof BaseErrorDTO) {
     *     echo "Custom fields:\n";
     *     foreach ($fields->custom_fields as $field) {
     *         echo "- {$field->name} ({$field->field_type->value})\n";
     *     }
     * }
     */
    public function getAll(): CustomFieldListDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT
        );

        return $this->castSingleResponse($response, CustomFieldListDTO::class);
    }

    /**
     * Creates a new custom field.
     * 
     * Once created, custom fields can be used when:
     * - Adding or updating contacts
     * - Building segments
     * - Personalizing email content with substitution tags
     * 
     * Naming rules:
     * - 1-100 characters
     * - Alphanumeric characters and underscores only
     * - Must start with a letter
     * - Case-insensitive (but displayed as entered)
     * 
     * @param string $name The field name. Must be unique in your account.
     * @param \SendgridCampaign\Entities\CustomField\Enums\CustomFieldType $fieldType 
     *        The data type: TEXT, NUMBER, or DATE.
     * 
     * @return CustomFieldDTO|BaseErrorDTO Returns the created field with its
     *         ID on success, or BaseErrorDTO on validation errors.
     * 
     * @see https://docs.sendgrid.com/api-reference/custom-fields/create-custom-field-definition
     * 
     * @example
     * use SendgridCampaign\Entities\CustomField\Enums\CustomFieldType;
     * 
     * // Create a text field
     * $field = $entity->create('company_name', CustomFieldType::TEXT);
     * 
     * // Create a number field
     * $field = $entity->create('order_count', CustomFieldType::NUMBER);
     * 
     * // Create a date field
     * $field = $entity->create('signup_date', CustomFieldType::DATE);
     */
    public function create(string $name, CustomFieldType $fieldType): CustomFieldDTO|BaseErrorDTO
    {
        $this->validateApiKey();
        $this->validateName($name);

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT,
            body: [
                'name' => $name,
                'field_type' => $fieldType->value
            ]
        );

        return $this->castSingleResponse($response, CustomFieldDTO::class);
    }

    /**
     * Updates a custom field's name.
     * 
     * Only the field name can be updated. The field type cannot be changed
     * after creation. To change the type, delete the field and create a new one.
     * 
     * Note: Changing a field name does not affect contact data - values are
     * preserved under the new name.
     * 
     * @param string $fieldId The ID of the custom field to update.
     * @param string $name The new name for the field.
     * 
     * @return CustomFieldDTO|BaseErrorDTO Returns the updated field on success,
     *         or BaseErrorDTO if the field doesn't exist or name is invalid.
     * 
     * @see https://docs.sendgrid.com/api-reference/custom-fields/update-custom-field-definition
     * 
     * @example
     * $updated = $entity->update('field-id', 'new_field_name');
     */
    public function update(string $id, string $name, ?CustomFieldType $fieldType): CustomFieldDTO|BaseErrorDTO
    {
        $this->validateApiKey();
        $this->validateName($name);

        $body = ['name' => $name];

        if ($fieldType !== null) {
            $body['field_type'] = $fieldType->value;
        }

        $response = $this->sendgridClient->patch(
            endpoint: self::BASE_ENDPOINT . '/' . $id,
            body: $body
        );

        return $this->castSingleResponse($response, CustomFieldDTO::class);
    }

    /**
     * Deletes a custom field.
     * 
     * ⚠️ WARNING: Deleting a custom field permanently removes:
     * - The field definition
     * - ALL data stored in this field across ALL contacts
     * 
     * This action cannot be undone. Back up your data before deleting.
     * 
     * Note: You cannot delete reserved (system) fields.
     * 
     * @param string $fieldId The ID of the custom field to delete.
     * 
     * @return bool|BaseErrorDTO Returns true on success, or BaseErrorDTO
     *         if the field doesn't exist or cannot be deleted.
     * 
     * @see https://docs.sendgrid.com/api-reference/custom-fields/delete-custom-field-definition
     * 
     * @example
     * $result = $entity->delete('field-id');
     * if ($result === true) {
     *     echo "Custom field deleted";
     * }
     */
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
