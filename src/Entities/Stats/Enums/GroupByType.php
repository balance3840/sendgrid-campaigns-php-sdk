<?php

namespace SendgridCampaign\Entities\Stats\Enums;

enum GroupByType: string
{
    case AB_VARIATION = 'ab_variation';
    case AB_PHASE = 'ab_phase';
}