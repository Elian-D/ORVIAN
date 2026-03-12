<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            // Hacemos nulables los campos que el usuario llenará en el Wizard
            $table->string('regimen_gestion')->nullable()->change();
            $table->string('modalidad')->nullable()->change();
            $table->string('regional_education_id')->nullable()->change();
            $table->string('educational_district_id')->nullable()->change();
            $table->unsignedBigInteger('municipality_id')->nullable()->change();
            // plan_id lo dejamos NOT NULL porque lo asignaremos por defecto
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('regimen_gestion')->nullable(false)->change();
            $table->string('modalidad')->nullable(false)->change();
            $table->string('regional_education_id')->nullable(false)->change();
            $table->string('educational_district_id')->nullable(false)->change();
            $table->unsignedBigInteger('municipality_id')->nullable(false)->change();
        });
    }
};
