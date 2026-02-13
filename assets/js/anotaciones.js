document.addEventListener('DOMContentLoaded', function() {
    // Elementos UI
    const notesGrid = document.getElementById('notesGrid');
    const saveStatus = document.getElementById('saveStatus');
    const colorOptions = document.querySelectorAll('.color-option');
    const selectMateria = document.getElementById('noteMateria');
    const modalNota = document.getElementById('modalNota');
    const filterMateria = document.getElementById('filterMateria');
    const filterDate = document.getElementById('filterDate');
    const btnClearFilters = document.getElementById('btnClearFilters');
    const modalInstance = new bootstrap.Modal(modalNota);
    const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminarNota'));
    const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminarNota');

    let selectedColor = 'white';
    let materiasCache = [];
    let notasCache = []; // Caché local de notas del backend
    let quill;
    let isSaving = false; // Flag para evitar guardados concurrentes
    let savePending = false; // Flag para encolar cambios pendientes

    // 1. Inicialización
    initQuill();
    cargarMaterias();
    setupSearchAndFilter();
    // Cargar notas desde el backend
    cargarNotasBackend();
    setupMobileToolbar(); // Inicializar lógica móvil
    setupExportButton(); // Configurar botón de exportación

    function initQuill() {
        // 1. Configurar Tamaños de Fuente Numéricos
        var Size = Quill.import('attributors/style/size');
        Size.whitelist = ['10px', '12px', '14px', '16px', '18px', '20px', '24px', '32px'];
        Quill.register(Size, true);

        quill = new Quill('#editor-container', {
            theme: 'snow',
            placeholder: '',
            modules: {
                history: {
                    delay: 2000,
                    maxStack: 500,
                    userOnly: true
                },
                toolbar: {
                    container: [
                        // FILA 1: Esenciales (Visibles siempre en móvil)
                        ['undo', 'redo'], // Deshacer y Rehacer (Prioridad alta)
                        [{ 'size': Size.whitelist }, 'bold', 'italic', { 'color': [] }, { 'list': 'bullet' }],
                        
                        // FILAS SIGUIENTES (Visibles al desplegar)
                        ['underline', { 'list': 'ordered' }, { 'align': [] }], // Alineación y otros
                        [{ 'header': [1, 2, 3, false] }, { 'font': [] }], // Encabezado y Fuente
                        ['strike', { 'background': [] }, { 'indent': '-1'}, { 'indent': '+1' }], // Extras formato
                        ['link', 'image', 'blockquote', 'code-block', 'clean'] // Insertar y limpiar
                    ],
                    handlers: {
                        'undo': function() { this.quill.history.undo(); },
                        'redo': function() { this.quill.history.redo(); }
                    }
                }
            }
        });

        // Inyectar iconos SVG para Deshacer/Rehacer (Quill no los incluye por defecto en toolbar custom)
        const undoBtn = document.querySelector('.ql-undo');
        const redoBtn = document.querySelector('.ql-redo');
        if (undoBtn) undoBtn.innerHTML = '<svg viewbox="0 0 18 18"><polygon class="ql-fill ql-stroke" points="6 10 4 12 2 10 6 10"></polygon><path class="ql-stroke" d="M8.09,13.91A4.6,4.6,0,0,0,9,14,5,5,0,1,0,4,9"></path></svg>';
        if (redoBtn) redoBtn.innerHTML = '<svg viewbox="0 0 18 18"><polygon class="ql-fill ql-stroke" points="12 10 14 12 16 10 12 10"></polygon><path class="ql-stroke" d="M9.91,13.91A4.6,4.6,0,0,1,9,14a5,5,0,1,1,5-5"></path></svg>';

        // Listener para cambios en el editor (Autoguardado)
        quill.on('text-change', function(delta, oldDelta, source) {
            if (source === 'user') {
                mostrarEstadoGuardando();
                debouncedSave();
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
    modalNota.addEventListener('hide.bs.modal', function() {
        // Solución al error "Blocked aria-hidden": Quitar el foco del botón cerrar antes de ocultar el modal
        if (document.activeElement && modalNota.contains(document.activeElement)) {
            document.activeElement.blur();
        }
    });

    modalNota.addEventListener('hidden.bs.modal', function () {
        resetForm();
        cargarNotasBackend(); // Recargar grid al cerrar para ver cambios reflejados
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
        if(saveStatus) saveStatus.innerHTML = '';
        if(saveStatus) saveStatus.className = 'text-muted small ms-auto me-3 d-none d-md-inline-flex align-items-center';
    }

    // 3. Selector de Color
    colorOptions.forEach(opt => {
        opt.addEventListener('click', function() {
            selectedColor = this.dataset.color;
            updateColorSelection();
            mostrarEstadoGuardando();
            debouncedSave();
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

    // 4. Lógica de Autoguardado
    
    // Función debounce para no guardar en cada tecla, sino esperar a que el usuario pare
    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            const context = this;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    const debouncedSave = debounce(() => {
        guardarNotaAuto();
    }, 1000); // Esperar 1 segundo de inactividad

    // Listeners para inputs
    document.getElementById('noteTitle').addEventListener('input', () => {
        mostrarEstadoGuardando();
        debouncedSave();
    });

    selectMateria.addEventListener('change', () => {
        mostrarEstadoGuardando();
        debouncedSave();
    });

    function mostrarEstadoGuardando() {
        // El guardado es automático, solo se notificarán errores.
    }

    async function guardarNotaAuto() {
        const id = document.getElementById('noteId').value;
        const title = document.getElementById('noteTitle').value.trim();
        const contentHtml = quill.root.innerHTML;
        const contentText = quill.getText(); // Texto plano para búsquedas
        const materiaId = selectMateria.value;

        // Validación: No guardar notas vacías nuevas
        if (!id && !contentText.trim() && !title) {
            if(saveStatus) saveStatus.textContent = '';
            return;
        }

        // Control de concurrencia: Si ya está guardando, marcar como pendiente y salir
        if (isSaving) {
            savePending = true;
            mostrarEstadoGuardando();
            return;
        }

        isSaving = true;

        const nota = {
            id: id ? id : null, // Si hay ID es edición
            titulo: title,
            contenido: contentHtml, // Guardamos HTML
            texto: contentText, // Guardamos texto plano
            materia_id: materiaId,
            color: selectedColor,
            accion: 'guardar'
        };

        try {
            const result = await guardarNotaBackend(nota);
            if (result && result.status === 'success') {
                // Si es nueva nota, actualizar el ID oculto para que los siguientes saves sean updates
                if (!id && result.id) {
                    document.getElementById('noteId').value = result.id;
                }
                // El guardado fue exitoso, limpiar cualquier mensaje de error previo.
                if (saveStatus) saveStatus.innerHTML = '';
            } else {
                actualizarUIStatus(result.message || 'Error al guardar', true);
            }
        } catch (e) {
            console.error(e);
            actualizarUIStatus('Error de conexión', true);
        } finally {
            isSaving = false;
            // Si hubo cambios mientras se guardaba, procesarlos ahora
            if (savePending) {
                savePending = false;
                guardarNotaAuto();
            }
        }
    }

    function actualizarUIStatus(mensaje, esError = false) {
        // Esta función ahora solo se usa para mostrar errores. El éxito es silencioso.
        if(!saveStatus || !esError) return;
        
        saveStatus.innerHTML = `<i class="fas fa-exclamation-circle me-2"></i>${mensaje}`;
        saveStatus.className = 'text-danger small ms-auto me-3 d-inline-flex align-items-center fw-bold';
    }

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
        // Retornar la promesa para manejarla en la función de autoguardado
        const response = await fetch('../backend/anotaciones_proceso.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(nota)
        });
        return await response.json();
    }

    window.eliminarNota = function(id) {
        document.getElementById('idNotaEliminar').value = id;
        modalEliminar.show();
    };

    btnConfirmarEliminar.addEventListener('click', function() {
        const id = document.getElementById('idNotaEliminar').value;
        
        fetch('../backend/anotaciones_proceso.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({accion: 'eliminar', id: id})
        }).then(res => res.json()).then(result => {
            if(result.status === 'success') {
                modalEliminar.hide();
                cargarNotasBackend();
                showToast('Nota eliminada correctamente', 'success');
            } else {
                showToast('Error: ' + result.message, 'error');
            }
        }).catch(err => {
            showToast('Error de conexión', 'error');
        });
    });

    // --- FUNCIONES DE EXPORTACIÓN ---
    
    const exportarPDF = () => {
        if (typeof html2pdf === 'undefined') {
            showToast('Error: Librería PDF no cargada. Verifique su conexión.', 'error');
            return;
        }

        const title = document.getElementById('noteTitle').value || 'Nota sin título';
        const content = document.querySelector('.ql-editor').innerHTML;
        
        // Crear elemento temporal adjunto al body para asegurar renderizado de estilos
        const element = document.createElement('div');
        element.style.width = '800px';
        element.style.padding = '30px';
        element.style.background = 'white';
        element.style.position = 'absolute';
        element.style.left = '-9999px';
        element.style.top = '0';
        element.style.fontFamily = 'Arial, sans-serif';
        
        element.innerHTML = `
            <h1 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px;">${title}</h1>
            <div class="ql-editor" style="padding: 0;">${content}</div>
        `;
        
        document.body.appendChild(element);

        const opt = {
            margin:       0.5,
            filename:     `${title.replace(/[^a-z0-9]/gi, '_')}.pdf`,
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2, useCORS: true, letterRendering: true },
            jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' },
            pagebreak:    { mode: ['avoid-all', 'css', 'legacy'] }
        };

        showToast('Generando PDF...', 'info');

        html2pdf().set(opt).from(element).save().then(() => {
            document.body.removeChild(element);
            showToast('PDF descargado correctamente', 'success');
        }).catch(err => {
            console.error(err);
            document.body.removeChild(element);
            showToast('Error al generar PDF', 'error');
        });
    };

    const exportarWord = () => {
        const title = document.getElementById('noteTitle').value || 'Nota sin título';
        const content = document.querySelector('.ql-editor').innerHTML;

        const preHtml = `<html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:w='urn:schemas-microsoft-com:office:word' xmlns='http://www.w3.org/TR/REC-html40'>
        <head><meta charset='utf-8'><title>${title}</title></head><body>`;
        const postHtml = "</body></html>";
        const html = preHtml + `<h1 style="margin-bottom:20px;">${title}</h1>` + content + postHtml;

        const blob = new Blob(['\ufeff', html], {
            type: 'application/msword'
        });
        
        const url = URL.createObjectURL(blob);
        const downloadLink = document.createElement("a");
        document.body.appendChild(downloadLink);
        downloadLink.href = url;
        downloadLink.download = `${title.replace(/[^a-z0-9]/gi, '_')}.doc`;
        downloadLink.click();
        
        document.body.removeChild(downloadLink);
        URL.revokeObjectURL(url);
        showToast('Documento Word descargado', 'success');
    };

    // 9. Configuración del Botón de Exportación (Modal)
    function setupExportButton() {
        // 1. Limpiar elementos anteriores si existen
        const oldDropdown = document.getElementById('dropdownExport');
        if (oldDropdown) {
            const container = oldDropdown.closest('.dropdown');
            if (container) container.remove();
            else oldDropdown.remove();
        }
        
        const oldBtn = document.getElementById('btnOpenExportModal');
        if (oldBtn) oldBtn.remove();

        // 2. Inyectar HTML del Modal de Exportación si no existe
        if (!document.getElementById('modalExportar')) {
            const modalHtml = `
            <div class="modal fade" id="modalExportar" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
                <div class="modal-dialog modal-dialog-centered modal-sm">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title fs-6">Exportar Nota</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body d-grid gap-2">
                            <button class="btn btn-outline-danger d-flex align-items-center justify-content-start gap-3 p-3" id="btnExportPdfModal">
                                <i class="fas fa-file-pdf fa-2x"></i> 
                                <span>Exportar a PDF</span>
                            </button>
                            <button class="btn btn-outline-primary d-flex align-items-center justify-content-start gap-3 p-3" id="btnExportWordModal">
                                <i class="fas fa-file-word fa-2x"></i> 
                                <span>Exportar a Word</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        // 3. Inyectar Botón en el Header del Modal de Nota
        const modalHeader = document.querySelector('#modalNota .modal-header');
        
        if (modalHeader) {
            const exportBtn = document.createElement('button');
            exportBtn.className = 'btn btn-light border btn-sm ms-auto me-3';
            exportBtn.id = 'btnOpenExportModal';
            exportBtn.type = 'button';
            exportBtn.innerHTML = '<i class="fas fa-download"></i> <span class="d-none d-sm-inline ms-1">Exportar</span>';
            
            // Insertar antes del botón de cerrar
            const closeBtn = modalHeader.querySelector('.btn-close');
            if (closeBtn) {
                modalHeader.insertBefore(exportBtn, closeBtn);
            } else {
                modalHeader.appendChild(exportBtn);
            }

            // 4. Asignar eventos
            exportBtn.addEventListener('click', () => {
                const exportModal = new bootstrap.Modal(document.getElementById('modalExportar'));
                exportModal.show();
            });

            // Eventos de los botones del modal
            const btnPdf = document.getElementById('btnExportPdfModal');
            const btnWord = document.getElementById('btnExportWordModal');

            // Usar replaceWith para eliminar listeners anteriores si la función se ejecuta múltiples veces
            if(btnPdf) {
                const newBtnPdf = btnPdf.cloneNode(true);
                btnPdf.parentNode.replaceChild(newBtnPdf, btnPdf);
                newBtnPdf.addEventListener('click', () => {
                    exportarPDF();
                    const modalEl = document.getElementById('modalExportar');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if(modalInstance) modalInstance.hide();
                });
            }

            if(btnWord) {
                const newBtnWord = btnWord.cloneNode(true);
                btnWord.parentNode.replaceChild(newBtnWord, btnWord);
                newBtnWord.addEventListener('click', () => {
                    exportarWord();
                    const modalEl = document.getElementById('modalExportar');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if(modalInstance) modalInstance.hide();
                });
            }
        }
    }

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
        
        modalInstance.show();
    };

    function escapeHtml(text) {
        if (!text) return '';
        return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Función auxiliar para Toast (Estandarizada)
    function showToast(message, type = 'info') {
        const toastEl = document.getElementById('liveToast');
        const toastTitle = document.getElementById('toastTitle');
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');
        
        if (type === 'success') {
            toastIcon.className = 'fas fa-check-circle me-2 text-success';
            toastTitle.textContent = '¡Éxito!';
        } else if (type === 'error') {
            toastIcon.className = 'fas fa-exclamation-circle me-2 text-danger';
            toastTitle.textContent = 'Error';
        } else {
            toastIcon.className = 'fas fa-info-circle me-2 text-primary';
            toastTitle.textContent = 'Información';
        }
        
        toastMessage.textContent = message;
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
    }
});