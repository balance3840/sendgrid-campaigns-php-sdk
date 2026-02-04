<?php

namespace SendgridCampaign\Entities\SingleSend\Enums;

enum StatusType: string
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case TRIGGERED = 'triggered';
}
