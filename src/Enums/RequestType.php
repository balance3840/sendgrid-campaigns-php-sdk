<?php

namespace SendgridCampaign\Enums;

enum RequestType: string {
    case POST = 'post';
    case GET = 'get';
    case PUT = 'put'; 
    case DELETE = 'delete';
    case PATCH = 'patch';
}