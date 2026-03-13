<?php

namespace App\Services;

use App\Models\StokLog;
use App\Models\ProdukVarian;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;

class StockService
{
    private function trxCode(Transaksi $trx): string
    {
        // kode_transaksi Anda sudah "TRX-xxxx"
        return (string) ($trx->kode_transaksi ?? ('TRX-' . $trx->id));
    }

    private function trxRef(Transaksi $trx): string
    {
        // kalau midtrans ada order_id, pakai itu, kalau tidak pakai trx_id
        if (!empty($trx->midtrans_order_id)) {
            return (string) $trx->midtrans_order_id;
        }
        return 'trx_id:' . (string) $trx->id;
    }

    public function deductWhenPaid(Transaksi $trx): void
    {
        DB::transaction(function () use ($trx) {

            // ✅ lock transaksi agar tidak double deduct
            $trx = Transaksi::lockForUpdate()->findOrFail($trx->id);

            if ((bool) $trx->stock_deducted) return;

            $trx->loadMissing(['items.produkVarian']);
            foreach ($trx->items as $item) {
                $qty = (int) $item->qty;
                if ($qty <= 0) continue;

                $varian = $item->produkVarian()->lockForUpdate()->first();
                if (!$varian) {
                    throw new \Exception('Produk varian tidak ditemukan saat potong stok.');
                }

                $stokSebelum = (int) $varian->stok;

                if ($stokSebelum < $qty) {
                    throw new \Exception('Stok tidak cukup saat transaksi PAID.');
                }

                $varian->stok = $stokSebelum - $qty;
                $varian->stok_log_keterangan = 'PAID';
                $varian->save();

                $stokSesudah = (int) $varian->stok;

                StokLog::create([
                    'produk_varian_id' => $varian->id,
                    'tipe' => 'OUT',
                    'jumlah' => $qty,
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSesudah,
                    'keterangan' => 'OUT | PAID | ' . $this->trxCode($trx) . ' | ref:' . $this->trxRef($trx),
                ]);
            }
            $trx->update([
                'stock_deducted' => true,
                'stock_deducted_at' => now(),
            ]);
        });
    }

    public function restore(Transaksi $trx, string $reason): void
    {
        DB::transaction(function () use ($trx, $reason) {

            // ✅ lock transaksi agar tidak double restore
            $trx = Transaksi::lockForUpdate()->findOrFail($trx->id);

            if (!(bool) $trx->stock_deducted) return;

            $trx->loadMissing(['items.produkVarian']);

            foreach ($trx->items as $item) {
                $qty = (int) $item->qty;
                if ($qty <= 0) continue;

                $varian = $item->produkVarian()->lockForUpdate()->first();
                if (!$varian) {
                    throw new \Exception('Produk varian tidak ditemukan saat restore stok.');
                }

                $stokSebelum = (int) $varian->stok;

                $varian->stok = $stokSebelum + $qty;
                $varian->stok_log_keterangan = 'RESTORE';
                $varian->save();

                $stokSesudah = (int) $varian->stok;

                $event = strtoupper(trim($reason));

                StokLog::create([
                    'produk_varian_id' => $varian->id,
                    'tipe' => 'IN',
                    'jumlah' => $qty,
                    'stok_sebelum' => $stokSebelum,
                    'stok_sesudah' => $stokSesudah,
                    'keterangan' => 'IN | ' . $event . ' | ' . $this->trxCode($trx) . ' | ref:' . $this->trxRef($trx),
                ]);
            }

            $trx->update([
                'stock_deducted' => false,
                'stock_deducted_at' => null,
            ]);
        });
    }
   public function manualIn(int $varianId, int $qty, string $note = 'STOK IN MANUAL'): void
{
    if ($qty <= 0) {
        throw new \InvalidArgumentException('Qty stok masuk harus lebih dari 0.');
    }

    DB::transaction(function () use ($varianId, $qty, $note) {

        $varian = ProdukVarian::lockForUpdate()->findOrFail($varianId);

        $stokSebelum = (int) $varian->stok;
        $varian->stok = $stokSebelum + $qty;
        $varian->save();

        StokLog::create([
            'produk_varian_id' => $varian->id,
            'tipe' => 'IN',
            'jumlah' => $qty,
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $varian->stok,
            'keterangan' => trim($note) !== '' ? $note : 'STOK IN MANUAL',
        ]);
    });
}
}
