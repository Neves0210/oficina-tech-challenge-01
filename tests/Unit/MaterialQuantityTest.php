<?php

namespace Tests\Unit;

use App\Domain\Materials\MaterialType;
use App\Domain\Materials\Quantity;
use App\Domain\Shared\BusinessRuleViolation;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MaterialQuantityTest extends TestCase
{
    #[DataProvider('validQuantities')]
    public function test_quantity_has_an_exact_canonical_representation(string|int $input, string $expected): void
    {
        $this->assertSame($expected, (new Quantity($input))->format());
    }

    public static function validQuantities(): array
    {
        return [[0, '0.000'], [2, '2.000'], ['0.1', '0.100'], ['2.345', '2.345'], ['999999999.999', '999999999.999']];
    }

    #[DataProvider('invalidQuantities')]
    public function test_invalid_or_ambiguous_quantities_are_rejected(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Quantity($input);
    }

    public static function invalidQuantities(): array
    {
        return array_map(fn ($v) => [$v], ['', '-1', '+1', '01', '1,5', '1e2', '1.0001', '1000000000', '1.', '.5', 'abc']);
    }

    public function test_fractional_addition_and_subtraction_do_not_use_floating_point(): void
    {
        $sum = (new Quantity('0.1'))->plus(new Quantity('0.2'));
        $this->assertSame('0.300', $sum->format());
        $this->assertSame('0.000', $sum->minus(new Quantity('0.300'))->format());
    }

    public function test_negative_balance_is_rejected(): void
    {
        $this->expectException(BusinessRuleViolation::class);
        (new Quantity('0.1'))->minus(new Quantity('0.2'));
    }

    public function test_balance_overflow_is_rejected(): void
    {
        $this->expectException(BusinessRuleViolation::class);
        (new Quantity('999999999.999'))->plus(new Quantity('0.001'));
    }

    public function test_parts_allow_whole_quantities_and_supplies_allow_fractions(): void
    {
        MaterialType::PART->validateQuantity(new Quantity('2.000'));
        MaterialType::SUPPLY->validateQuantity(new Quantity('0.001'));
        $this->expectException(InvalidArgumentException::class);
        MaterialType::PART->validateQuantity(new Quantity('0.001'));
    }

    public function test_movements_cannot_have_zero_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MaterialType::SUPPLY->validateQuantity(new Quantity(0));
    }
}
