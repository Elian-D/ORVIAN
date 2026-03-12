<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            // cycle: para diferenciar 1er Ciclo y 2do Ciclo según MINERD
            $table->string('cycle')->after('order')->default(1);
            // allows_technical: define si en este grado se pueden inscribir títulos técnicos
            $table->boolean('allows_technical')->after('cycle')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropColumn(['cycle', 'allows_technical']);
        });
    }
};