<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class KomerceController extends Controller
{
    public function searchDestination(Request $request)
    {
        $url = config('services.komerce.search_url');
        $key = config('services.komerce.api_key');

        $search = trim((string) $request->query('search', ''));
        $limit = (int) $request->query('limit', 10);
        $offset = (int) $request->query('offset', 0);

        if ($limit <= 0) {
            $limit = 10;
        }

        if ($limit > 50) {
            $limit = 50;
        }

        if ($offset < 0) {
            $offset = 0;
        }

        if (mb_strlen($search) < 3) {
            return response()->json([
                'success' => true,
                'degraded' => false,
                'data' => [],
            ], 200);
        }

        $cacheKey = 'dest_search:' . md5(mb_strtolower($search) . "|{$limit}|{$offset}");

        if (! $url || ! $key) {
            $cached = Cache::get($cacheKey, []);

            return response()->json([
                'success' => true,
                'degraded' => true,
                'message' => 'KOMERCE_SEARCH_URL / KOMERCE_API_KEY belum diisi. Gunakan input alamat manual atau cache.',
                'data' => is_array($cached) ? $cached : [],
            ], 200);
        }

        try {
            /** @var Response $resp */
            $resp = Http::withHeaders([
                'key' => $key,
                'accept' => 'application/json',
            ])
                ->timeout(5)
                ->get(rtrim($url, '/'), [
                    'search' => $search,
                    'limit' => $limit,
                    'offset' => $offset,
                ]);
        } catch (\Throwable $e) {
            $cached = Cache::get($cacheKey, []);

            return response()->json([
                'success' => true,
                'degraded' => true,
                'type' => 'network_failed',
                'message' => 'Server destination sedang gangguan. Menampilkan hasil dari cache (jika ada).',
                'data' => is_array($cached) ? $cached : [],
            ], 200);
        }

        if (! $resp->successful()) {
            $cached = Cache::get($cacheKey, []);

            return response()->json([
                'success' => true,
                'degraded' => true,
                'type' => 'http_error',
                'message' => 'Server destination membalas error. Menampilkan hasil dari cache (jika ada).',
                'http_code' => $resp->status(),
                'data' => is_array($cached) ? $cached : [],
            ], 200);
        }

        $decoded = $resp->json();

        if (! is_array($decoded)) {
            $cached = Cache::get($cacheKey, []);

            return response()->json([
                'success' => true,
                'degraded' => true,
                'type' => 'invalid_json',
                'message' => 'Response destination tidak valid. Menampilkan hasil dari cache (jika ada).',
                'data' => is_array($cached) ? $cached : [],
            ], 200);
        }

        $list = $decoded['data'] ?? [];

        if (is_array($list) && count($list) > 0) {
            Cache::put($cacheKey, $list, now()->addDays(7));
        }

        return response()->json([
            'success' => true,
            'degraded' => false,
            'data' => is_array($list) ? $list : [],
        ], 200);
    }
}
