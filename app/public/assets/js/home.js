// Landing Page Functionality

document.addEventListener('DOMContentLoaded', function() {
    initLandingPage();
});

function initLandingPage() {
    initSmoothScrolling();
    initFAQ();
}

// Smooth scrolling for anchor links
function initSmoothScrolling() {
    const anchors = document.querySelectorAll('a[href^="#"]');
    anchors.forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Don't prevent default for modal triggers
            if (this.hasAttribute('data-modal')) {
                return;
            }
            
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// FAQ accordion
function initFAQ() {
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        if (question) {
            question.addEventListener('click', function() {
                const answer = item.querySelector('.faq-answer');
                const isOpen = item.classList.contains('open');
                
                // Close all FAQ items
                faqItems.forEach(faq => {
                    faq.classList.remove('open');
                    const faqAnswer = faq.querySelector('.faq-answer');
                    if (faqAnswer) faqAnswer.style.display = 'none';
                });
                
                // Open clicked item if it wasn't already open
                if (!isOpen) {
                    item.classList.add('open');
                    if (answer) answer.style.display = 'block';
                }
            });
        }
    });
}