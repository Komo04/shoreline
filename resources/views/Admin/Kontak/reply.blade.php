@extends('layouts.Admin.mainlayout')
@section('content')

<h4>Balas Pesan</h4>

<div class="card mb-3">
  <div class="card-body">
    <p><b>Nama:</b> {{ $kontak->nama }}</p>
    <p><b>Email:</b> {{ $kontak->email }}</p>
    <p><b>Subjek Asli:</b> {{ $kontak->subjek }}</p>
    <hr>
    <p style="white-space: pre-line;">{{ $kontak->pesan }}</p>
  </div>
</div>

<form method="POST" action="{{ route('admin.kontak.replySend', $kontak->id) }}">
  @csrf

  <div class="mb-3">
    <label class="form-label">Subjek Balasan</label>
    <input
      type="text"
      name="subject"
      class="form-control @error('subject') is-invalid @enderror"
      value="{{ old('subject', 'Re: ' . $kontak->subjek) }}"
    >
    @error('subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="mb-3">
    <label class="form-label">Isi Balasan</label>
    <textarea
      name="message"
      rows="6"
      class="form-control @error('message') is-invalid @enderror"
    >{{ old('message') }}</textarea>
    @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <button class="btn btn-primary">Kirim Balasan</button>
  <a href="{{ route('admin.kontak.show', $kontak->id) }}" class="btn btn-secondary">Batal</a>
</form>

@endsection
