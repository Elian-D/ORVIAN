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
        Schema::table('schools', function (Blueprint $table) {
            // Campos de Estado (después de is_active)
            $table->boolean('is_suspended')->default(false)->after('is_active');
            
            // Campos de Ubicación (después de address_detail para mayor coherencia)
            // Usamos 10,8 para una precisión de aproximadamente 1.1 metros, ideal para Google Maps
            $table->decimal('latitude', 10, 8)->nullable()->after('address_detail');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['is_suspended', 'latitude', 'longitude']);
        });
    }
};