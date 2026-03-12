<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Reemplazo de sector por régimen de gestión
            $table->string('regimen_gestion')->after('name'); 
            $table->dropColumn('sector');

            // Geografía Educativa (MINERD)
            $table->string('regional_education_id')->after('regimen_gestion');
            $table->string('educational_district_id')->after('regional_education_id');

            // Foreign Keys para Geografía Educativa
            $table->foreign('regional_education_id')->references('id')->on('regional_education');
            $table->foreign('educational_district_id')->references('id')->on('educational_districts');

            // Información de Contacto y Dirección Detallada
            $table->string('phone')->nullable()->after('modalidad');
            $table->text('address_detail')->nullable()->after('phone');

            // Ajuste de nulabilidad para migración de datos
            $table->string('modalidad')->change();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropForeign(['regional_education_id']);
            $table->dropForeign(['educational_district_id']);
            $table->dropColumn(['regimen_gestion', 'regional_education_id', 'educational_district_id', 'phone', 'address_detail']);
            $table->string('sector')->after('name');
        });
    }
};