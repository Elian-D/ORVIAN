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
        Schema::create('school_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->cascadeOnDelete();
            
            // Ejemplo: Matutina, Vespertina, Nocturna, Jornada Extendida
            $table->string('type'); 
            
            // Horarios de la tanda
            $table->time('start_time')->comment('Hora de inicio de la tanda');
            $table->time('end_time')->comment('Hora de finalización de la tanda');
            
            $table->timestamps();

            // Evitar tandas duplicadas del mismo tipo en la misma escuela
            $table->unique(['school_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_shifts');
    }
};