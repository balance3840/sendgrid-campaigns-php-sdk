<?php
require_once __DIR__ . '/../../vendor/autoload.php';


$apiKey = ''; // Your SendGrid API Key

function getAllContacts() {

    $contactList = new \SendgridCampaign\Entities\ContactList\ContactList(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactList->getAll();
}

function createContactList(string $name) {

    $contactList = new \SendgridCampaign\Entities\ContactList\ContactList(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactList->create($name);
}

function deleteContactList(string $listId, bool $deleteContacts = false) {
    
    $contactList = new \SendgridCampaign\Entities\ContactList\ContactList(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactList->delete($listId, $deleteContacts);
}

function getContactListById(string $listId, bool $includeContactSample = false) {

    $contactList = new \SendgridCampaign\Entities\ContactList\ContactList(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactList->getById($listId, $includeContactSample);
}

function getContactsCountInList(string $listId) {

    $contactList = new \SendgridCampaign\Entities\ContactList\ContactList(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactList->getContactsCount($listId);
}

function updateContactListName(string $listId, string $newName) {

    $contactList = new \SendgridCampaign\Entities\ContactList\ContactList(
        apiKey: 'YOUR_API_KEY'
    );

    return $contactList->update($listId, $newName);
}

function removeContactsList(string $listId, array $contactIds) {
    $contactList = new \SendgridCampaign\Entities\ContactList\ContactList(
        apiKey: $GLOBALS['apiKey']
    );

    return $contactList->removeContacts($listId, $contactIds);
}

echo json_encode(getAllContacts(), JSON_PRETTY_PRINT);
//echo json_encode(createContactList('My New List from SDK'), JSON_PRETTY_PRINT);
// echo json_encode(deleteContactList('LIST_ID', deleteContacts: false), JSON_PRETTY_PRINT);
// echo json_encode(getContactListById('LIST_ID', true), JSON_PRETTY_PRINT);
//echo json_encode(getContactsCountInList('LIST_ID'), JSON_PRETTY_PRINT);
// echo json_encode(updateContactListName('LIST_ID', 'Updated List Name from SDK'), JSON_PRETTY_PRINT);
// echo json_encode(removeContactsList('LIST_ID', [
//     'CONTACT_ID'
// ]), JSON_PRETTY_PRINT);