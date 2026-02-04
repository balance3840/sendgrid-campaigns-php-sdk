# SendGrid Campaigns PHP SDK

A comprehensive PHP SDK for interacting with SendGrid's Marketing Campaigns API. This library provides an intuitive, object-oriented interface for managing contacts, lists, designs, single sends, and more.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## Table of Contents

- [Installation](#installation)
- [Getting Started](#getting-started)
- [Core Concepts](#core-concepts)
- [Supported Entities](#supported-entities)
  - [Contacts](#contacts)
  - [Contact Lists](#contact-lists)
  - [Custom Fields](#custom-fields)
  - [Designs](#designs)
  - [Single Sends](#single-sends)
  - [Senders](#senders)
  - [Unsubscribe Groups](#unsubscribe-groups)
  - [Stats](#stats)
  - [Test Emails](#test-emails)
- [Complete Workflow Example](#complete-workflow-example)
- [Error Handling](#error-handling)
- [Contributing](#contributing)
- [License](#license)

---

## Installation

Install the package via Composer:

```bash
composer require ramiroestrella/sendgrid-campaigns-sdk
```

## Getting Started

### Basic Setup

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use SendgridCampaign\Entities\Contact\Contact;

$apiKey = 'YOUR_SENDGRID_API_KEY';

$contact = new Contact(apiKey: $apiKey);
```

### Using SendgridClient for Multiple Entities

If you're working with multiple entities, you can create a shared `SendgridClient` instance:

```php
use SendgridCampaign\Clients\SendgridClient;
use SendgridCampaign\Entities\Contact\Contact;
use SendgridCampaign\Entities\ContactList\ContactList;

$sendgridClient = SendgridClient::create(
    apiKey: 'YOUR_SENDGRID_API_KEY',
    onBehalfOf: 'subuser-name' // Optional: for subuser operations
);

$contact = new Contact(sendgridClient: $sendgridClient);
$contactList = new ContactList(sendgridClient: $sendgridClient);
```

---

## Core Concepts

### DTOs (Data Transfer Objects)

All data is represented using DTOs that extend [`BaseDTO`](src/DTO/BaseDTO.php). These provide type-safe data structures with automatic serialization/deserialization:

```php
use SendgridCampaign\Entities\Contact\DTO\ContactDTO;

$contact = new ContactDTO(
    email: 'john@example.com',
    first_name: 'John',
    last_name: 'Doe'
);

// Convert to array
$array = $contact->toArray(excludeNullValues: true);

// Create from array
$contact = ContactDTO::fromArray([
    'email' => 'john@example.com',
    'first_name' => 'John'
]);
```

### Error Handling

All methods return either the expected DTO or a [`BaseErrorDTO`](src/DTO/BaseErrorDTO.php):

```php
$result = $contact->getById('contact-id');

if ($result instanceof BaseErrorDTO) {
    foreach ($result->errors as $error) {
        echo "Error: {$error->message}\n";
    }
} else {
    echo "Contact email: {$result->email}\n";
}
```

---

## Supported Entities

## Contacts

The [`Contact`](src/Entities/Contact/Contact.php) entity manages contact records in SendGrid.

### Get Sample Contacts

Retrieve a sample of contacts from your account:

```php
$contact = new Contact(apiKey: $apiKey);
$sample = $contact->getSample();

if (!$sample instanceof BaseErrorDTO) {
    foreach ($sample->result as $contactDTO) {
        echo "Email: {$contactDTO->email}\n";
    }
}
```

### Get Contact by ID

```php
$contact = $contact->getById('contact-id-here');

if (!$contact instanceof BaseErrorDTO) {
    echo "Name: {$contact->first_name} {$contact->last_name}\n";
    echo "Email: {$contact->email}\n";
}
```

### Search Contacts by Email

```php
$results = $contact->getByEmail(
    emails: ['john@example.com', 'jane@example.com'],
    phone_number_id: null,
    external_id: null,
    anonymous_id: null
);

if (!$results instanceof BaseErrorDTO) {
    foreach ($results as $contactDTO) {
        echo "Found: {$contactDTO->email}\n";
    }
}
```

### Create or Update Contacts

```php
use SendgridCampaign\Entities\Contact\DTO\ContactDTO;

$contacts = [
    new ContactDTO(
        email: 'john@example.com',
        first_name: 'John',
        last_name: 'Doe',
        custom_fields: [
            'company' => 'Acme Inc',
            'employee_id' => 12345
        ]
    ),
    new ContactDTO(
        email: 'jane@example.com',
        first_name: 'Jane',
        last_name: 'Smith'
    )
];

$job = $contact->createOrUpdate(
    contacts: $contacts,
    listIds: ['list-id-1', 'list-id-2'] // Optional
);

if (!$job instanceof BaseErrorDTO) {
    echo "Job ID: {$job->job_id}\n";
}
```

### Search Contacts with Query

Use SendGrid's query language to search contacts:

```php
$results = $contact->search(
    query: 'email LIKE "%@example.com" AND last_clicked >= "2024-01-01"'
);

if (!$results instanceof BaseErrorDTO) {
    echo "Found {$results->contact_count} contacts\n";
    foreach ($results->result as $contactDTO) {
        echo "- {$contactDTO->email}\n";
    }
}
```

### Import Contacts from CSV

You can import contacts from CSV content or a file:

```php
// From CSV string
$csvContent = <<<CSV
email,first_name,last_name,company
john@example.com,John,Doe,Acme Inc
jane@example.com,Jane,Smith,Tech Corp
CSV;

$import = $contact->import(
    fileContent: $csvContent,
    fileType: ContactImportFileType::CSV,
    listIds: ['list-id']
);

// From local file
$import = $contact->importFromFile(
    filePath: 'contacts.csv',
    listIds: ['list-id']
);

// From URL
$import = $contact->importFromFile(
    filePath: 'https://example.com/contacts.csv',
    listIds: ['list-id']
);

if (!$import instanceof BaseErrorDTO) {
    echo "Job ID: {$import->job_id}\n";
}
```

### Check Import Status

```php
$status = $contact->getImportStatus('job-id');

if (!$status instanceof BaseErrorDTO) {
    echo "Status: {$status->status}\n";
    echo "Created: {$status->results?->created_count}\n";
    echo "Updated: {$status->results?->updated_count}\n";
}
```

### Get Contact Count

```php
$count = $contact->getCount();

if (!$count instanceof BaseErrorDTO) {
    echo "Total contacts: {$count->contact_count}\n";
    echo "Billable contacts: {$count->billable_count}\n";
}
```

### Export Contacts

```php
$job = $contact->export(
    listIds: ['list-id-1'],
    sendEmailNotification: true,
    fileType: ContactImportFileType::CSV,
    maxFileSizeInMb: 5000
);

if (!$job instanceof BaseErrorDTO) {
    echo "Export Job ID: {$job->job_id}\n";
}
```

### Check Export Status

```php
$status = $contact->exportStatus('job-id');

if (!$status instanceof BaseErrorDTO) {
    echo "Status: {$status->status->value}\n";
    if ($status->urls) {
        foreach ($status->urls as $url) {
            echo "Download: {$url}\n";
        }
    }
}
```

### Delete Contacts

```php
// Delete specific contacts
$job = $contact->delete(
    contactIds: ['contact-id-1', 'contact-id-2']
);

// Delete all contacts (use with caution!)
$job = $contact->delete(deleteAll: true);
```

### Delete Contact Identifier

Remove a specific identifier from a contact:

```php
use SendgridCampaign\Entities\Contact\Enums\ContactIdentifierType;

$job = $contact->deleteIndentifier(
    contactId: 'contact-id',
    identifierType: ContactIdentifierType::EXTERNALID,
    identifierValue: 'external-123'
);
```

---

## Contact Lists

The [`ContactList`](src/Entities/ContactList/ContactList.php) entity manages contact list operations.

### Get All Lists

```php
use SendgridCampaign\Entities\ContactList\ContactList;

$contactList = new ContactList(apiKey: $apiKey);
$lists = $contactList->getAll();

if (!$lists instanceof BaseErrorDTO) {
    foreach ($lists->result as $list) {
        echo "List: {$list->name} ({$list->contact_count} contacts)\n";
    }
}
```

### Get List by ID

```php
$list = $contactList->getById(
    listId: 'list-id',
    includeContactSample: true // Optional: include sample contacts
);

if (!$list instanceof BaseErrorDTO) {
    echo "List: {$list->name}\n";
    echo "Contacts: {$list->contact_count}\n";
    
    foreach ($list->contact_sample as $contact) {
        echo "- {$contact->email}\n";
    }
}
```

### Create List

```php
$newList = $contactList->create('My New List');

if (!$newList instanceof BaseErrorDTO) {
    echo "Created list: {$newList->id}\n";
}
```

### Update List Name

```php
$updatedList = $contactList->update(
    listId: 'list-id',
    newName: 'Updated List Name'
);
```

### Get Contact Count in List

```php
$count = $contactList->getContactsCount('list-id');

if (!$count instanceof BaseErrorDTO) {
    echo "Contacts: {$count->contact_count}\n";
    echo "Billable: {$count->billable_count}\n";
}
```

### Remove Contacts from List

```php
$job = $contactList->removeContacts(
    listId: 'list-id',
    contactIds: ['contact-id-1', 'contact-id-2']
);
```

### Delete List

```php
// Delete list only
$contactList->delete(
    listId: 'list-id',
    deleteContactsFromList: false
);

// Delete list and its contacts
$contactList->delete(
    listId: 'list-id',
    deleteContactsFromList: true
);
```

---

## Custom Fields

The [`CustomField`](src/Entities/CustomField/CustomField.php) entity manages custom field definitions.

### Get All Custom Fields

```php
use SendgridCampaign\Entities\CustomField\CustomField;

$customField = new CustomField(apiKey: $apiKey);
$fields = $customField->getAll();

if (!$fields instanceof BaseErrorDTO) {
    foreach ($fields->custom_fields as $field) {
        echo "Field: {$field->name} ({$field->field_type->value})\n";
    }
    
    // Reserved fields are also included
    foreach ($fields->reserved_fields as $field) {
        echo "Reserved: {$field->name}\n";
    }
}
```

### Create Custom Field

```php
use SendgridCampaign\Entities\CustomField\Enums\CustomFieldType;

$field = $customField->create(
    name: 'employee_id',
    fieldType: CustomFieldType::NUMBER
);

if (!$field instanceof BaseErrorDTO) {
    echo "Created field ID: {$field->id}\n";
}

// Available types: TEXT, NUMBER, DATE
```

### Update Custom Field

```php
$updated = $customField->update(
    id: 'field-id',
    name: 'new_field_name',
    fieldType: CustomFieldType::TEXT // Optional
);
```

### Delete Custom Field

```php
$customField->delete('field-id');
```

**Note:** Custom field names must:
- Begin with a letter (A-Z) or underscore (_)
- Contain only alphanumeric characters and underscores
- Not match reserved field names (email, first_name, etc.)

---

## Designs

The [`Design`](src/Entities/Design/Design.php) entity manages email design templates.

### Get All Designs

```php
use SendgridCampaign\Entities\Design\Design;

$design = new Design(apiKey: $apiKey);
$designs = $design->getAll(
    pageSize: 100,
    pageToken: null,
    summary: true
);

if (!$designs instanceof BaseErrorDTO) {
    foreach ($designs->result as $designDTO) {
        echo "Design: {$designDTO->name}\n";
        echo "Editor: {$designDTO->editor->value}\n";
    }
}
```

### Get Design by ID

```php
$designDetail = $design->getById('design-id');

if (!$designDetail instanceof BaseErrorDTO) {
    echo "Name: {$designDetail->name}\n";
    echo "HTML: {$designDetail->html_content}\n";
}
```

### Create Design

```php
use SendgridCampaign\Entities\Design\Enums\EditorType;

$newDesign = $design->create(
    htmlContent: '<html><body><h1>Hello {{first_name}}!</h1></body></html>',
    name: 'My Email Template',
    subject: 'Welcome Email',
    editor: EditorType::CODE, // or EditorType::DESIGN
    plainContent: 'Hello {{first_name}}!'
);

if (!$newDesign instanceof BaseErrorDTO) {
    echo "Created design ID: {$newDesign->id}\n";
}
```

### Duplicate Design

```php
$duplicated = $design->duplicate(
    designId: 'design-id',
    name: 'Copy of Design',
    editor: EditorType::DESIGN
);
```

### Update Design

```php
$updated = $design->update(
    designId: 'design-id',
    name: 'Updated Design Name',
    htmlContent: '<html>...</html>',
    plainContent: 'Plain text version',
    generatePlainContent: false,
    subject: 'New Subject',
    categories: ['newsletter', 'promotional']
);
```

### Delete Design

```php
$design->delete('design-id');
```

---

## Single Sends

The [`SingleSend`](src/Entities/SingleSend/SingleSend.php) entity manages one-time email campaigns.

### Get All Single Sends

```php
use SendgridCampaign\Entities\SingleSend\SingleSend;

$singleSend = new SingleSend(apiKey: $apiKey);
$sends = $singleSend->getAll(
    pageSize: 50,
    pageToken: null
);

if (!$sends instanceof BaseErrorDTO) {
    foreach ($sends->result as $send) {
        echo "Campaign: {$send->name} - Status: {$send->status->value}\n";
    }
}
```

### Get Single Send by ID

```php
$send = $singleSend->getById('single-send-id');

if (!$send instanceof BaseErrorDTO) {
    echo "Name: {$send->name}\n";
    echo "Status: {$send->status->value}\n";
    echo "Send At: {$send->send_at}\n";
}
```

### Create Single Send

```php
use SendgridCampaign\Entities\SingleSend\DTO\{SingleSendDTO, SendToDTO, EmailConfigDTO};

$newSend = $singleSend->create(
    new SingleSendDTO(
        name: 'Monthly Newsletter',
        send_to: new SendToDTO(
            list_ids: ['list-id-1', 'list-id-2']
            // Or use: segment_ids: ['segment-id']
            // Or use: all: true (send to all contacts)
        ),
        email_config: new EmailConfigDTO(
            subject: 'Welcome to Our Newsletter',
            design_id: 'design-id',
            sender_id: 123456,
            suppression_group_id: 789
        ),
        categories: ['newsletter', 'monthly']
    )
);

if (!$newSend instanceof BaseErrorDTO) {
    echo "Created single send ID: {$newSend->id}\n";
}
```

### Schedule Single Send

```php
// Schedule for specific time (ISO 8601 format)
$scheduled = $singleSend->schedule(
    singleSendId: 'single-send-id',
    sendAt: '2024-12-25T10:00:00Z'
);

// Send immediately
$scheduled = $singleSend->schedule(
    singleSendId: 'single-send-id',
    sendAt: 'now'
);
```

### Search Single Sends

```php
use SendgridCampaign\Entities\SingleSend\Enums\StatusType;

$results = $singleSend->search(
    name: 'Newsletter',
    status: [StatusType::DRAFT, StatusType::SCHEDULED],
    categories: ['monthly'],
    pageSize: 50
);
```

### Get All Categories

```php
$categories = $singleSend->getAllCategories();

if (!$categories instanceof BaseErrorDTO) {
    foreach ($categories as $category) {
        echo "Category: {$category}\n";
    }
}
```

### Update Single Send

```php
$updated = $singleSend->update(
    singleSendId: 'single-send-id',
    singleSendDTO: new SingleSendDTO(
        name: 'Updated Campaign Name'
    )
);
```

### Duplicate Single Send

```php
$duplicate = $singleSend->duplicate(
    singleSendId: 'single-send-id',
    name: 'Copy of Campaign'
);
```

### Delete Single Send

```php
// Delete single campaign
$singleSend->delete('single-send-id');

// Bulk delete (max 50 at once)
$singleSend->bulkDelete([
    'single-send-id-1',
    'single-send-id-2',
    'single-send-id-3'
]);
```

### Delete Schedule

Unschedule a single send without deleting it:

```php
$unscheduled = $singleSend->deleteSchedule('single-send-id');
```

---

## Senders

The [`Sender`](src/Entities/Sender/Sender.php) entity manages sender identities.

### Get All Senders

```php
use SendgridCampaign\Entities\Sender\Sender;

$sender = new Sender(apiKey: $apiKey);
$senders = $sender->getAll();

if (!$senders instanceof BaseErrorDTO) {
    foreach ($senders as $senderDTO) {
        echo "Sender: {$senderDTO->nickname}\n";
        echo "From: {$senderDTO->from_email}\n";
    }
}
```

### Get All Verified Senders

```php
$verified = $sender->getAllVerified(
    limit: 50,
    lastSeenID: null,
    id: null
);
```

### Get Sender by ID

```php
$senderDetail = $sender->getById(123456);

if (!$senderDetail instanceof BaseErrorDTO) {
    echo "Nickname: {$senderDetail->nickname}\n";
    echo "Verified: " . ($senderDetail->verified ? 'Yes' : 'No') . "\n";
}
```

---

## Unsubscribe Groups

The [`UnsubscribeGroup`](src/Entities/UnsubscripeGroup/UnsubscribeGroup.php) entity manages suppression groups.

### Get All Unsubscribe Groups

```php
use SendgridCampaign\Entities\UnsubscripeGroup\UnsubscribeGroup;

$unsubscribeGroup = new UnsubscribeGroup(apiKey: $apiKey);
$groups = $unsubscribeGroup->getAll();

if (!$groups instanceof BaseErrorDTO) {
    foreach ($groups as $group) {
        echo "Group: {$group->name}\n";
        echo "Unsubscribes: {$group->unsubscribes}\n";
    }
}
```

### Get Unsubscribe Group by ID

```php
$group = $unsubscribeGroup->getById(12345);

if (!$group instanceof BaseErrorDTO) {
    echo "Name: {$group->name}\n";
    echo "Description: {$group->description}\n";
}
```

### Create Unsubscribe Group

```php
$newGroup = $unsubscribeGroup->create(
    name: 'Marketing Emails',
    description: 'Promotional and marketing content',
    isDefault: false
);
```

### Update Unsubscribe Group

```php
$updated = $unsubscribeGroup->update(
    groupId: 12345,
    name: 'Updated Name',
    description: 'Updated description',
    isDefault: true
);
```

### Delete Unsubscribe Group

```php
$unsubscribeGroup->delete(12345);
```

---

## Stats

The [`Stats`](src/Entities/Stats/Stats.php) entity retrieves campaign statistics.

### Get Stats by Single Send ID

```php
use SendgridCampaign\Entities\Stats\Stats;
use SendgridCampaign\Entities\Stats\Enums\{AggregatedByType, GroupByType};

$stats = new Stats(apiKey: $apiKey);
$results = $stats->getById(
    singleSendId: 'single-send-id',
    aggregatedBy: AggregatedByType::DAY,
    startDate: '2024-01-01',
    endDate: '2024-12-31',
    pageSize: 100,
    groupBy: [GroupByType::AB_VARIATION]
);

if (!$results instanceof BaseErrorDTO) {
    foreach ($results->result as $stat) {
        echo "Opens: {$stat->stats->opens}\n";
        echo "Clicks: {$stat->stats->clicks}\n";
        echo "Bounces: {$stat->stats->bounces}\n";
    }
}
```

### Get All Stats

```php
$allStats = $stats->getAll(
    singleSendIds: ['id-1', 'id-2'],
    pageSize: 50
);
```

### Get Link Click Stats

```php
use SendgridCampaign\Entities\Stats\Enums\AbPhaseType;

$linkStats = $stats->getLinkStats(
    singleSendId: 'single-send-id',
    page_size: 100,
    group_by: [GroupByType::AB_VARIATION],
    ab_phase_id: AbPhaseType::SEND
);

if (!$linkStats instanceof BaseErrorDTO) {
    echo "Total Clicks: {$linkStats->total_clicks}\n";
    
    foreach ($linkStats->result as $link) {
        echo "URL: {$link->url}\n";
        echo "Clicks: {$link->clicks}\n";
    }
}
```

### Export Stats

```php
$csv = $stats->export(
    singleSendIds: ['id-1', 'id-2'],
    timezone: 'America/New_York'
);

if (!$csv instanceof BaseErrorDTO) {
    file_put_contents('stats.csv', $csv);
}
```

---

## Test Emails

The [`TestEmail`](src/Entities/TestEmail/TestEmail.php) entity sends test emails.

### Send Test Email

```php
use SendgridCampaign\Entities\TestEmail\TestEmail;

$testEmail = new TestEmail(apiKey: $apiKey);

$testEmail->send(
    templateId: 'd-template-id',
    emails: ['test@example.com', 'test2@example.com'],
    sender_id: 123456,
    suppression_group_id: 789,
    custom_unsubscribe_url: 'https://example.com/unsubscribe'
);
```

---

## Complete Workflow Example

Here's a complete example demonstrating a typical workflow:

```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use SendgridCampaign\Clients\SendgridClient;
use SendgridCampaign\Entities\Contact\Contact;
use SendgridCampaign\Entities\Contact\DTO\ContactDTO;
use SendgridCampaign\Entities\ContactList\ContactList;
use SendgridCampaign\Entities\CustomField\CustomField;
use SendgridCampaign\Entities\CustomField\Enums\CustomFieldType;
use SendgridCampaign\Entities\Design\Design;
use SendgridCampaign\Entities\Design\Enums\EditorType;
use SendgridCampaign\Entities\SingleSend\SingleSend;
use SendgridCampaign\Entities\SingleSend\DTO\{SingleSendDTO, SendToDTO, EmailConfigDTO};

$apiKey = 'YOUR_SENDGRID_API_KEY';

// Create shared client
$sendgridClient = SendgridClient::create(apiKey: $apiKey);

// Initialize entities
$contactList = new ContactList(sendgridClient: $sendgridClient);
$contact = new Contact(sendgridClient: $sendgridClient);
$customField = new CustomField(sendgridClient: $sendgridClient);
$design = new Design(sendgridClient: $sendgridClient);
$singleSend = new SingleSend(sendgridClient: $sendgridClient);

// 1. Create a contact list
$newList = $contactList->create('Holiday Campaign');
echo "Created list: {$newList->id}\n";

// 2. Create custom field
$customField->create(
    name: 'loyalty_points',
    fieldType: CustomFieldType::NUMBER
);

// 3. Add contacts
$contacts = [
    new ContactDTO(
        email: 'customer1@example.com',
        first_name: 'John',
        last_name: 'Doe',
        custom_fields: ['loyalty_points' => 1500]
    ),
    new ContactDTO(
        email: 'customer2@example.com',
        first_name: 'Jane',
        last_name: 'Smith',
        custom_fields: ['loyalty_points' => 2300]
    )
];

$job = $contact->createOrUpdate(
    contacts: $contacts,
    listIds: [$newList->id]
);
echo "Added contacts, job ID: {$job->job_id}\n";

// 4. Create email design
$newDesign = $design->create(
    name: 'Holiday Special',
    subject: 'Exclusive Offer for {{first_name}}!',
    htmlContent: '<html><body>
        <h1>Hi {{first_name}},</h1>
        <p>You have {{loyalty_points}} points!</p>
    </body></html>',
    editor: EditorType::CODE
);
echo "Created design: {$newDesign->id}\n";

// 5. Create and schedule single send
$campaign = $singleSend->create(
    new SingleSendDTO(
        name: 'Holiday Campaign 2024',
        send_to: new SendToDTO(list_ids: [$newList->id]),
        email_config: new EmailConfigDTO(
            sender_id: YOUR_SENDER_ID,
            design_id: $newDesign->id,
            suppression_group_id: YOUR_SUPPRESSION_GROUP_ID
        ),
        categories: ['holiday', 'promotion']
    )
);

echo "Created campaign: {$campaign->id}\n";

// Schedule to send tomorrow at 9 AM
$scheduled = $singleSend->schedule(
    singleSendId: $campaign->id,
    sendAt: date('Y-m-d\T09:00:00\Z', strtotime('+1 day'))
);

echo "Campaign scheduled for: {$scheduled->send_at}\n";
```

---

## Error Handling

All API methods return either the expected DTO or a [`BaseErrorDTO`](src/DTO/BaseErrorDTO.php). Always check the return type:

```php
$result = $contact->getById('invalid-id');

if ($result instanceof BaseErrorDTO) {
    echo "API Error:\n";
    foreach ($result->errors as $error) {
        echo "- Field: {$error->field}\n";
        echo "  Message: {$error->message}\n";
        echo "  Error ID: {$error->error_id}\n";
    }
} else {
    // Success - $result is ContactDTO
    echo "Email: {$result->email}\n";
}
```

### Common Exceptions

- [`MissingApiKeyException`](src/Exceptions/MissingApiKeyException.php): Thrown when API key is not provided
- [`EmptyContactsException`](src/Exceptions/EmptyContactsException.php): Thrown when trying to perform operations with an empty contacts array

```php
use SendgridCampaign\Exceptions\MissingApiKeyException;

try {
    $contact = new Contact(); // No API key provided
} catch (MissingApiKeyException $e) {
    echo "Error: {$e->getMessage()}\n";
}
```

---

## API Reference

### Key Classes

| Class | Description | File |
|-------|-------------|------|
| [`Contact`](src/Entities/Contact/Contact.php) | Manage contacts | [Contact.php](src/Entities/Contact/Contact.php) |
| [`ContactList`](src/Entities/ContactList/ContactList.php) | Manage contact lists | [ContactList.php](src/Entities/ContactList/ContactList.php) |
| [`CustomField`](src/Entities/CustomField/CustomField.php) | Manage custom fields | [CustomField.php](src/Entities/CustomField/CustomField.php) |
| [`Design`](src/Entities/Design/Design.php) | Manage email designs | [Design.php](src/Entities/Design/Design.php) |
| [`SingleSend`](src/Entities/SingleSend/SingleSend.php) | Manage campaigns | [SingleSend.php](src/Entities/SingleSend/SingleSend.php) |
| [`Sender`](src/Entities/Sender/Sender.php) | Manage sender identities | [Sender.php](src/Entities/Sender/Sender.php) |
| [`UnsubscribeGroup`](src/Entities/UnsubscripeGroup/UnsubscribeGroup.php) | Manage suppression groups | [UnsubscribeGroup.php](src/Entities/UnsubscripeGroup/UnsubscribeGroup.php) |
| [`Stats`](src/Entities/Stats/Stats.php) | Get campaign statistics | [Stats.php](src/Entities/Stats/Stats.php) |
| [`TestEmail`](src/Entities/TestEmail/TestEmail.php) | Send test emails | [TestEmail.php](src/Entities/TestEmail/TestEmail.php) |

### Enums

| Enum | Values | File |
|------|--------|------|
| [`ContactIdentifierType`](src/Entities/Contact/Enums/ContactIdentifierType.php) | EMAIL, PHONENUMBERID, EXTERNALID, ANONYMOUSID | [ContactIdentifierType.php](src/Entities/Contact/Enums/ContactIdentifierType.php) |
| [`ContactImportFileType`](src/Entities/Contact/Enums/ContactImportFileType.php) | CSV, JSON | [ContactImportFileType.php](src/Entities/Contact/Enums/ContactImportFileType.php) |
| [`CustomFieldType`](src/Entities/CustomField/Enums/CustomFieldType.php) | TEXT, NUMBER, DATE | [CustomFieldType.php](src/Entities/CustomField/Enums/CustomFieldType.php) |
| [`EditorType`](src/Entities/Design/Enums/EditorType.php) | DESIGN, CODE | [EditorType.php](src/Entities/Design/Enums/EditorType.php) |
| [`StatusType`](src/Entities/SingleSend/Enums/StatusType.php) | DRAFT, SCHEDULED, TRIGGERED | [StatusType.php](src/Entities/SingleSend/Enums/StatusType.php) |
| [`AbTestType`](src/Entities/SingleSend/Enums/AbTestType.php) | SUBJECT, CONTENT | [AbTestType.php](src/Entities/SingleSend/Enums/AbTestType.php) |

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## Support

For issues, questions, or contributions, please visit the [GitHub repository](https://github.com/balance3840/sendgrid-campaigns-php-sdk).

---

## Acknowledgments

- Built on top of [GuzzleHttp](https://github.com/guzzle/guzzle)
- Designed to work with [SendGrid's Marketing Campaigns API](https://docs.sendgrid.com/api-reference/marketing-campaigns)
```