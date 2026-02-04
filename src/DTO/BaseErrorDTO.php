<?php

namespace SendgridCampaign\DTO;

class BaseErrorDTO extends BaseDTO
{
    /**
     * @var SendgridErrorDTO[]
     */
    public array $errors = [];
}
