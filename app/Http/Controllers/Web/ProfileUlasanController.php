<?php

namespace App\Http\Controllers\Web;

use App\Models\Ulasan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class ProfileUlasanController extends Controller
{
    public function index()
    {
        $ulasans = Ulasan::with('produk')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('web.Ulasan.ulasan', compact('ulasans'));
    }

    public function edit(Ulasan $ulasan)
    {
        abort_if($ulasan->user_id !== Auth::id(), 403);

        return view('web.Ulasan.edit', compact('ulasan'));
    }

    public function update(Request $request, Ulasan $ulasan)
    {
        abort_if($ulasan->user_id !== Auth::id(), 403);

        $data = $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'komentar' => 'nullable|string',
        ]);

        $ulasan->update($data);

       return redirect()->route('user.ulasans.index')
  ->with('flash', ['type'=>'success','action'=>'update','entity'=>'Ulasan']);
    }
    public function destroy($id)
    {
        $ulasan = Ulasan::findOrFail($id);
        abort_if($ulasan->user_id !== Auth::id(), 403);

        $ulasan->delete();

        return redirect()->route('user.ulasans.index')
            ->with('flash', ['type' => 'success', 'action' => 'delete', 'entity' => 'Ulasan']);
    }

}
