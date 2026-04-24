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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();

            // Relación opcional con título técnico.
            // NULL = materia básica (Lengua Española, etc.)
            // Con ID = Módulo Formativo Técnico (MF_...)
            $table->foreignId('technical_title_id')
                  ->nullable()
                  ->constrained('technical_titles')
                  ->nullOnDelete();

            // Identificadores
            $table->string('code', 50)->unique();   // Ej: "ESP", "MF_053_3"
            $table->string('name', 255);            // Nombre del módulo o materia

            // Tipo de materia (Cambiado de ENUM a STRING para mayor flexibilidad)
            // Las constantes se definirán en el Modelo (ej: Subject::TYPE_BASIC)
            $table->string('type', 20)->default('basic');

            // Carga horaria
            $table->integer('hours_weekly')->default(0);  // Horas por semana
            $table->integer('hours_total')->default(0);   // Horas totales según el diseño curricular

            // Estética e Interfaz
            $table->string('color', 7)->default('#64748B');
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();

            // Índices para optimizar búsquedas frecuentes
            $table->index(['type', 'is_active']);
            $table->index('technical_title_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};