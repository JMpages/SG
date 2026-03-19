document.addEventListener('DOMContentLoaded', () => {
    const app = {
        // Estado de la aplicación
        state: {
            materia: null,
            notas: {}, // { 'criterioId-evalNum': { calificacion: 5.0, nota_maxima: 100 } }
            isLoading: true,
            isSaving: false,
        },

        // Elementos del DOM
        elements: {
            headerPlaceholder: document.getElementById('materia-header-placeholder'),
            criteriosContainer: document.getElementById('criterios-container'),
            resumenCriteriosContainer: document.getElementById('resumen-criterios-container'),
            notaFinalTotal: document.getElementById('nota-final-total'),
            barraNotaFinal: document.getElementById('barra-nota-final'),
            btnGuardar: document.getElementById('btn-guardar-notas'),
            btnGuardarMobile: document.getElementById('btn-guardar-notas-mobile'),

            // Elementos flotantes para móvil
            notaFinalTotalMobile: document.getElementById('nota-final-total-mobile'),
            barraNotaFinalMobile: document.getElementById('barra-nota-final-mobile'),
            btnGuardarMobileFloating: document.getElementById('btn-guardar-notas-mobile-floating'),
        },

        // Inicialización
        init() {
            this.addEventListeners();
            this.loadMateriaData();
        },

        // Event Listeners
        addEventListeners() {
            this.elements.criteriosContainer.addEventListener('input', this.handleNotaInputChange.bind(this));
            this.elements.criteriosContainer.addEventListener('keydown', this.handleNotaInputKeydown.bind(this));
            this.elements.criteriosContainer.addEventListener('click', this.handleCriterioClick.bind(this));
            this.elements.criteriosContainer.addEventListener('change', this.handleCriterioChange.bind(this));
            
            // Botones de guardado
            if (this.elements.btnGuardar) {
                this.elements.btnGuardar.addEventListener('click', this.handleGuardarNotas.bind(this));
            }
            if (this.elements.btnGuardarMobile) {
                this.elements.btnGuardarMobile.addEventListener('click', this.handleGuardarNotas.bind(this));
            }
            if (this.elements.btnGuardarMobileFloating) {
                this.elements.btnGuardarMobileFloating.addEventListener('click', this.handleGuardarNotas.bind(this));
            }

            // Listener para animación del colapso (icono giratorio)
            const collapseElement = document.getElementById('resumen-criterios-collapse');
            if (collapseElement) {
                collapseElement.addEventListener('show.bs.collapse', function () {
                    const icon = document.querySelector('[data-bs-target="#resumen-criterios-collapse"] .fa-chevron-down');
                    if(icon) icon.style.transform = 'rotate(180deg)';
                    if(icon) icon.style.transition = 'transform 0.3s ease';
                });
                collapseElement.addEventListener('hide.bs.collapse', function () {
                    const icon = document.querySelector('[data-bs-target="#resumen-criterios-collapse"] .fa-chevron-down');
                    if(icon) icon.style.transform = 'rotate(0deg)';
                });
            }
        },

        // Cargar datos de la materia
        async loadMateriaData() {
            this.state.isLoading = true;
            this.render();

            try {
                const response = await fetch(`../backend/materias/materias_controller.php?accion=detalle&id=${MATERIA_ID}`);
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor.');
                }
                const result = await response.json();

                if (result.status === 'success') {
                    this.state.materia = result.data;
                    this.populateInitialNotas();
                    this.state.isLoading = false;
                    this.render();
                    this.updateSummary();
                    this.handleDeepLink(); // Verificar si hay que enfocar una nota específica
                } else {
                    throw new Error(result.message || 'No se pudieron cargar los datos.');
                }
            } catch (error) {
                this.state.isLoading = false;
                this.render();
                showToast(error.message, 'error');
            }
        },

        // Manejar enlace profundo a una nota específica
        handleDeepLink() {
            const params = new URLSearchParams(window.location.search);
            const criterioId = params.get('criterio');
            const num = params.get('num');

            if (criterioId && num) {
                // Pequeño delay para asegurar renderizado
                setTimeout(() => {
                    const inputId = `nota-${criterioId}-${num}`;
                    const input = document.getElementById(inputId);
                    if (input) {
                        input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        input.focus();
                        // Efecto visual de resaltado temporal
                        input.classList.add('bg-warning', 'bg-opacity-25');
                        setTimeout(() => input.classList.remove('bg-warning', 'bg-opacity-25'), 2000);
                    }
                }, 500);
            }
        },

        // Poblar el estado inicial de las notas desde los datos cargados
        populateInitialNotas() {
            this.state.notas = {};
            if (!this.state.materia || !this.state.materia.criterios) return;

            this.state.materia.criterios.forEach(criterio => {
                for (const [evalNum, notaData] of Object.entries(criterio.notas)) {
                    const key = `${criterio.id}-${evalNum}`;
                    this.state.notas[key] = {
                        calificacion: notaData.real,
                        nota_maxima: notaData.nota_maxima || criterio.nota_maxima
                    };
                }
            });
        },

        // Renderizar la UI completa
        render() {
            this.renderHeader();
            this.renderCriterios();
        },

        // Renderizar el header de la materia
        renderHeader() {
            if (this.state.isLoading || !this.state.materia) {
                this.elements.headerPlaceholder.innerHTML = '';
                return;
            }
            const { nombre } = this.state.materia;
            this.elements.headerPlaceholder.innerHTML = `
                <div class="materia-header">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="materias.php">Mis Materias</a></li>
                            <li class="breadcrumb-item active" aria-current="page">${escapeHtml(nombre)}</li>
                        </ol>
                    </nav>
                    <h1><i class="fas fa-book-open me-3"></i>${escapeHtml(nombre)}</h1>
                </div>
            `;
        },

        // Renderizar las tarjetas de criterios y sus notas
        renderCriterios() {
            if (this.state.isLoading) {
                this.elements.criteriosContainer.innerHTML = `
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                    </div>`;
                return;
            }

            if (!this.state.materia || this.state.materia.criterios.length === 0) {
                this.elements.criteriosContainer.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Esta materia no tiene criterios de evaluación definidos. Puedes agregarlos editando la materia.
                    </div>`;
                return;
            }

            this.elements.criteriosContainer.innerHTML = this.state.materia.criterios.map(criterio => {
                const evaluacionesHtml = Array.from({ length: criterio.cantidad_evaluaciones }, (_, i) => {
                    const evalNum = i + 1;
                    const key = `${criterio.id}-${evalNum}`;
                    const notaData = this.state.notas[key] || {};
                    const valor = (notaData.calificacion !== null && notaData.calificacion !== undefined) ? notaData.calificacion : '';
                    const notaMaxVal = parseFloat(notaData.nota_maxima) || parseFloat(criterio.nota_maxima) || 100;

                    // Opciones comunes para nota máxima
                    const commonMaxOptions = [5, 10, 20, 50, 100];
                    if (!commonMaxOptions.includes(notaMaxVal)) commonMaxOptions.push(notaMaxVal);
                    commonMaxOptions.sort((a, b) => a - b);

                    const percentage = valor !== '' ? (parseFloat(valor) / notaMaxVal) * 100 : 0;
                    const statusClass = percentage >= 70 ? 'success' : (percentage >= 60 ? 'warning' : 'danger');

                    return `
                        <div class="evaluacion-row mb-3 p-3 shadow-sm border ${valor !== '' ? 'has-value' : ''}" 
                             id="row-${key}">
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <label for="nota-${key}" class="form-label small text-muted mb-0 fw-bold d-flex align-items-center">
                                    <span class="eval-number-badge me-2">${evalNum}</span>
                                    <span class="text-truncate">${escapeHtml(criterio.nombre)}</span>
                                </label>
                                <div class="badge-status-container">
                                    ${valor !== '' ? `<span class="badge badge-status-${statusClass}">${percentage.toFixed(0)}%</span>` : ''}
                                </div>
                            </div>

                            <div class="input-group grade-input-group-premium">
                                <input type="number" class="form-control nota-input shadow-none" 
                                       id="nota-${key}" 
                                       data-criterio-id="${criterio.id}" 
                                       data-eval-num="${evalNum}"
                                       data-nota-maxima="${notaMaxVal}"
                                       placeholder="0.0" 
                                       min="0" max="${notaMaxVal * 5}" step="0.01" 
                                       value="${valor}"
                                       style="font-weight: 800; font-size: 1.1rem; padding: 0.5rem 0.75rem; background-color: var(--input-bg); color: var(--text-primary); border: none;">
                                
                                <span class="input-group-text px-2" style="border: none; opacity: 0.4; font-weight: 300; background-color: var(--input-bg); color: var(--text-muted);">/</span>
                                
                                <div class="scale-selector-wrapper">
                                    <select class="form-select scale-select-native" data-key="${key}">
                                        ${commonMaxOptions.map(opt => `
                                            <option value="${opt}" ${opt === notaMaxVal ? 'selected' : ''}>${opt}</option>
                                        `).join('')}
                                        <option value="custom">Otro...</option>
                                    </select>
                                    <i class="fas fa-caret-down scale-select-icon"></i>
                                </div>
                            </div>

                            <!-- Barra de progreso individual -->
                            <div class="eval-progress-container mt-2">
                                <div class="eval-progress-bar bg-${statusClass}" style="width: ${Math.min(percentage, 100)}%"></div>
                            </div>
                        </div>
                    `;
                }).join('');

                return `
                    <div class="criterio-card shadow-sm border-0 mb-4 h-100">
                        <div class="criterio-card-header d-flex justify-content-between align-items-center p-3 border-bottom">
                            <h3 class="h6 mb-0 fw-bold"><i class="fas fa-list-check me-2 text-primary"></i>${escapeHtml(criterio.nombre)}</h3>
                            <span class="badge bg-primary rounded-pill px-3">${parseFloat(criterio.porcentaje)}% del total</span>
                        </div>
                        <div class="criterio-card-body p-3">
                            ${evaluacionesHtml}
                            <div class="d-grid mt-2">
                                <button class="btn btn-sm btn-outline-primary btn-add-eval" data-criterio-id="${criterio.id}">
                                    <i class="fas fa-plus me-1"></i> Añadir Nota
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        },

        // Actualizar el resumen de notas
        updateSummary() {
            if (this.state.isLoading || !this.state.materia) return;

            let notaFinalTotal = 0;
            let porcentajeTotalCubierto = 0;

            const resumenCriteriosHtml = this.state.materia.criterios.map(criterio => {
                const notasDelCriterio = [];
                let sumaNotas = 0;
                let notasValidas = 0;

                for (let i = 1; i <= criterio.cantidad_evaluaciones; i++) {
                    const key = `${criterio.id}-${i}`;
                    const notaData = this.state.notas[key];
                    if (notaData && notaData.calificacion !== undefined && notaData.calificacion !== null && notaData.calificacion !== '') {
                        const calif = parseFloat(notaData.calificacion);
                        const max = parseFloat(notaData.nota_maxima) || parseFloat(criterio.nota_maxima) || 100;
                        if (!isNaN(calif)) {
                            // Normalizar a base 100 para promediar correctamente
                            sumaNotas += (calif / max) * 100;
                            notasValidas++;
                        }
                    }
                }

                // El promedio del criterio ahora es normalizado a 100
                const promedioNormalizado = notasValidas > 0 ? sumaNotas / notasValidas : 0;
                const contribucionFinal = (promedioNormalizado * parseFloat(criterio.porcentaje)) / 100;

                if (notasValidas > 0) {
                    notaFinalTotal += contribucionFinal;
                    porcentajeTotalCubierto += parseFloat(criterio.porcentaje);
                }

                // Para visualización volvemos a la escala del criterio
                const criterioMax = parseFloat(criterio.nota_maxima) || 100;
                const promedioVisual = (promedioNormalizado * criterioMax) / 100;

                // Determinar color del promedio
                let colorPromedio = 'text-muted';
                if (notasValidas > 0) {
                    const ratio = promedioNormalizado / 100;
                    if (ratio >= 0.9) colorPromedio = 'text-success fw-bold';
                    else if (ratio >= 0.7) colorPromedio = 'text-primary fw-bold';
                    else if (ratio >= 0.6) colorPromedio = 'text-warning fw-bold';
                    else colorPromedio = 'text-danger fw-bold';
                }

                return `
                    <div class="resumen-criterio-item">
                        <div class="nombre">${escapeHtml(criterio.nombre)} (${parseFloat(criterio.porcentaje)}%)</div>
                        <div class="nota-promedio ${colorPromedio}">${promedioVisual.toFixed(2)} / ${criterioMax}</div>
                    </div>
                `;
            }).join('');

            this.elements.resumenCriteriosContainer.innerHTML = resumenCriteriosHtml;

            // La nota final se calcula sobre la base de lo calificado.
            // Si se ha calificado el 50% del curso, la nota final es sobre ese 50%.
            // Para proyectar a 100, se podría hacer (notaFinalTotal / porcentajeTotalCubierto) * 100, pero puede ser confuso.
            // Mostraremos la nota acumulada.

            // Determinar letra según escala universitaria
            let letra = '';
            let estadoTexto = '';
            let badgeClass = '';
            let estadoClass = '';

            if (notaFinalTotal >= 91) {
                letra = 'A'; estadoTexto = 'Excelente'; estadoClass = 'text-success';
            } else if (notaFinalTotal >= 81) {
                letra = 'B'; estadoTexto = 'Bueno'; estadoClass = 'text-primary';
            } else if (notaFinalTotal >= 71) {
                letra = 'C'; estadoTexto = 'Suficiente'; estadoClass = 'text-info';
            } else if (notaFinalTotal >= 61) {
                letra = 'D'; estadoTexto = 'Insuficiente'; estadoClass = 'text-warning';
            } else {
                letra = 'F'; estadoTexto = 'Reprobado'; estadoClass = 'text-danger';
            }

            const notaFinalContent = `${notaFinalTotal.toFixed(2)} <span class="badge bg-white ${estadoClass} ms-2" style="font-size: 0.5em; vertical-align: middle;">${letra}</span>`;
            
            if (this.elements.notaFinalTotal) this.elements.notaFinalTotal.innerHTML = notaFinalContent;
            if (this.elements.notaFinalTotalMobile) this.elements.notaFinalTotalMobile.innerHTML = notaFinalContent;
            
            const widthPorcentaje = (notaFinalTotal / 100) * 100;
            if (this.elements.barraNotaFinal) this.elements.barraNotaFinal.style.width = `${widthPorcentaje}%`;
            if (this.elements.barraNotaFinalMobile) this.elements.barraNotaFinalMobile.style.width = `${widthPorcentaje}%`;
        },

        handleNotaInputChange(e) {
            if (e.target.matches('input.form-control')) {
                const input = e.target;
                const criterioId = input.dataset.criterioId;
                const evalNum = input.dataset.evalNum;
                const key = `${criterioId}-${evalNum}`;
                const notaMaxima = parseFloat(input.dataset.notaMaxima) || 100;

                let value = input.value;
                const parts = value.split('.');
                if (parts.length > 1 && parts[1].length > 2) {
                    value = `${parts[0]}.${parts[1].substring(0, 2)}`;
                    input.value = value;
                }

                let valor = value === '' ? null : parseFloat(value);

                if (valor !== null && !isNaN(valor)) {
                    if (valor > notaMaxima) {
                        valor = notaMaxima;
                        input.value = valor.toFixed(2);
                    } else if (valor < 0) {
                        valor = 0;
                        input.value = valor.toFixed(2);
                    }
                    
                    // Feedback visual instantáneo en el input
                    const ratio = valor / notaMaxima;
                    input.classList.remove('is-valid', 'is-invalid', 'border-warning');
                    if (ratio >= 0.7) input.classList.add('is-valid');
                    else if (ratio >= 0.6) input.classList.add('border-warning');
                    else input.classList.add('is-invalid');
                } else {
                    input.classList.remove('is-valid', 'is-invalid', 'border-warning');
                }

                this.state.notas[key] = {
                    ...this.state.notas[key],
                    calificacion: valor
                };
                this.updateSummary();
                
                // Actualizar barra de progreso individual sin re-renderizar todo
                this.updateIndividualProgress(key, valor, notaMaxima);
            }
        },

        updateIndividualProgress(key, valor, notaMaxima) {
            const row = document.getElementById(`row-${key}`);
            const input = document.getElementById(`nota-${key}`);
            if (!row || !input) return;

            const percentage = valor !== null ? (parseFloat(valor) / notaMaxima) * 100 : 0;
            const statusClass = percentage >= 70 ? 'success' : (percentage >= 60 ? 'warning' : 'danger');
            
            const progressBar = row.querySelector('.eval-progress-bar');
            const statusBadgeContainer = row.querySelector('.badge-status-container');
            const evalNumberBadge = row.querySelector('.eval-number-badge');

            if (progressBar) {
                progressBar.style.width = `${Math.min(percentage, 100)}%`;
                progressBar.className = `eval-progress-bar bg-${statusClass}`;
            }

            if (valor !== null && valor !== '') {
                row.classList.add('has-value');
                if (evalNumberBadge) evalNumberBadge.classList.add('bg-primary', 'text-white');
                if (statusBadgeContainer) {
                    statusBadgeContainer.innerHTML = `<span class="badge badge-status-${statusClass}">${percentage.toFixed(0)}%</span>`;
                }
            } else {
                row.classList.remove('has-value');
                if (evalNumberBadge) evalNumberBadge.classList.remove('bg-primary', 'text-white');
                if (statusBadgeContainer) statusBadgeContainer.innerHTML = '';
            }
        },

        handleNotaInputKeydown(e) {
            // Prevenir la entrada de la letra 'e' en los campos numéricos, que es válida para notación científica.
            if (e.target.matches('input[type="number"]') && e.key.toLowerCase() === 'e') {
                e.preventDefault();
            }
        },

        handleCriterioClick(e) {
            const btnAdd = e.target.closest('.btn-add-eval');
            if (btnAdd) {
                this.handleAddEval(parseInt(btnAdd.dataset.criterioId));
                return;
            }
        },

        handleCriterioChange(e) {
            const select = e.target.closest('.scale-select-native');
            if (select) {
                const key = select.dataset.key;
                let val = select.value;
                
                if (val === 'custom') {
                    const customVal = prompt('Ingrese la nota máxima deseada (ej: 80):');
                    const parsed = parseFloat(customVal);
                    if (isNaN(parsed) || parsed <= 0) {
                        showToast('Valor no válido', 'error');
                        // Reset select to previous value
                        const currentMax = this.state.notas[key] ? this.state.notas[key].nota_maxima : 100;
                        select.value = currentMax;
                        return;
                    }
                    val = parsed;
                }
                
                this.updateNotaMax(key, parseFloat(val));
            }
        },

        updateNotaMax(key, nuevoMax) {
            this.state.notas[key] = {
                ...this.state.notas[key],
                nota_maxima: nuevoMax
            };
            
            showToast(`Escala actualizada a /${nuevoMax}`, 'success');
            this.render();
            this.updateSummary();
        },

        handleAddEval(criterioId) {
            const criterio = this.state.materia.criterios.find(c => c.id === criterioId);
            if (!criterio) return;

            criterio.cantidad_evaluaciones++;
            // El renderizado se encargará de mostrar el nuevo input preservando los valores actuales del estado
            this.render();
            this.updateSummary();
            
            // Enfocar el nuevo input
            setTimeout(() => {
                const newKey = `${criterioId}-${criterio.cantidad_evaluaciones}`;
                const input = document.getElementById(`nota-${newKey}`);
                if (input) {
                    input.focus();
                    input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        },

        async handleGuardarNotas() {
            if (this.state.isSaving) return;
            
            this.state.isSaving = true;
            
            // Feedback en botones
            const buttons = [this.elements.btnGuardar, this.elements.btnGuardarMobile, this.elements.btnGuardarMobileFloating].filter(Boolean);
            const originalHTMLs = buttons.map(btn => btn.innerHTML);

            buttons.forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>`;
            });

            const notasPayload = [];
            for (const [key, data] of Object.entries(this.state.notas)) {
                const [criterioId, evalNum] = key.split('-');
                
                notasPayload.push({
                    criterio_id: parseInt(criterioId, 10),
                    numero_evaluacion: parseInt(evalNum, 10),
                    calificacion: data.calificacion,
                    nota_maxima: data.nota_maxima
                });
            }

            try {
                const response = await fetch('../backend/notas/notas_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        accion: 'guardar_calificaciones',
                        materia_id: MATERIA_ID,
                        notas: notasPayload,
                    }),
                });

                const result = await response.json();

                if (response.ok) {
                    showToast(result.message, 'success');
                } else {
                    throw new Error(result.message || 'Error al guardar las notas.');
                }
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.state.isSaving = false;
                buttons.forEach((btn, index) => {
                    btn.disabled = false;
                    btn.innerHTML = originalHTMLs[index];
                });
            }
        },
    };

    // Funciones auxiliares globales
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

    function escapeHtml(text) {
        if (text == null) return '';
        return text.toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Iniciar la aplicación
    app.init();
});