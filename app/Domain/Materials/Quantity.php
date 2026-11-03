<?php

namespace App\Domain\Materials;

use App\Domain\Shared\BusinessRuleViolation;
use InvalidArgumentException;

final readonly class Quantity
{
    public const MAX_MILLIUNITS = 999999999999;

    public int $milliunits;

    public function __construct(string|int $value)
    {
        if (! preg_match('/^(0|[1-9][0-9]{0,8})(?:\.([0-9]{1,3}))?$/D', (string) $value, $matches)) {
            throw new InvalidArgumentException('Use quantidade decimal sem sinal, com até três casas e máximo de 999999999.999.');
        }
        $this->milliunits = (int) $matches[1] * 1000 + (int) str_pad($matches[2] ?? '', 3, '0');
    }

    public function format(): string
    {
        return intdiv($this->milliunits, 1000).'.'.str_pad((string) ($this->milliunits % 1000), 3, '0', STR_PAD_LEFT);
    }

    public function isWhole(): bool
    {
        return $this->milliunits % 1000 === 0;
    }

    public function assertPositive(): void
    {
        if ($this->milliunits === 0) {
            throw new InvalidArgumentException('A quantidade deve ser positiva.');
        }
    }

    public function plus(self $other): self
    {
        return self::fromMilliunits($this->milliunits + $other->milliunits);
    }

    public function minus(self $other): self
    {
        return self::fromMilliunits($this->milliunits - $other->milliunits);
    }

    private static function fromMilliunits(int $value): self
    {
        if ($value < 0) {
            throw new BusinessRuleViolation('Estoque insuficiente.');
        }
        if ($value > self::MAX_MILLIUNITS) {
            throw new BusinessRuleViolation('O saldo ultrapassa o limite de estoque.');
        }

        return new self(intdiv($value, 1000).'.'.str_pad((string) ($value % 1000), 3, '0', STR_PAD_LEFT));
    }
}
