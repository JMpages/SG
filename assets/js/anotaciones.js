document.addEventListener('DOMContentLoaded', function() {
    // Elementos UI
    const notesGrid = document.getElementById('notesGrid');
    const saveStatus = document.getElementById('saveStatus');
    const colorOptions = document.querySelectorAll('.color-option');
    const selectMateria = document.getElementById('noteMateria');
    const selectMateriaMobile = document.getElementById('noteMateriaMobile');
    const modalNota = document.getElementById('modalNota');
    const filterMateria = document.getElementById('filterMateria');
    const sortNotes = document.getElementById('sortNotes');
    const btnClearFilters = document.getElementById('btnClearFilters');
    const btnDownloadPdf = document.getElementById('btnDownloadPdf');
    const btnDownloadPdfMobile = document.getElementById('btnDownloadPdfMobile');
    const modalInstance = new bootstrap.Modal(modalNota);
    const modalEliminar = new bootstrap.Modal(document.getElementById('modalEliminarNota'));
    const btnConfirmarEliminar = document.getElementById('btnConfirmarEliminarNota');

    let selectedColor = 'white';
    let materiasCache = [];
    let notasCache = []; // Caché local de notas del backend
    let activeNoteId = null; // ID en memoria para evitar duplicados por latencia
    let quill;
    let isSaving = false; // Flag para evitar guardados concurrentes
    let savePending = false; // Flag para encolar cambios pendientes
    let signaturePad; // Instancia de escritura a mano
    let drawingHistory = []; // Historial de dibujo
    let drawingHistoryStep = -1; // Paso actual del historial
    let isSelectionMode = false; // Estado de selección
    let selectedNoteIds = new Set(); // IDs seleccionados

    // 1. Inicialización
    initQuill();
    cargarMaterias();
    setupSearchAndFilter();
    initDrawing(); // Inicializar lógica de dibujo
    // Cargar notas desde el backend
    cargarNotasBackend();
    initSelectionMode(); // Inicializar UI de selección
    setupMobileToolbar(); // Inicializar lógica móvil

    // Listener para PDF
    if(btnDownloadPdf) btnDownloadPdf.addEventListener('click', descargarPDF);
    if(btnDownloadPdfMobile) btnDownloadPdfMobile.addEventListener('click', descargarPDF);

    // Listener para botones de Deshacer/Rehacer (Inteligente: Texto o Dibujo)
    const btnUndo = document.getElementById('btnUndo');
    const btnRedo = document.getElementById('btnRedo');
    const editorPage = document.getElementById('editor-page');

    // Funciones centralizadas para Deshacer/Rehacer
    function performUndo() {
        if (editorPage && editorPage.classList.contains('drawing-mode')) {
            if (drawingHistoryStep > 0) {
                drawingHistoryStep--;
                signaturePad.fromData(drawingHistory[drawingHistoryStep]);
                debouncedSave();
            }
        } else {
            if(quill) quill.history.undo();
        }
    }

    function performRedo() {
        if (editorPage && editorPage.classList.contains('drawing-mode')) {
            if (drawingHistoryStep < drawingHistory.length - 1) {
                drawingHistoryStep++;
                signaturePad.fromData(drawingHistory[drawingHistoryStep]);
                debouncedSave();
            }
        } else {
            if(quill) quill.history.redo();
        }
    }

    if(btnUndo) btnUndo.addEventListener('click', performUndo);
    if(btnRedo) btnRedo.addEventListener('click', performRedo);

    // Listener Global de Teclado para Ctrl+Z / Ctrl+Y
    document.addEventListener('keydown', function(e) {
        // Solo actuar si el modal de nota está visible
        if (!document.getElementById('modalNota').classList.contains('show')) return;

        if ((e.ctrlKey || e.metaKey) && !e.altKey) {
            const key = e.key.toLowerCase();
            const isShift = e.shiftKey;

            // Undo: Ctrl+Z
            if (key === 'z' && !isShift) {
                e.preventDefault();
                performUndo();
            }
            
            // Redo: Ctrl+Y OR Ctrl+Shift+Z
            if (key === 'y' || (key === 'z' && isShift)) {
                e.preventDefault();
                performRedo();
            }
        }
    });

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
    }

    // 1.1 Lógica de Escritura a Mano
    function initDrawing() {
        const canvas = document.getElementById('drawing-canvas');
        const editorPage = document.getElementById('editor-page');
        const eraserCursorPreview = document.getElementById('eraser-cursor-preview');
        
        if(!canvas || !editorPage) return;

        // Función para obtener el color adecuado según el tema (Negro en claro, Blanco en oscuro)
        const getThemePenColor = () => {
            const themeAttr = document.documentElement.getAttribute('data-theme') || document.body.getAttribute('data-theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = themeAttr === 'dark' || (!themeAttr && themeAttr !== 'light' && prefersDark);
            return isDark ? '#ffffff' : '#000000';
        };

        const defaultColor = getThemePenColor();

        // Variable para rastrear si el usuario ha elegido un color manualmente
        let userHasSelectedColor = false;

        // Inicializar SignaturePad
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)', // Transparente para ver las líneas de fondo CSS
            penColor: defaultColor,
            minWidth: 1, // Ajustado al valor inicial del slider (2 * 0.5)
            maxWidth: 2,
            throttle: 16 // Mejora rendimiento en móviles
        });

        // Observador para cambios de tema dinámicos
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && (mutation.attributeName === 'data-theme' || mutation.attributeName === 'class')) {
                    if (!userHasSelectedColor) {
                        updateAllColorPickers(getThemePenColor(), false); // false = no marcar como selección de usuario
                    }
                }
            });
        });
        
        observer.observe(document.documentElement, { attributes: true });
        observer.observe(document.body, { attributes: true });

        // Función para guardar estado en el historial
        const saveDrawingState = () => {
            drawingHistoryStep++;
            if (drawingHistoryStep < drawingHistory.length) {
                drawingHistory.length = drawingHistoryStep;
            }
            // Clonar datos para evitar referencias
            drawingHistory.push(JSON.parse(JSON.stringify(signaturePad.toData())));
        };

        // Guardar automáticamente al terminar un trazo
        signaturePad.addEventListener("endStroke", () => {
            saveDrawingState();
            mostrarEstadoGuardando();
            debouncedSave();
        });

        // Ajustar tamaño del canvas al abrir el modal y al redimensionar
        modalNota.addEventListener('shown.bs.modal', function () {
            resizeCanvas();
        });
        window.addEventListener('resize', resizeCanvas);

        // Observar cambios de tamaño en la hoja (por contenido de texto) para ajustar el canvas
        const resizeObserver = new ResizeObserver(entries => {
            for (let entry of entries) {
                resizeCanvas();
            }
        });
        resizeObserver.observe(editorPage);

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
                    // FIX: Ocultar el cursor del borrador y resetear el estado al cambiar a modo texto
                    editorPage.classList.remove('eraser-active');
                    if (eraserCursorPreview) eraserCursorPreview.style.display = 'none';
                } else if (mode === 'draw') {
                    panelEdicion.classList.add('d-none');
                    panelDraw.classList.remove('d-none');
                    editorPage.classList.add('drawing-mode'); // Activa canvas
                    resizeCanvas(); // Asegurar tamaño correcto
                }
            });
        });

        // --- HERRAMIENTAS DE DIBUJO ---
        
        // Botón Borrar
        document.querySelectorAll('.btn-clear-canvas').forEach(btn => {
            btn.addEventListener('click', () => {
                signaturePad.clear();
                saveDrawingState(); // Guardar el estado vacío en el historial
            });
        });

        // Herramientas Lápiz / Borrador
        const penButtons = document.querySelectorAll('.btn-tool-pen');
        const eraserButtons = document.querySelectorAll('.btn-tool-eraser');
        const penWidthInputs = document.querySelectorAll('.pen-width-input');

        function setTool(type) {
            if (type === 'pen') {
                signaturePad.compositeOperation = 'source-over';
                penButtons.forEach(b => b.classList.add('active'));
                eraserButtons.forEach(b => b.classList.remove('active'));

                editorPage.classList.remove('eraser-active');
                if (eraserCursorPreview) eraserCursorPreview.style.display = 'none';

            } else if (type === 'eraser') {
                signaturePad.compositeOperation = 'destination-out';
                penButtons.forEach(b => b.classList.remove('active'));
                eraserButtons.forEach(b => b.classList.add('active'));

                editorPage.classList.add('eraser-active');
            }
            // Recalcular grosor al cambiar de herramienta
            if(penWidthInputs.length > 0) penWidthInputs[0].dispatchEvent(new Event('input'));
        }

        penButtons.forEach(btn => btn.addEventListener('click', () => setTool('pen')));
        eraserButtons.forEach(btn => btn.addEventListener('click', () => setTool('eraser')));

        // Selectores de Grosor (Múltiples instancias: Desktop/Mobile)
        
        const updatePenIndicator = (inputElement, width) => {
            if(inputElement) {
                const size = 6 + (width * 3); 
                inputElement.style.setProperty('--pen-size', `${size}px`);
            }
        };

        // Inicializar sliders
        penWidthInputs.forEach(input => {
            input.style.setProperty('--pen-color', defaultColor);
            updatePenIndicator(input, parseFloat(input.value));
            
            input.addEventListener('input', (e) => {
                const width = parseFloat(e.target.value);
                const isEraser = editorPage.classList.contains('eraser-active');

                // Aumentamos los multiplicadores para mayor grosor y flexibilidad
                const multiplier = isEraser ? 12 : 3; 
                const effectiveWidth = width * multiplier;

                signaturePad.minWidth = effectiveWidth * 0.5;
                signaturePad.maxWidth = effectiveWidth;
                
                // Sincronizar todos los sliders
                penWidthInputs.forEach(other => {
                    other.value = width;
                    updatePenIndicator(other, width);
                });

                // Actualizar preview del borrador
                if (eraserCursorPreview) {
                    const size = signaturePad.maxWidth;
                    eraserCursorPreview.style.width = `${size}px`;
                    eraserCursorPreview.style.height = `${size}px`;
                }
            });
        });

        // Selectores de Color (Múltiples instancias)
        const pickerContainers = document.querySelectorAll('.drawing-color-picker-container');
        
        pickerContainers.forEach(container => {
            const trigger = container.querySelector('.draw-color-trigger');
            const dropdown = container.querySelector('.drawing-palette-dropdown');
            const currentColorIndicator = container.querySelector('.current-draw-color');
            const colorOptions = container.querySelectorAll('.draw-color-option');
            const customColorInput = container.querySelector('.custom-pen-color');

            // Inicializar color
            if(currentColorIndicator) currentColorIndicator.style.backgroundColor = defaultColor;
            if(customColorInput) customColorInput.value = defaultColor;

            // Toggle dropdown
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                // Cerrar otros dropdowns primero
                document.querySelectorAll('.drawing-palette-dropdown').forEach(d => {
                    if(d !== dropdown) d.classList.add('d-none');
                });
                dropdown.classList.toggle('d-none');
            });

            // Opciones de color
            colorOptions.forEach(opt => {
                opt.addEventListener('click', function(e) {
                    e.stopPropagation(); 
                    const color = this.dataset.color;
                    updateAllColorPickers(color, true);
                });
            });

            // Color personalizado
            if(customColorInput) {
                customColorInput.addEventListener('input', (e) => {
                    updateAllColorPickers(e.target.value, true);
                });
            }
        });

        // Función para actualizar todos los selectores de color a la vez
        function updateAllColorPickers(color, isUserAction = false) {
            signaturePad.penColor = color;
            if (isUserAction) {
                userHasSelectedColor = true;
                setTool('pen'); // Si cambias de color manualmente, vuelves al lápiz
            }
            
            // Actualizar UI de todos los pickers
            pickerContainers.forEach(c => {
                const indicator = c.querySelector('.current-draw-color');
                const input = c.querySelector('.custom-pen-color');
                const drop = c.querySelector('.drawing-palette-dropdown');
                
                if(indicator) indicator.style.backgroundColor = color;
                if(input) input.value = color;
                if(drop) drop.classList.add('d-none');
            });

            // Actualizar color de los sliders
            penWidthInputs.forEach(input => {
                input.style.setProperty('--pen-color', color);
            });
        }

        // Cerrar dropdowns al hacer clic fuera
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.drawing-color-picker-container')) {
                document.querySelectorAll('.drawing-palette-dropdown').forEach(d => d.classList.add('d-none'));
            }
        });

        // Listeners para el cursor del borrador
         if (canvas && eraserCursorPreview) {
            // Usamos pointermove en window con captura para garantizar que recibimos el evento
            // incluso si SignaturePad o el canvas capturan el puntero.
            window.addEventListener('pointermove', (e) => {
                // FIX: Asegurarse de que el cursor solo aparezca en modo dibujo Y con borrador activo
                if (editorPage.classList.contains('drawing-mode') && editorPage.classList.contains('eraser-active')) {
                    const rect = canvas.getBoundingClientRect();
                    const isInside = e.clientX >= rect.left && e.clientX <= rect.right &&
                                   e.clientY >= rect.top && e.clientY <= rect.bottom;

                    if (isInside) {
                        eraserCursorPreview.style.display = 'block';
                        // Posición relativa al canvas
                        eraserCursorPreview.style.left = `${e.clientX - rect.left}px`;
                        eraserCursorPreview.style.top = `${e.clientY - rect.top}px`;
                    } else {
                        eraserCursorPreview.style.display = 'none';
                    }
                }
            }, { capture: true });
        }
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

    function renderPreviewDrawings() {
        const previewCanvases = document.querySelectorAll('.note-drawing-preview');
        if (previewCanvases.length === 0) return;
    
        previewCanvases.forEach(canvas => {
            try {
                const data = JSON.parse(canvas.dataset.drawingData);
                if (data && data.length > 0) {
                    // Ajustar dimensiones del canvas para alta resolución
                    const ratio = Math.max(window.devicePixelRatio || 1, 1);
                    canvas.width = canvas.offsetWidth * ratio;
                    canvas.height = canvas.offsetHeight * ratio;
                    const ctx = canvas.getContext("2d");
                    ctx.scale(ratio, ratio);
    
                    // Usar una instancia temporal de SignaturePad para dibujar
                    const tempPad = new SignaturePad(canvas);
                    tempPad.fromData(data);
                    
                    // Desactivar cualquier interacción con el canvas de vista previa
                    tempPad.off();
                }
            } catch (e) {
                console.error("Error al renderizar el dibujo de vista previa:", e);
            }
        });
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

    // 1.6 Configuración Modo Selección (Eliminación Múltiple)
    function initSelectionMode() {
        // 1. Inyectar Barra de Selección en el body
        const selectionBarHtml = `
            <div id="selectionBar" class="selection-bar px-4">
                <div class="d-flex align-items-center gap-3">
                    <button id="btnCancelSelection" class="btn btn-link text-white p-0 fs-5"><i class="fas fa-times"></i></button>
                    <span id="selectionCount" class="fw-bold fs-5">0 seleccionadas</span>
                </div>
                <div class="d-flex gap-3 align-items-center">
                     <button id="btnExportSelected" class="btn btn-light text-primary btn-sm rounded-circle shadow-sm" style="width: 36px; height: 36px;" disabled title="Exportar a PDF"><i class="fas fa-file-pdf"></i></button>
                     <button id="btnDeleteSelected" class="btn btn-light text-danger btn-sm rounded-circle shadow-sm" style="width: 36px; height: 36px;" disabled title="Eliminar"><i class="fas fa-trash"></i></button>
                     <button id="btnSelectAll" class="btn btn-outline-light btn-sm rounded-pill px-3 ms-2">Todo</button>
                </div>
            </div>`;
        document.body.insertAdjacentHTML('beforeend', selectionBarHtml);

        // Event Listeners de la barra
        document.getElementById('btnCancelSelection').addEventListener('click', toggleSelectionMode);
        
        document.getElementById('btnSelectAll').addEventListener('click', () => {
            const allIds = notasCache.map(n => n.id);
            if (selectedNoteIds.size === allIds.length) {
                selectedNoteIds.clear();
            } else {
                allIds.forEach(id => selectedNoteIds.add(id));
            }
            updateSelectionUI();
        });

        document.getElementById('btnExportSelected').addEventListener('click', () => {
            descargarSeleccionPDF();
        });

        document.getElementById('btnDeleteSelected').addEventListener('click', async () => {
            if (selectedNoteIds.size === 0) return;
            
            // FIX: Asegurarse de que el input exista, ya que innerHTML lo puede destruir.
            let idInput = document.getElementById('idNotaEliminar');
            if (!idInput) {
                const modalFooter = document.querySelector('#modalEliminarNota .modal-footer');
                if (modalFooter) {
                    const newInput = document.createElement('input');
                    newInput.type = 'hidden';
                    newInput.id = 'idNotaEliminar';
                    modalFooter.appendChild(newInput);
                    idInput = newInput;
                }
            }
            if (idInput) idInput.value = ''; // Limpiar ID para indicar modo lote
            
            // Actualizar mensaje del modal
            const modalBody = document.querySelector('#modalEliminarNota .modal-body');
            if(modalBody) {
                modalBody.innerHTML = `<p>¿Estás seguro de eliminar <strong>${selectedNoteIds.size}</strong> notas seleccionadas? Esta acción no se puede deshacer.</p>`;
            }
            
            modalEliminar.show();
        });
    }

    window.toggleNoteSelection = function(id) {
        if (selectedNoteIds.has(id)) {
            selectedNoteIds.delete(id);
        } else {
            selectedNoteIds.add(id);
        }
        
        // Activar modo selección automáticamente si hay items
        if (selectedNoteIds.size > 0 && !isSelectionMode) {
            isSelectionMode = true;
            document.getElementById('selectionBar').classList.add('show');
        } else if (selectedNoteIds.size === 0 && isSelectionMode) {
            // Desactivar si no queda ninguno (comportamiento tipo Google Keep)
            isSelectionMode = false;
            document.getElementById('selectionBar').classList.remove('show');
        }
        
        updateSelectionUI();
    };

    function toggleSelectionMode() {
        isSelectionMode = !isSelectionMode;
        
        const bar = document.getElementById('selectionBar');
        if (isSelectionMode) {
            bar.classList.add('show');
        } else {
            bar.classList.remove('show');
            selectedNoteIds.clear();
        }
        updateSelectionUI();
    }

    function updateSelectionUI() {
        // Actualizar contador
        const count = selectedNoteIds.size;
        document.getElementById('selectionCount').textContent = `${count} seleccionada${count !== 1 ? 's' : ''}`;
        document.getElementById('btnDeleteSelected').disabled = count === 0;
        document.getElementById('btnExportSelected').disabled = count === 0;
        
        // Actualizar visualmente las tarjetas sin re-renderizar todo el HTML para mantener animaciones
        document.querySelectorAll('.note-card').forEach(card => {
            const id = parseInt(card.dataset.id);
            const checkbox = card.querySelector('.note-select-btn');
            const icon = checkbox.querySelector('i');
            
            if (selectedNoteIds.has(id)) {
                card.classList.add('selected');
                checkbox.classList.add('checked');
                icon.className = 'fas fa-check-circle';
            } else {
                card.classList.remove('selected');
                checkbox.classList.remove('checked');
                icon.className = 'far fa-circle';
            }
        });
        
        // Si cambiamos de estado (show/hide checkboxes globales), puede requerir clases en el contenedor
        if(isSelectionMode) {
            notesGrid.classList.add('selection-active');
            document.body.classList.add('selection-mode-active');
        } else {
            notesGrid.classList.remove('selection-active');
            document.body.classList.remove('selection-mode-active');
        }
    }

    // 1.5 Configuración de Búsqueda y Filtros
    function setupSearchAndFilter() {
        // Eventos de cambio en filtros
        filterMateria.addEventListener('change', filtrarNotas);
        sortNotes.addEventListener('change', filtrarNotas);

        // Botón limpiar
        btnClearFilters.addEventListener('click', () => {
            filterMateria.value = 'all';
            sortNotes.value = 'edited_desc';
            filtrarNotas();
        });
    }

    function filtrarNotas() {
        const materiaId = filterMateria.value;
        const sortType = sortNotes.value;
        
        let notas = [...notasCache]; // Usar caché del backend

        // 1. Filtro por Materia
        if (materiaId !== 'all') {
            notas = notas.filter(n => n.materia_id == materiaId);
        }

        // 3. Ordenamiento (Doble filtro)
        notas.sort((a, b) => {
            let dateA, dateB;
            
            if (sortType === 'edited_desc') {
                // Si no hay fecha de actualización, usar fecha de creación
                dateA = new Date(a.fecha_actualizacion || a.fecha_creacion);
                dateB = new Date(b.fecha_actualizacion || b.fecha_creacion);
                return dateB - dateA; // Descendente (Más reciente primero)
            } else if (sortType === 'created_desc') {
                return new Date(b.fecha_creacion) - new Date(a.fecha_creacion);
            } else if (sortType === 'created_asc') {
                return new Date(a.fecha_creacion) - new Date(b.fecha_creacion);
            }
            return 0;
        });

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
        activeNoteId = null; // Limpiar ID en memoria
        document.getElementById('noteTitle').value = '';
        
        // FIX: Limpiar el editor y su historial de forma robusta al cerrar.
        if (quill) {
            quill.setContents([], 'silent');
            quill.history.clear();
        }

        selectMateria.value = '';
        if(signaturePad) signaturePad.clear(); // Limpiar canvas
        drawingHistory = [[]]; // Reiniciar historial con un estado vacío
        drawingHistoryStep = 0; // Apuntar al estado vacío
        if(selectMateriaMobile) selectMateriaMobile.value = '';
        selectedColor = 'white';
        updateColorSelection();
        if(saveStatus) saveStatus.innerHTML = '';
        if(saveStatus) saveStatus.className = 'd-flex align-items-center me-2';
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
    }, 3000); // Esperar 3 segundos de inactividad

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
        if(saveStatus) {
            // Quitamos clases que oculten y añadimos margen
            saveStatus.className = 'me-2 d-block';
            
            // width: 60px en móvil, 100px en PC para que quepa bien
            saveStatus.innerHTML = `
                <div class="progress" style="height: 4px; width: 60px; min-width: 60px; background-color: #e9ecef;">
                    <div id="progressBarSave" class="progress-bar bg-primary" role="progressbar" style="width: 0%; transition: width 0.5s ease-in-out;"></div>
                </div>
                <div class="text-muted text-center" style="font-size: 0.65rem; margin-top: 2px;">Guardando...</div>
            `;
            
            // Pequeño retardo para permitir que el navegador pinte el 0% antes de animar al 90%
            setTimeout(() => {
                const bar = document.getElementById('progressBarSave');
                if(bar) bar.style.width = '90%';
            }, 50);
        }
    }

    async function guardarNotaAuto() {
        // Usamos activeNoteId preferentemente. Si no existe en memoria, buscamos en el DOM.
        // Esto evita que, si el DOM tarda en actualizarse, se envíe un ID vacío.
        const id = activeNoteId || document.getElementById('noteId').value;
        
        const title = document.getElementById('noteTitle').value.trim();
        const contentHtml = quill.root.innerHTML;
        const contentText = quill.getText(); // Texto plano para búsquedas
        const materiaId = selectMateria ? selectMateria.value : (selectMateriaMobile ? selectMateriaMobile.value : '');

        const drawingData = signaturePad ? signaturePad.toData() : [];
        const isDrawingEmpty = drawingData.length === 0;

        // Validación: No guardar notas vacías nuevas (debe tener título, texto o dibujo)
        if (!id && !contentText.trim() && !title && isDrawingEmpty) {
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

        // Combinar HTML y Datos de Dibujo (JSON) en el mismo campo
        const drawingDataStr = JSON.stringify(drawingData);
        const combinedContent = contentHtml + '<!--DRAWING_JSON_START-->' + drawingDataStr + '<!--DRAWING_JSON_END-->';

        const nota = {
            id: id ? id : null, // Si hay ID es edición
            titulo: title,
            contenido: combinedContent, // Guardamos HTML + Dibujo
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
                    activeNoteId = result.id; // Actualizar memoria INMEDIATAMENTE
                    document.getElementById('noteId').value = result.id;
                }
                // Feedback de éxito
                if (saveStatus) {
                    saveStatus.innerHTML = `
                        <div class="progress" style="height: 4px; width: 60px; min-width: 60px; background-color: #e9ecef;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 100%; transition: none;"></div>
                        </div>
                        <div class="text-success text-center" style="font-size: 0.65rem; margin-top: 2px;">Guardado</div>
                    `;
                    
                    // Limpiar mensaje después de 2 segundos
                    setTimeout(() => {
                        if(saveStatus && saveStatus.innerHTML.includes('Guardado')) {
                            saveStatus.innerHTML = '';
                            saveStatus.className = 'd-flex align-items-center me-2';
                        }
                    }, 2000);
                }
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
        if(!saveStatus || !esError) return;
        
        // En caso de error mostramos texto rojo sin barra
        saveStatus.className = 'me-2 d-block text-end';
        saveStatus.innerHTML = `<span class="text-danger small fw-bold"><i class="fas fa-exclamation-circle me-1"></i>${mensaje}</span>`;
    }

    // 5. Cargar Materias (Backend Integration)
    async function cargarMaterias() {
        try {
            const response = await fetch('../backend/materias/materias_controller.php?accion=listar');
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

            // Separar HTML y Dibujo para la vista previa
            let cleanContent = nota.contenido || '';
            let drawingDataJson = '[]';
            const separatorStart = '<!--DRAWING_JSON_START-->';
            const separatorEnd = '<!--DRAWING_JSON_END-->';

            if (cleanContent.includes(separatorStart)) {
                const parts = cleanContent.split(separatorStart);
                cleanContent = parts[0];
                const jsonPart = parts[1].split(separatorEnd)[0];
                if (jsonPart && jsonPart.length > 2) { // Asegurarse de que no sea solo '[]'
                    drawingDataJson = jsonPart;
                }
            }
            
            // Corrección de fecha: Usar fecha_actualizacion o fecha_creacion del backend
            let fechaDisplay = 'Sin fecha';
            if (nota.fecha_actualizacion) {
                fechaDisplay = new Date(nota.fecha_actualizacion).toLocaleDateString();
            } else if (nota.fecha_creacion) {
                fechaDisplay = new Date(nota.fecha_creacion).toLocaleDateString();
            }

            const isSelected = selectedNoteIds.has(nota.id);
            const selectionClass = isSelectionMode ? 'selection-mode' : '';
            const selectedCardClass = isSelected ? 'selected' : '';
            const checkClass = isSelected ? 'checked' : '';
            const checkIcon = isSelected ? 'fas fa-check-circle' : 'far fa-circle';

            return `
                <div class="note-card ${bgClass} ${selectionClass} ${selectedCardClass}" data-id="${nota.id}" onclick="abrirNotaParaEditar(${nota.id})">
                    <div class="note-select-btn ${checkClass}" onclick="event.stopPropagation(); toggleNoteSelection(${nota.id})">
                        <i class="${checkIcon}"></i>
                    </div>
                    ${materiaBadge}
                    ${nota.titulo ? `<h3 class="note-title">${escapeHtml(nota.titulo)}</h3>` : ''}
                    <div class="note-content">
                        ${cleanContent}
                        ${drawingDataJson.length > 2 ? `<canvas class="note-drawing-preview" data-drawing-data='${drawingDataJson}'></canvas>` : ''}
                    </div>
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

        renderPreviewDrawings();
    }

    // 7.1 Exportar Selección a PDF (PDF Combinado con saltos de página)
    function descargarSeleccionPDF() {
        if (selectedNoteIds.size === 0) return;

        const btnExport = document.getElementById('btnExportSelected');
        const originalContent = btnExport.innerHTML;
        btnExport.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btnExport.disabled = true;

        // Construir contenido combinado
        let combinedContent = '';
        const ids = Array.from(selectedNoteIds);
        
        // 1. Generar Índice (SOLO SI HAY MÁS DE 1 NOTA)
        if (ids.length > 1) {
            let indexContent = `
                <div class="index-container">
                    <div style="text-align: center; margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 1rem;">
                        <h1>Índice de Notas</h1>
                        <p style="color: #666; margin: 0;">Generado el ${new Date().toLocaleDateString()}</p>
                    </div>
                    <ul style="list-style: none; padding: 0;">
            `;

            ids.forEach((id, index) => {
                const nota = notasCache.find(n => n.id === id);
                if (!nota) return;
                const title = nota.titulo ? escapeHtml(nota.titulo) : 'Sin Título';
                // Estimación: Índice es Pág 1, Nota 1 es Pág 2, etc.
                const pageNum = index + 2;
                
                indexContent += `
                    <li style="margin-bottom: 1rem;">
                        <a href="#print-note-${index}" style="text-decoration: none; color: inherit; display: flex; align-items: baseline; border-bottom: 1px dotted #ccc; padding-bottom: 5px;">
                            <span style="font-weight: bold; margin-right: 10px; width: 25px;">${index + 1}.</span>
                            <span style="flex: 1; font-weight: 500;">${title}</span>
                            <span style="font-size: 0.9rem; margin-left: 10px; color: #666;">Pág. ${pageNum}</span>
                        </a>
                    </li>`;
            });

            indexContent += `</ul></div><div style="page-break-after: always;"></div>`;
            combinedContent += indexContent;
        }

        ids.forEach((id, index) => {
            const nota = notasCache.find(n => n.id === id);
            if (!nota) return;

            // Limpiar contenido (quitar JSON de dibujo si existe)
            let content = nota.contenido || '';
            if (content.includes('<!--DRAWING_JSON_START-->')) {
                content = content.split('<!--DRAWING_JSON_START-->')[0];
            }

            // Título
            // Numerar solo si hay múltiples notas
            const prefix = ids.length > 1 ? `${index + 1}. ` : '';
            const title = nota.titulo ? `<h1>${prefix}${escapeHtml(nota.titulo)}</h1>` : `<h1>${prefix}Sin Título</h1>`;
            
            // Salto de página (excepto en el último elemento)
            const pageBreak = index < ids.length - 1 ? '<div style="page-break-after: always;"></div>' : '';

            combinedContent += `
                <div id="print-note-${index}" class="note-print-container">
                    ${title}
                    <div class="ql-editor" style="padding: 0 !important;">
                        ${content}
                    </div>
                </div>
                ${pageBreak}
            `;
        });

        // Determinar nombre del archivo PDF inteligentemente
        // 1. Si es múltiple: "Notas_Combinadas_YYYY-MM-DD"
        let pdfFilename = `Notas_Combinadas_${new Date().toISOString().slice(0, 10)}`;
        
        // 2. Si es solo una nota seleccionada: Usar su título original
        if (ids.length === 1) {
            const singleNote = notasCache.find(n => n.id === ids[0]);
            if (singleNote) pdfFilename = singleNote.titulo || 'Nota_sin_titulo';
        }

        // Reutilizar lógica de iframe de descargarPDF pero con contenido múltiple
        crearIframeImpresion(pdfFilename, combinedContent, () => {
            btnExport.innerHTML = originalContent;
            btnExport.disabled = false;
            showToast('Generando PDF combinado...', 'success');
        });
    }

    // 7. Generar PDF (Versión Impresión Nativa para Texto Real)
    function descargarPDF() {
        // Usamos un iframe oculto y window.print() para garantizar que el texto sea seleccionable (vectorial)
        // y no una imagen como sucede con html2canvas/html2pdf.
        
        const titleInput = document.getElementById('noteTitle');
        const rawTitle = titleInput.value.trim() || 'Nota_sin_titulo';
        const editorContent = document.querySelector('.ql-editor').innerHTML;
        
        // Construimos el HTML idéntico al de la exportación masiva
        const singleNoteContent = `
            <div class="note-print-container">
                <h1>${escapeHtml(rawTitle)}</h1>
                <div class="ql-editor" style="padding: 0 !important;">${editorContent}</div>
            </div>
        `;
        
        crearIframeImpresion(rawTitle, singleNoteContent);
    }

    function crearIframeImpresion(title, htmlContent, callback = null) {
        
        // Feedback visual (Loading)
        // (Opcional: Si se llama desde el botón individual, desactivarlo)
        if(document.getElementById('btnDownloadPdf')) {
             document.getElementById('btnDownloadPdf').disabled = true;
        }

        // TRUCO: Guardar título original y cambiarlo temporalmente.
        // Esto fuerza al navegador a usar este texto como nombre de archivo predeterminado.
        const originalPageTitle = document.title;
        document.title = title;

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
                @page { size: A4; margin: 0; }
                body { 
                    padding: 0 2cm; /* Solo márgenes laterales en el body */
                    margin: 0;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    color: #000;
                    background: #fff;
                }
                .note-print-container, .index-container {
                    padding-top: 2cm; /* Cada nota empuja su propio inicio hacia abajo */
                    padding-bottom: 2cm;
                    position: relative;
                }
                h1 { 
                    margin-bottom: 3rem; 
                    margin-top: 0;
                    border-bottom: none;
                    padding-bottom: 0;
                    font-size: 1.8rem;
                    font-weight: 700;
                    line-height: 1.2;
                }
                .ql-container.ql-snow { border: none !important; }
                .ql-editor { padding: 0 !important; overflow: visible !important; }
                img { max-width: 100%; height: auto; }

                /* Asegurar que los saltos de página funcionen */
                div[style*="page-break-after: always"] { page-break-after: always !important; display: block; height: 1px; }
            </style>
        `;

        doc.open();
        doc.write(`
            <!DOCTYPE html>
            <html lang="es">
            <head>
                <meta charset="UTF-8">
                <title>${title}</title>
                ${styles}
            </head>
            <body>
                <div class="container-fluid">
                    <div class="ql-container ql-snow">
                         ${htmlContent}
                    </div>
                </div>
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            try {
                                // Enfocar iframe para asegurar contexto correcto
                                window.focus();
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
            document.title = originalPageTitle; // Restaurar el título original de la web

            if(document.getElementById('btnDownloadPdf')) {
                 document.getElementById('btnDownloadPdf').disabled = false;
            }
            
            if(callback) callback();
            else showToast('Selecciona "Guardar como PDF" en la ventana de impresión.', 'info');
            
            // Limpiar iframe después de un tiempo prudencial
            setTimeout(() => {
                if(document.body.contains(iframe)) document.body.removeChild(iframe);
            }, 60000);
        }, 1000);
    }

    // --- FUNCIONES BACKEND ---

    async function cargarNotasBackend() {
        try {
            const response = await fetch('../backend/anotaciones/anotaciones_controller.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({accion: 'listar'})
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
        const response = await fetch('../backend/anotaciones/anotaciones_controller.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(nota)
        });
        return await response.json();
    }

    window.eliminarNota = function(id) {
        // FIX: Asegurarse de que el input exista.
        let idInput = document.getElementById('idNotaEliminar');
        if (!idInput) {
            const modalFooter = document.querySelector('#modalEliminarNota .modal-footer');
            if (modalFooter) {
                const newInput = document.createElement('input');
                newInput.type = 'hidden';
                newInput.id = 'idNotaEliminar';
                modalFooter.appendChild(newInput);
                idInput = newInput;
            }
        }
        if (idInput) idInput.value = id;
        
        // Restaurar mensaje original para eliminación individual
        const modalBody = document.querySelector('#modalEliminarNota .modal-body');
        if(modalBody) {
            modalBody.innerHTML = '¿Estás seguro de que deseas eliminar esta nota?';
        }
        
        modalEliminar.show();
    };

    btnConfirmarEliminar.addEventListener('click', async function() {
        const idInput = document.getElementById('idNotaEliminar');
        const id = idInput ? idInput.value : null;
        
        // CASO 1: Eliminación Individual (Si hay un ID en el input hidden)
        if (id) {
            fetch('../backend/anotaciones/anotaciones_controller.php', {
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
        } 
        // CASO 2: Eliminación por Lote (Si no hay ID pero hay selección)
        else if (selectedNoteIds.size > 0) {
            try {
                const response = await fetch('../backend/anotaciones/anotaciones_controller.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        accion: 'eliminar_lote',
                        ids: Array.from(selectedNoteIds)
                    })
                });
                const result = await response.json();
                if (result.status === 'success') {
                    modalEliminar.hide();
                    showToast(result.message, 'success');
                    toggleSelectionMode(); // Salir del modo selección
                    cargarNotasBackend(); // Recargar lista
                } else {
                    showToast(result.message || 'Error al eliminar', 'error');
                }
            } catch (e) {
                showToast('Error de conexión', 'error');
            }
        }
    });

    // Función para abrir nota existente en el modal
    window.abrirNotaParaEditar = function(id) {
        // Si estamos en modo selección, togglear selección en lugar de abrir
        if (isSelectionMode) {
            toggleNoteSelection(id);
            return;
        }

        const nota = notasCache.find(n => n.id == id);
        if (!nota) return;

        activeNoteId = nota.id; // Guardar ID en memoria al abrir
        document.getElementById('noteId').value = nota.id;
        document.getElementById('noteTitle').value = nota.titulo;
        
        // Separar HTML y Dibujo
        let content = nota.contenido || '';
        let drawingData = [];
        const separatorStart = '<!--DRAWING_JSON_START-->';
        const separatorEnd = '<!--DRAWING_JSON_END-->';

        if (content.includes(separatorStart)) {
            const parts = content.split(separatorStart);
            content = parts[0];
            const jsonPart = parts[1].split(separatorEnd)[0];
            try { drawingData = JSON.parse(jsonPart); } catch(e) {}
        }

        // FIX: Usar la API de Quill para establecer el contenido de forma robusta
        // y limpiar el historial para evitar que se mezclen acciones entre notas.
        const delta = quill.clipboard.convert(content);
        quill.setContents(delta, 'silent');
        quill.history.clear();

        selectMateria.value = nota.materia_id || '';
        if(selectMateriaMobile) selectMateriaMobile.value = nota.materia_id || '';
        selectedColor = nota.color || 'white';
        
        updateColorSelection();
        
        // REQUERIMIENTO: Asegurar que siempre inicie en la sección de Estilo (Texto)
        const tabEstilo = document.querySelector('.btn-nav-icon[data-tab="estilo"]');
        if(tabEstilo) {
            // Desactivar todos los botones de navegación
            document.querySelectorAll('.btn-nav-icon').forEach(b => b.classList.remove('active'));
            // Activar botón de estilo
            tabEstilo.classList.add('active');
            
            // Resetear visibilidad de paneles
            const panelEdicion = document.getElementById('panel-edicion');
            const panelDraw = document.getElementById('panel-draw');
            
            if(panelEdicion) panelEdicion.classList.remove('d-none');
            if(panelDraw) panelDraw.classList.add('d-none');
            if(editorPage) editorPage.classList.remove('drawing-mode');
        }

        modalInstance.show();

        // Cargar dibujo después de que el modal se muestre (para asegurar dimensiones correctas)
        const onShown = () => {
            if(signaturePad) {
                signaturePad.clear();

                if(drawingData.length > 0) {
                    signaturePad.fromData(drawingData);
                    // FIX: Establecer el dibujo cargado como el estado base (Paso 0) del historial.
                    // Esto impide que el primer "Deshacer" borre todo el contenido previo.
                    drawingHistory = [JSON.parse(JSON.stringify(drawingData))];
                    drawingHistoryStep = 0; 
                } else {
                    // Si no hay dibujo previo, iniciamos con lienzo vacío como base
                    drawingHistory = [[]];
                    drawingHistoryStep = 0;
                }
            }
        };
        modalNota.addEventListener('shown.bs.modal', onShown, { once: true });
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