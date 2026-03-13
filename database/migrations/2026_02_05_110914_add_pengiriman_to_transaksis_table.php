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
        Schema::table('transaksis', function (Blueprint $table) {

            if (!Schema::hasColumn('transaksis', 'ekspedisi')) {
                $table->string('ekspedisi')->nullable();
            }

            if (!Schema::hasColumn('transaksis', 'no_resi')) {
                $table->string('no_resi')->nullable();
            }

            if (!Schema::hasColumn('transaksis', 'tanggal_dikirim')) {
                $table->timestamp('tanggal_dikirim')->nullable();
            }
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksis', function (Blueprint $table) {
            //
        });
    }
};
