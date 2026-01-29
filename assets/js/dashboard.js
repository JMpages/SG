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

// Agregar animación de entrada a los stat cards
document.addEventListener('DOMContentLoaded', function() {
    const statCards = document.querySelectorAll('.stat-card');
    
    statCards.forEach((card, index) => {
        card.style.animation = `slideUp 0.5s ease-out ${index * 0.1}s forwards`;
        card.style.opacity = '0';
    });
});

// Animación de slide up
const style = document.createElement('style');
style.textContent = `
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);
