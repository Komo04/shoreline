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
        Schema::create('transaksi_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaksi_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('produk_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('produk_varian_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('nama_produk');
            $table->string('warna')->nullable();
            $table->string('ukuran')->nullable();

            $table->integer('qty');
            $table->bigInteger('harga_satuan');
            $table->bigInteger('subtotal');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_items');
    }
};
