@php
$containerFooter = !empty($containerNav) ? $containerNav : 'container-fluid';
@endphp

<footer class="content-footer footer ipms-dashboard-footer">
    <div class="{{ $containerFooter }}">
        <div class="footer-container ipms-dashboard-footer__container d-flex align-items-center justify-content-between gap-3 py-4 flex-md-row flex-column">
            <div class="ipms-dashboard-footer__identity">
                <span class="ipms-dashboard-footer__icon" aria-hidden="true">
                    <i class="icon-base ri ri-shield-keyhole-line"></i>
                </span>
                <span class="ipms-dashboard-footer__name">
                    <strong>{{ config('app.name', 'IPMS') }}</strong>
                    <span>Intellectual Property Management System</span>
                </span>
            </div>
            <small class="ipms-dashboard-footer__copyright">
                &copy; {{ now()->year }} {{ config('app.name', 'IPMS') }}. All rights reserved.
            </small>
        </div>
    </div>
</footer>
