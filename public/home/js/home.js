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
