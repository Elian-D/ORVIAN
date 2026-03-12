<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_sections', function (Blueprint $table) {
            // 1. Agregar nuevos campos
            $table->string('label', 10)->after('school_id')->comment('Ej: A, B, C');
            
            // FK hacia títulos técnicos (nullable porque no todas las secciones son técnicas)
            // Asumimos que la tabla de títulos se llama 'technical_titles'
            $table->foreignId('technical_title_id')
                  ->after('label')
                  ->nullable()
                  ->constrained('technical_titles')
                  ->nullOnDelete();

            // 2. Eliminar el campo viejo
            $table->dropColumn('name');
        });
    }

    public function down(): void
    {
        Schema::table('school_sections', function (Blueprint $table) {
            $table->string('name')->after('school_id');
            $table->dropForeign(['technical_title_id']);
            $table->dropColumn(['label', 'technical_title_id']);
        });
    }
};