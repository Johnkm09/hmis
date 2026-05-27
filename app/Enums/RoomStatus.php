<?php

namespace App\Enums;

enum RoomStatus:string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case RESERVED = 'reserved';
    case MAINTENANCE = 'maintenance';
}
