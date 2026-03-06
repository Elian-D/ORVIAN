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
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('sigerd_code')->unique();
            $table->string('name');
            
            // Caracterización
            $table->string('modalidad'); // Usaremos constantes en el modelo
            $table->string('sector');    // Público / Privado
            $table->string('jornada');   // Extendida, etc.
            
            // Ubicación (Relación con la geografía que ya importamos)
            // Usamos district_id como referencia principal, nullable por si es zona urbana directa
            $table->foreignId('district_id')->nullable()->constrained('districts');
            $table->foreignId('municipality_id')->constrained('municipalities');
            
            // Relación con Plan
            $table->foreignId('plan_id')->constrained('plans');
            
            // Estado del Tenant
            $table->boolean('is_active')->default(true);
            $table->boolean('is_configured')->default(false); // Para el Wizard
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
