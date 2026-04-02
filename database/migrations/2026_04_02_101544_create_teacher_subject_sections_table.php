<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teacher_subject_sections', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_section_id')->constrained('school_sections')->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            
            // Estado y Auditoría
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // REGLA DE ORO: Evita duplicados de asignación
            $table->unique(
                ['teacher_id', 'subject_id', 'school_section_id', 'academic_year_id'], 
                'unique_teacher_assignment'
            );

            // CORRECCIÓN: Se agrega un nombre corto al índice ('tss_section_year_idx')
            // para evitar el error de los 64 caracteres de MySQL.
            $table->index(
                ['school_section_id', 'academic_year_id'], 
                'tss_section_year_index' 
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_subject_sections');
    }
};