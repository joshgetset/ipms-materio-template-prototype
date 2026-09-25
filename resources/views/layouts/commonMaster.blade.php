<!DOCTYPE html>
<html
    lang="en"
    dir="ltr"
    data-bs-theme="light"
    data-template="vertical-menu-template"
>

<head>

    <meta charset="utf-8" />

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    />

    <title>
        @yield('title', 'Dashboard') | {{ config('app.name', 'IPMS') }}
    </title>

    <meta
        name="description"
        content="Intellectual Property Management System"
    />

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    />

    <!-- Favicon -->
    <link
        rel="icon"
        type="image/x-icon"
        href="{{ asset('assets/img/favicon/favicon.ico') }}"
    />

    @if (empty($skipTemplateAssets))
        <!-- Materio Styles -->
        @include('layouts/sections/styles')
    @endif

    @stack('styles')

    <!-- Materio Helper / Config Scripts -->
    @include('layouts/sections/scriptsIncludes')

</head>

<body
    data-toast-type="{{ e(session('toast_type') ?? 'success') }}"
    data-toast-message="{{ e(session('toast_message') ?? '') }}"
>

    <!-- Layout Content -->
    @yield('layoutContent')
    <!-- / Layout Content -->

    @if (empty($skipTemplateAssets))
        <!-- Materio Scripts -->
        @include('layouts/sections/scripts')
    @endif

    @stack('scripts')

</body>

</html>