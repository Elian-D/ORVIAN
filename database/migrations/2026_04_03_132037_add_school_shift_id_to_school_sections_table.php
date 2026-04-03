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
        Schema::table('school_sections', function (Blueprint $table) {
            $table->foreignId('school_shift_id')
                ->after('school_id')
                ->constrained('school_shifts')
                ->cascadeOnDelete();
            
            // Índice para optimizar consultas por tanda (Performance)
            $table->index(['school_id', 'school_shift_id'], 'idx_sections_school_shift');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('school_sections', function (Blueprint $table) {
            // Eliminar la llave foránea e índice antes de borrar la columna
            $table->dropForeign(['school_shift_id']);
            $table->dropIndex('idx_sections_school_shift');
            $table->dropColumn('school_shift_id');
        });
    }
};