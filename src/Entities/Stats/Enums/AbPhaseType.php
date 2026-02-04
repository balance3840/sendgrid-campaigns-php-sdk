<?php

namespace SendgridCampaign\Entities\Stats\Enums;

enum AbPhaseType: string
{
    case SEND = 'send';
    case TEST = 'test';
    case ALL = 'all';
}
