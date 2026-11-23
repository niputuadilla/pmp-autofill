@extends('layouts.pmp')
@section('title', 'Add Request Satuan')
@section('crumb', 'Account Opening > Account Opening')
@section('content')
<div class="head">
    <h1 class="title" style="margin:0">Add Request Satuan</h1>
    <a class="btn" href="{{ route('ao.index') }}">Cancel</a>
</div>
<div class="alert info">Tampilan referensi Manual Satuan (tidak berfungsi di prototype). Untuk mencoba fitur baru, pilih <a href="{{ route('ao.bulk') }}"><b>Manual Bulk</b></a>.</div>

<div class="card">
    <div class="sec">
        <div class="name">Informasi Mitra</div>
        <div class="grid">
            <div class="f span6"><label>Mitra <b>*</b></label><select disabled><option>Input Kode atau Nama Mitra</option></select></div>
            <div class="f span3"><label>Nama Perusahaan</label><input type="text" disabled placeholder="Nama Perusahaan"></div>
            <div class="f span3"><label>Kode Cabang</label><input type="text" disabled value="{{ config('pmp.branch_code') }}"></div>
            <div class="f span6"><label>Pipeline ID</label><input type="text" disabled placeholder="Pipeline ID"></div>
            <div class="f span3"><label>Sektor Industri <b>*</b></label><select disabled><option>Select...</option></select></div>
            <div class="f span3"><label>Kode Produk</label><input type="text" disabled placeholder="Kode Produk"></div>
            <div class="f span6"><label>Biaya Tabungan</label><input type="text" disabled placeholder="Biaya Tabungan"></div>
            <div class="f span3"><label>Status Kerjasama</label><input type="text" disabled placeholder="Status Kerjasama"></div>
            <div class="f span3"><label>Status Benefit</label><input type="text" disabled placeholder="Status Benefit"></div>
        </div>
    </div>
    <div class="sec">
        <div class="name">Informasi Data Diri</div>
        <div class="grid">
            <div class="f span2"><label>Gelar Depan</label><input type="text" disabled placeholder="Input Gelar"></div>
            <div class="f span2"><label>Nama Lengkap <b>*</b></label><input type="text" disabled placeholder="Nama Lengkap Sesuai Identitas"></div>
            <div class="f span2"><label>Gelar Belakang</label><input type="text" disabled placeholder="Input Gelar"></div>
            <div class="f span2"><label>Jenis Kelamin <b>*</b></label><select disabled><option>Select...</option></select></div>
            <div class="f span2"><label>Kota Lahir</label><input type="text" disabled placeholder="Kota Lahir"></div>
            <div class="f span2"><label>Tanggal Lahir <b>*</b></label><input type="text" disabled placeholder="mm/dd/yyyy"></div>
            <div class="f span6"><label>Kewarganegaraan <b>*</b></label><select disabled><option>WNI</option></select></div>
            <div class="f span3"><label>Kota KTP <b>*</b></label><input type="text" disabled placeholder="Kota KTP"></div>
            <div class="f span3"><label>No KTP <b>*</b></label><input type="text" disabled placeholder="No KTP"></div>
            <div class="f span6"><label>Nama Ibu Kandung <b>*</b></label><input type="text" disabled placeholder="Nama Ibu Kandung"></div>
            <div class="f span6"><label>Alamat 1 <b>*</b></label><textarea rows="2" disabled placeholder="Input Alamat Utama"></textarea></div>
            <div class="f span6"><label>Alamat 2 <b>*</b></label><textarea rows="2" disabled placeholder="Input Alamat Lain"></textarea></div>
            <div class="f span6"><label>Kode Pos <b>*</b></label><select disabled><option>Select...</option></select></div>
            <div class="f span6"><label>Status Kawin <b>*</b></label><select disabled><option>Select...</option></select></div>
            <div class="f span6"><label>Nomor Kontak Handphone <b>*</b></label><input type="text" disabled placeholder="(Kode Negara) Nomor Kontak Handphone"></div>
        </div>
    </div>
    <div class="sec">
        <div class="name">Informasi Pekerjaan</div>
        <div class="grid">
            <div class="f span2"><label>Pekerjaan <b>*</b></label><select disabled><option>Select...</option></select></div>
            <div class="f span2"><label>Jabatan <b>*</b></label><select disabled><option>Select...</option></select></div>
            <div class="f span2"><label>Tanggal Mulai <b>*</b></label><input type="text" disabled placeholder="mm / dd / yyyy"></div>
            <div class="f span3"><label>Gaji <b>*</b></label><input type="text" disabled placeholder="Gaji"></div>
            <div class="f span3"><label>Pendapatan Lain</label><input type="text" disabled value="0"></div>
        </div>
    </div>
    <div class="sec">
        <div class="name">Informasi Lainnya</div>
        <div>
            <div class="dim" style="margin-bottom:8px">Sarana komunikasi pribadi untuk tujuan pemasaran dan atau penawaran yang dipilih nasabah</div>
            @foreach(\App\Support\Pmp::CONSENT_CHANNELS as $label)
                <label class="check"><input type="checkbox" disabled> {{ $label }}</label>
            @endforeach
        </div>
    </div>
    <div class="sec">
        <div class="name">File Upload</div>
        <div class="f"><label>Dokumen APR Nasabah <b>*</b></label>
            <div class="dz" style="cursor:default"><div class="ic">&#9729;</div>Select a file or drag and drop here<small>pdf, size no more than 5MB</small></div>
        </div>
    </div>
    <div style="text-align:right"><button class="btn primary disabled">Submit</button></div>
</div>
@endsection
