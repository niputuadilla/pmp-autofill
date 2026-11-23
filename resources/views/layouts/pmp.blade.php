<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Payroll Management Platform') - Prototype</title>
    <link rel="stylesheet" href="{{ asset('css/pmp.css') }}">
    <script defer src="{{ asset('js/alpine.min.js') }}"></script>
</head>
<body>
<div class="app">
    <aside class="side">
        <div class="brand">
            <div class="logo">P</div>
            <span>Payroll<br>Management<br>Platform</span>
        </div>
        <nav class="menu">
            <a href="#">Main Page</a>
            <a href="#">User Activity</a>
            <a href="#">Mitra Management <i class="caret">&#9656;</i></a>
            <a href="#">Payroll <i class="caret">&#9656;</i></a>
            <a href="#">Collection <i class="caret">&#9656;</i></a>
            <a href="#">Pipeline Payroll <i class="caret">&#9656;</i></a>
            <div class="m open">Account Opening <i class="caret">&#9662;</i></div>
            <a class="sub {{ ($nav ?? '') === 'ao' ? 'active' : '' }}" href="{{ route('ao.index') }}">Account Opening</a>
            <a class="sub {{ ($nav ?? '') === 'reports' ? 'active' : '' }}" href="{{ route('ao.reports') }}">Reports</a>
            <a href="#">Account Calibration <i class="caret">&#9656;</i></a>
            <a href="#">Payroll Account Claim Request <i class="caret">&#9656;</i></a>
            <a href="#">Template Dokumen <i class="caret">&#9656;</i></a>
            <a href="#">Report Overview</a>
            <a href="#">Account Statement</a>
        </nav>
        <div class="foot">Prototype lokal. Menu lain hanya tampilan.</div>
    </aside>
    <div class="main">
        <header class="top">
            <div class="crumb">@yield('crumb', 'Account Opening')</div>
            <div class="right"><span>v 2.0.10 (prototype)</span><span class="avatar">TD</span></div>
        </header>
        <main class="content">@yield('content')</main>
        <div class="footer">Prototype fitur Autofill APR. Bukan sistem produksi.</div>
    </div>
</div>
@stack('scripts')
</body>
</html>
