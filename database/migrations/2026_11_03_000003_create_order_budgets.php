<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('version');
            $table->unsignedBigInteger('total_cents');
            $table->string('decision', 8)->default('PENDING');
            $table->timestamp('presented_at');
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['service_order_id', 'version']);
        });
        Schema::create('order_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workshop_service_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('kind', 7);
            $table->string('name');
            $table->string('unit', 20);
            $table->decimal('quantity', 12, 3);
            $table->unsignedInteger('unit_price_cents');
            $table->unsignedBigInteger('subtotal_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_budget_items');
        Schema::dropIfExists('order_budgets');
    }
};
