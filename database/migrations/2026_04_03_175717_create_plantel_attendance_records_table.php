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
        Schema::create('plantel_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('daily_attendance_session_id')
                    ->constrained('daily_attendance_sessions')
                    ->cascadeOnDelete();
            $table->foreignId('school_shift_id')->constrained('school_shifts');
            $table->date('date');
            $table->time('time');

            // ← CAMBIO: string en lugar de enum
            $table->string('status', 20)->default('present');
            // ← CAMBIO: string en lugar de enum
            $table->string('method', 20)->default('manual');

            $table->foreignId('registered_by')->nullable()->constrained('users');

            $table->decimal('temperature', 4, 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');

            $table->timestamps();

            $table->unique(['student_id', 'date', 'school_shift_id'], 'plantel_attendance_unique');
            $table->index(['school_id', 'date']);
            $table->index(['daily_attendance_session_id']);
            $table->index(['status', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantel_attendance_records');
    }
};
