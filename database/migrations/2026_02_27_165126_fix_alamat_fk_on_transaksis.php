<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('transaksis') || ! Schema::hasColumn('transaksis', 'alamat_id')) {
            return;
        }

        Schema::table('transaksis', function (Blueprint $table) {
            try {
                $table->dropForeign(['alamat_id']);
            } catch (\Throwable $e) {
                // FK sudah tidak ada / nama constraint berbeda.
            }

            $table->foreignId('alamat_id')->nullable()->change();

            try {
                $table->foreign('alamat_id')
                    ->references('id')->on('alamats')
                    ->nullOnDelete();
            } catch (\Throwable $e) {
                // Constraint sudah ada, lewati agar migration tetap idempotent.
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaksis') || ! Schema::hasColumn('transaksis', 'alamat_id')) {
            return;
        }

        Schema::table('transaksis', function (Blueprint $table) {
            try {
                $table->dropForeign(['alamat_id']);
            } catch (\Throwable $e) {
                // FK sudah tidak ada / nama constraint berbeda.
            }

            $table->foreignId('alamat_id')->nullable(false)->change();

            try {
                $table->foreign('alamat_id')
                    ->references('id')->on('alamats')
                    ->cascadeOnDelete();
            } catch (\Throwable $e) {
                // Constraint sudah ada, lewati agar rollback tetap aman.
            }
        });
    }
};
