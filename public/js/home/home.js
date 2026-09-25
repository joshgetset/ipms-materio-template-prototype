document.addEventListener('DOMContentLoaded', function () {

    const patentSearchForm =
        document.getElementById('patentSearchForm');

    const patentQuery =
        document.getElementById('patentQuery');

    const patentSearchModal =
        document.getElementById('patentSearchModal');

    const patentModalOverlay =
        document.getElementById('patentModalOverlay');

    const patentModalClose =
        document.getElementById('patentModalClose');

    const patentResults =
        document.getElementById('patentResults');

    const patentModalTitle =
        document.getElementById('patentModalTitle');

    const patentModalDescription =
        document.getElementById('patentModalDescription');


    const patentDetailsModal =
        document.getElementById('patentDetailsModal');

    const patentDetailsOverlay =
        document.getElementById('patentDetailsOverlay');

    const patentDetailsClose =
        document.getElementById('patentDetailsClose');

    const patentDetailsContent =
        document.getElementById('patentDetailsContent');


    // Config passed from the Blade view instead of being inlined here.
    const searchUrl =
        patentSearchForm.dataset.searchUrl;

    const csrfToken =
        document
            .querySelector('meta[name="csrf-token"]')
            ?.content;


    /*
    |--------------------------------------------------------------------------
    | Escape HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {

        if (
            value === null ||
            value === undefined
        ) {
            return '';
        }

        const div =
            document.createElement('div');

        div.textContent =
            String(value);

        return div.innerHTML;
    }


    /*
    |--------------------------------------------------------------------------
    | Get title
    |--------------------------------------------------------------------------
    */

    function getPatentTitle(patent) {

        const titles =
            patent?.biblio?.invention_title;

        if (Array.isArray(titles)) {
            const preferredTitle =
                titles.find(title => title?.lang === 'en') ||
                titles[0];

            if (preferredTitle?.text) {
                return preferredTitle.text;
            }
        }

        return (
            patent?.biblio?.invention_title ||
            patent?.title ||
            'Untitled Patent'
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Get inventors
    |--------------------------------------------------------------------------
    */

    function getPatentInventors(patent) {

        const inventors =
            patent?.biblio?.parties?.inventors ||
            patent?.inventor;

        if (!Array.isArray(inventors)) {
            return 'Not available';
        }

        const names =
            inventors
                .map(inventor => {

                    return (
                        inventor?.extracted_name?.value ||
                        inventor?.name ||
                        inventor?.display_name ||
                        ''
                    );

                })
                .filter(Boolean);

        return names.length
            ? names.join(', ')
            : 'Not available';

    }


    /*
    |--------------------------------------------------------------------------
    | Get abstract
    |--------------------------------------------------------------------------
    */

    function getPatentAbstract(patent) {

        const abstract =
            patent?.abstract;

        if (!abstract) {
            return 'No abstract available.';
        }

        if (typeof abstract === 'string') {
            return abstract;
        }

        if (Array.isArray(abstract)) {
            const preferredAbstract =
                abstract.find(item => item?.lang === 'en') ||
                abstract[0];

            return preferredAbstract?.text ||
                'No abstract available.';
        }

        if (abstract?.text) {
            return abstract.text;
        }

        return 'No abstract available.';
    }


    /*
    |--------------------------------------------------------------------------
    | Open search modal
    |--------------------------------------------------------------------------
    */

    function openSearchModal() {

        patentSearchModal.classList.remove('hidden');

        document.body.style.overflow = 'hidden';

    }


    /*
    |--------------------------------------------------------------------------
    | Close search modal
    |--------------------------------------------------------------------------
    */

    function closeSearchModal() {

        patentSearchModal.classList.add('hidden');

        document.body.style.overflow = '';

    }


    /*
    |--------------------------------------------------------------------------
    | Open details modal
    |--------------------------------------------------------------------------
    */

    function openDetailsModal(patent) {

        const title =
            getPatentTitle(patent);

        const inventors =
            getPatentInventors(patent);

        const abstract =
            getPatentAbstract(patent);

        const lensId =
            patent?.lens_id ||
            'Not available';

        const publicationReference =
            patent?.biblio?.publication_reference;

        const publicationNumber =
            publicationReference?.doc_number ||
            patent?.doc_number ||
            'Not available';

        const jurisdiction =
            publicationReference?.jurisdiction ||
            patent?.jurisdiction ||
            'Not available';

        const publicationDate =
            patent?.date_published ||
            'Not available';

        const publicationType =
            patent?.publication_type ||
            'Not available';


        patentDetailsContent.innerHTML = `

            <div class="details-label">
                PATENT RECORD
            </div>

            <h2 class="details-title">
                ${escapeHtml(title)}
            </h2>

            <div class="details-grid">

                <div class="details-item">

                    <span class="details-item-label">
                        Lens ID
                    </span>

                    <span class="details-item-value">
                        ${escapeHtml(lensId)}
                    </span>

                </div>


                <div class="details-item">

                    <span class="details-item-label">
                        Publication Number
                    </span>

                    <span class="details-item-value">
                        ${escapeHtml(publicationNumber)}
                    </span>

                </div>


                <div class="details-item">

                    <span class="details-item-label">
                        Jurisdiction
                    </span>

                    <span class="details-item-value">
                        ${escapeHtml(jurisdiction)}
                    </span>

                </div>


                <div class="details-item">

                    <span class="details-item-label">
                        Publication Date
                    </span>

                    <span class="details-item-value">
                        ${escapeHtml(publicationDate)}
                    </span>

                </div>


                <div class="details-item">

                    <span class="details-item-label">
                        Document Type
                    </span>

                    <span class="details-item-value">
                        ${escapeHtml(publicationType)}
                    </span>

                </div>


                <div class="details-item">

                    <span class="details-item-label">
                        Inventor
                    </span>

                    <span class="details-item-value">
                        ${escapeHtml(inventors)}
                    </span>

                </div>

            </div>


            <div class="details-abstract">

                <h4>
                    ABSTRACT
                </h4>

                <p>
                    ${escapeHtml(abstract)}
                </p>

            </div>

        `;


        patentDetailsModal.classList.remove('hidden');

    }


    /*
    |--------------------------------------------------------------------------
    | Close details
    |--------------------------------------------------------------------------
    */

    function closeDetailsModal() {

        patentDetailsModal.classList.add('hidden');

    }


    /*
    |--------------------------------------------------------------------------
    | Patent search
    |--------------------------------------------------------------------------
    */

    patentSearchForm.addEventListener(
        'submit',
        async function(event) {

            event.preventDefault();


            const query =
                patentQuery.value.trim();


            if (!query) {

                patentQuery.focus();

                return;

            }


            openSearchModal();


            patentModalTitle.textContent =
                'Searching patents...';

            patentModalDescription.textContent =
                `Searching for "${query}"`;

            patentResults.innerHTML = `

                <div class="patent-empty">

                    <div class="patent-empty-icon">
                        ...
                    </div>

                    <h3>
                        Searching patent records
                    </h3>

                    <p>
                        Please wait while IPMS searches
                        the Lens patent database.
                    </p>

                </div>

            `;


            try {

                const response =
                    await fetch(
                        searchUrl,
                        {

                            method: 'POST',

                            headers: {

                                'Content-Type':
                                    'application/json',

                                'Accept':
                                    'application/json',

                                'X-CSRF-TOKEN':
                                    csrfToken

                            },

                            body: JSON.stringify({
                                query: query
                            })

                        }
                    );


                const data =
                    await response.json();


                if (
                    !response.ok ||
                    !data.success
                ) {

                    throw new Error(
                        data.message ||
                        'Patent search failed.'
                    );

                }


                displayPatentResults(
                    data.data || [],
                    data.total || 0,
                    query
                );


            } catch (error) {

                patentModalTitle.textContent =
                    'Search unavailable';

                patentModalDescription.textContent =
                    'IPMS could not complete the patent search.';


                patentResults.innerHTML = `

                    <div class="patent-empty">

                        <div class="patent-empty-icon">
                            !
                        </div>

                        <h3>
                            Unable to search patents
                        </h3>

                        <p>
                            ${escapeHtml(error.message)}
                        </p>

                    </div>

                `;

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Display results
    |--------------------------------------------------------------------------
    */

    function displayPatentResults(
        patents,
        total,
        query
    ) {

        patentModalTitle.textContent =
            'Patent Search Results';


        patentModalDescription.textContent =
            `${total} ${
                total === 1
                    ? 'record'
                    : 'records'
            } found for "${query}"`;


        if (!patents.length) {

            patentResults.innerHTML = `

                <div class="patent-empty">

                    <div class="patent-empty-icon">
                        0
                    </div>

                    <h3>
                        No patents found
                    </h3>

                    <p>
                        No patent records matched
                        "<strong>${escapeHtml(query)}</strong>".
                        Try a different patent title,
                        identifier, inventor, or keyword.
                    </p>

                </div>

            `;

            return;

        }


        patentResults.innerHTML = '';


        patents.forEach(
            (patent, index) => {

                const title =
                    getPatentTitle(patent);


                const inventors =
                    getPatentInventors(patent);


                const publicationReference =
                    patent?.biblio?.publication_reference;

                const publicationNumber =
                    publicationReference?.doc_number ||
                    patent?.doc_number ||
                    'Not available';


                const jurisdiction =
                    publicationReference?.jurisdiction ||
                    patent?.jurisdiction ||
                    '';


                const result =
                    document.createElement('div');


                result.className =
                    'patent-result';


                result.innerHTML = `

                    <div class="patent-result-info">

                        <div class="patent-result-meta">

                            ${escapeHtml(jurisdiction)}

                            ${
                                publicationNumber !==
                                'Not available'
                                    ? ` · ${escapeHtml(
                                        publicationNumber
                                    )}`
                                    : ''
                            }

                        </div>


                        <div class="patent-result-title">

                            ${escapeHtml(title)}

                        </div>


                        <div class="patent-result-inventor">

                            Inventor:
                            ${escapeHtml(inventors)}

                        </div>

                    </div>


                    <button
                        type="button"
                        class="patent-view"
                    >
                        View Details
                    </button>

                `;


                result
                    .querySelector('.patent-view')
                    .addEventListener(
                        'click',
                        function() {

                            openDetailsModal(
                                patent
                            );

                        }
                    );


                patentResults.appendChild(
                    result
                );

            }
        );

    }


    /*
    |--------------------------------------------------------------------------
    | Modal events
    |--------------------------------------------------------------------------
    */

    patentModalClose.addEventListener(
        'click',
        closeSearchModal
    );


    patentModalOverlay.addEventListener(
        'click',
        closeSearchModal
    );


    patentDetailsClose.addEventListener(
        'click',
        closeDetailsModal
    );


    patentDetailsOverlay.addEventListener(
        'click',
        closeDetailsModal
    );


    /*
    |--------------------------------------------------------------------------
    | Escape key
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function(event) {

            if (event.key !== 'Escape') {
                return;
            }


            if (
                !patentDetailsModal
                    .classList
                    .contains('hidden')
            ) {

                closeDetailsModal();

                return;

            }


            if (
                !patentSearchModal
                    .classList
                    .contains('hidden')
            ) {

                closeSearchModal();

            }

        }
    );

});

/* =========================================================
   HERO CAROUSEL
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const slides = document.querySelectorAll(".hero-slide");
    const dots = document.querySelectorAll(".carousel-dot");
    const nextButton = document.getElementById("carouselNext");

    if (!slides.length) {
        return;
    }

    let currentSlide = 0;

    function showSlide(index) {

        currentSlide = index;

        slides.forEach((slide, i) => {
            slide.classList.toggle("active", i === currentSlide);
        });

        dots.forEach((dot, i) => {
            dot.classList.toggle("active", i === currentSlide);
        });
    }


    dots.forEach((dot, index) => {

        dot.addEventListener("click", function () {
            showSlide(index);
        });

    });


    if (nextButton) {

        nextButton.addEventListener("click", function () {

            const nextSlide =
                (currentSlide + 1) % slides.length;

            showSlide(nextSlide);

        });

    }

});

/* =========================================================
   DYNAMIC GLASS NAVBAR
   ========================================================= */

document.addEventListener("DOMContentLoaded", function () {

    const navbar = document.querySelector(".navbar");
    const heroCarousel = document.querySelector(".hero-carousel");
    const topButton = document.querySelector(".nav-top");

    if (!navbar || !heroCarousel || !topButton) {
        return;
    }

    function updateNavbar() {
        const carouselBottom = heroCarousel.getBoundingClientRect().bottom;
        const shouldDock = carouselBottom <= navbar.offsetHeight;

        if (window.scrollY > 30) {
            navbar.classList.add("scrolled");
        } else {
            navbar.classList.remove("scrolled");
        }

        navbar.classList.toggle("docked", shouldDock);
        topButton.classList.toggle("visible", shouldDock);

    }

    updateNavbar();

    window.addEventListener(
        "scroll",
        updateNavbar,
        { passive: true }
    );

    topButton.addEventListener("click", function () {
        window.scrollTo({
            top: 0,
            behavior: "smooth"
        });
    });

});