<?php

namespace SendgridCampaign\Entities\Design;

use SendgridCampaign\DTO\BaseErrorDTO;
use SendgridCampaign\DTO\BaseListDto;
use SendgridCampaign\Entities\BaseEntity;
use SendgridCampaign\Entities\Design\DTO\DesignDTO;
use SendgridCampaign\Entities\Design\Enums\EditorType;

/**
 * SendGrid Design (Email Template) Management Entity
 * 
 * This class manages email designs/templates in SendGrid Marketing Campaigns.
 * Designs are reusable email templates that can be used across multiple campaigns.
 * 
 * Key features:
 * - Create and store reusable email designs
 * - Support for both visual editor and HTML editor templates
 * - Duplicate designs for quick variations
 * 
 * Design types (editor modes):
 * - Code: Raw HTML editing for full control
 * - Design: Visual drag-and-drop editor
 * 
 * Use designs to:
 * - Maintain brand consistency across campaigns
 * - Speed up campaign creation
 * - Enable non-technical users to create emails
 * 
 * @package SendgridCampaign\Entities\Design
 * @see https://docs.sendgrid.com/api-reference/designs-api
 * 
 * @example
 * $design = new Design('your-api-key');
 * 
 * // Get all designs
 * $designs = $design->getAll();
 * 
 * // Create from HTML
 * $result = $design->create($designDTO);
 */
class Design extends BaseEntity
{
    public const BASE_ENDPOINT = 'designs';

    /**
     * Retrieves all designs from your account.
     * 
     * Returns both your custom designs and SendGrid's pre-built templates.
     * Use the 'summary' parameter to reduce response size when you only
     * need basic information.
     * 
     * @param int $pageSize Number of designs per page. Default: 10. Max: 50.
     * @param bool $summary If true, returns only basic info (faster response).
     *                      Default: false (returns full design details).
     * 
     * @return BaseListDto<DesignDTO>|BaseErrorDTO Returns a list of designs
     *         on success, or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/designs-api/list-designs
     * 
     * @example
     * // Get all designs with full details
     * $designs = $entity->getAll(pageSize: 50);
     * 
     * // Get summary only (faster)
     * $summaries = $entity->getAll(summary: true);
     * 
     * if (!$designs instanceof BaseErrorDTO) {
     *     foreach ($designs->result as $design) {
     *         echo "{$design->name} - {$design->editor->value}\n";
     *     }
     * }
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

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT,
            queryParams: $queryParams
        );

        return $this->castListResponse($response, DesignDTO::class);
    }

    /**
     * Retrieves a specific design by its ID.
     * 
     * Returns the complete design including HTML content, design metadata,
     * and editor configuration.
     * 
     * @param string $designId The unique identifier of the design.
     * 
     * @return DesignDTO|BaseErrorDTO Returns the design data on success,
     *         or BaseErrorDTO if not found.
     * 
     * @see https://docs.sendgrid.com/api-reference/designs-api/get-design
     * 
     * @example
     * $design = $entity->getById('design-uuid');
     * if (!$design instanceof BaseErrorDTO) {
     *     echo "Name: {$design->name}\n";
     *     echo "HTML length: " . strlen($design->html_content) . " chars\n";
     * }
     */
    public function getById(string $designId): DesignDTO|BaseErrorDTO
    {
        $this->validateApiKey();

        $response = $this->sendgridClient->get(
            endpoint: self::BASE_ENDPOINT . '/' . $designId
        );

        return $this->castSingleResponse($response, DesignDTO::class);
    }

    /**
     * Creates a new email design.
     * 
     * You can create designs with either raw HTML content or using the
     * visual editor format. For Code editor designs, provide html_content.
     * For Design editor designs, provide the design JSON structure.
     * 
     * Required fields:
     * - name: A unique name for the design
     * - html_content: The HTML template (for Code editor)
     * - OR generate_plain_content with design JSON (for Design editor)
     * 
     * @param DesignDTO $design DTO containing design configuration:
     *        - name: Design name (required)
     *        - html_content: HTML template content
     *        - plain_content: Plain text version (optional)
     *        - subject: Default email subject (optional)
     *        - editor: Editor type (code or design)
     * 
     * @return DesignDTO|BaseErrorDTO Returns the created design with its ID
     *         on success, or BaseErrorDTO on validation errors.
     * 
     * @see https://docs.sendgrid.com/api-reference/designs-api/create-design
     * 
     * @example
     * $design = DesignDTO::fromArray([
     *     'name' => 'Monthly Newsletter Template',
     *     'html_content' => '<html>...</html>',
     *     'subject' => '{{month}} Newsletter',
     *     'editor' => 'code'
     * ]);
     * 
     * $result = $entity->create($design);
     * if (!$result instanceof BaseErrorDTO) {
     *     echo "Created design: {$result->id}\n";
     * }
     */
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

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT,
            body: $body
        );

        return $this->castSingleResponse($response, DesignDTO::class);
    }

    /**
     * Duplicates an existing design.
     * 
     * Creates a copy of the design with a new name. Useful for creating
     * variations of a template without modifying the original.
     * 
     * @param string $designId The ID of the design to duplicate.
     * @param string $name The name for the new duplicated design.
     *                     Must be unique in your account.
     * @param EditorType|null $editor Optional editor type for the copy.
     *                                If null, uses the same editor as original.
     * 
     * @return DesignDTO|BaseErrorDTO Returns the new design on success,
     *         or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/designs-api/duplicate-design
     * 
     * @example
     * $copy = $entity->duplicate(
     *     designId: 'original-uuid',
     *     name: 'Newsletter Template (Holiday Version)'
     * );
     */
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

        $response = $this->sendgridClient->post(
            endpoint: self::BASE_ENDPOINT . '/' . $designId,
            body: $body
        );

        return $this->castSingleResponse($response, DesignDTO::class);
    }

    /**
     * Updates an existing design.
     * 
     * You can update any design property. Make sure to include the design ID.
     * This performs a full replacement of the specified fields.
     * 
     * @param DesignDTO $design DTO with updated design data. Must include ID.
     * 
     * @return DesignDTO|BaseErrorDTO Returns the updated design on success,
     *         or BaseErrorDTO on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/designs-api/update-design
     * 
     * @example
     * $design = $entity->getById('design-uuid');
     * $design->name = 'Updated Newsletter Template';
     * $design->html_content = '<html>Updated content...</html>';
     * 
     * $result = $entity->update($design);
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

        $response = $this->sendgridClient->patch(
            endpoint: self::BASE_ENDPOINT . '/' . $designId,
            body: $body
        );

        return $this->castSingleResponse($response, DesignDTO::class);
    }

    /**
     * Deletes a design.
     * 
     * Deleting a design does not affect campaigns that have already used it.
     * Those campaigns retain a copy of the design at the time they were created.
     * 
     * ⚠️ This action is permanent and cannot be undone.
     * 
     * @param string $designId The ID of the design to delete.
     * 
     * @return bool|BaseErrorDTO Returns true on success, or BaseErrorDTO
     *         on failure.
     * 
     * @see https://docs.sendgrid.com/api-reference/designs-api/delete-design
     * 
     * @example
     * $result = $entity->delete('design-uuid');
     * if ($result === true) {
     *     echo "Design deleted";
     * }
     */
    public function delete(string $designId): void
    {
        $this->validateApiKey();

        $this->sendgridClient->delete(
            endpoint: self::BASE_ENDPOINT . '/' . $designId
        );
    }
}
