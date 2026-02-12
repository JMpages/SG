document.addEventListener('DOMContentLoaded', function() {
    // Elementos UI
    const btnAddNote = document.getElementById('btnAddNote');
    const notesGrid = document.getElementById('notesGrid');
    const colorOptions = document.querySelectorAll('.color-option');
    const selectMateria = document.getElementById('noteMateria');
    const modalNota = document.getElementById('modalNota');
    const filterMateria = document.getElementById('filterMateria');
    const filterDate = document.getElementById('filterDate');
    const btnClearFilters = document.getElementById('btnClearFilters');
    const modalInstance = new bootstrap.Modal(modalNota);

    let selectedColor = 'white';
    let materiasCache = [];
    let notasCache = []; // Caché local de notas del backend
    let quill;

    // 1. Inicialización
    initQuill();
    cargarMaterias();
    setupSearchAndFilter();
    // Cargar notas desde el backend
    cargarNotasBackend();
    setupMobileToolbar(); // Inicializar lógica móvil

    function initQuill() {
        // 1. Configurar Tamaños de Fuente Numéricos
        var Size = Quill.import('attributors/style/size');
        Size.whitelist = ['10px', '12px', '14px', '16px', '18px', '20px', '24px', '32px'];
        Quill.register(Size, true);

        quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: '',
            modules: {
                toolbar: [
                    // FILA 1: Esenciales (Visibles siempre en móvil)
                    [{ 'size': Size.whitelist }, 'bold', 'italic', { 'color': [] }, { 'list': 'bullet' }],
                    
                    // FILAS SIGUIENTES (Visibles al desplegar)
                    ['underline', { 'list': 'ordered' }, { 'align': [] }], // Alineación y otros
                    [{ 'header': [1, 2, 3, false] }, { 'font': [] }], // Encabezado y Fuente
                    ['strike', { 'background': [] }, { 'indent': '-1'}, { 'indent': '+1' }], // Extras formato
                    ['link', 'image', 'blockquote', 'code-block', 'clean'] // Insertar y limpiar
                ]
            }
        });

        // Mover la barra de herramientas generada por Quill al contenedor superior
        const toolbar = document.querySelector('.ql-toolbar');
        const stickyContainer = document.getElementById('toolbar-sticky-container');
        if (toolbar && stickyContainer) {
            stickyContainer.appendChild(toolbar);
        }
    }

    // 1.2 Configuración Toolbar Móvil
    function setupMobileToolbar() {
        const toggleBtn = document.getElementById('mobile-toolbar-toggle');
        const sidebar = document.getElementById('tools-sidebar');
        
        if(toggleBtn && sidebar) {
            toggleBtn.addEventListener('click', function() {
                sidebar.classList.toggle('expanded');
                const icon = this.querySelector('i');
                if (sidebar.classList.contains('expanded')) {
                    icon.className = 'fas fa-chevron-down';
                } else {
                    icon.className = 'fas fa-chevron-up';
                }
            });
        }
    }

    // 1.5 Configuración de Búsqueda y Filtros
    function setupSearchAndFilter() {
        // Eventos de cambio en filtros
        filterMateria.addEventListener('change', filtrarNotas);
        filterDate.addEventListener('change', filtrarNotas);

        // Botón limpiar
        btnClearFilters.addEventListener('click', () => {
            filterMateria.value = 'all';
            filterDate.value = '';
            filtrarNotas();
        });
    }

    function filtrarNotas() {
        const materiaId = filterMateria.value;
        const fechaSeleccionada = filterDate.value; // YYYY-MM-DD
        
        let notas = [...notasCache]; // Usar caché del backend

        // 1. Filtro por Materia
        if (materiaId !== 'all') {
            notas = notas.filter(n => n.materia_id == materiaId);
        }

        // 2. Filtro por Fecha
        if (fechaSeleccionada) {
            notas = notas.filter(n => {
                // La fecha viene del backend como "YYYY-MM-DD HH:MM:SS"
                // Tomamos solo la parte de la fecha "YYYY-MM-DD"
                const fechaNota = n.fecha_actualizacion 
                    ? n.fecha_actualizacion.split(' ')[0] 
                    : (n.fecha_creacion ? n.fecha_creacion.split(' ')[0] : '');
                
                return fechaNota === fechaSeleccionada;
            });
        }

        renderNotes(notas);
    }

    // 2. Eventos del Modal
    modalNota.addEventListener('hidden.bs.modal', function () {
        resetForm();
    });

    modalNota.addEventListener('shown.bs.modal', function () {
        document.getElementById('noteTitle').focus();
    });

    function resetForm() {
        document.getElementById('noteId').value = ''; // Limpiar ID oculto
        document.getElementById('noteTitle').value = '';
        quill.setContents([]);
        selectMateria.value = '';
        selectedColor = 'white';
        updateColorSelection();
        document.getElementById('btnAddNote').innerHTML = '<i class="fas fa-save me-2"></i> Guardar Nota';
    }

    // 3. Selector de Color
    colorOptions.forEach(opt => {
        opt.addEventListener('click', function() {
            selectedColor = this.dataset.color;
            updateColorSelection();
        });
    });

    function updateColorSelection() {
        colorOptions.forEach(opt => {
            if (opt.dataset.color === selectedColor) opt.classList.add('selected');
            else opt.classList.remove('selected');
        });
        
        // Cambiar color del contenido del modal para retroalimentación visual
        // En este nuevo diseño, cambiamos el color de la "Hoja" (#editor-page)
        const editorPage = document.getElementById('editor-page');
        
        // Limpiar clases anteriores de color
        editorPage.style.backgroundColor = '';
        
        if (selectedColor !== 'white') {
            // Obtener el color computado de la clase CSS
            const tempDiv = document.createElement('div');
            tempDiv.className = `note-bg-${selectedColor}`;
            document.body.appendChild(tempDiv);
            const color = getComputedStyle(tempDiv).backgroundColor;
            document.body.removeChild(tempDiv);
            
            editorPage.style.backgroundColor = color;
        } else {
            // Resetear al blanco (o color del tema oscuro manejado por CSS)
            editorPage.style.backgroundColor = ''; 
        }
    }

    // 4. Guardar Nota
    btnAddNote.addEventListener('click', () => {
        const id = document.getElementById('noteId').value;
        const title = document.getElementById('noteTitle').value.trim();
        const contentHtml = quill.root.innerHTML;
        const contentText = quill.getText(); // Texto plano para búsquedas
        const materiaId = selectMateria.value;

        if (!contentText.trim() && !title) return;

        const nuevaNota = {
            id: id ? id : null, // Si hay ID es edición
            titulo: title,
            contenido: contentHtml, // Guardamos HTML
            texto: contentText, // Guardamos texto plano
            materia_id: materiaId,
            color: selectedColor
        };

        // Guardar en Backend
        guardarNotaBackend(nuevaNota);
    });

    // 5. Cargar Materias (Backend Integration)
    async function cargarMaterias() {
        try {
            const response = await fetch('../backend/obtener_materias.php');
            const result = await response.json();
            if (result.status === 'success') {
                materiasCache = result.data;
                
                // Llenar select del formulario
                let options = '<option value="">Sin materia (General)</option>';
                let filterOptions = '<option value="all">Todas</option>';
                
                materiasCache.forEach(m => {
                    if(m.activa == 1) {
                        options += `<option value="${m.id}">${m.nombre}</option>`;
                        filterOptions += `<option value="${m.id}">${m.nombre}</option>`;
                    }
                });
                
                selectMateria.innerHTML = options;
                filterMateria.innerHTML = filterOptions;
            }
        } catch (e) {
            console.error("Error cargando materias", e);
        }
    }

    // 6. Renderizado
    function renderNotes(notas) {
        if (notas.length === 0) {
            const totalNotas = notasCache.length;
            const isFiltered = totalNotas > 0;
            
            const msg = isFiltered 
                ? 'No se encontraron notas con estos filtros.' 
                : 'No tienes notas aún. ¡Crea la primera con el botón +!';
            
            const icon = isFiltered ? 'fa-search' : 'fa-sticky-note';

            notesGrid.innerHTML = `
                <div class="col-12 text-center py-5 text-muted w-100" style="grid-column: 1 / -1;">
                    <i class="fas ${icon} fa-3x mb-3 opacity-50"></i>
                    <p>${msg}</p>
                </div>`;
            return;
        }

        notesGrid.innerHTML = notas.map(nota => {
            const materia = materiasCache.find(m => m.id == nota.materia_id);
            const materiaBadge = materia 
                ? `<div class="note-badge"><i class="fas fa-book me-1"></i>${materia.nombre}</div>` 
                : '';
            
            const bgClass = nota.color !== 'white' ? `note-bg-${nota.color}` : '';
            
            // Corrección de fecha: Usar fecha_actualizacion o fecha_creacion del backend
            let fechaDisplay = 'Sin fecha';
            if (nota.fecha_actualizacion) {
                fechaDisplay = new Date(nota.fecha_actualizacion).toLocaleDateString();
            } else if (nota.fecha_creacion) {
                fechaDisplay = new Date(nota.fecha_creacion).toLocaleDateString();
            }

            return `
                <div class="note-card ${bgClass}" onclick="abrirNotaParaEditar(${nota.id})">
                    ${materiaBadge}
                    ${nota.titulo ? `<h3 class="note-title">${escapeHtml(nota.titulo)}</h3>` : ''}
                    <div class="note-content">${nota.contenido}</div> <!-- Render HTML directly (Sanitize in prod) -->
                    <div class="note-footer">
                        <span class="note-date">${fechaDisplay}</span>
                        <div class="note-actions">
                            <button class="btn btn-sm btn-light rounded-circle text-danger" onclick="event.stopPropagation(); eliminarNota(${nota.id})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // --- FUNCIONES BACKEND ---

    async function cargarNotasBackend() {
        try {
            const response = await fetch('../backend/anotaciones_proceso.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({accion: 'obtener'})
            });
            const result = await response.json();
            if (result.status === 'success') {
                notasCache = result.data;
                filtrarNotas();
            }
        } catch (e) {
            console.error("Error cargando notas", e);
        }
    }

    async function guardarNotaBackend(nota) {
        nota.accion = 'guardar'; // Definir acción
        try {
            const response = await fetch('../backend/anotaciones_proceso.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(nota)
            });
            const result = await response.json();
            if (result.status === 'success') {
                await cargarNotasBackend(); // Recargar lista
                modalInstance.hide();
            } else {
                alert('Error al guardar: ' + result.message);
            }
        } catch (e) {
            console.error(e);
            alert('Error de conexión');
        }
    }

    window.eliminarNota = function(id) {
        if(confirm('¿Eliminar nota?')) {
            fetch('../backend/anotaciones_proceso.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({accion: 'eliminar', id: id})
            }).then(res => res.json()).then(result => {
                if(result.status === 'success') {
                    cargarNotasBackend();
                } else {
                    alert('Error: ' + result.message);
                }
            });
        }
    };

    // Función para abrir nota existente en el modal
    window.abrirNotaParaEditar = function(id) {
        const nota = notasCache.find(n => n.id == id);
        if (!nota) return;

        document.getElementById('noteId').value = nota.id;
        document.getElementById('noteTitle').value = nota.titulo;
        quill.root.innerHTML = nota.contenido; // Cargar HTML en Quill
        selectMateria.value = nota.materia_id || '';
        selectedColor = nota.color || 'white';
        
        updateColorSelection();
        document.getElementById('btnAddNote').innerHTML = '<i class="fas fa-save me-2"></i> Actualizar';
        
        modalInstance.show();
    };

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }
});