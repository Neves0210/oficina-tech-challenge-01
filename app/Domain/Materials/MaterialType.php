<?php

namespace App\Domain\Materials;

use InvalidArgumentException;

enum MaterialType: string
{
    case PART = 'PART';
    case SUPPLY = 'SUPPLY';

    public function validateQuantity(Quantity $quantity): void
    {
        $quantity->assertPositive();
        if ($this === self::PART && ! $quantity->isWhole()) {
            throw new InvalidArgumentException('Peças exigem quantidade inteira.');
        }
    }
}
