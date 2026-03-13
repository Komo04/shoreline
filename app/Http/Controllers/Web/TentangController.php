<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TentangController extends Controller
{
    public function index()
    {
        return view('web.Tentang.tentang');
    }
}
