<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identidad y Perfil
            $table->string('avatar_path')->nullable()->after('password');
            $table->string('avatar_color', 7)->nullable()->after('avatar_path');
            $table->string('phone')->nullable()->after('avatar_color');
            $table->string('position')->nullable()->after('phone');
            
            // Estado y Presencia
            $table->string('status')->default('offline')->after('position'); // online, away, busy, offline
            $table->timestamp('last_login_at')->nullable()->after('status');
            
            // Preferencias de UI (JSON)
            $table->json('preferences')->nullable()->after('last_login_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar_path', 
                'avatar_color', 
                'phone', 
                'position', 
                'status',
                'last_login_at',
                'preferences'
            ]);
        });
    }
};