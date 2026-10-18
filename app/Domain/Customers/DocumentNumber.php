<?php

namespace App\Domain\Customers;

use InvalidArgumentException;

final readonly class DocumentNumber
{
    public string $value;

    public string $type;

    public function __construct(string $input)
    {
        $value = strtoupper(str_replace(['.', '/', '-', ' '], '', $input));
        if (preg_match('/^[0-9]{11}$/D', $value)) {
            $type = 'CPF';
            $weights = [range(10, 2), range(11, 2)];
            $length = 9;
        } elseif (preg_match('/^[A-Z0-9]{12}[0-9]{2}$/D', $value)) {
            $type = 'CNPJ';
            $weights = [[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]];
            $length = 12;
        } else {
            throw new InvalidArgumentException('Informe um CPF ou CNPJ válido.');
        }

        if (preg_match('/^(.)\1+$/D', $value)) {
            throw new InvalidArgumentException('Informe um CPF ou CNPJ válido.');
        }

        $base = substr($value, 0, $length);
        foreach ($weights as $round) {
            $sum = 0;
            foreach ($round as $index => $weight) {
                $sum += (ord($base[$index]) - 48) * $weight;
            }
            $remainder = $sum % 11;
            $base .= (string) ($remainder < 2 ? 0 : 11 - $remainder);
        }
        if ($base !== $value) {
            throw new InvalidArgumentException('Informe um CPF ou CNPJ válido.');
        }
        $this->value = $value;
        $this->type = $type;
    }
}
