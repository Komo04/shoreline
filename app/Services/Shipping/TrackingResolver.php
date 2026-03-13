<?php

namespace App\Services\Shipping;

use App\Models\Transaksi;
use App\Support\Shipping\DummyTracking;

class TrackingResolver
{
    use DummyTracking;

    public function resolve(Transaksi $t): array
    {
        if (! $t->no_resi || ! $t->kurir_kode) {
            return [
                'success' => false,
                'message' => 'Resi atau kurir belum diinput.',
                'data' => null,
            ];
        }

        $t->loadMissing('alamat');

        if ($this->isDummyAwb((string) $t->no_resi)) {
            return [
                'success' => true,
                'message' => 'Tracking dummy (skripsi).',
                'data' => $this->dummyTracking($t),
            ];
        }

        /** @var KomerceTrackingService $svc */
        $svc = app(KomerceTrackingService::class);
        $r = $svc->track((string) $t->no_resi, (string) $t->kurir_kode);

        return [
            'success' => (bool) ($r['success'] ?? false),
            'message' => (string) ($r['message'] ?? 'OK'),
            'data' => $r['data'] ?? null,
        ];
    }
}
