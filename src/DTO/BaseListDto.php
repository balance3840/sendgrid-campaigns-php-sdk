<?php

namespace SendgridCampaign\DTO;

/**
 * @template T of BaseDTO
 */
class BaseListDto extends BaseDTO
{
    /**
     * Summary of __construct
     * @param T[]|null $result
     * @param MetadataDTO|null $_metadata
     */
    public function __construct(
        public ?array $result = null,
        public ?MetadataDTO $_metadata = null
    ) {
    }
}
