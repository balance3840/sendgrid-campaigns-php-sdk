<?php

namespace SendgridCampaign\DTO;

class MetadataDTO extends BaseDTO
{
    public ?string $self = null;
    public ?string $next = null;
    public ?string $prev = null;
    public ?int $count = null;
}
