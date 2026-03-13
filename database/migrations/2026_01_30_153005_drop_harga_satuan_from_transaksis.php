<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // NO-OP: kolom `harga_satuan` tidak lagi digunakan di level transaksis.
    }

    public function down(): void
    {
        // NO-OP
    }
};
