@isset($pageConfigs)
{!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@extends('layouts/commonMaster')

@php
/* Display elements */
$contentNavbar = $contentNavbar ?? true;
$containerNav = $containerNav ?? 'container-xxl';
$isNavbar = $isNavbar ?? true;
$isMenu = $isMenu ?? true;
$isFlex = $isFlex ?? false;
$isFooter = $isFooter ?? true;
$customizerHidden = $customizerHidden ?? '';

/* HTML Classes */
$navbarDetached = 'navbar-detached';
$menuFixed = isset($configData['menuFixed']) ? $configData['menuFixed'] : '';
if (isset($navbarType)) {
$configData['navbarType'] = $navbarType;
}
$navbarType = isset($configData['navbarType']) ? $configData['navbarType'] : '';
$footerFixed = isset($configData['footerFixed']) ? $configData['footerFixed'] : '';
$menuCollapsed = isset($configData['menuCollapsed']) ? $configData['menuCollapsed'] : '';

/* Content classes */
$container = ($container ?? 'container-xxl');

@endphp

@section('layoutContent')
<div class="layout-wrapper layout-content-navbar {{ $isMenu ? '' : 'layout-without-menu' }}">
    <div class="layout-container">

        @if ($isMenu)
        @include('layouts/sections/menu/verticalMenu')
        @endif


        <!-- Layout page -->
        <div class="layout-page">

            {{-- Below commented code read by artisan command while installing jetstream. !! Do not remove if you want to use jetstream. --}}
            {{-- <x-banner /> --}}

            <!-- BEGIN: Navbar-->
            @if ($isNavbar)
            @include('layouts/sections/navbar/navbar')
            @endif
            <!-- END: Navbar-->


            <!-- Content wrapper -->
            <div class="content-wrapper">

                <!-- Content -->
                @if ($isFlex)
                <div class="{{ $container }} d-flex align-items-stretch flex-grow-1 p-0">
                    @else
                    <div class="{{ $container }} flex-grow-1 container-p-y">
                        @endif

                        @php
                        $breadcrumbs = match (request()->route()?->getName()) {
                        'dashboard-analytics' => [
                        ['label' => 'Home', 'url' => route('dashboard-analytics')],
                        ['label' => 'Dashboard'],
                        ],
                        'admin-dashboard.index' => [
                        ['label' => 'Home', 'url' => route('dashboard-analytics')],
                        ['label' => 'Admin Dashboard'],
                        ],
                        'admin-dashboard.disclosures.show' => [
                        ['label' => 'Home', 'url' => route('dashboard-analytics')],
                        ['label' => 'Admin Dashboard', 'url' => route('admin-dashboard.index')],
                        ['label' => 'Review Submission'],
                        ],
                        'dashboard.innovation-disclosure.create' => [
                        ['label' => 'Home', 'url' => route('dashboard-analytics')],
                        ['label' => 'Innovation Disclosure'],
                        ],
                        'dashboard.submissions.index' => [
                        ['label' => 'Home', 'url' => route('dashboard-analytics')],
                        ['label' => 'Submissions'],
                        ],
                        'dashboard.submissions.show' => [
                        ['label' => 'Home', 'url' => route('dashboard-analytics')],
                        ['label' => 'Submissions', 'url' => route('dashboard.submissions.index')],
                        ['label' => 'Review Submission'],
                        ],
                        default => [
                        ['label' => 'Home', 'url' => route('dashboard-analytics')],
                        ['label' => trim($__env->yieldContent('title', 'Page')) ?: 'Page'],
                        ],
                        };
                        @endphp
                        <nav class="page-breadcrumb mb-6" aria-label="Breadcrumb">
                            <ol class="breadcrumb breadcrumb-custom-icon mb-0">
                                @foreach ($breadcrumbs as $breadcrumb)
                                <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                                    @if (! $loop->last && isset($breadcrumb['url']))
                                    <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                                    @else
                                    <span @if ($loop->last) aria-current="page" @endif>{{ $breadcrumb['label'] }}</span>
                                    @endif
                                    @unless ($loop->last)
                                    <i class="breadcrumb-icon icon-base ri ri-arrow-right-s-line align-middle" aria-hidden="true"></i>
                                    @endunless
                                </li>
                                @endforeach
                            </ol>
                        </nav>

                        @yield('content')

                    </div>
                    <!-- / Content -->

                    <!-- Footer -->
                    @if ($isFooter)
                    @include('layouts/sections/footer/footer')
                    @endif
                    <!-- / Footer -->
                    <div class="content-backdrop fade"></div>
                </div>
                <!--/ Content wrapper -->
            </div>
            <!-- / Layout page -->
        </div>

        @if ($isMenu)
        <!-- Overlay -->
        <div class="layout-overlay layout-menu-toggle"></div>
        @endif
        <!-- Drag Target Area To SlideIn Menu On Small Screens -->
        <div class="drag-target"></div>
    </div>
    <!-- / Layout wrapper -->
</div>
@endsection