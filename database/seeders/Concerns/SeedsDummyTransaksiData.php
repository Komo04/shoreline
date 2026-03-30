<?php

namespace Database\Seeders\Concerns;

use App\Models\DetailTransaksi;
use App\Models\Pembayaran;
use App\Models\ProdukVarian;
use App\Models\Transaksi;
use App\Models\TransaksiItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

trait SeedsDummyTransaksiData
{
    protected function randomElement(array $items): mixed
    {
        return $items[array_rand($items)];
    }

    protected function supportedCouriers(): array
    {
        return [
            [
                'ekspedisi' => 'JNE',
                'kurir_kode' => 'jne',
            ],
            [
                'ekspedisi' => 'JNT',
                'kurir_kode' => 'jnt',
            ],
            [
                'ekspedisi' => 'POS',
                'kurir_kode' => 'pos',
            ],
        ];
    }

    protected function normalizeCourier(?string $kurirCode = null, ?string $ekspedisi = null): array
    {
        $value = strtolower(trim((string) ($kurirCode ?: $ekspedisi ?: '')));

        return match ($value) {
            'jne' => ['ekspedisi' => 'JNE', 'kurir_kode' => 'jne'],
            'jnt', 'j&t', 'j&t express' => ['ekspedisi' => 'JNT', 'kurir_kode' => 'jnt'],
            'pos', 'pos indonesia', 'posindonesia' => ['ekspedisi' => 'POS', 'kurir_kode' => 'pos'],
            default => $this->randomElement($this->supportedCouriers()),
        };
    }

    protected function shouldHaveDummyResi(string $status): bool
    {
        return in_array($status, ['dikirim', 'selesai'], true);
    }

    protected function makeDummyResi(string $kurirCode, CarbonInterface $timestamp): string
    {
        $prefix = strtoupper(match (strtolower(trim($kurirCode))) {
            'jne' => 'JNE',
            'jnt' => 'JNT',
            'pos' => 'POS',
            default => 'JNE',
        });

        return sprintf('DUMMY-%s-REG-%s', $prefix, $timestamp->format('YmdHis'));
    }

    protected function dummyTransactionItems(Collection $varians): array
    {
        $available = $varians
            ->filter(fn (ProdukVarian $varian) => $varian->produk !== null)
            ->values();

        if ($available->isEmpty()) {
            throw new \RuntimeException('Seeder transaksi membutuhkan minimal satu produk varian.');
        }

        $itemCount = min($available->count(), random_int(1, 3));
        $selected = $available->shuffle()->take($itemCount)->values();

        $items = [];
        $subtotal = 0;

        foreach ($selected as $varian) {
            $hargaSatuan = (int) round((float) $varian->produk->harga);
            $maxQty = max(1, min((int) $varian->stok, 3));
            $qty = random_int(1, $maxQty);
            $itemSubtotal = $hargaSatuan * $qty;

            $items[] = [
                'produk_id' => $varian->produk_id,
                'produk_varian_id' => $varian->id,
                'nama_produk' => $varian->produk->nama_produk,
                'warna' => $varian->warna,
                'ukuran' => $varian->ukuran,
                'qty' => $qty,
                'harga_satuan' => $hargaSatuan,
                'subtotal' => $itemSubtotal,
            ];

            $subtotal += $itemSubtotal;
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
        ];
    }

    protected function itemsFromExistingTransaction(Transaksi $transaksi): array
    {
        return $transaksi->items
            ->map(fn (TransaksiItem $item) => [
                'produk_id' => $item->produk_id,
                'produk_varian_id' => $item->produk_varian_id,
                'nama_produk' => $item->nama_produk,
                'warna' => $item->warna,
                'ukuran' => $item->ukuran,
                'qty' => (int) $item->qty,
                'harga_satuan' => (int) $item->harga_satuan,
                'subtotal' => (int) $item->subtotal,
            ])
            ->all();
    }

    protected function replaceTransactionItems(Transaksi $transaksi, array $items): void
    {
        TransaksiItem::where('transaksi_id', $transaksi->id)->delete();
        DetailTransaksi::where('transaksi_id', $transaksi->id)->delete();

        foreach ($items as $item) {
            TransaksiItem::create([
                'transaksi_id' => $transaksi->id,
                'produk_id' => $item['produk_id'],
                'produk_varian_id' => $item['produk_varian_id'],
                'nama_produk' => $item['nama_produk'],
                'warna' => $item['warna'],
                'ukuran' => $item['ukuran'],
                'qty' => $item['qty'],
                'harga_satuan' => $item['harga_satuan'],
                'subtotal' => $item['subtotal'],
            ]);

            DetailTransaksi::create([
                'transaksi_id' => $transaksi->id,
                'produk_id' => $item['produk_id'],
                'produk_varian_id' => $item['produk_varian_id'],
                'jumlah_produk' => $item['qty'],
                'harga_satuan' => $item['harga_satuan'],
                'subtotal' => $item['subtotal'],
            ]);
        }
    }

    protected function upsertPembayaran(Transaksi $transaksi): void
    {
        Pembayaran::updateOrCreate(
            ['transaksi_id' => $transaksi->id],
            [
                'status_pembayaran' => $transaksi->status_transaksi === 'dibatalkan' ? 'ditolak' : 'paid',
                'metode_pembayaran' => $transaksi->metode_pembayaran,
                'total_pembayaran' => $transaksi->total_pembayaran,
                'tanggal_pembayaran' => $transaksi->paid_at ?? $transaksi->updated_at ?? now(),
                'created_at' => $transaksi->paid_at ?? $transaksi->created_at ?? now(),
                'updated_at' => $transaksi->updated_at ?? now(),
            ]
        );
    }
}
