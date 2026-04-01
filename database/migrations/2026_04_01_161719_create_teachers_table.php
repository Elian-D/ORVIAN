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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Acceso al sistema
            
            // Datos personales
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('gender', ['M', 'F']);
            $table->date('date_of_birth')->nullable();
            $table->string('rnc', 13)->nullable()->unique();
            
            // Datos profesionales
            $table->string('employee_code', 50)->nullable()->unique();
            $table->string('specialization', 255)->nullable(); // Ej: "Matemáticas", "Lengua Española"
            $table->enum('employment_type', ['Full-Time', 'Part-Time', 'Substitute'])->default('Full-Time');
            
            // Contacto
            $table->string('phone', 20)->nullable();
            $table->string('emergency_contact_name', 255)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            
            // Identificación
            $table->string('photo_path')->nullable();
            $table->string('qr_code', 32)->unique()->index();
            
            // Estado
            $table->boolean('is_active')->default(true);
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index(['school_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
