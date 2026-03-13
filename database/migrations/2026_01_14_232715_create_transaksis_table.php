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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_transaksi')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('alamat_id')->constrained()->cascadeOnDelete();
            $table->enum('metode_pembayaran', ['transfer', 'qris', 'midtrans'])->default('transfer');
            $table->string('status_transaksi')->default('pending');
            $table->boolean('stock_deducted')->default(false);
            $table->timestamp('stock_deducted_at')->nullable();
            $table->string('ekspedisi')->nullable();
            $table->bigInteger('ongkir')->default(0);
            $table->string('kurir_kode')->nullable();
            $table->string('kurir_layanan')->nullable();
            $table->string('kurir_etd')->nullable();
            // contoh isi: "11", "2-3", "11 HARI"

            $table->boolean('kurir_etd_is_business_days')
                ->default(false);
            $table->bigInteger('total_pembayaran');
            $table->timestamp('payment_deadline')->nullable();
            $table->string('no_resi')->nullable();
            $table->timestamp('tanggal_dikirim')->nullable();
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_payment_type')->nullable();

            $table->string('snap_token')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
