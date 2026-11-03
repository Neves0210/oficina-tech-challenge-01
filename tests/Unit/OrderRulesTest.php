<?php

namespace Tests\Unit;

use App\Domain\Materials\Quantity;
use App\Domain\Orders\BudgetDecision;
use App\Domain\Orders\OrderStatus;
use App\Domain\Shared\BusinessRuleViolation;
use App\Domain\Shared\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class OrderRulesTest extends TestCase
{
    public function test_pending_is_not_a_customer_decision(): void
    {
        $this->expectException(BusinessRuleViolation::class);
        BudgetDecision::PENDING->assertDecisionAllowed(BudgetDecision::PENDING);
    }

    public function test_subtotals_round_each_item_half_up_with_exact_cents(): void
    {
        $one = (new Money(101))->times(new Quantity('0.5'));
        $this->assertSame(51, $one->cents);
        $this->assertSame(102, $one->plus($one)->cents);
        $this->assertSame(303, (new Money(101))->times(new Quantity(3))->cents);
        $this->assertSame(0, (new Money(0))->times(new Quantity('999999999.999'))->cents);
    }

    #[DataProvider('invalidMoney')]
    public function test_money_limits_reject_overflow_before_losing_precision(string $operation): void
    {
        $this->expectException(BusinessRuleViolation::class);
        match ($operation) {
            'negative' => new Money(-1),
            'constructor' => new Money(Money::MAX_CENTS + 1),
            'sum' => (new Money(Money::MAX_CENTS))->plus(new Money(1)),
            'multiply' => (new Money(Money::MAX_CENTS))->times(new Quantity(2)),
            'fraction' => (new Money(Money::MAX_CENTS))->times(new Quantity('1.001')),
        };
    }

    public static function invalidMoney(): array
    {
        return array_map(fn ($v) => [$v], ['negative', 'constructor', 'sum', 'multiply', 'fraction']);
    }

    #[DataProvider('transitions')]
    public function test_exactly_the_documented_transitions_are_allowed(OrderStatus $from, OrderStatus $to, bool $allowed): void
    {
        if (! $allowed) {
            $this->expectException(BusinessRuleViolation::class);
        }
        $from->assertTransitionTo($to);
        if ($allowed) {
            $this->assertTrue(true);
        }
    }

    public static function transitions(): array
    {
        $allowed = [
            [OrderStatus::RECEIVED, OrderStatus::DIAGNOSIS],
            [OrderStatus::DIAGNOSIS, OrderStatus::WAITING],
            [OrderStatus::WAITING, OrderStatus::DIAGNOSIS],
            [OrderStatus::WAITING, OrderStatus::EXECUTING],
            [OrderStatus::EXECUTING, OrderStatus::FINISHED],
            [OrderStatus::FINISHED, OrderStatus::DELIVERED],
        ];
        $cases = [];
        foreach (OrderStatus::cases() as $from) {
            foreach (OrderStatus::cases() as $to) {
                $cases[] = [$from, $to, in_array([$from, $to], $allowed, true)];
            }
        }

        return $cases;
    }

    public function test_an_order_awaiting_approval_is_not_editable(): void
    {
        OrderStatus::RECEIVED->assertEditable();
        OrderStatus::DIAGNOSIS->assertEditable();
        $this->expectException(BusinessRuleViolation::class);
        OrderStatus::WAITING->assertEditable();
    }
}
