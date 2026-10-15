<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();

            // Decimal allows fractional quantities if selling by weight/length, but for mobile phones it will be whole numbers (e.g., 5.000)
            $table->decimal('quantity_on_hand', 14, 3)->default(0);
            $table->decimal('quantity_reserved', 14, 3)->default(0); // Reserved for pending online orders or layaways
            $table->decimal('reorder_level', 14, 3)->default(0);    // Low-stock alert threshold

            $table->timestamps();

            // Composite unique index: A location can only have one balance row per product variant
            $table->unique(['stock_location_id', 'product_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
