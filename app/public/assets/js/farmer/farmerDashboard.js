// Farmer Dashboard - Main Coordinator
// This file loads and coordinates all farmer dashboard modules

// Initialize Navigation
function initializeFarmerNavigation() {
    document.querySelectorAll('.menu-link').forEach(link => {
        link.addEventListener('click', function(e) {
            const section = this.dataset.section;
            const href = this.getAttribute('href');
            
            if (section) {
                e.preventDefault();
                showSection(section);
            }
        });
    });
}

// Section Navigation
function showSection(sectionId) {
    if (sectionId === 'overview') sectionId = 'dashboard';
    
    document.querySelectorAll('.content-section').forEach(section => {
        section.style.display = 'none';
    });
    
    const targetSection = document.getElementById(sectionId + '-section');
    if (targetSection) {
        targetSection.style.display = 'block';
    }
    
    document.querySelectorAll('.menu-link').forEach(link => {
        link.classList.remove('active');
        const key = link.dataset.section;
        if (key === sectionId || (sectionId === 'dashboard' && key === 'overview')) {
            link.classList.add('active');
        }
    });
}

// Utility function
function escapeHtml(str){ 
    return String(str ?? '').replace(/[&<>"']/g, s=>({ 
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' 
    }[s])); 
}

// Export functions
window.showSection = showSection;
window.escapeHtml = escapeHtml;

// Initialize navigation on load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeFarmerNavigation);
} else {
    initializeFarmerNavigation();
}
