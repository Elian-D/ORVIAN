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
        Schema::create('classroom_attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_subject_section_id')
                    ->constrained('teacher_subject_sections')
                    ->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('class_time');

            // ← CAMBIO: string en lugar de enum
            $table->string('status', 20)->default('present');

            $table->text('teacher_notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['student_id', 'teacher_subject_section_id', 'date'],
                'classroom_attendance_unique'
            );
            $table->index(['school_id', 'date']);
            $table->index(['teacher_id', 'date']);
            $table->index(['status', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_attendance_records');
    }
};
