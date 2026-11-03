<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Orders\BudgetDecision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderBudget extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['decision' => BudgetDecision::class, 'total_cents' => 'integer', 'version' => 'integer', 'presented_at' => 'immutable_datetime', 'decided_at' => 'immutable_datetime'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderBudgetItem::class)->orderBy('id');
    }
}
