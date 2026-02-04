<?php

namespace SendgridCampaign\Entities\Contact\Enums;

enum ContactImportFileType: string
{
    case CSV = 'csv';
    case JSON = 'json';
}
