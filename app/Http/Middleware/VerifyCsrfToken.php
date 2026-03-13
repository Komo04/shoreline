<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * URIs yang dikecualikan dari verifikasi CSRF.
     * Midtrans akan POST tanpa CSRF token, jadi wajib dikecualikan.
     */
    protected $except = [
        'midtrans/notification',
        'midtrans/webhook',
    ];
}
