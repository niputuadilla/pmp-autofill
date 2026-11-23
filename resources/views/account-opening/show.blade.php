@extends('layouts.pmp')
@section('title', 'Verifikasi Request')
@section('crumb', 'Account Opening > Verifikasi Request')
@section('content')
<div x-data="{ rejecting: false }">
    <div class="head">
        <h1 class="title" style="margin:0">Verifikasi Request</h1>
        <div class="actions">
            <a class="btn" href="{{ route('ao.index') }}">Kembali</a>
            @if($req->isApproved())
                <a class="btn primary" href="{{ route('ao.download', $req) }}">&#8681; Download BAOWNITEMPLATE.xlsx</a>
            @endif
        </div>
    </div>

    @if(session('flash'))<div class="alert ok">{{ session('flash') }}</div>@endif
    @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif

    @if($req->isWaiting())
        <div class="alert info">Data nasabah sudah lengkap dan terverifikasi Dukcapil. Request ini menunggu verifikasi <b>Branch Operation Manager</b> sebelum nomor rekening diterbitkan.</div>
    @elseif($req->isApproved())
        <div class="alert ok">Disetujui oleh {{ $req->approved_by }} pada {{ $req->approved_at?->format('Y-m-d H:i') }}. Nomor rekening akan diterbitkan pada proses berikutnya.</div>
    @else
        <div class="alert err">Ditolak oleh {{ $req->approved_by }} pada {{ $req->approved_at?->format('Y-m-d H:i') }}. Alasan: {{ $req->reject_note }}</div>
    @endif

    <div class="card">
        <div class="grid" style="grid-template-columns:repeat(4,1fr)">
            <div class="f"><label>Request ID</label><span class="mono">{{ $req->displayRequestId() }}</span></div>
            <div class="f"><label>Group Code</label><span>{{ $req->group_code }}</span></div>
            <div class="f"><label>Mitra ID</label><span>{{ $req->mitra_id }}</span></div>
            <div class="f"><label>Nama Perusahaan</label><span>{{ $req->mitra['name'] ?? '-' }}</span></div>
            <div class="f"><label>Kode Cabang</label><span>{{ $req->branch }}</span></div>
            <div class="f"><label>Nama Cabang</label><span>{{ $branchName }}</span></div>
            <div class="f"><label>Warga Negara</label><span>{{ $nationality }}</span></div>
            <div class="f"><label>Jumlah Data</label><span>{{ $req->jumlah }}</span></div>
            <div class="f"><label>Status</label><span><span class="pill ok">{{ $req->dataStatus() }}</span></span></div>
            <div class="f"><label>Persetujuan BOM</label><span><span class="pill {{ $req->isApproved() ? 'ok' : ($req->isWaiting() ? 'warn' : 'err') }}">{{ $req->status }}</span></span></div>
            <div class="f"><label>Tanggal Request</label><span>{{ $req->created_at->format('Y-m-d H:i') }}</span></div>
            <div class="f"><label>Consent (pesan, suara, SMS, email)</label><span class="mono">{{ $req->consent }}</span></div>
        </div>
    </div>

    <div class="card">
        <div class="head" style="margin-bottom:8px"><b>Request ({{ $req->jumlah }} nasabah)</b></div>
        <div class="scroll">
            <table class="t">
                <thead><tr><th>#</th>@foreach($columns as $c)<th>{{ $labels[$c] }}</th>@endforeach<th>APR</th></tr></thead>
                <tbody>
                @foreach($req->items as $i => $it)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        @foreach($columns as $c)<td class="{{ in_array($c, ['no_ktp','npwp','no_hp','tgl_lhr','tgl_mulai']) ? 'mono' : '' }}">{{ $it->data[$c] ?? '' }}</td>@endforeach
                        <td>@if($it->file_path)<a href="{{ route('ao.item.pdf', $it) }}" target="_blank">{{ $it->original_name ?: 'PDF' }}</a>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($req->isWaiting())
    <div class="card">
        <div x-show="!rejecting" style="display:flex;justify-content:flex-end;gap:10px">
            <button class="btn" @click="rejecting = true">Tolak</button>
            <form method="POST" action="{{ route('ao.approve', $req) }}" onsubmit="return confirm('Setujui request {{ $req->jumlah }} nasabah ini?')">
                @csrf
                <button class="btn primary" type="submit">Verifikasi &amp; Setujui</button>
            </form>
        </div>
        <form x-show="rejecting" x-cloak method="POST" action="{{ route('ao.reject', $req) }}">
            @csrf
            <div class="f"><label>Alasan penolakan <b>*</b></label><textarea name="note" rows="3" required maxlength="500" style="width:100%"></textarea></div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:10px">
                <button class="btn" type="button" @click="rejecting = false">Batal</button>
                <button class="btn primary" type="submit">Tolak Request</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
