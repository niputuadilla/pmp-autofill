@extends('layouts.pmp')
@section('title', 'Reports')
@section('crumb', 'Account Opening > Reports')
@section('content')
<div class="head">
    <h1 class="title" style="margin:0">Reports</h1>
    <select style="width:260px"><option>Report Request Per Region</option></select>
</div>
<div class="card">
    <h2 style="font-size:15px;margin-bottom:12px">Report Request Per Region</h2>
    <table class="t">
        <thead><tr><th>Mekanisme Request</th><th>Region</th><th>Waiting</th><th>Success</th><th>Reject</th></tr></thead>
        <tbody>
        <tr><td><b>Manual Bulk</b></td><td>Branch {{ $branch }}</td><td>{{ $waiting }}</td><td>{{ $bulk }}</td><td>{{ $rejected }}</td></tr>
        <tr><td><b>Manual Satuan</b></td><td>Branch {{ $branch }}</td><td>0</td><td>0</td><td>0</td></tr>
        </tbody>
    </table>
    <p class="dim" style="margin-top:12px">Prototype: hanya menghitung request Manual Bulk yang dibuat lewat fitur autofill.</p>
</div>
@endsection
