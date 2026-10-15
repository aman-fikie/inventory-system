<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();

            // Every variant belongs to a product. If the product is deleted, delete its variants too.
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // Unique business identifiers for scanning and tracking
            $table->string('sku', 80)->unique();
            $table->string('barcode', 100)->unique()->nullable();

            $table->string('variant_name', 120)->default('Default');

            // Flexible attributes for variable products (stored as JSON, e.g., {"color": "Red", "size": "L"})
            $table->json('attributes')->nullable();

            // Pricing fields (decimal(14,2) prevents floating-point currency rounding errors)
            $table->decimal('cost_price', 14, 2)->default(0);
            $table->decimal('selling_price', 14, 2)->default(0);
            $table->decimal('wholesale_price', 14, 2)->nullable();
            $table->decimal('minimum_selling_price', 14, 2)->nullable();

            $table->decimal('weight', 12, 3)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
