<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('school_shifts', function (Blueprint $table) {
            // Minutos después del start_time que se considera "Tardanza"
            $table->unsignedSmallInteger('late_threshold_minutes')->default(0)->after('end_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_shifts', function (Blueprint $table) {
            $table->dropColumn([
                'late_threshold_minutes',
            ]);
        });
    }
};