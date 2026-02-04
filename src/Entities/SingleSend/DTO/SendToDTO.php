<?php

namespace SendgridCampaign\Entities\SingleSend\DTO;

use SendgridCampaign\DTO\BaseDTO;

class SendToDTO extends BaseDTO
{
    /**
     * @param string[]|null $list_ids
     * @param string[]|null $segment_ids
     * @param bool|null $all
     */
    public function __construct(
        public ?array $list_ids = null,
        public ?array $segment_ids = null,
        public ?bool $all = null
    ) {
    }
}
