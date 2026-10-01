@php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
$currentUser = Auth::user();
$displayName = $currentUser?->name ?: $currentUser?->username ?: 'Account';
@endphp

<!--  Brand demo (display only for navbar-full and hide on below xl) -->
@if(isset($navbarFull))
<div class="navbar-brand app-brand demo d-none d-xl-flex py-0 me-6">
    <a href="{{url('/')}}" class="app-brand-link gap-2">
        <span class="app-brand-logo demo">@include('_partials.macros')</span>
        <span class="app-brand-text demo menu-text fw-bold">{{config('variables.templateName')}}</span>
    </a>
</div>
@endif

<div class="navbar-nav-right d-flex align-items-center px-4" id="navbar-collapse">
    <a class="navbar-brand fw-semibold d-none d-sm-block text-truncate w-50" href="{{ url('/') }}">Intellectual Property Management System</a>
    <ul class="navbar-nav flex-row align-items-center flex-shrink-0 ms-auto">
        <!-- Place this tag where you want the button to render. -->
        <li class="nav-item lh-1 me-4">
            <a class="github-button" href="{{config('variables.repository')}}" data-icon="octicon-star" data-size="large" data-show-count="true" aria-label="Star themeselection/sneat-html-laravel-admin-template-free on GitHub">Star</a>
        </li>

        <!-- User -->
        <li class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown" aria-label="{{ $displayName }} account menu">
                <div class="avatar avatar-online">
                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="icon-base ri ri-user-3-line"></i></span>
                </div>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar avatar-online">
                                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="icon-base ri ri-user-3-line"></i></span>
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0">{{ $displayName }}</h6>
                                <small class="text-body-secondary">{{ $currentUser?->email ?? 'Signed in' }}</small>
                            </div>
                        </div>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider my-1"></div>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <i class="icon-base ri ri-user-3-line icon-md me-3"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <i class="icon-base ri ri-settings-4-line icon-md me-4"></i><span>Settings</span>
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="javascript:void(0);">
                        <span class="d-flex align-items-center align-middle">
                            <i class="flex-shrink-0 icon-base ri ri-bank-card-line icon-md me-3"></i>
                            <span class="flex-grow-1 align-middle ms-1">Billing Plan</span>
                            <span class="flex-shrink-0 badge rounded-pill bg-danger">4</span>
                        </span>
                    </a>
                </li>
                <li>
                    <div class="dropdown-divider my-1"></div>
                </li>
                <li>
                    <div class="d-grid px-4 pt-2 pb-1">
                        <form method="POST" action="{{ route('logout') }}" data-logout-form>
                            @csrf
                            <button class="btn btn-danger d-flex align-items-center justify-content-between w-100" type="submit">
                                <small class="align-middle">Logout</small>
                                <i class="ri ri-logout-box-r-line ms-2 icon-xs" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                </li>
            </ul>
        </li>
        <!--/ User -->
    </ul>
</div>