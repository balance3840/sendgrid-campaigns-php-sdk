<?php
namespace SendgridCampaign\Entities\TestEmail;

use SendgridCampaign\Entities\BaseEntity;

class TestEmail extends BaseEntity
{
    public const BASE_ENDPOINT = 'marketing/test';

    public function send(
        string $templateId,
        array $emails,
        ?string $version_id_override = null,
        ?int $sender_id = null,
        ?string $custom_unsubscribe_url = null,
        ?int $suppression_group_id = null,
        ?string $from_address = null
    ): void {
        $this->validateApiKey();

        $body = [
            'template_id' => $templateId,
            'emails' => $emails,
        ];

        if ($version_id_override) {
            $body['version_id_override'] = $version_id_override;
        }

        if ($sender_id) {
            $body['sender_id'] = $sender_id;
        }

        if ($custom_unsubscribe_url) {
            $body['custom_unsubscribe_url'] = $custom_unsubscribe_url;
        }

        if ($suppression_group_id) {
            $body['suppression_group_id'] = $suppression_group_id;
        }

        if ($from_address) {
            $body['from_address'] = $from_address;
        }

       $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT . '/send_email',
            body: $body
        );
    }
}