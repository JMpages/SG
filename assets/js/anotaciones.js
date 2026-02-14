document.addEventListener('DOMContentLoaded', function() {
    // Elementos UI
    const notesGrid = document.getElementById('notesGrid');
    const saveStatus = document.getElementById('saveStatus');
    const colorOptions = document.querySelectorAll('.color-option');
    const selectMateria = document.getElementById('noteMateria');
    const selectMateriaMobile = document.getElementById('noteMateriaMobile');
    const modalNota = document.getElementById('modalNota');
    const filterMateria = document.getElementById('filterMateria');
    const filterDate = document.getElementById('filterDate');
    const btnClearFilters = document.getElementById('btnClearFilters');
    const btnDownloadPdf = document.getElementById('btnDownloadPdf');
    const btnDownloadPdfMobile = document.getElementById('btnDownloadPdfMobile');
    const modalInstance = new bootstrap.Modal(modalNota);
    const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminarNota'));
    const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminarNota');

    let selectedColor = 'white';
    let materiasCache = [];
    let notasCache = []; // Caché local de notas del backend
    let quill;
    let isSaving = false; // Flag para evitar guardados concurrentes
    let savePending = false; // Flag para encolar cambios pendientes
    let signaturePad; // Instancia de escritura a mano

    // 1. Inicialización
    initQuill();
    cargarMaterias();
    setupSearchAndFilter();
    initDrawing(); // Inicializar lógica de dibujo
    // Cargar notas desde el backend
    cargarNotasBackend();
    setupMobileToolbar(); // Inicializar lógica móvil

    // Listener para PDF
    if(btnDownloadPdf) btnDownloadPdf.addEventListener('click', descargarPDF);
    if(btnDownloadPdfMobile) btnDownloadPdfMobile.addEventListener('click', descargarPDF);

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
                    container: '#custom-toolbar', // Usar el contenedor HTML manual
                    handlers: {
                    }
                }
            }
        });

        // 2. Inicializar Tooltips de Bootstrap para la barra de herramientas
        // Usamos setTimeout para asegurar que el DOM de Quill esté listo
        setTimeout(() => {
            const toolbar = document.querySelector('.ql-toolbar');
            if (toolbar) {
                const tooltipsMap = {
                    '.ql-bold': 'Negrita',
                    '.ql-italic': 'Cursiva',
                    '.ql-underline': 'Subrayado',
                    '.ql-strike': 'Tachado',
                    '.ql-clean': 'Limpiar formato',
                    '.ql-list': 'Listas', // Selectores genéricos funcionan para botones y pickers
                    '.ql-align': 'Alineación',
                    '.ql-color': 'Color de texto',
                    '.ql-background': 'Color de fondo',
                    '.ql-indent[value="-1"]': 'Disminuir sangría',
                    '.ql-indent[value="+1"]': 'Aumentar sangría',
                    '.ql-font': 'Fuente',
                    '.ql-size': 'Tamaño'
                };

                for (const [selector, title] of Object.entries(tooltipsMap)) {
                    // Buscamos tanto botones directos como contenedores de pickers (.ql-picker.ql-list)
                    const elements = toolbar.querySelectorAll(selector + ', .ql-picker' + selector);
                    elements.forEach(el => {
                        const tooltip = new bootstrap.Tooltip(el, { 
                            title: title, 
                            trigger: 'hover', // Usar hover nativo (excluye focus para evitar que se pegue al clic)
                            container: 'body', // Evita problemas de z-index/corte
                            placement: () => window.innerWidth < 768 ? 'bottom' : 'top' // Dirección opuesta a las opciones
                        });
                        
                        // Asegurar que se oculte inmediatamente al hacer clic
                        el.addEventListener('click', () => tooltip.hide());
                    });
                }
            }
        }, 100);

        // Listener para cambios en el editor (Autoguardado)
        quill.on('text-change', function(delta, oldDelta, source) {
            if (source === 'user') {
                mostrarEstadoGuardando();
                debouncedSave();
            }
        });

        // Listeners para botones de Deshacer/Rehacer en el header
        const btnUndo = document.getElementById('btnUndo');
        const btnRedo = document.getElementById('btnRedo');
        
        if(btnUndo) btnUndo.addEventListener('click', () => quill.history.undo());
        if(btnRedo) btnRedo.addEventListener('click', () => quill.history.redo());
    }

    // 1.1 Lógica de Escritura a Mano
    function initDrawing() {
        const canvas = document.getElementById('drawing-canvas');
        const editorPage = document.getElementById('editor-page');
        
        if(!canvas || !editorPage) return;

        // Inicializar SignaturePad
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)', // Transparente para ver las líneas de fondo CSS
            penColor: 'black',
            minWidth: 0.5,
            maxWidth: 2,
            throttle: 16 // Mejora rendimiento en móviles
        });

        // Ajustar tamaño del canvas al abrir el modal y al redimensionar
        modalNota.addEventListener('shown.bs.modal', function () {
            resizeCanvas();
        });
        window.addEventListener('resize', resizeCanvas);

        // --- PESTAÑAS (TEXTO vs DIBUJO) ---
        const tabButtons = document.querySelectorAll('.btn-nav-icon');
        const panelEdicion = document.getElementById('panel-edicion');
        const toolbarEstilo = document.getElementById('toolbar-estilo');
        const panelDraw = document.getElementById('panel-draw');

        tabButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Activar botón
                tabButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const mode = this.dataset.tab;
                
                if (mode === 'estilo') {
                    panelEdicion.classList.remove('d-none');
                    panelDraw.classList.add('d-none');
                    editorPage.classList.remove('drawing-mode');
                } else if (mode === 'draw') {
                    panelEdicion.classList.add('d-none');
                    panelDraw.classList.remove('d-none');
                    editorPage.classList.add('drawing-mode'); // Activa canvas
                    resizeCanvas(); // Asegurar tamaño correcto
                }
            });
        });

        // --- HERRAMIENTAS DE DIBUJO ---
        
        // Insertar Dibujo
        document.getElementById('btnInsertDrawing').addEventListener('click', () => {
            if (signaturePad.isEmpty()) {
                showToast('El lienzo está vacío', 'warning');
                return;
            }
            // Convertir a imagen
            const data = signaturePad.toDataURL('image/png');
            
            // Insertar en Quill
            const range = quill.getSelection(true);
            const index = range ? range.index : quill.getLength();
            quill.insertEmbed(index, 'image', data);
            quill.setSelection(index + 1);
            
            // Limpiar y volver a modo texto (opcional, o quedarse en dibujo)
            signaturePad.clear();
            showToast('Dibujo insertado', 'success');
        });

        // Botón Borrar
        document.getElementById('btnClearCanvas').addEventListener('click', () => {
            signaturePad.clear();
        });

        // Selector de Color
        document.querySelectorAll('input[name="penColor"]').forEach(input => {
            input.addEventListener('change', (e) => {
                signaturePad.penColor = e.target.value;
            });
        });

        // Selector de Grosor
        document.getElementById('penWidth').addEventListener('input', (e) => {
            const width = parseFloat(e.target.value);
            signaturePad.minWidth = width * 0.5;
            signaturePad.maxWidth = width;
        });
    }

    function resizeCanvas() {
        const canvas = document.getElementById('drawing-canvas');
        const editorPage = document.getElementById('editor-page');
        
        if(canvas && editorPage) {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            
            // Guardar contenido actual
            const data = signaturePad.toData();
            
            // Ajustar dimensiones físicas y de dibujo
            // El canvas debe cubrir todo el editor page
            canvas.width = editorPage.offsetWidth * ratio;
            canvas.height = editorPage.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            
            // Restaurar contenido (SignaturePad limpia al cambiar dimensiones)
            signaturePad.clear();
            signaturePad.fromData(data);
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
        if(selectMateriaMobile) selectMateriaMobile.value = '';
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

    // Sincronizar selectores de materia (Escritorio <-> Móvil)
    function handleMateriaChange(e) {
        const val = e.target.value;
        if(selectMateria && selectMateria !== e.target) selectMateria.value = val;
        if(selectMateriaMobile && selectMateriaMobile !== e.target) selectMateriaMobile.value = val;
        mostrarEstadoGuardando();
        debouncedSave();
    }
    if(selectMateria) selectMateria.addEventListener('change', handleMateriaChange);
    if(selectMateriaMobile) selectMateriaMobile.addEventListener('change', handleMateriaChange);

    function mostrarEstadoGuardando() {
        // El guardado es automático, solo se notificarán errores.
    }

    async function guardarNotaAuto() {
        const id = document.getElementById('noteId').value;
        const title = document.getElementById('noteTitle').value.trim();
        const contentHtml = quill.root.innerHTML;
        const contentText = quill.getText(); // Texto plano para búsquedas
        const materiaId = selectMateria ? selectMateria.value : (selectMateriaMobile ? selectMateriaMobile.value : '');

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
                let options = '<option value="">Sin materia</option>';
                let optionsMobile = '<option value="">&#xf02d;</option>'; // Icono para móvil
                let filterOptions = '<option value="all">Todas</option>';
                
                materiasCache.forEach(m => {
                    if(m.activa == 1) {
                        options += `<option value="${m.id}">${m.nombre}</option>`;
                        optionsMobile += `<option value="${m.id}">${m.nombre}</option>`;
                        filterOptions += `<option value="${m.id}">${m.nombre}</option>`;
                    }
                });
                
                if(selectMateria) selectMateria.innerHTML = options;
                if(selectMateriaMobile) selectMateriaMobile.innerHTML = optionsMobile;
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

    // 7. Generar PDF (Versión Impresión Nativa para Texto Real)
    function descargarPDF() {
        // Usamos un iframe oculto y window.print() para garantizar que el texto sea seleccionable (vectorial)
        // y no una imagen como sucede con html2canvas/html2pdf.
        
        const titleInput = document.getElementById('noteTitle');
        const rawTitle = titleInput.value.trim() || 'Nota_sin_titulo';
        const editorContent = document.querySelector('.ql-editor').innerHTML;
        
        // Feedback visual (Loading)
        const originalContent = btnDownloadPdf.innerHTML;
        btnDownloadPdf.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btnDownloadPdf.disabled = true;

        // Crear iframe oculto
        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        document.body.appendChild(iframe);

        const doc = iframe.contentWindow.document;
        
        // Estilos CSS: Bootstrap + Quill + Ajustes de Impresión
        const styles = `
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
            <style>
                @page { size: A4; margin: 2cm; }
                body { 
                    padding: 0; 
                    margin: 0;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    color: #000;
                    background: #fff;
                }
                h1 { 
                    margin-bottom: 1.5rem; 
                    border-bottom: 2px solid #eee; 
                    padding-bottom: 0.5rem;
                    font-size: 2rem;
                }
                .ql-container.ql-snow { border: none !important; }
                .ql-editor { padding: 0 !important; overflow: visible !important; }
                img { max-width: 100%; height: auto; }
            </style>
        `;

        doc.open();
        doc.write(`
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>${rawTitle}</title>
                ${styles}
            </head>
            <body>
                <div class="container-fluid">
                    <h1>${rawTitle}</h1>
                    <div class="ql-container ql-snow">
                        <div class="ql-editor">
                            ${editorContent}
                        </div>
                    </div>
                </div>
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            try {
                                window.print();
                            } catch(e) { console.error(e); }
                        }, 500);
                    };
                </script>
            </body>
            </html>
        `);
        doc.close();

        // Restaurar botón
        setTimeout(() => {
            btnDownloadPdf.innerHTML = originalContent;
            btnDownloadPdf.disabled = false;
            showToast('Selecciona "Guardar como PDF" en la ventana de impresión.', 'info');
            
            // Limpiar iframe después de un tiempo prudencial
            setTimeout(() => {
                if(document.body.contains(iframe)) document.body.removeChild(iframe);
            }, 60000);
        }, 1000);
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

    // Función para abrir nota existente en el modal
    window.abrirNotaParaEditar = function(id) {
        const nota = notasCache.find(n => n.id == id);
        if (!nota) return;

        document.getElementById('noteId').value = nota.id;
        document.getElementById('noteTitle').value = nota.titulo;
        quill.root.innerHTML = nota.contenido; // Cargar HTML en Quill
        selectMateria.value = nota.materia_id || '';
        if(selectMateriaMobile) selectMateriaMobile.value = nota.materia_id || '';
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