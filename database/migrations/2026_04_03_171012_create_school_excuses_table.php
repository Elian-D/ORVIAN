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
    Schema::create('attendance_excuses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('school_id')->constrained()->cascadeOnDelete();
        $table->foreignId('student_id')->constrained()->cascadeOnDelete();

        $table->date('date_start');
        $table->date('date_end');

        // ← CAMBIO: string en lugar de enum para tipo de excusa
        $table->string('type', 30)->default('full_absence');

        $table->text('reason');
        $table->string('attachment_path')->nullable();

        // ← CAMBIO: string en lugar de enum para estado
        $table->string('status', 20)->default('pending');

        $table->foreignId('submitted_by')->constrained('users');
        $table->timestamp('submitted_at');
        $table->foreignId('reviewed_by')->nullable()->constrained('users');
        $table->timestamp('reviewed_at')->nullable();
        $table->text('review_notes')->nullable();

        $table->timestamps();
        $table->softDeletes();

        $table->index(['student_id', 'date_start', 'date_end']);
        $table->index(['school_id', 'status']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_excuses');
    }
};