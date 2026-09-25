document.addEventListener('DOMContentLoaded', function () {
  const slides = document.querySelectorAll('.hero-slide');

  const dots = document.querySelectorAll('.carousel-dot');

  const nextButton = document.getElementById('carouselNext');

  const photoAlt = document.querySelector('.hero-photo-alt');

  const photoBase = document.querySelector('.hero-photo-base');

  if (!slides.length) {
    return;
  }

  let currentSlide = 0;
  let isAnimating = false;

  function showSlide(index) {
    if (isAnimating || index === currentSlide) {
      return;
    }

    isAnimating = true;

    slides[currentSlide].classList.remove('active');

    currentSlide = index;

    slides[currentSlide].classList.add('active');

    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === currentSlide);
    });

    setTimeout(() => {
      isAnimating = false;
    }, 800);
  }

  dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
      showSlide(index);
    });
  });

  if (nextButton) {
    nextButton.addEventListener('click', () => {
      advancePhoto();
    });
  }

  const photoImages = ['ripe.png', 'slsu_main.png', 'landmark.jpg', 'RIES.JPG'];

  let currentPhoto = 0;

  function advancePhoto() {
    if (!photoAlt || !photoBase) {
      return;
    }

    currentPhoto = (currentPhoto + 1) % photoImages.length;
    const nextPhoto = photoImages[currentPhoto];

    if (photoAlt.classList.contains('is-visible')) {
      photoBase.style.backgroundImage = `url("../../images/hero-carousel/${nextPhoto}")`;
      photoAlt.classList.remove('is-visible');
    } else {
      photoAlt.style.backgroundImage = `url("../../images/hero-carousel/${nextPhoto}")`;
      photoAlt.classList.add('is-visible');
    }
  }

  setInterval(advancePhoto, 5000);
});
