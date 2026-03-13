@extends('layouts.Admin.mainlayout')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">Dashboard Admin</h4>
            <div class="text-muted">Ringkasan toko & aktivitas terbaru</div>
        </div>
        <div class="text-muted small">{{ now()->format('d M Y, H:i') }}</div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Pendapatan Hari Ini</div>
                    <div class="fs-4 fw-bold">Rp {{ number_format($pendapatanHariIni,0,',','.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Pendapatan Minggu Ini</div>
                    <div class="fs-4 fw-bold">Rp {{ number_format($pendapatanMingguIni,0,',','.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Pendapatan Bulan Ini</div>
                    <div class="fs-4 fw-bold">Rp {{ number_format($pendapatanBulanIni,0,',','.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Transaksi Bulan Ini</div>
                    <div class="fs-4 fw-bold">{{ number_format($jumlahTransaksiBulanIni) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick info --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Produk</div>
                    <div class="fs-4 fw-bold">{{ number_format($jumlahProduk) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Customer</div>
                    <div class="fs-4 fw-bold">{{ number_format($jumlahCustomer) }}</div>
                </div>
            </div>
        </div>

        {{-- Status pesanan --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold mb-0">Status Pesanan (Bulan Ini)</h6>
                        <span class="text-muted small">auto</span>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        @php
                            $allStatus = ['pending','paid','diproses','dikirim','selesai','batal'];
                        @endphp

                        @foreach($allStatus as $st)
                            <span class="badge rounded-pill
                                {{ $st=='pending' ? 'bg-warning-subtle text-warning' : '' }}
                                {{ in_array($st,['paid','selesai']) ? 'bg-success-subtle text-success' : '' }}
                                {{ in_array($st,['diproses','dikirim']) ? 'bg-primary-subtle text-primary' : '' }}
                                {{ $st=='batal' ? 'bg-danger-subtle text-danger' : '' }}
                            ">
                                {{ $st }}: {{ $statusCounts[$st] ?? 0 }}
                            </span>
                        @endforeach
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Chart + Stok --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Pendapatan 12 Bulan</h6>
                    <canvas id="incomeChart" height="110"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Stok Menipis (Varian)</h6>

                    @forelse($stokMenipis as $v)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div class="me-2">
                                <div class="fw-semibold">{{ $v->produk?->nama_produk ?? 'Produk' }}</div>
                                <div class="text-muted small">
                                    {{ $v->warna ?? '-' }} • {{ $v->ukuran ?? '-' }}
                                </div>
                            </div>
                            <span class="badge bg-danger-subtle text-danger">Stok: {{ $v->stok }}</span>
                        </div>
                    @empty
                        <div class="text-muted">Tidak ada stok menipis.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Pending + Recent --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Transaksi Pending</h6>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Tanggal</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transaksiPending as $t)
                                    <tr>
                                        <td>{{ $t->kode_transaksi ?? '-' }}</td>
                                        <td>{{ \Carbon\Carbon::parse($t->created_at)->format('d M Y') }}</td>
                                        <td class="text-end">Rp {{ number_format($t->total_pembayaran ?? 0,0,',','.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Tidak ada pending.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Transaksi Terbaru</h6>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transaksiTerbaru as $t)
                                    <tr>
                                        <td>{{ $t->kode_transaksi ?? '-' }}</td>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ $t->status_transaksi ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-end">Rp {{ number_format($t->total_pembayaran ?? 0,0,',','.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted py-3">Belum ada transaksi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Ulasan terbaru --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Ulasan Terbaru</h6>

            @forelse($ulasanTerbaru as $u)
                <div class="py-2 border-bottom">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">{{ $u->produk?->nama_produk ?? 'Produk' }}</div>
                            <div class="text-warning small">
                                @for($i=1;$i<=5;$i++)
                                    <i class="{{ $i <= ($u->rating ?? 0) ? 'fa-solid' : 'fa-regular' }} fa-star"></i>
                                @endfor
                                <span class="text-muted ms-2">({{ $u->rating ?? 0 }}/5)</span>
                            </div>
                        </div>
                        <div class="text-muted small">{{ \Carbon\Carbon::parse($u->created_at)->format('d M Y') }}</div>
                    </div>
                    <div class="text-muted mt-2">“{{ $u->komentar ?? '-' }}”</div>
                </div>
            @empty
                <div class="text-muted">Belum ada ulasan.</div>
            @endforelse
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = @json($chartLabels);
    const series = @json($chartSeries);

    const ctx = document.getElementById('incomeChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Pendapatan',
                data: series,
                tension: 0.35,
                fill: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: true } },
            scales: {
                y: {
                    ticks: { callback: (v) => 'Rp ' + v.toLocaleString('id-ID') }
                }
            }
        }
    });
</script>
@endpush
