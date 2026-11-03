<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('type', 6);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('unit', 20);
            $table->unsignedInteger('price_cents');
            $table->decimal('stock_quantity', 12, 3)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['type', 'id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction', 3);
            $table->decimal('quantity', 12, 3);
            $table->string('reason', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('materials');
    }
};
