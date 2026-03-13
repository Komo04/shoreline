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
        Schema::create('ulasans', function (Blueprint $table) {
            $table->id();
            $table->text('komentar')->nullable();

            // rating 1-5
            $table->unsignedTinyInteger('rating')->default(5);

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('produk_id')
                ->constrained('produks')
                ->onDelete('cascade');

            // 1 user hanya boleh 1 review per produk
            $table->unique(['user_id', 'produk_id']);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ulasans');
    }
};
