<?php

namespace App\Models;
use App\Models\StokLog;
use Illuminate\Database\Eloquent\Model;

class ProdukVarian extends Model
{
    protected $table = 'produk_varians';

    protected $fillable = [
        'produk_id',
        'warna',
        'ukuran',
        'stok',
        'gambar_varian',
        'berat_gram',
    ];

    /**
     * Properti sementara untuk dikirim ke Observer
     * (tidak masuk database)
     */
    public ?string $stok_log_keterangan = null;

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function stokLogs()
    {
        return $this->hasMany(StokLog::class, 'produk_varian_id');
    }
}
