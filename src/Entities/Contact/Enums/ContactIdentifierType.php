<?php

namespace SendgridCampaign\Entities\Contact\Enums;

enum ContactIdentifierType: string
{
    case EMAIL = 'EMAIL';
    case PHONENUMBERID = 'PHONENUMBERID';
    case EXTERNALID = 'EXTERNALID';
    case ANONYMOUSID = 'ANONYMOUSID';
}
