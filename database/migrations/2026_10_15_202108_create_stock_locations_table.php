<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            // Belongs to a store. If store is deleted, cascade delete locations.
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();

            $table->string('code', 30)->unique();
            $table->string('name', 120);

            // Location type enum/string (warehouse, shop_floor, returns, transit, damaged)
            $table->string('location_type', 40);

            $table->boolean('is_sellable')->default(false); // Only shop_floor usually has true here
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_locations');
    }
};
