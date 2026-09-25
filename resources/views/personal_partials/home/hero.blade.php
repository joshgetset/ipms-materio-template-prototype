<section class="hero">

    <div class="container">

        <div class="hero-carousel">

            <!-- Rotating background photo layers -->
            <div class="hero-photo-base" aria-hidden="true"></div>
            <div class="hero-photo-alt" aria-hidden="true"></div>

            <!-- SLIDE 1 -->
            <div class="hero-slide active">

                <div class="hero-slide-content">

                    <div class="hero-badge">
                        Intellectual Property Management
                    </div>

                    <h1>
                        Manage your intellectual property
                        <span>with clarity.</span>
                    </h1>

                    <p class="hero-description">
                        IPMS provides a centralized platform for organizing,
                        monitoring, and managing intellectual property records
                        and related activities.
                    </p>

                    <div class="hero-actions">

                        <a href="#" class="btn btn-primary">
                            Get Started
                        </a>

                        <a href="#about" class="btn btn-secondary">
                            Learn More
                        </a>

                    </div>

                </div>

            </div>


            <!-- SLIDE 2 -->
            <div class="hero-slide">

                <div class="hero-slide-content">

                    <div class="hero-badge">
                        Centralized Records
                    </div>

                    <h1>
                        Keep every IP record
                        <span>organized.</span>
                    </h1>

                    <p class="hero-description">
                        Maintain structured records for intellectual property
                        assets, applications, registrations, inventors,
                        and related information in one platform.
                    </p>

                    <div class="hero-actions">

                        <a href="#features" class="btn btn-primary">
                            Explore Features
                        </a>

                        <a href="#about" class="btn btn-secondary">
                            Learn More
                        </a>

                    </div>

                </div>

            </div>


            <!-- SLIDE 3 -->
            <div class="hero-slide">

                <div class="hero-slide-content">

                    <div class="hero-badge">
                        Patent Search
                    </div>

                    <h1>
                        Discover relevant
                        <span>patent information.</span>
                    </h1>

                    <p class="hero-description">
                        Search patent information directly from the landing
                        page and review available patent details through
                        the integrated search interface.
                    </p>

                    <div class="hero-actions">

                        <a href="#" class="btn btn-primary"
                            onclick="document.getElementById('patentQuery').focus(); return false;">
                            Search Patent
                        </a>

                        <a href="#features" class="btn btn-secondary">
                            View Capabilities
                        </a>

                    </div>

                </div>

            </div>


            <!-- SCHOOL LOGO -->
            <div class="hero-school-logo">
                <img
                    src="{{ asset('images/hero-carousel/slsu_logo.png') }}"
                    alt="SLSU logo"
                    class="hero-school-logo-img">
            </div>


            <!-- CAROUSEL CONTROLS -->
            <div class="hero-carousel-controls">

                <button
                    type="button"
                    class="carousel-dot active"
                    data-slide="0"
                    aria-label="Go to slide 1"></button>

                <button
                    type="button"
                    class="carousel-dot"
                    data-slide="1"
                    aria-label="Go to slide 2"></button>

                <button
                    type="button"
                    class="carousel-dot"
                    data-slide="2"
                    aria-label="Go to slide 3"></button>

                <button
                    type="button"
                    class="carousel-arrow"
                    id="carouselNext"
                    aria-label="Next image">
                    <i class="icon-base ri ri-arrow-right-line" aria-hidden="true"></i>
                </button>

            </div>

        </div>

    </div>

</section>