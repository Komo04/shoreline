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
        Schema::create('produk_varians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained()->cascadeOnDelete();
            $table->string('warna', 50);
            $table->string('ukuran', 20);
            $table->integer('stok')->default(0);
            $table->string('gambar_varian', 255)->nullable();
             $table->unsignedInteger('berat_gram')->default(0);
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_varians');
    }
};
