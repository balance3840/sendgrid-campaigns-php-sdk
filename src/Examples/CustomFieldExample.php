<?php

use SendgridCampaign\Entities\CustomField\DTO\CustomFieldDTO;

require_once __DIR__ . '/../../vendor/autoload.php';


$apiKey = ''; // Your SendGrid API Key

function getAllCustomFields() {

    $customFieldEntity = new \SendgridCampaign\Entities\CustomField\CustomField(
        apiKey: $GLOBALS['apiKey']
    );

    return $customFieldEntity->getAll();
}

function createCustomField(string $name, \SendgridCampaign\Entities\CustomField\Enums\CustomFieldType $fieldType): void {

    $customFieldEntity = new \SendgridCampaign\Entities\CustomField\CustomField(
        apiKey: $GLOBALS['apiKey']
    );

    $customFieldEntity->create(
        name: $name,
        fieldType: $fieldType
    );
}

function updateCustomField(string $id, string $name, ?\SendgridCampaign\Entities\CustomField\Enums\CustomFieldType $fieldType): CustomFieldDTO {

    $customFieldEntity = new \SendgridCampaign\Entities\CustomField\CustomField(
        apiKey: $GLOBALS['apiKey']
    );

    return $customFieldEntity->update(
        id: $id,
        name: $name,
        fieldType: $fieldType
    );
}

function deleteCustomField(string $id): void {

    $customFieldEntity = new \SendgridCampaign\Entities\CustomField\CustomField(
        apiKey: $GLOBALS['apiKey']
    );
    $customFieldEntity->delete($id);
}

// echo json_encode(getAllCustomFields(), JSON_PRETTY_PRINT);
// createCustomField(
//     name: 'first_name',
//     fieldType: \SendgridCampaign\Entities\CustomField\Enums\CustomFieldType::TEXT
// );

// echo json_encode(
//     updateCustomField(
//         id: 'CUSTOM_FIELD_ID',
//         name: 'Prefered_Language',
//         fieldType: null
//     ),
//     JSON_PRETTY_PRINT
// );

// deleteCustomField('CUSTOM_FIELD_ID');