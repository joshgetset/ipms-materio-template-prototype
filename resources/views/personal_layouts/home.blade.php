<?php $skipTemplateAssets = true; ?>

@extends('layouts.blankLayout')

@section('title', 'Home')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap"
    rel="stylesheet">

@vite('resources/assets/vendor/fonts/iconify/iconify.css')
<link rel="stylesheet" href="{{ asset('css/home/home.css') }}">
<link rel="stylesheet" href="{{ asset('css/home/navbar.css') }}">
<link rel="stylesheet" href="{{ asset('css/home/hero_carousel.css') }}">
<link rel="stylesheet" href="{{ asset('css/home/home_sections.css') }}">
@endpush

@section('content')

@include('personal_partials.home.navbar')

<main>
    @include('personal_partials.home.hero')
    @include('personal_partials.home.about')
    @include('personal_partials.home.features')
    @include('personal_partials.home.how-it-works')
    @include('personal_partials.home.cta')
</main>

@include('personal_partials.home.patent-modals')
@include('personal_partials.home.footer')

@endsection

@push('scripts')
<script src="{{ asset('js/home/navbar.js') }}" defer></script>
<script src="{{ asset('js/home/home.js') }}" defer></script>
<script src="{{ asset('js/home/carousel.js') }}" defer></script>
<script src="{{ asset('js/home/side-nav.js') }}" defer></script>
<script src="{{ asset('js/login.js') }}" defer></script>
@endpush