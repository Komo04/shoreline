<?php

namespace Database\Seeders;

use App\Models\Produk;
use App\Models\Ulasan;
use App\Models\User;
use Illuminate\Database\Seeder;

class UlasanSeeder extends Seeder
{
    private const TARGET_ULASAN = 180;

    public function run(): void
    {
        $faker = fake('id_ID');

        $customers = User::query()
            ->where('user_role', 'customer')
            ->get(['id']);

        $produkIds = Produk::query()->pluck('id');

        if ($customers->isEmpty() || $produkIds->isEmpty()) {
            return;
        }

        $existingPairs = Ulasan::query()
            ->select('user_id', 'produk_id')
            ->get()
            ->map(fn (Ulasan $ulasan) => $ulasan->user_id . ':' . $ulasan->produk_id)
            ->flip();

        $pairs = [];

        foreach ($customers as $customer) {
            foreach ($produkIds as $produkId) {
                $key = $customer->id . ':' . $produkId;

                if (! $existingPairs->has($key)) {
                    $pairs[] = [
                        'user_id' => $customer->id,
                        'produk_id' => $produkId,
                    ];
                }
            }
        }

        if ($pairs === []) {
            return;
        }

        shuffle($pairs);

        $toCreate = min(self::TARGET_ULASAN, count($pairs));

        for ($i = 0; $i < $toCreate; $i++) {
            $pair = $pairs[$i];
            $rating = $faker->randomElement([3, 4, 4, 4, 5, 5, 5]);
            $createdAt = $faker->dateTimeBetween('2026-01-01', '2026-03-31');

            Ulasan::create([
                'user_id' => $pair['user_id'],
                'produk_id' => $pair['produk_id'],
                'rating' => $rating,
                'komentar' => $this->fakeKomentar($rating),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    private function fakeKomentar(int $rating): string
    {
        $comments = match ($rating) {
            5 => [
                'Produk sangat bagus, bahan nyaman dan pengiriman cepat.',
                'Suka sekali sama kualitasnya, sesuai foto dan ukuran pas.',
                'Recommended, jahitan rapi dan dipakai nyaman seharian.',
                'Warnanya bagus, kualitas mantap, bakal repeat order.',
            ],
            4 => [
                'Bagus dan sesuai deskripsi, hanya pengiriman sedikit lama.',
                'Kualitas oke, ukuran pas, overall puas dengan produk ini.',
                'Produknya bagus, bahan nyaman, worth it untuk dibeli.',
                'Cukup memuaskan, warna dan model sesuai ekspektasi.',
            ],
            default => [
                'Produknya cukup baik, semoga ke depan variasinya lebih banyak.',
                'Lumayan bagus, masih nyaman dipakai dan sesuai harga.',
                'Cukup puas, kualitas oke untuk pemakaian harian.',
            ],
        };

        return fake()->randomElement($comments);
    }
}
