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
            $table->foreignId('school_section_id')->constrained('school_sections');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Para Classroom Virtual
            
            // Datos personales
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('gender', ['M', 'F']);
            $table->date('date_of_birth');
            $table->string('place_of_birth', 255)->nullable();
            $table->string('rnc', 13)->nullable()->unique(); // Cédula: 402-1234567-8
            
            // Datos médicos
            $table->string('blood_type', 3)->nullable();
            $table->text('allergies')->nullable();
            $table->text('medical_conditions')->nullable();
            
            // Identificación y biometría
            $table->string('photo_path')->nullable();
            $table->string('qr_code', 32)->unique()->index();
            $table->longText('face_encoding')->nullable(); // JSON encoding facial
            
            // Estado y fechas
            $table->boolean('is_active')->default(true);
            $table->date('enrollment_date');
            $table->date('withdrawal_date')->nullable();
            $table->text('withdrawal_reason')->nullable();
            
            // Metadata flexible
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
