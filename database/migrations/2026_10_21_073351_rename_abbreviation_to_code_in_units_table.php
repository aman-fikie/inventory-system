<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Rename the abbreviation column to code
            $table->renameColumn('abbreviation', 'code');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            // Rollback change if needed
            $table->renameColumn('code', 'abbreviation');
        });
    }
};
