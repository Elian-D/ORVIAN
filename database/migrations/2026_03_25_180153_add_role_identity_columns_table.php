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
        Schema::table('roles', function (Blueprint $table) {
            $table->string('color', 7)->nullable()->after('guard_name')
                    ->comment('Color hexadecimal para badge (ej. #9333EA)');
            $table->boolean('is_system')->default(false)->after('color')
                    ->comment('Protección contra eliminación/renombrado');
            
            $table->index(['school_id', 'is_system']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // 1. Eliminar el índice compuesto primero
            // Laravel por defecto nombra los índices como: tabla_columna1_columna2_index
            $table->dropIndex(['school_id', 'is_system']);

            // 2. Eliminar las columnas creadas
            $table->dropColumn(['color', 'is_system']);
        });
    }
};
