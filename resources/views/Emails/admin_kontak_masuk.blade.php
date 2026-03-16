<p>Ada pesan baru dari formulir kontak website.</p>

<p><b>Nama:</b> {{ $kontak->nama }}</p>
<p><b>Email:</b> {{ $kontak->email }}</p>
<p><b>Subjek:</b> {{ $kontak->subjek }}</p>

<hr>

<p><b>Isi Pesan:</b></p>
<p style="white-space: pre-line;">{{ $kontak->pesan }}</p>
