<?php

namespace App\Enums;

enum RoleName: string
{
    case ADMIN = 'admin';
    case RECEPTIONIST = 'receptionist';
    case CASHIER = 'cashier';
    case PHARMACIST = 'pharmacist';
}
