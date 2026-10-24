<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/cdmp-logo.svg') }}">
    <title>@yield('title', 'CDMP Exam Simulator')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="site-shell">
    <header class="site-header">
        <a class="brand" href="{{ route('dashboard') }}" aria-label="CDMP Exam Simulator home">
            <img class="brand-mark" src="{{ asset('images/cdmp-logo.svg') }}" alt="" width="36" height="36" aria-hidden="true">
            <span class="brand-name">CDMP <span class="brand-light">Exam Simulator</span></span>
        </a>
        <nav class="main-nav" aria-label="Main navigation">
            <a class="nav-link" href="{{ route('dashboard') }}" @class(['is-active' => request()->routeIs('dashboard')])>Overview</a>
            <a class="nav-link" href="{{ route('history.index') }}" @class(['is-active' => request()->routeIs('history.*')])>History</a>
            <a class="nav-action" href="{{ route('exams.create') }}">Start exam <span aria-hidden="true">→</span></a>
        </nav>
    </header>

    @if ($errors->any())
        <div class="flash flash-error" role="alert">
            <strong>We couldn’t start this session.</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <main class="page-content">
        @yield('content')
    </main>

    <footer class="site-footer">
        <span>Practice questions from your supplied PDF.</span>
        <span>Progress is saved in this browser only. · This is not an official DAMA examination product.</span>
    </footer>
</div>
</body>
</html>
