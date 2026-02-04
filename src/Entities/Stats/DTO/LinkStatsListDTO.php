<?php

namespace SendgridCampaign\Entities\Stats\DTO;

use SendgridCampaign\DTO\BaseListDto;

class LinkStatsListDTO extends BaseListDto
{
    public ?int $total_clicks = null;
}
