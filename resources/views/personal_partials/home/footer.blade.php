<footer class="site-footer">

    <div class="container">

        <div class="footer-grid">

            <div class="footer-brand">

                <div class="footer-brand-row">
                    <div class="footer-logo">IP</div>

                    <div>
                        <div class="footer-name">IPMS</div>
                        <div class="footer-tagline">Intellectual Property Management System</div>
                    </div>
                </div>

                <p>
                    A centralized platform for organizing, monitoring,
                    and managing intellectual property records and
                    related activities.
                </p>

            </div>

            <nav class="footer-col" aria-label="Product">
                <h3 class="footer-title">Product</h3>

                <ul class="footer-links">
                    <li><a href="#about">About</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#how-it-works">How it works</a></li>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="Account">
                <h3 class="footer-title">Account</h3>

                <ul class="footer-links">
                    <li><a href="{{ route('login') }}">Sign in</a></li>
                    <li><a href="{{ route('signin') }}">Create account</a></li>
                </ul>
            </nav>

            <nav class="footer-col" aria-label="Tools">
                <h3 class="footer-title">Tools</h3>

                <ul class="footer-links">
                    <li><a href="#patentQuery">Patent search</a></li>
                </ul>
            </nav>

        </div>

        <div class="footer-bottom">

            <p>© {{ date('Y') }} IPMS. All rights reserved.</p>

            <p>Intellectual Property Management System</p>

        </div>

    </div>

</footer>