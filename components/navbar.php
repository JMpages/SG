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
/* Estilos base redefinidos para mejor distribución */
.navbar-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0.75rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 5rem;
}

.navbar-top-bar {
    display: flex;
    align-items: center;
    gap: 1rem;
}

/* Botones de autenticación personalizados */
.btn-auth {
    padding: 0.5rem 1.25rem;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.9rem;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-login {
    background: transparent;
    border: 1px solid var(--primary-color);
    color: var(--primary-color);
}

.btn-login:hover {
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
}

.btn-register {
    background: var(--primary-gradient);
    border: none;
    color: white;
}

.btn-register:hover {
    opacity: 0.9;
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(102, 126, 234, 0.3);
    color: white;
}

@media (max-width: 768px) {
    .navbar-container {
        position: relative;
        padding: 0.75rem 1.5rem;
    }

    .navbar-top-bar {
        justify-content: space-between;
        width: 100%;
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

    .navbar-content {
        display: none; /* Oculto por defecto en móvil */
        position: absolute;
        top: 100%;
        left: 0;
        width: 100%;
        background-color: var(--bg-body);
        background-color: color-mix(in srgb, var(--bg-body) 80%, transparent);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        z-index: 1050;
        border-top: 1px solid var(--border-light);
        
        flex-direction: column;
        gap: 1.5rem;
        padding: 1.5rem;
        animation: slideDown 0.3s ease forwards;
    }

    .navbar-content.active {
        display: flex;
    }

    .navbar-menu {
        /* Resetear estilos conflictivos de navbar.css que ocultan el menú */
        position: static;
        max-height: none;
        opacity: 1;
        visibility: visible;
        transform: none;
        
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
        width: 100%;
        background: var(--secondary-color);
        background: color-mix(in srgb, var(--secondary-color) 60%, transparent);
        border-radius: 0.75rem;
        overflow: hidden;
        border: 1px solid var(--border-color);
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .navbar-menu li {
        width: 100%;
        text-align: center;
        display: block;
    }

    .navbar-menu .nav-link {
        display: block;
        padding: 1rem;
        border-bottom: 1px solid var(--border-light);
        color: var(--text-primary);
        text-decoration: none;
    }

    .navbar-menu li:last-child .nav-link {
        border-bottom: none;
    }

    .navbar-menu .nav-link.active {
        background: rgba(102, 126, 234, 0.1);
        border-left: 4px solid var(--primary-color);
    }

    .navbar-actions {
        display: flex;
        align-items: flex-start;
        flex-direction: row;
        width: 100%;
        gap: 0.75rem;
        order: -1;
    }

    .theme-toggle {
        width: auto;
        flex: 0 0 auto;
        padding: 0.75rem;
        background: var(--secondary-color);
        background: color-mix(in srgb, var(--secondary-color) 60%, transparent);
        border: 1px solid var(--border-color);
        border-radius: 0.5rem;
        color: var(--text-primary);
        justify-content: center;
        display: flex;
        align-items: center;
    }
    
    .theme-toggle span {
        display: none;
    }

    .user-menu {
        flex: 1;
        width: auto;
        display: flex;
        flex-direction: column;
        position: relative;
    }
    
    .user-btn {
        width: 100%;
        justify-content: space-between;
        padding: 0.75rem 1rem;
        display: flex;
        align-items: center;
        background: var(--secondary-color);
        background: color-mix(in srgb, var(--secondary-color) 60%, transparent);
        border: 1px solid var(--border-color);
    }
    
    .dropdown-menu {
        position: static;
        width: 100%;
        box-shadow: none;
        border: none;
        background: transparent;
        text-align: center;
        padding: 0;
        margin-top: 0.5rem;
        display: none;
    }
    
    .dropdown-menu.active {
        display: block;
        animation: fadeIn 0.3s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
}

/* Estilos Desktop para la nueva estructura */
@media (min-width: 769px) {
    .navbar-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-grow: 1;
        width: 100%;
    }

    .navbar-menu {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 1.5rem;
    }

    .navbar-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .hamburger {
        display: none;
    }
}
</style>

<nav class="navbar-modern">
    <div class="navbar-container">
        <!-- Header: Logo y Hamburger (Visible siempre el logo, hamburger solo móvil) -->
        <div class="navbar-top-bar">
            <div class="navbar-logo">Notas</div>
            <div class="hamburger" id="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <!-- Contenido Colapsable (Menú + Acciones Derecha) -->
        <div class="navbar-content" id="navbarContent">
            <!-- Menu Principal -->
            <ul class="navbar-menu" id="navbarMenu">
                <li><a href="<?php echo $ruta_base; ?>index.php" class="nav-link <?php echo $pagina_actual == 'index.php' ? 'active' : ''; ?>">Inicio</a></li>
                
                <?php if(isset($_SESSION['usuario'])): ?>
                    <li><a href="<?php echo $ruta_vistas; ?>dashboard.php" class="nav-link <?php echo $pagina_actual == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="<?php echo $ruta_vistas; ?>materias.php" class="nav-link <?php echo ($pagina_actual == 'materias.php' || $pagina_actual == 'materia_detalle.php') ? 'active' : ''; ?>">Materias</a></li>
                    <li><a href="<?php echo $ruta_vistas; ?>tareas.php" class="nav-link <?php echo $pagina_actual == 'tareas.php' ? 'active' : ''; ?>">Tareas</a></li>
                    <li><a href="<?php echo $ruta_vistas; ?>anotaciones.php" class="nav-link <?php echo $pagina_actual == 'anotaciones.php' ? 'active' : ''; ?>">Anotaciones</a></li>
                <?php endif; ?>
            </ul>

            <!-- Acciones Derecha (Tema + Usuario) -->
            <div class="navbar-actions" id="navbarActions">
                <!-- Toggle Tema -->
                <button class="theme-toggle" id="themeToggle" title="Cambiar tema">
                    <svg id="themeIcon" class="icon-svg" viewBox="0 0 24 24">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                    <span class="d-md-none">Cambiar Tema</span>
                </button>

                <?php if(isset($_SESSION['usuario'])): ?>
                    <div class="user-menu">
                        <button class="user-btn" id="userBtn">
                            <svg class="icon-svg" viewBox="0 0 24 24">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            <span><?php echo htmlspecialchars($_SESSION['usuario']); ?></span>
                            <svg class="icon-svg ms-1" style="width: 16px; height: 16px;" viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        <div class="dropdown-menu" id="dropdown">
                            <a href="<?php echo $ruta_backend; ?>logout.php" class="dropdown-item justify-content-center justify-content-md-start">
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
                    <div class="d-flex gap-2 w-100 justify-content-center">
                        <a href="<?php echo $ruta_vistas; ?>login.php" class="btn-auth btn-login">Iniciar Sesión</a>
                        <a href="<?php echo $ruta_vistas; ?>registro.php" class="btn-auth btn-register">Registrarse</a>
                    </div>
                <?php endif; ?>
            </div>
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

themeToggle.addEventListener('click', function(e) {
    e.stopPropagation();
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
const navbarContent = document.getElementById('navbarContent');

if (hamburger) {
    hamburger.addEventListener('click', function(e) {
        e.stopPropagation();
        hamburger.classList.toggle('active');
        navbarContent.classList.toggle('active');
        if (dropdown) dropdown.classList.remove('active');
    });
}

// Cerrar menú al clickear en links
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function() {
        if (hamburger) hamburger.classList.remove('active');
        if (navbarContent) navbarContent.classList.remove('active');
        if (dropdown) dropdown.classList.remove('active');
    });
});

// Cerrar menú al clickear fuera
document.addEventListener('click', function(e) {
    const isClickInsideContent = navbarContent?.contains(e.target);
    const isClickInsideHamburger = hamburger?.contains(e.target);
    
    if (!isClickInsideContent && !isClickInsideHamburger) {
        if (hamburger) hamburger.classList.remove('active');
        if (navbarContent) navbarContent.classList.remove('active');
    }
});
</script>