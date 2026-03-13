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
        Schema::table('produk_varians', function (Blueprint $table) {
            $table->unique(['produk_id', 'warna', 'ukuran'], 'produk_varian_unique');
        });
    }

    public function down(): void
    {
        Schema::table('produk_varians', function (Blueprint $table) {
            $table->dropUnique('produk_varian_unique');
        });
    }
};
