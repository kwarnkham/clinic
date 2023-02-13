<?php

namespace App\Enums;

enum ResponseStatus: int
{
    case OK = 200;
    case CREATED = 201;
    case REDIRECTED_BACK = 302;
    case BAD_REQUEST = 400;
    case UNAUTHENTICATED = 401;
    case PAYMENT_REQUIRED = 402;
    case UNAUTHORIZED = 403;
    case NOT_FOUND = 404;
    case VALIDATION_ERROR = 422;
    case SERVER_ERROR = 500;
}
