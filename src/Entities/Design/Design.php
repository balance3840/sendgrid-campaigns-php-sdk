<?php

namespace SendgridCampaign\Entities\Design;

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\Design\DTO\DesignDTO;
use SendgridCampaign\Entities\Design\Enums\EditorType;

class Design extends BaseEntity
{
    public const BASE_ENDPOINT = 'designs';

    /**
     * @param integer $pageSize
     * @param string|null $pageToken
     * @param boolean|null $summary
     * @return BaseListDto<DesignDTO>|BaseErrorDTO
     */
    public function getAll(
        int $pageSize = 100,
        ?string $pageToken = null,
        ?bool $summary = true
    ): BaseListDto|BaseErrorDTO {
        $this->validateApiKey();

        $queryParams = [
            'page_token' => $pageToken,
            'page_size' => $pageSize,
            'summary' => $summary ? 'true' : 'false',
        ];

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT,
            queryParams: $queryParams
        );

        return $this->castListResponse($response, DesignDTO::class);
    }

    /**
     *
     * @param string $designId
     * @return DesignDTO|BaseErrorDTO
     */
    public function getById(string $designId): DesignDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response =$this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $designId
        );

        return $this->castSingleResponse($response, DesignDTO::class);
    }

    public function create(
        string $htmlContent,
        ?string $subject = null,
        ?string $name = null,
        ?EditorType $editor = null,
        ?string $plainContent = null
    ): DesignDTO|BaseErrorDTO {
        $this->validateApiKey();

        $body = [
            'html_content' => $htmlContent,
        ];

        if ($name !== null) {
            $body['name'] = $name;
        }

        if ($subject !== null) {
            $body['subject'] = $subject;
        }

        if ($editor !== null) {
            $body['editor'] = $editor->value;
        }

        if ($plainContent !== null) {
            $body['plain_content'] = $plainContent;
        }

        $response =$this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT,
            body: $body
        );

        return $this->castSingleResponse($response, DesignDTO::class);
    }

    public function duplicate(
        string $designId,
        ?string $name = null,
        ?EditorType $editor = null,
    ): DesignDTO|BaseErrorDTO {
        $this->validateApiKey();

        $body = [];

        if ($name !== null) {
            $body['name'] = $name;
        }

        if ($editor !== null) {
            $body['editor'] = $editor->value;
        }

        $response =$this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT . '/' . $designId,
            body: $body
        );

        return $this->castSingleResponse($response, DesignDTO::class);
    }

    /**
     * @param string $designId
     * @param string|null $htmlContent
     * @param string|null $name
     * @param string|null $subject
     * @param bool|null $generatePlainContent
     * @param EditorType|null $editor
     * @param string|null $plainContent
     * @param string[]|null $categories
     * @return DesignDTO|BaseErrorDTO
     */
    public function update(
        string $designId,
        ?string $htmlContent = null,
        ?string $name = null,
        ?string $subject = null,
        ?bool $generatePlainContent = true,
        ?string $plainContent = null,
        ?array $categories = null
    ): DesignDTO|BaseErrorDTO {
        $this->validateApiKey();

        $body = [];

        if ($htmlContent !== null) {
            $body['html_content'] = $htmlContent;
        }

        if ($name !== null) {
            $body['name'] = $name;
        }

        if ($plainContent !== null) {
            $body['plain_content'] = $plainContent;
        }

        if ($generatePlainContent !== null) {
            $body['generate_plain_content'] = $generatePlainContent ? true : false;
        }

        if ($subject !== null) {
            $body['subject'] = $subject;
        }

        if ($categories !== null) {
            if (count($categories) > 10) {
                throw new \InvalidArgumentException('You can only assign up to 10 categories to a design.');
            }
            $body['categories'] = $categories;
        }

        $response =$this->sendgridClient->patch(
            endpoint: self::BASE_ENDPOINT . '/' . $designId,
            body: $body
        );

        return $this->castSingleResponse($response, DesignDTO::class);
    }

    public function delete(string $designId): void
    {
        $this->validateApiKey();

       $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $designId
        );
    }
}
