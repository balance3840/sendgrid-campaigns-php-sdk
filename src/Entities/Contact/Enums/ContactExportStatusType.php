<?php

namespace SendgridCampaign\Entities\Contact\Enums;

enum ContactExportStatusType: string
{
    case PENDING = 'pending';
    case READY = 'ready';
    case FAILURE = 'failure';
}
