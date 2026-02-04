<?php

namespace SendgridCampaign\Entities\SingleSend\Enums;

enum AbTestType: string
{
    case SUBJECT = 'subject';
    case CONTENT = 'content';
}
