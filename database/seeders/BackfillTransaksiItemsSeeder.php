<?php

namespace Database\Seeders;

use App\Models\ProdukVarian;
use App\Models\Transaksi;
use Database\Seeders\Concerns\SeedsDummyTransaksiData;
use Illuminate\Database\Seeder;

class BackfillTransaksiItemsSeeder extends Seeder
{
    use SeedsDummyTransaksiData;

    public function run(): void
    {
        $varians = ProdukVarian::with('produk')->whereHas('produk')->get();

        Transaksi::with(['items', 'pembayaran'])
            ->orderBy('id')
            ->chunkById(100, function ($transaksis) use ($varians) {
                foreach ($transaksis as $transaksi) {
                    if ($transaksi->items->isEmpty()) {
                        $generated = $this->dummyTransactionItems($varians);
                        $items = $generated['items'];
                        $subtotal = $generated['subtotal'];
                    } else {
                        $items = $this->itemsFromExistingTransaction($transaksi);
                        $subtotal = array_sum(array_column($items, 'subtotal'));
                    }

                    $this->replaceTransactionItems($transaksi, $items);

                    $courier = $this->normalizeCourier($transaksi->kurir_kode, $transaksi->ekspedisi);
                    $shippedAt = $transaksi->tanggal_dikirim ?? $transaksi->paid_at ?? $transaksi->created_at ?? now();
                    $shouldHaveResi = $this->shouldHaveDummyResi((string) $transaksi->status_transaksi);

                    $transaksi->update([
                        'ekspedisi' => $courier['ekspedisi'],
                        'kurir_kode' => $courier['kurir_kode'],
                        'kurir_layanan' => 'REG',
                        'kurir_etd' => $transaksi->kurir_etd ?: (string) fake()->numberBetween(1, 4),
                        'kurir_etd_is_business_days' => true,
                        'no_resi' => $shouldHaveResi
                            ? $this->makeDummyResi($courier['kurir_kode'], $shippedAt)
                            : null,
                        'tanggal_dikirim' => $shouldHaveResi ? $shippedAt : null,
                        'total_pembayaran' => $subtotal + (int) $transaksi->ongkir,
                    ]);

                    $this->upsertPembayaran($transaksi->fresh());
                }
            });
    }
}
