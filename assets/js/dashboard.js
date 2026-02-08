// Actualizar la fecha actual
function actualizarFecha() {
    const opciones = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const hoy = new Date();
    const fechaFormato = hoy.toLocaleDateString('es-ES', opciones);
    
    // Capitalizar primera letra
    const fechaCapitalizada = fechaFormato.charAt(0).toUpperCase() + fechaFormato.slice(1);
    
    const elementoFecha = document.getElementById('current-date');
    if(elementoFecha) {
        elementoFecha.textContent = fechaCapitalizada;
    }
}

// Ejecutar al cargar
document.addEventListener('DOMContentLoaded', function() {
    actualizarFecha();
});
