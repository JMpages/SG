/**
 * Lógica para el manejo de perfil de usuario
 */

document.addEventListener('DOMContentLoaded', function() {
    // Si estamos en una vista que requiere cargar datos del perfil
    const perfilContainer = document.getElementById('perfil-container');
    if (perfilContainer) {
        cargarDatosPerfil();
    }

    // Formulario de actualización de datos básicos
    const formPerfil = document.getElementById('form-perfil-datos');
    if (formPerfil) {
        formPerfil.addEventListener('submit', function(e) {
            e.preventDefault();
            actualizarPerfil();
        });
    }

    // Formulario de cambio de contraseña
    const formPassword = document.getElementById('form-perfil-password');
    if (formPassword) {
        formPassword.addEventListener('submit', function(e) {
            e.preventDefault();
            cambiarPassword();
        });
    }

    // Botón eliminar cuenta
    const btnEliminar = document.getElementById('btn-eliminar-cuenta');
    if (btnEliminar) {
        btnEliminar.addEventListener('click', function() {
            if(confirm('¿Estás seguro de que deseas eliminar tu cuenta? Esta acción no se puede deshacer y perderás todos tus datos.')) {
                const password = prompt('Por favor, ingresa tu contraseña para confirmar:');
                if(password) {
                    eliminarCuenta(password);
                }
            }
        });
    }

    // Listener para cargar actividad solo cuando se abre la pestaña
    const tabActividad = document.getElementById('actividad-tab');
    if (tabActividad) {
        tabActividad.addEventListener('shown.bs.tab', cargarActividad);
    }
});

async function cargarDatosPerfil() {
    try {
        const response = await fetch('../backend/perfil_proceso.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'obtener_datos' })
        });
        const data = await response.json();

        if (data.status === 'success') {
            // Rellenar campos si existen
            const inputNombre = document.getElementById('perfil-nombre');
            if(inputNombre) inputNombre.value = data.data.username;
            
            // Actualizar elementos de texto si existen
            const textUsuario = document.getElementById('display-usuario');
            if(textUsuario) textUsuario.textContent = data.data.username;
        }
    } catch (error) {
        console.error('Error al cargar perfil:', error);
    }
}

async function actualizarPerfil() {
    const nombre = document.getElementById('perfil-nombre').value;
    
    try {
        const response = await fetch('../backend/perfil_proceso.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                accion: 'actualizar_perfil',
                nombre: nombre
            })
        });
        const data = await response.json();
        
        alert(data.message);
        if(data.status === 'success') {
            location.reload(); // Recargar para actualizar nombre en header/sesión
        }
    } catch (error) {
        alert('Error de conexión');
    }
}

async function cambiarPassword() {
    const actual = document.getElementById('pass-actual').value;
    const nueva = document.getElementById('pass-nueva').value;
    const confirm = document.getElementById('pass-confirm').value;

    try {
        const response = await fetch('../backend/perfil_proceso.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                accion: 'cambiar_password',
                password_actual: actual,
                password_nueva: nueva,
                password_confirm: confirm
            })
        });
        const data = await response.json();
        
        alert(data.message);
        if(data.status === 'success') {
            document.getElementById('form-perfil-password').reset();
        }
    } catch (error) {
        alert('Error de conexión');
    }
}

async function eliminarCuenta(password) {
    try {
        const response = await fetch('../backend/perfil_proceso.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                accion: 'eliminar_cuenta',
                password: password
            })
        });
        const data = await response.json();
        
        if(data.status === 'success') {
            alert(data.message);
            window.location.href = '../view/login.php';
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Error al intentar eliminar la cuenta');
    }
}

async function cargarActividad() {
    const container = document.getElementById('activity-container');
    
    try {
        const response = await fetch('../backend/perfil_proceso.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ accion: 'obtener_actividad' })
        });
        const result = await response.json();

        if (result.status === 'success' && result.data.length > 0) {
            let html = '<div class="activity-list">';
            
            result.data.forEach(log => {
                // Formatear fecha si existe, sino usar texto genérico
                const fecha = log.fecha ? new Date(log.fecha).toLocaleString() : 'Reciente';
                const icon = log.dispositivo.toLowerCase().includes('móvil') ? 'fa-mobile-alt' : 'fa-desktop';
                
                html += `
                    <div class="activity-item">
                        <div class="activity-icon"></div>
                        <div class="activity-content">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 fw-bold text-primary">${log.sistema_operativo} - ${log.navegador}</h6>
                                    <p class="mb-0 small text-muted"><i class="fas ${icon} me-1"></i> ${log.dispositivo} • IP: ${log.ip}</p>
                                </div>
                                <small class="text-muted" style="font-size: 0.75rem;">${fecha}</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<div class="text-center text-muted py-3"><i class="fas fa-history fa-2x mb-2 opacity-50"></i><p>No hay actividad registrada reciente.</p></div>';
        }
    } catch (error) {
        console.error(error);
        container.innerHTML = '<div class="text-center text-danger py-3">Error al cargar la actividad.</div>';
    }
}