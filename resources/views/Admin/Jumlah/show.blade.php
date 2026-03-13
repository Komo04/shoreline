@extends('layouts.Admin.mainlayout')

@section('title', 'Detail Stok Produk')

@section('content')
<div class="container-fluid">

    <h4 class="fw-bold mb-3">{{ $produk->nama_produk }}</h4>

    <p>
        Total Stok:
        <span class="badge bg-success">
            {{ $produk->varians->sum('stok') }}
        </span>
    </p>

    <table class="table table-bordered align-middle">
        <thead class="table-light">
            <tr>
                <th>Warna</th>
                <th>Ukuran</th>
                <th>Stok</th>
            </tr>
        </thead>
        <tbody>

            @foreach ($produk->varians as $varian)
            <tr>
                <td>{{ $varian->warna }}</td>
                <td>{{ $varian->ukuran }}</td>
                <td>
                    @if($varian->stok == 0)
                    <span class="badge bg-danger">Habis</span>

                    @elseif($varian->stok <= 5) <span class="badge bg-warning text-dark">
                        {{ $varian->stok }} (Menipis)
                        </span>

                        @else
                        <span class="badge bg-success">
                            {{ $varian->stok }}
                        </span>
                        @endif
                </td>
                <td>
                    @if($varian->stok == 0)
                    <span class="badge bg-danger">Habis</span>

                    @elseif($varian->stok <= 5) <span class="badge bg-warning text-dark">
                        {{ $varian->stok }} (Menipis)
                        </span>

                        @else
                        <span class="badge bg-success">
                            {{ $varian->stok }}
                        </span>
                        @endif
                </td>

            </tr>
            @endforeach

        </tbody>
    </table>

</div>
@endsection
