<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Alamat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlamatController extends Controller
{
    public function index()
    {
        $alamats = Alamat::where('user_id', Auth::id())
            ->orderByDesc('is_default')
            ->orderByDesc('id')
            ->get();

        return view('web.Alamat.create', compact('alamats'));
    }

    public function create()
    {
        $user = Auth::user();
        return view('web.Alamat.create', compact('user'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_penerima'  => 'required|string',
            'nama_pengirim'  => 'required|string',
            'no_telp'        => ['required', 'regex:/^[0-9]+$/', 'digits_between:8,15'],
            'alamat_lengkap' => 'required|string',

            // ✅ Komerce jadi OPSIONAL (user boleh isi manual)
            'destination_id' => 'nullable|integer',

            // ✅ Tetap wajib (bisa auto-filled dari Komerce atau diisi manual)
            'provinsi'  => 'required|string',
            'kota'      => 'required|string',
            'kecamatan' => 'required|string',
            'kelurahan' => 'required|string',
            'kode_pos'  => ['required', 'regex:/^[0-9]+$/'],
        ]);

        $userId = Auth::id();
        $validated['user_id'] = $userId;

        // alamat pertama otomatis default
        $validated['is_default'] = !Alamat::where('user_id', $userId)->exists();

        Alamat::create($validated);

       return redirect()->route('checkout')->with('flash', [
  'type' => 'success',
  'action' => 'create',
  'entity' => 'Alamat',
]);
    }

    public function setDefault(Alamat $alamat)
    {
        abort_if($alamat->user_id !== Auth::id(), 403);

        Alamat::where('user_id', Auth::id())->update(['is_default' => false]);
        $alamat->update(['is_default' => true]);

        return back()->with('flash', [
  'type' => 'success',
  'action' => 'update',
  'entity' => 'Alamat Default',
]);
    }

    public function edit(Alamat $alamat)
    {
        abort_if($alamat->user_id !== Auth::id(), 403);

        $user = Auth::user();
        return view('web.Alamat.edit', compact('alamat', 'user'));
    }

    public function update(Request $request, Alamat $alamat)
    {
        abort_if($alamat->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'nama_penerima'  => 'required|string',
            'nama_pengirim'  => 'required|string',
            'no_telp'        => ['required', 'regex:/^[0-9]+$/', 'digits_between:8,15'],
            'alamat_lengkap' => 'required|string',

            // ✅ OPSIONAL juga di edit (kalau user tidak cari ulang)
            'destination_id' => 'nullable|integer',

            'provinsi'  => 'required|string',
            'kota'      => 'required|string',
            'kecamatan' => 'required|string',
            'kelurahan' => 'required|string',
            'kode_pos'  => ['required', 'regex:/^[0-9]+$/'],
        ]);

        $alamat->update($validated);

        return redirect()->route('checkout')->with('flash', [
  'type' => 'success',
  'action' => 'update',
  'entity' => 'Alamat',
]);
    }

    public function destroy(Alamat $alamat)
    {
        abort_if($alamat->user_id !== Auth::id(), 403);

        $userId = Auth::id();
        $wasDefault = $alamat->is_default;

        $alamat->delete();

        if ($wasDefault) {
            $newDefault = Alamat::where('user_id', $userId)->latest()->first();
            if ($newDefault) {
                $newDefault->update(['is_default' => true]);
            }
        }

        return back()->with('flash', [
  'type' => 'success',
  'action' => 'delete',
  'entity' => 'Alamat',
]);
    }
}
