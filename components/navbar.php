<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pagina_actual = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="../assets/css/navbar.css">

<nav class="navbar-modern">
    <div class="navbar-container">
        <!-- Logo -->
        <div class="navbar-logo">Notas</div>

        <!-- Hamburger Menu -->
        <div class="hamburger" id="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <!-- Menu Principal -->
        <ul class="navbar-menu" id="navbarMenu">
            <li><a href="index.php" class="nav-link <?php echo $pagina_actual == 'index.php' ? 'active' : ''; ?>">Inicio</a></li>
            
            <?php if(isset($_SESSION['usuario'])): ?>
                <li><a href="dashboard.php" class="nav-link <?php echo $pagina_actual == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="materias.php" class="nav-link <?php echo $pagina_actual == 'materias.php' ? 'active' : ''; ?>">Materias</a></li>
                <li><a href="tareas.php" class="nav-link <?php echo $pagina_actual == 'tareas.php' ? 'active' : ''; ?>">Tareas</a></li>
            <?php endif; ?>
        </ul>

        <!-- Menu Derecho -->
        <div class="navbar-right" id="navbarRight">
            <!-- Toggle Tema -->
            <button class="theme-toggle" id="themeToggle" title="Cambiar tema">
                <svg id="themeIcon" class="icon-svg" viewBox="0 0 24 24">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </button>

            <?php if(isset($_SESSION['usuario'])): ?>
                <div class="user-menu">
                    <button class="user-btn" id="userBtn">
                        <svg class="icon-svg" viewBox="0 0 24 24">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
                    </button>
                    <div class="dropdown-menu" id="dropdown">
                        <a href="../backend/logout.php" class="dropdown-item">
                            <svg class="icon-svg" viewBox="0 0 24 24">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Cerrar Sesión
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <a href="login.php" class="btn-secondary">Iniciar Sesión</a>
                <a href="registro.php" class="btn-primary">Registrarse</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<script>
// Sistema de Tema
const html = document.documentElement;
const themeToggle = document.getElementById('themeToggle');
const themeIcon = document.getElementById('themeIcon');

const moonIcon = '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>';
const sunIcon = '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>';

const savedTheme = localStorage.getItem('theme') || 'light';
html.setAttribute('data-theme', savedTheme);
updateThemeIcon(savedTheme);

function updateThemeIcon(theme) {
    themeIcon.innerHTML = theme === 'light' ? moonIcon : sunIcon;
}

themeToggle.addEventListener('click', function() {
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    updateThemeIcon(newTheme);
});

// Sistema de Dropdown - Versión simplificada
const userBtn = document.getElementById('userBtn');
const dropdown = document.getElementById('dropdown');

if (userBtn && dropdown) {
    // Click en el botón
    userBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const isActive = dropdown.classList.contains('active');
        dropdown.classList.toggle('active', !isActive);
    });

    // Click en cualquier parte del documento
    document.addEventListener('click', function(e) {
        const userMenu = userBtn.closest('.user-menu');
        if (userMenu && !userMenu.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });

    // Evitar cerrar al clickear dentro del dropdown
    dropdown.addEventListener('click', function(e) {
        if (e.target.closest('.dropdown-item')) {
            e.stopPropagation();
        }
    });
}

// Hamburger Menu
const hamburger = document.getElementById('hamburger');
const navbarMenu = document.getElementById('navbarMenu');
const navbarRight = document.getElementById('navbarRight');

if (hamburger) {
    hamburger.addEventListener('click', function(e) {
        e.stopPropagation();
        hamburger.classList.toggle('active');
        navbarMenu.classList.toggle('active');
        if (navbarRight) navbarRight.classList.toggle('mobile-show');
        if (dropdown) dropdown.classList.remove('active');
    });
}

// Cerrar menú al clickear en links
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function() {
        if (hamburger) hamburger.classList.remove('active');
        if (navbarMenu) navbarMenu.classList.remove('active');
        if (navbarRight) navbarRight.classList.remove('mobile-show');
        if (dropdown) dropdown.classList.remove('active');
    });
});

// Cerrar menú al clickear fuera
document.addEventListener('click', function(e) {
    const isClickInsideMenu = navbarMenu?.contains(e.target);
    const isClickInsideHamburger = hamburger?.contains(e.target);
    const isClickInsideRight = navbarRight?.contains(e.target);
    
    if (!isClickInsideMenu && !isClickInsideHamburger && !isClickInsideRight) {
        if (hamburger) hamburger.classList.remove('active');
        if (navbarMenu) navbarMenu.classList.remove('active');
        if (navbarRight) navbarRight.classList.remove('mobile-show');
    }
});
</script>
