<?php

namespace App\Domain\Materials;

enum MovementDirection: string
{
    case IN = 'IN';
    case OUT = 'OUT';
}
