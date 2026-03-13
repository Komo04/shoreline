<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::table('keranjangs', function (Blueprint $table) {
        $table->foreignId('varian_id')
              ->after('produk_id')
              ->constrained('produk_varians')
              ->cascadeOnDelete();
    });
}

public function down()
{
    Schema::table('keranjangs', function (Blueprint $table) {
        $table->dropForeign(['varian_id']);
        $table->dropColumn('varian_id');
    });
}

};
