<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('transaksis')) {
            return;
        }

        Schema::table('transaksis', function (Blueprint $table) {
            if (! Schema::hasColumn('transaksis', 'shipping_nama_penerima')) {
                $table->string('shipping_nama_penerima', 100)->nullable();
            }
            if (! Schema::hasColumn('transaksis', 'shipping_nama_pengirim')) {
                $table->string('shipping_nama_pengirim', 100)->nullable();
            }
            if (! Schema::hasColumn('transaksis', 'shipping_no_telp')) {
                $table->string('shipping_no_telp', 20)->nullable();
            }
            if (! Schema::hasColumn('transaksis', 'shipping_kota')) {
                $table->string('shipping_kota', 100)->nullable();
            }
            if (! Schema::hasColumn('transaksis', 'shipping_kecamatan')) {
                $table->string('shipping_kecamatan', 100)->nullable();
            }
            if (! Schema::hasColumn('transaksis', 'shipping_kelurahan')) {
                $table->string('shipping_kelurahan', 100)->nullable();
            }
            if (! Schema::hasColumn('transaksis', 'shipping_provinsi')) {
                $table->string('shipping_provinsi', 100)->nullable();
            }
            if (! Schema::hasColumn('transaksis', 'shipping_kode_pos')) {
                $table->string('shipping_kode_pos', 10)->nullable();
            }
            if (! Schema::hasColumn('transaksis', 'shipping_alamat_lengkap')) {
                $table->text('shipping_alamat_lengkap')->nullable();
            }
            if (! Schema::hasColumn('transaksis', 'shipping_destination_id')) {
                $table->string('shipping_destination_id')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('transaksis')) {
            return;
        }

        $columns = [
            'shipping_nama_penerima',
            'shipping_nama_pengirim',
            'shipping_no_telp',
            'shipping_kota',
            'shipping_kecamatan',
            'shipping_kelurahan',
            'shipping_provinsi',
            'shipping_kode_pos',
            'shipping_alamat_lengkap',
            'shipping_destination_id',
        ];

        foreach ($columns as $column) {
            if (! Schema::hasColumn('transaksis', $column)) {
                continue;
            }

            Schema::table('transaksis', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
};
