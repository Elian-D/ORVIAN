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
        Schema::table('plantel_attendance_records', function (Blueprint $table) {
            $table->foreignId('corrected_by_user_id')
                ->nullable()
                ->constrained('users');

            $table->timestamp('corrected_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plantel_attendance_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('corrected_by_user_id');
            $table->dropColumn('corrected_at');
        });
    }
};