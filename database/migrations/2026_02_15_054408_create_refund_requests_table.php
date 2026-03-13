<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('method'); // manual / midtrans
            $table->string('status')->default('requested'); // requested, processing, refunded, failed

            $table->unsignedBigInteger('amount');
            $table->string('reason')->nullable();


            // khusus manual
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();

            // midtrans tracking
            $table->json('midtrans_response')->nullable();
            $table->string('midtrans_refund_key')->nullable();
            $table->json('midtrans_request')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamp('stock_restored_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            // bukti upload
            $table->string('proof_path')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
