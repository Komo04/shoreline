@extends('layouts.Admin.mainlayout')
@section('content')

<h4>Detail Pesan</h4>

<div class="card">
    <div class="card-body">
        <p><b>Nama:</b> {{ $kontak->nama }}</p>
        <p><b>Email:</b> {{ $kontak->email }}</p>
        <p><b>Subjek:</b> {{ $kontak->subjek }}</p>
        <p><b>Dikirim:</b> {{ $kontak->created_at->format('d-m-Y H:i') }}</p>
        <hr>
        <p style="white-space: pre-line;">{{ $kontak->pesan }}</p>
    </div>
</div>



<a href="{{ route('admin.kontak.replyForm', $kontak->id) }}" class="btn btn-primary mt-3">
    Balas
</a>
<a href="{{ route('admin.kontak.index') }}" class="btn btn-secondary mt-3">
    Kembali
</a>
@endsection
