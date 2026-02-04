<?php

namespace SendgridCampaign\Entities\CustomField\Enums;

enum CustomFieldType: string
{
    case TEXT = 'Text';
    case NUMBER = 'Number';
    case DATE = 'Date';
}
