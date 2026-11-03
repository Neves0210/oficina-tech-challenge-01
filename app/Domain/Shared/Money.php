<?php

namespace App\Domain\Shared;

use App\Domain\Materials\Quantity;

final readonly class Money
{
    // Mantém os centavos exatos também para consumidores JSON/JavaScript.
    public const MAX_CENTS = 9007199254740991;

    public function __construct(public int $cents)
    {
        if ($cents < 0 || $cents > self::MAX_CENTS) {
            throw new BusinessRuleViolation('Valor monetário fora do limite permitido.');
        }
    }

    public function plus(self $other): self
    {
        if ($this->cents > self::MAX_CENTS - $other->cents) {
            throw new BusinessRuleViolation('O total ultrapassa o limite monetário.');
        }

        return new self($this->cents + $other->cents);
    }

    public function times(Quantity $quantity): self
    {
        $whole = intdiv($quantity->milliunits, 1000);
        if ($whole > 0 && $this->cents > intdiv(self::MAX_CENTS, $whole)) {
            throw new BusinessRuleViolation('O subtotal ultrapassa o limite monetário.');
        }
        $fraction = intdiv($this->cents * ($quantity->milliunits % 1000) + 500, 1000);

        return new self($this->cents * $whole + $fraction);
    }
}
