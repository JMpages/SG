<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pagina_actual = basename($_SERVER['PHP_SELF']);

// Detectar si estamos en la raíz (index.php) o en una subcarpeta (view/)
$es_root = file_exists('view/login.php'); // Truco: si existe esta ruta relativa, estamos en root
$ruta_base = $es_root ? '' : '../';
$ruta_vistas = $es_root ? 'view/' : '';
$ruta_backend = $es_root ? 'backend/' : '../backend/';
?>

<link rel="stylesheet" href="<?php echo $ruta_base; ?>assets/css/navbar.css">

<style>
/* Mejoras para móvil inyectadas */
@media (max-width: 768px) {
    .navbar-container {
        flex-wrap: wrap;
        padding: 1rem;
        gap: 0.5rem;
    }

    .navbar-logo {
        flex-grow: 1;
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--primary-color);
    }

    .hamburger {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        width: 30px;
        height: 21px;
        cursor: pointer;
        z-index: 100;
    }
    
    .hamburger span {
        display: block;
        height: 3px;
        width: 100%;
        background-color: var(--text-primary);
        border-radius: 3px;
        transition: all 0.3s ease;
    }

    .hamburger.active span:nth-child(1) {
        transform: translateY(9px) rotate(45deg);
    }
    .hamburger.active span:nth-child(2) {
        opacity: 0;
    }
    .hamburger.active span:nth-child(3) {
        transform: translateY(-9px) rotate(-45deg);
    }

    .navbar-menu {
        display: none;
        width: 100%;
        flex-direction: column;
        background-color: var(--secondary-color);
        background-color: color-mix(in srgb, var(--secondary-color) 90%, transparent);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        margin-top: 1rem;
        border-radius: 1rem;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-color);
        overflow: hidden;
        order: 3;
        animation: slideDown 0.3s ease forwards;
    }

    .navbar-menu.active {
        display: flex;
    }

    .navbar-menu li {
        width: 100%;
        text-align: center;
    }

    .navbar-menu .nav-link {
        display: block;
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
    }

    .navbar-menu .nav-link.active {
        background-color: color-mix(in srgb, var(--primary-color) 10%, transparent);
        color: var(--primary-color);
        font-weight: 600;
        border-left: 4px solid var(--primary-color);
        padding-left: calc(1rem - 4px);
    }

    .navbar-right {
        display: none;
        width: 100%;
        justify-content: center;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background-color: var(--secondary-color);
        background-color: color-mix(in srgb, var(--secondary-color) 90%, transparent);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 1rem;
        margin-top: 0.5rem;
        box-shadow: var(--shadow-lg);
        border: 1px solid var(--border-color);
        order: 4;
        animation: slideDown 0.3s ease forwards;
    }

    .navbar-right.mobile-show {
        display: flex;
    }
    
    .user-menu {
        width: 100%;
    }
    
    .user-btn {
        width: 100%;
        justify-content: center;
        padding: 0.75rem;
        background: var(--bg-light);
        border-radius: 0.5rem;
    }
    
    .dropdown-menu {
        position: static;
        width: 100%;
        box-shadow: none;
        border: none;
        background: transparent;
        text-align: center;
        padding-top: 0.5rem;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
}
</style>

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
            <li><a href="<?php echo $ruta_base; ?>index.php" class="nav-link <?php echo $pagina_actual == 'index.php' ? 'active' : ''; ?>">Inicio</a></li>
            
            <?php if(isset($_SESSION['usuario'])): ?>
                <li><a href="<?php echo $ruta_vistas; ?>dashboard.php" class="nav-link <?php echo $pagina_actual == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="<?php echo $ruta_vistas; ?>materias.php" class="nav-link <?php echo $pagina_actual == 'materias.php' ? 'active' : ''; ?>">Materias</a></li>
                <li><a href="<?php echo $ruta_vistas; ?>tareas.php" class="nav-link <?php echo $pagina_actual == 'tareas.php' ? 'active' : ''; ?>">Tareas</a></li>
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
                        <a href="<?php echo $ruta_backend; ?>logout.php" class="dropdown-item">
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
                <a href="<?php echo $ruta_vistas; ?>login.php" class="btn-secondary">Iniciar Sesión</a>
                <a href="<?php echo $ruta_vistas; ?>registro.php" class="btn-primary">Registrarse</a>
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
