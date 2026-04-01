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
            // Multimedia
            $table->string('logo_path')->nullable()->after('name');
            
            // Geografía Política (Provincia)
            // Nota: Se asume que la tabla se llama 'provinces'
            $table->foreignId('province_id')
                ->nullable()
                ->after('municipality_id')
                ->constrained('provinces')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['province_id']);
            $table->dropColumn(['logo_path', 'province_id']);
        });
    }
};