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
        Schema::create('technical_families', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Ej: IFC, ART
            $table->string('name');
            $table->string('modality'); // Técnico Profesional o Modalidad en Artes
            $table->string('ordenance')->nullable(); // Ej: 06-2017
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_families');
    }
};
