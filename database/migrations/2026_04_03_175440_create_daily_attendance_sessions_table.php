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
        Schema::create('daily_attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_shift_id')->constrained('school_shifts');
            $table->date('date');

            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('opened_by')->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users');

            $table->integer('total_expected')->default(0);
            $table->integer('total_registered')->default(0);
            $table->integer('total_present')->default(0);
            $table->integer('total_late')->default(0);
            $table->integer('total_absent')->default(0);
            $table->integer('total_excused')->default(0);

            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'date', 'school_shift_id'], 'daily_session_unique');
            $table->index(['school_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_attendance_sessions');
    }
};
