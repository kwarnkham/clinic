<?php

namespace App\Enums;

enum PurchaseStatus: int
{
    case NORMAL = 1;
    case CANCELED = 2;
}
