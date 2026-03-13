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
    Schema::table('kontaks', function (Blueprint $table) {
        $table->timestamp('dibalas_pada')->nullable()->after('dibaca_pada');
        $table->string('balasan_subjek')->nullable()->after('dibalas_pada');
    });
}

public function down(): void
{
    Schema::table('kontaks', function (Blueprint $table) {
        $table->dropColumn(['dibalas_pada', 'balasan_subjek']);
    });
}
};
