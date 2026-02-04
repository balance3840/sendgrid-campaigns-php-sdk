<?php

namespace SendgridCampaign\DTO;

class SendgridErrorDTO extends BaseDTO
{
    public ?string $field = null;
    public ?string $message = null;
    public ?string $error_id = null;
    public ?int $status_code = null;
}
