<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('code', 40)->nullable()->unique()->after('parent_id');
            $table->integer('sort_order')->default(0)->after('description');
            $table->foreignId('created_by')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['code', 'sort_order', 'created_by']);
        });
    }
};
