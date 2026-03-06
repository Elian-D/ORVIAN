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
        // Catálogo maestro de lo que el sistema puede hacer
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // Ej: "Reconocimiento Facial"
            $table->string('slug')->unique(); // Ej: "attendance_facial"
            $table->string('module');        // Ej: "Asistencia", "Académico", etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla pivote para asignar features a planes
        Schema::create('feature_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->onDelete('cascade');
            $table->foreignId('feature_id')->constrained()->onDelete('cascade');
            $table->json('settings')->nullable(); // Para límites extra si se desea
            $table->timestamps();
        });

        // Añadir estado al Plan
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_plan');
        Schema::dropIfExists('features');
        
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
