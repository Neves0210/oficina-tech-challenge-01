<?php

namespace App\Domain\Vehicles;

use InvalidArgumentException;

final readonly class Plate
{
    public string $value;

    public function __construct(string $input)
    {
        $value = strtoupper(str_replace(['-', ' '], '', $input));
        if (! preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/D', $value)) {
            throw new InvalidArgumentException('Informe uma placa brasileira válida.');
        }
        $this->value = $value;
    }
}
