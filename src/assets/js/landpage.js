/**
 * CUSTOM IMAGE SLIDER LOGIC
 */
let currentSlide = 0;
let slideInterval; // Declare it here so it's accessible everywhere

function showSlide(index) {
    const slidesContainer = document.querySelector(".slides");
    const slides = document.querySelectorAll(".slide");
    const dots = document.querySelectorAll(".dot");

    if (!slidesContainer || slides.length === 0) return;

    // Reset loop
    if (index >= slides.length) index = 0;
    if (index < 0) index = slides.length - 1;

    currentSlide = index;

    // Move slides
    slidesContainer.style.transform = `translateX(-${currentSlide * 100}%)`;

    // Update dots
    dots.forEach(dot => dot.classList.remove("active"));
    if (dots[currentSlide]) {
        dots[currentSlide].classList.add("active");
    }
}

// Function to start/restart the timer
function startAutoPlay() {
    if (slideInterval) clearInterval(slideInterval);
    slideInterval = setInterval(() => {
        showSlide(currentSlide + 1);
    }, 5000);
}

// Initialize Slider when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    const dots = document.querySelectorAll(".dot");
    
    // Initial start
    startAutoPlay();

    // Dot navigation
    dots.forEach((dot, idx) => {
        dot.addEventListener("click", () => {
            showSlide(idx);
            startAutoPlay(); // Reset timer on click
        });
    });
});

/**
 * UTILITY & NAVIGATION LOGIC
 */

// Back to Top Button
const backToTopBtn = document.createElement('button');
backToTopBtn.id = 'backToTop';
backToTopBtn.innerHTML = '↑';
backToTopBtn.title = 'Back to Top';
document.body.appendChild(backToTopBtn);

window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
        backToTopBtn.classList.add('show');
    } else {
        backToTopBtn.classList.remove('show');
    }
});

backToTopBtn.addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Smooth scroll for all anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        const target = document.querySelector(targetId);
        
        if (target) {
            const headerOffset = 80;
            const elementPosition = target.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    });
});

// Add active state to Quick Nav items on scroll
const sections = document.querySelectorAll('section, .slider');
const navItems = document.querySelectorAll('.quick-nav-item');

window.addEventListener('scroll', () => {
    let current = '';
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        if (window.pageYOffset >= (sectionTop - 200)) {
            current = section.getAttribute('id');
        }
    });

    navItems.forEach(item => {
        item.style.background = 'rgba(255, 255, 255, 0.15)'; 
        if (item.getAttribute('href').slice(1) === current && current !== null) {
            item.style.background = 'rgba(255, 255, 255, 0.35)';
        }
    });
});