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
            // Hash bcrypt del PIN numérico de 4-6 dígitos.
            // Null = sin PIN configurado (primer arranque, setup libre).
            $table->string('kiosk_pin')->nullable()->after('logo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn('kiosk_pin');
        });
    }
};