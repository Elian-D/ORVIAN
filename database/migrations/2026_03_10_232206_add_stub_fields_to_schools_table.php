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
            // TTL para limpieza de registros basura (24h por defecto en la lógica)
            $table->timestamp('stub_expires_at')->nullable()->after('is_configured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Eliminamos la columna si se revierte la migración
            $table->dropColumn('stub_expires_at');
        });
    }
};