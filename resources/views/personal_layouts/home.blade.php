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
<link rel="stylesheet" href="{{ asset('home/css/home.css') }}">
<link rel="stylesheet" href="{{ asset('home/css/navbar.css') }}">
<link rel="stylesheet" href="{{ asset('home/css/hero_carousel.css') }}">
<link rel="stylesheet" href="{{ asset('home/css/home_sections.css') }}">
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

@include('personal_partials.home.footer')

@endsection

@push('scripts')
<script src="{{ asset('home/js/navbar.js') }}" defer></script>
<script src="{{ asset('home/js/home.js') }}" defer></script>
<script src="{{ asset('home/js/carousel.js') }}" defer></script>
<script src="{{ asset('home/js/side-nav.js') }}" defer></script>
<script src="{{ asset('auth/js/login.js') }}" defer></script>
@endpush