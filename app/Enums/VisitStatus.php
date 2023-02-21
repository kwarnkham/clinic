<?php

namespace App\Enums;

enum VisitStatus: int
{
    case PENDING = 1;
    case PRODUCTS_ADDED = 2;
    case CONFIRMED = 3;
    case COMPLETED = 4;
    case CANCELED = 5;
}
