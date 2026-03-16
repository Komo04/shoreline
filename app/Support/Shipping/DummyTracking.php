<?php

namespace App\Support\Shipping;

use App\Models\Transaksi;
use Carbon\Carbon;

trait DummyTracking
{
    protected function isDummyAwb(?string $awb): bool
    {
        $awb = trim((string) $awb);
        if ($awb === '') return true;

        $upper = strtoupper($awb);
        if (str_starts_with($upper, 'DUMMY-') || str_starts_with($upper, 'TEST-')) return true;

        if (strlen($awb) < 8) return true;

        return false;
    }

    protected function parseEtdRange(?string $etd): array
    {
        $etd = trim((string) $etd);
        preg_match('/(\d+)(?:\s*-\s*(\d+))?/u', $etd, $m);

        $min = isset($m[1]) ? (int) $m[1] : 0;
        $max = isset($m[2]) ? (int) $m[2] : $min;

        if ($min <= 0) $min = 1;
        if ($max <= 0) $max = $min;

        return [$min, $max];
    }

    protected function addBusinessDays(Carbon $date, int $days): Carbon
    {
        $d = $date->copy();
        $remaining = max(0, $days);

        while ($remaining > 0) {
            $d->addDay();
            if ($d->isWeekend()) continue;
            $remaining--;
        }

        return $d;
    }

    protected function dummyTracking(Transaksi $t): array
    {
        $originCity = 'Kabupaten Badung, Bali';
        $destCity   = $t->alamat?->kota ?? 'Surabaya';

        // ✅ Semua base waktu kita anggap WITA
        $tz = 'Asia/Makassar';

        $start = $t->tanggal_dikirim ?? $t->paid_at ?? $t->created_at ?? now();
        $start = Carbon::parse($start)->timezone($tz);

        // kalau start masa depan (data aneh), amanin
        if ($start->isFuture()) $start = now($tz)->subHours(1);

        [$minDays, $maxDays] = $this->parseEtdRange($t->kurir_etd);
        $useBusinessDays = (bool) ($t->kurir_etd_is_business_days ?? false);

        $totalDays = max(1, (int) $maxDays);

        $now = now($tz);

        $events = [];

        // ✅ MODE A: ETD 1 hari (pakai jam)
        if ($totalDays <= 1) {
            $milestonesHours = [
                ['h' => 0,  'desc' => 'Paket diterima oleh kurir.', 'city' => $originCity],
                ['h' => 2,  'desc' => 'Paket diproses di gudang asal.', 'city' => $originCity],
                ['h' => 5,  'desc' => 'Paket diberangkatkan ke kota tujuan.', 'city' => $originCity],
                ['h' => 10, 'desc' => 'Paket tiba di hub kota tujuan.', 'city' => $destCity],
                ['h' => 15, 'desc' => 'Paket keluar untuk pengantaran (out for delivery).', 'city' => $destCity],
                ['h' => 20, 'desc' => 'Paket diterima oleh penerima.', 'city' => $destCity, 'delivered' => true],
            ];

            foreach ($milestonesHours as $m) {
                $dt = $start->copy()->addHours((int)$m['h']);
                $events[] = [
                    'dt' => $dt,
                    'city' => $m['city'],
                    'description' => $m['desc'],
                    'delivered' => !empty($m['delivered']),
                ];
            }
        }
        // ✅ MODE B: ETD > 1 hari (realtime berdasarkan progres jam)
        else {
            // durasi total = totalDays hari → dalam jam
            $totalHours = $totalDays * 24;

            // milestone persentase perjalanan
            $milestones = [
                ['p' => 0.00, 'desc' => 'Paket diterima oleh kurir.', 'city' => $originCity],
                ['p' => 0.20, 'desc' => 'Paket diproses di gudang asal.', 'city' => $originCity],
                ['p' => 0.45, 'desc' => 'Paket diberangkatkan ke kota tujuan.', 'city' => $originCity],
                ['p' => 0.70, 'desc' => 'Paket tiba di hub kota tujuan.', 'city' => $destCity],
                ['p' => 0.88, 'desc' => 'Paket keluar untuk pengantaran (out for delivery).', 'city' => $destCity],
                ['p' => 1.00, 'desc' => 'Paket diterima oleh penerima.', 'city' => $destCity, 'delivered' => true],
            ];

            foreach ($milestones as $m) {
                // offset jam proporsional
                $hourOffset = (int) floor($m['p'] * $totalHours);
                $hourOffset = max(0, min($totalHours, $hourOffset));

                // kalau business days, kita konversi jam -> hari kerja (kasar tapi realistis)
                if ($useBusinessDays) {
                    $dayOffset = (int) floor($hourOffset / 24);
                    $dt = $this->addBusinessDays($start, $dayOffset)->copy();
                    // jam dalam hari tetap mengikuti sisa jam
                    $remainHours = $hourOffset % 24;
                    $dt->addHours($remainHours);
                } else {
                    $dt = $start->copy()->addHours($hourOffset);
                }

                $events[] = [
                    'dt' => $dt,
                    'city' => $m['city'],
                    'description' => $m['desc'],
                    'delivered' => !empty($m['delivered']),
                ];
            }
        }

        // urutkan event
        usort($events, fn($a, $b) => $a['dt'] <=> $b['dt']);

        // tampilkan bertahap (yang sudah lewat waktunya)
        $visible = array_values(array_filter($events, fn($e) => $e['dt']->lte($now)));
        if (count($visible) === 0) $visible = [$events[0]];

        $timeline = array_map(function ($e) {
            return [
                // ✅ ini jadi sumber utama FE
                'datetime_iso'  => $e['dt']->toIso8601String(),     // contoh: 2026-02-26T10:10:00+08:00
                'datetime_wita' => $e['dt']->format('Y-m-d H:i'),   // contoh: 2026-02-26 10:10
                // fallback
                'datetime'      => $e['dt']->format('Y-m-d H:i'),
                'city'          => $e['city'],
                'description'   => $e['description'],
            ];
        }, $visible);

        // delivered harus berdasarkan event delivered + waktunya sudah lewat
        $deliveredEvent = null;
        foreach (array_reverse($events) as $ev) {
            if (!empty($ev['delivered'])) {
                $deliveredEvent = $ev;
                break;
            }
        }
        $isDelivered = $deliveredEvent ? $deliveredEvent['dt']->lte($now) : false;

        return [
            'delivered' => $isDelivered,
            'summary' => [
                'status' => $isDelivered ? 'DELIVERED' : 'ON_PROGRESS',
                'origin' => $originCity,
                'destination' => $destCity,
                'etd' => $t->kurir_etd ? (string) $t->kurir_etd : null,
                'etd_is_business_days' => $useBusinessDays,
            ],
            'delivery_status' => [
                'pod_receiver' => $isDelivered ? ($t->alamat?->nama_penerima ?? '-') : '-',
            ],
            'details' => [
                'shipper_name' => 'Gudang Pusat Bali',
                'shipper_address1' => 'Jl. Arjuna (Double Six) Legian',
                'shipper_address2' => 'Kuta, Badung',
                'shipper_address3' => 'Bali 80361',
                'receiver_name' => $t->alamat?->nama_penerima,
                'receiver_address1' => $t->alamat?->alamat_lengkap,
                'receiver_address2' => trim(($t->alamat?->kelurahan ?? '') . ' ' . ($t->alamat?->kecamatan ?? '')),
                'receiver_address3' => $t->alamat?->provinsi,
                'receiver_city' => $t->alamat?->kota,
            ],
            'timeline' => $timeline,
        ];
    }
}
