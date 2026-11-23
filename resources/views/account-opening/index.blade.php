@extends('layouts.pmp')
@section('title', 'Account Opening')
@section('crumb', 'Account Opening > Account Opening')
@section('content')
<div x-data="{ open: false }">
    <div class="head">
        <h1 class="title" style="margin:0">Account Opening</h1>
        <div class="actions">
            <a class="btn" href="{{ route('ao.template') }}">&#8681; Download Template Bulk</a>
            <button class="btn primary" @click="open = true">Add Request</button>
        </div>
    </div>

    <div class="card">
        <table class="t">
            <thead><tr>
                <th>Request ID</th><th>Tanggal</th><th>Group Code</th><th>Branch</th><th>Mitra ID</th><th>Mekanisme</th><th>Jumlah</th><th>Status</th><th style="text-align:right">Action</th>
            </tr></thead>
            <tbody>
            @forelse($requests as $r)
                <tr>
                    <td class="mono">{{ $r->displayRequestId() }}</td>
                    <td>{{ $r->created_at->format('Y-m-d') }}</td>
                    <td>{{ $r->group_code }}</td>
                    <td>{{ $r->branch }}</td>
                    <td>{{ $r->mitra_id }}</td>
                    <td><span class="pill blue">{{ $r->mekanisme }}</span></td>
                    <td>{{ $r->jumlah }}</td>
                    <td><span class="pill {{ $r->isApproved() ? 'ok' : ($r->isWaiting() ? 'warn' : 'err') }}">{{ $r->status }}</span></td>
                    <td style="text-align:right"><a class="btn sm {{ $r->isWaiting() ? 'primary' : '' }}" href="{{ route('ao.show', $r) }}">{{ $r->isWaiting() ? 'Verifikasi' : 'Detail' }}</a></td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">Belum ada request. Klik <b>Add Request</b> untuk mencoba Manual Bulk dengan autofill.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="overlay" x-show="open" x-cloak @click.self="open = false" @keydown.escape.window="open = false">
        <div class="modal">
            <h3>Add Request</h3>
            <div class="dim">Pilih mekanisme pembukaan rekening.</div>
            <a class="opt" href="{{ route('ao.bulk') }}">
                <div class="ic">&#9783;</div>
                <div><b>Manual Bulk <span class="new">BARU: Autofill</span></b>
                <span>Upload banyak PDF scan APR, data diri &amp; pekerjaan terisi otomatis dalam tabel. Informasi mitra diisi sekali untuk semua.</span></div>
            </a>
            <a class="opt" href="{{ route('ao.satuan') }}">
                <div class="ic">&#9778;</div>
                <div><b>Manual Satuan</b><span>Isi form satu nasabah per request.</span></div>
            </a>
            <div style="text-align:right;margin-top:16px"><button class="btn" @click="open = false">Batal</button></div>
        </div>
    </div>
</div>
@endsection
