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
        Schema::table('student_import_records', function (Blueprint $table) {
            $table->integer('waiting_room_rows')->default(0)->after('failed_rows');
        });
    }

    public function down(): void
    {
        Schema::table('student_import_records', function (Blueprint $table) {
            $table->dropColumn('waiting_room_rows');
        });
    }
};
