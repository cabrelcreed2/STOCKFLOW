/* ==============================================
   GESTION RESPONSIVE - STOCKFLOW
   ============================================== */

document.addEventListener('DOMContentLoaded', function() {
    // ==========================================
    // 1. GESTION DE LA SIDEBAR MOBILE
    // ==========================================
    
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const topBar = document.querySelector('.top-bar');
    
    function toggleSidebar() {
        if (!sidebar) return;
        const isActive = sidebar.classList.contains('active');
        
        if (isActive) {
            sidebar.classList.remove('active');
        } else {
            sidebar.classList.add('active');
        }
    }
    
    // Créer et ajouter le bouton hamburger
    if (sidebar && window.innerWidth <= 768) {
        if (!document.querySelector('.sidebar-toggle')) {
            const hamburger = document.createElement('button');
            hamburger.className = 'sidebar-toggle';
            hamburger.innerHTML = '<i class="fas fa-bars"></i>';
            hamburger.addEventListener('click', toggleSidebar);
            
            if (topBar) {
                topBar.insertBefore(hamburger, topBar.firstChild);
            }
        }
    }
    
    // Fermer la sidebar au clic sur un lien
    if (sidebar) {
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768 && sidebar.classList.contains('active')) {
                    sidebar.classList.remove('active');
                }
            });
        });
    }
    
    // Fermer la sidebar au clic extérieur
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('active')) {
            const isClickOnSidebar = sidebar.contains(e.target);
            const isClickOnHamburger = e.target.closest('.sidebar-toggle');
            
            if (!isClickOnSidebar && !isClickOnHamburger) {
                sidebar.classList.remove('active');
            }
        }
    });
    
    // Gérer le redimensionnement
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar) {
            sidebar.classList.remove('active');
        }
    });
    
    // ==========================================
    // 2. GESTION DU THÈME
    // ==========================================
    
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
    
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        localStorage.setItem('stockflow_theme', newTheme);
        
        updateThemeIcon(newTheme);
    }
    
    function updateThemeIcon(theme) {
        const icon = document.querySelector('#theme-toggle i');
        if (icon) {
            icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
        }
    }
    
    // Initialiser l'icône du thème
    const savedTheme = document.documentElement.getAttribute('data-theme') || 'light';
    updateThemeIcon(savedTheme);
    
    // ==========================================
    // 3. TABLES RESPONSIVES
    // ==========================================
    
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        if (!table.closest('.table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
    
    // ==========================================
    // 4. IMAGES RESPONSIVES
    // ==========================================
    
    const images = document.querySelectorAll('img:not(.img-responsive):not(.img-fluid)');
    images.forEach(img => {
        if (!img.classList.contains('img-fluid')) {
            img.classList.add('img-fluid');
        }
        if (!img.hasAttribute('loading')) {
            img.setAttribute('loading', 'lazy');
        }
    });
});

// ==========================================
// FONCTIONS UTILITAIRES
// ==========================================

function isMobile() {
    return window.innerWidth <= 768;
}

function isTablet() {
    return window.innerWidth > 768 && window.innerWidth <= 1024;
}

function isDesktop() {
    return window.innerWidth > 1024;
}

function getScreenSize() {
    const width = window.innerWidth;
    if (width < 480) return 'xs';
    if (width < 768) return 'sm';
    if (width < 1024) return 'md';
    if (width < 1440) return 'lg';
    return 'xl';
}
