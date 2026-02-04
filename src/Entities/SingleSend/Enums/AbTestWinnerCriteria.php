<?php

namespace SendgridCampaign\Entities\SingleSend\Enums;

enum AbTestWinnerCriteria: string
{
    case OPEN = 'open';
    case CLICK = 'click';
    case MANUAL = 'manual';
}
