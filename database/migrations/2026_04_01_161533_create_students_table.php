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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            
            // Hacemos la sección nullable por si importan estudiantes pero aún no los asignan a un curso
            $table->foreignId('school_section_id')->nullable()->constrained('school_sections');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); 
            
            // DATOS PERSONALES ESENCIALES (Obligatorios)
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            
            // DATOS PERSONALES FLEXIBLES (Ahora opcionales)
            $table->enum('gender', ['M', 'F'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth', 255)->nullable();
            $table->string('rnc', 20)->nullable()->unique(); // Subí a 20 por si hay formatos extraños
            
            // DATOS MÉDICOS (Opcionales)
            $table->string('blood_type', 3)->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_conditions')->nullable();
            
            // IDENTIFICACIÓN Y BIOMETRÍA
            $table->string('photo_path')->nullable();
            $table->string('qr_code', 32)->unique()->index(); // Obligatorio: Es el motor del módulo de asistencia
            $table->longText('face_encoding')->nullable(); 
            
            // ESTADO Y FECHAS
            $table->boolean('is_active')->default(true);
            $table->date('enrollment_date')->nullable(); // Ahora opcional
            $table->date('withdrawal_date')->nullable();
            $table->text('withdrawal_reason')->nullable();
            
            // METADATA FLEXIBLE
            // NOTA: Aquí podrás guardar el ID del estudiante del sistema MINERD cuando logres hacer la importación
            // Ej: {"minerd_id": "12345678"}
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['school_id', 'is_active']);
            $table->index(['school_section_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};