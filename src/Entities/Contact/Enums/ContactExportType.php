<?php

namespace SendgridCampaign\Entities\Contact\Enums;

enum ContactExportType: string
{
    case CONTACTS_EXPORT = 'contacts_export';
    case LIST_EXPORT = 'list_export';
    case SEGMENT_EXPORT = 'segment_export';
}
