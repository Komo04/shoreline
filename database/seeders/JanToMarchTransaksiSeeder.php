<?php

namespace Database\Seeders;

use App\Models\Alamat;
use App\Models\Pembayaran;
use App\Models\Transaksi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JanToMarchTransaksiSeeder extends Seeder
{
    private const WILAYAH = [
        [
            'provinsi' => 'DKI Jakarta',
            'kota' => 'Jakarta Selatan',
            'kecamatan' => 'Kebayoran Baru',
            'kelurahan' => 'Melawai',
            'kode_pos' => '12160',
        ],
        [
            'provinsi' => 'Jawa Barat',
            'kota' => 'Bandung',
            'kecamatan' => 'Coblong',
            'kelurahan' => 'Dago',
            'kode_pos' => '40135',
        ],
        [
            'provinsi' => 'Jawa Tengah',
            'kota' => 'Semarang',
            'kecamatan' => 'Tembalang',
            'kelurahan' => 'Sendangmulyo',
            'kode_pos' => '50272',
        ],
        [
            'provinsi' => 'DI Yogyakarta',
            'kota' => 'Sleman',
            'kecamatan' => 'Depok',
            'kelurahan' => 'Caturtunggal',
            'kode_pos' => '55281',
        ],
        [
            'provinsi' => 'Jawa Timur',
            'kota' => 'Surabaya',
            'kecamatan' => 'Wonokromo',
            'kelurahan' => 'Darmo',
            'kode_pos' => '60241',
        ],
        [
            'provinsi' => 'Bali',
            'kota' => 'Denpasar',
            'kecamatan' => 'Denpasar Selatan',
            'kelurahan' => 'Sanur Kauh',
            'kode_pos' => '80228',
        ],
        [
            'provinsi' => 'Sulawesi Selatan',
            'kota' => 'Makassar',
            'kecamatan' => 'Panakkukang',
            'kelurahan' => 'Masale',
            'kode_pos' => '90231',
        ],
        [
            'provinsi' => 'Sumatera Utara',
            'kota' => 'Medan',
            'kecamatan' => 'Medan Baru',
            'kelurahan' => 'Petisah Hulu',
            'kode_pos' => '20153',
        ],
        [
            'provinsi' => 'Sumatera Barat',
            'kota' => 'Padang',
            'kecamatan' => 'Kuranji',
            'kelurahan' => 'Korong Gadang',
            'kode_pos' => '25157',
        ],
        [
            'provinsi' => 'Kalimantan Timur',
            'kota' => 'Balikpapan',
            'kecamatan' => 'Balikpapan Selatan',
            'kelurahan' => 'Damai',
            'kode_pos' => '76114',
        ],
    ];

    public function run(): void
    {
        $faker = fake('id_ID');
        $startDate = Carbon::create(2026, 1, 1, 0, 0, 0);
        $endDate = Carbon::create(2026, 3, 31, 23, 59, 59);

        $customers = User::factory()
            ->count(20)
            ->create();

        $alamatByUser = $customers->mapWithKeys(function (User $user) use ($faker) {
            $wilayah = fake()->randomElement(self::WILAYAH);

            $alamat = Alamat::create([
                'user_id' => $user->id,
                'nama_penerima' => $user->name,
                'nama_pengirim' => $user->name,
                'no_telp' => $user->no_telp,
                'kota' => $wilayah['kota'],
                'kecamatan' => $wilayah['kecamatan'],
                'kelurahan' => $wilayah['kelurahan'],
                'provinsi' => $wilayah['provinsi'],
                'destination_id' => (string) $faker->numberBetween(100000, 999999),
                'kode_pos' => $wilayah['kode_pos'],
                'alamat_lengkap' => $faker->streetAddress() . ', ' . $wilayah['kelurahan'] . ', ' . $wilayah['kecamatan'],
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return [$user->id => $alamat];
        });

        for ($i = 1; $i <= 100; $i++) {
            /** @var User $user */
            $user = $customers->random();
            /** @var Alamat $alamat */
            $alamat = $alamatByUser[$user->id];

            $paidAt = Carbon::createFromTimestamp(
                fake()->numberBetween($startDate->timestamp, $endDate->timestamp)
            );
            $createdAt = (clone $paidAt)->subHours(fake()->numberBetween(1, 72));
            $status = fake()->randomElement(['paid', 'diproses', 'dikirim', 'selesai']);
            $subtotal = fake()->numberBetween(150000, 1500000);
            $ongkir = fake()->numberBetween(10000, 50000);
            $total = $subtotal + $ongkir;

            $transaksi = Transaksi::create([
                'kode_transaksi' => 'TRX-' . $paidAt->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'user_id' => $user->id,
                'alamat_id' => $alamat->id,
                'metode_pembayaran' => fake()->randomElement(['transfer', 'qris', 'midtrans']),
                'status_transaksi' => $status,
                'stock_deducted' => true,
                'stock_deducted_at' => (clone $paidAt)->subMinutes(fake()->numberBetween(5, 90)),
                'ekspedisi' => fake()->randomElement(['JNE', 'J&T', 'SiCepat']),
                'ongkir' => $ongkir,
                'kurir_kode' => fake()->randomElement(['jne', 'jnt', 'sicepat']),
                'kurir_layanan' => fake()->randomElement(['REG', 'YES', 'BEST']),
                'kurir_etd' => (string) fake()->numberBetween(1, 4),
                'kurir_etd_is_business_days' => true,
                'total_pembayaran' => $total,
                'payment_deadline' => (clone $createdAt)->addDay(),
                'no_resi' => 'RESI' . fake()->numerify('##########'),
                'tanggal_dikirim' => $status === 'paid' ? null : (clone $paidAt)->addDay(),
                'midtrans_order_id' => fake()->boolean(40) ? 'MID-' . Str::upper(Str::random(10)) : null,
                'midtrans_transaction_id' => fake()->boolean(40) ? (string) Str::uuid() : null,
                'midtrans_payment_type' => fake()->boolean(40) ? fake()->randomElement(['bank_transfer', 'qris']) : null,
                'snap_token' => fake()->boolean(40) ? Str::random(32) : null,
                'paid_at' => $paidAt,
                'shipping_nama_penerima' => $alamat->nama_penerima,
                'shipping_nama_pengirim' => $alamat->nama_pengirim,
                'shipping_no_telp' => $alamat->no_telp,
                'shipping_kota' => $alamat->kota,
                'shipping_kecamatan' => $alamat->kecamatan,
                'shipping_kelurahan' => $alamat->kelurahan,
                'shipping_provinsi' => $alamat->provinsi,
                'shipping_kode_pos' => $alamat->kode_pos,
                'shipping_alamat_lengkap' => $alamat->alamat_lengkap,
                'shipping_destination_id' => (string) $alamat->destination_id,
                'created_at' => $createdAt,
                'updated_at' => $paidAt,
            ]);

            Pembayaran::create([
                'transaksi_id' => $transaksi->id,
                'status_pembayaran' => 'paid',
                'metode_pembayaran' => $transaksi->metode_pembayaran,
                'total_pembayaran' => $total,
                'tanggal_pembayaran' => $paidAt,
                'created_at' => $paidAt,
                'updated_at' => $paidAt,
            ]);
        }
    }
}
