<!-- Navbar -->
@if (isset($navbarDetached) && $navbarDetached == 'navbar-detached')
<nav class="layout-navbar {{ $containerNav }} {{ $navbarDetached }} navbar navbar-expand-xl align-items-center bg-white shadow mt-4 rounded-3" id="layout-navbar">
    @include('layouts/sections/navbar/navbar-partial')
</nav>
@else
<nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-white shadow" id="layout-navbar">
    <div class="{{ $containerNav }}">@include('layouts/sections/navbar/navbar-partial')</div>
</nav>
@endif
<!-- / Navbar -->