<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Solo agregar tutor_name si no existe — validar contra migraciones de v0.4.0
            if (! Schema::hasColumn('students', 'tutor_name')) {
                $table->string('tutor_name', 120)
                      ->nullable()
                      ->after('last_name')
                      ->comment('Nombre completo del tutor o responsable del estudiante');
            }

            // tutor_phone es nuevo en v0.5.0
            $table->string('tutor_phone', 20)
                  ->nullable()
                  ->after('tutor_name')
                  ->comment('Número WhatsApp del tutor en formato E.164. Ej: +18091234567');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('tutor_phone');
            $table->dropColumn('tutor_name');
        });
    }
};