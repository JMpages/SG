// Validaciones de formulario de registro
document.addEventListener('DOMContentLoaded', function() {
    const formularioRegistro = document.querySelector('form[action*="registro_proceso"]');
    
    if(formularioRegistro) {
        formularioRegistro.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Obtener valores
            const nombre = document.getElementById('nombre').value.trim();
            const email = document.getElementById('email') ? document.getElementById('email').value.trim() : '';
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirm').value;
            
            // Limpiar mensajes previos
            limpiarMensajes();
            
            // Validaciones
            let errores = [];
            
            // Validar nombre de usuario
            if(nombre === '') {
                errores.push('El nombre de usuario es requerido');
            } else if(nombre.length < 3) {
                errores.push('El nombre de usuario debe tener al menos 3 caracteres');
            } else if(nombre.length > 50) {
                errores.push('El nombre de usuario no puede exceder 50 caracteres');
            } else if(!validarFormatoNombre(nombre)) {
                errores.push('El nombre de usuario solo puede contener letras, números, guiones y guiones bajos');
            }
            
            // Validar email
            if(document.getElementById('email')) {
                if(email !== '' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    errores.push('El formato del correo electrónico no es válido');
                }
            }

            // Validar contraseña
            if(password === '') {
                errores.push('La contraseña es requerida');
            } else if(password.length < 8) {
                errores.push('La contraseña debe tener al menos 8 caracteres');
            }
            
            // Validar confirmación de contraseña
            if(passwordConfirm === '') {
                errores.push('La confirmación de contraseña es requerida');
            }
            
            // Validar que las contraseñas coincidan
            if(password !== passwordConfirm && password !== '' && passwordConfirm !== '') {
                errores.push('Las contraseñas no coinciden');
            }
            
            // Si hay errores, mostrarlos
            if(errores.length > 0) {
                mostrarErrores(errores);
                return false;
            }
            
            // Si no hay errores, enviar formulario
            formularioRegistro.submit();
        });
    }
});

// Función para validar el formato del nombre de usuario
function validarFormatoNombre(nombre) {
    const regex = /^[a-zA-Z0-9_-]+$/;
    return regex.test(nombre);
}

// Función para mostrar errores
function mostrarErrores(errores) {
    // Crear contenedor para los errores si no existe
    let contenedorErrores = document.getElementById('contenedor-errores');
    
    if(!contenedorErrores) {
        contenedorErrores = document.createElement('div');
        contenedorErrores.id = 'contenedor-errores';
        const formulario = document.querySelector('form[action*="registro_proceso"]');
        formulario.parentElement.insertBefore(contenedorErrores, formulario);
    }
    
    // Crear HTML para los errores
    let htmlErrores = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    htmlErrores += '<strong>¡Error!</strong><br>';
    
    errores.forEach(function(error) {
        htmlErrores += '• ' + error + '<br>';
    });
    
    htmlErrores += '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    htmlErrores += '</div>';
    
    contenedorErrores.innerHTML = htmlErrores;
    
    // Scroll hacia el error
    contenedorErrores.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Función para limpiar mensajes de error
function limpiarMensajes() {
    const contenedorErrores = document.getElementById('contenedor-errores');
    if(contenedorErrores) {
        contenedorErrores.innerHTML = '';
    }
}

// Validación en tiempo real del campo de nombre
document.addEventListener('DOMContentLoaded', function() {
    const campoNombre = document.getElementById('nombre');
    
    if(campoNombre) {
        campoNombre.addEventListener('input', function() {
            let valor = this.value;
            
            // Permitir solo alfanuméricos, guiones y guiones bajos
            let valorLimpio = valor.replace(/[^a-zA-Z0-9_-]/g, '');
            
            if(valor !== valorLimpio) {
                this.value = valorLimpio;
            }
        });
    }
});

// =====================================================
// VALIDACIONES PARA EL FORMULARIO DE LOGIN
// =====================================================
document.addEventListener('DOMContentLoaded', function() {
    const formularioLogin = document.querySelector('form[action*="login_proceso"]');
    
    if(formularioLogin) {
        formularioLogin.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Obtener valores
            const usuario = document.getElementById('usuario').value.trim();
            const password = document.getElementById('password').value;
            
            // Limpiar mensajes previos
            limpiarMensajesLogin();
            
            // Validaciones
            let errores = [];
            
            // Validar nombre de usuario
            if(usuario === '') {
                errores.push('El nombre de usuario es requerido');
            } else if(usuario.length < 3) {
                errores.push('El nombre de usuario debe tener al menos 3 caracteres');
            }
            
            // Validar contraseña
            if(password === '') {
                errores.push('La contraseña es requerida');
            } else if(password.length < 6) {
                errores.push('La contraseña debe tener al menos 6 caracteres');
            }
            
            // Si hay errores, mostrarlos
            if(errores.length > 0) {
                mostrarErroresLogin(errores);
                return false;
            }
            
            // Si no hay errores, enviar formulario
            formularioLogin.submit();
        });
    }
});

// Función para mostrar errores en login
function mostrarErroresLogin(errores) {
    // Crear contenedor para los errores si no existe
    let contenedorErrores = document.getElementById('contenedor-errores-login');
    
    if(!contenedorErrores) {
        contenedorErrores = document.createElement('div');
        contenedorErrores.id = 'contenedor-errores-login';
        const formulario = document.querySelector('form[action*="login_proceso"]');
        formulario.parentElement.insertBefore(contenedorErrores, formulario);
    }
    
    // Crear HTML para los errores
    let htmlErrores = '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    htmlErrores += '<strong>¡Error!</strong><br>';
    
    errores.forEach(function(error) {
        htmlErrores += '• ' + error + '<br>';
    });
    
    htmlErrores += '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    htmlErrores += '</div>';
    
    contenedorErrores.innerHTML = htmlErrores;
    
    // Scroll hacia el error
    contenedorErrores.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Función para limpiar mensajes de error en login
function limpiarMensajesLogin() {
    const contenedorErrores = document.getElementById('contenedor-errores-login');
    if(contenedorErrores) {
        contenedorErrores.innerHTML = '';
    }
}
