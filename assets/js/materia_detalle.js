document.addEventListener('DOMContentLoaded', () => {
    const app = {
        // Estado de la aplicación
        state: {
            materia: null,
            notas: {}, // { 'criterioId-evalNum': { real: 5.0, simulacion: 4.5 } }
            esModoSimulacion: false,
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
            modoSimulacionSwitch: document.getElementById('modoSimulacion'),
            btnGuardar: document.getElementById('btn-guardar-notas'),
        },

        // Inicialización
        init() {
            this.addEventListeners();
            this.loadMateriaData();
        },

        // Event Listeners
        addEventListeners() {
            this.elements.modoSimulacionSwitch.addEventListener('change', this.handleModoSimulacionToggle.bind(this));
            this.elements.btnGuardar.addEventListener('click', this.handleGuardarNotas.bind(this));
            // Delegación de eventos para los inputs de notas
            this.elements.criteriosContainer.addEventListener('input', this.handleNotaInputChange.bind(this));
            this.elements.criteriosContainer.addEventListener('keydown', this.handleNotaInputKeydown.bind(this));
        },

        // Cargar datos de la materia
        async loadMateriaData() {
            this.state.isLoading = true;
            this.render();

            try {
                const response = await fetch(`../backend/obtener_detalle_materia.php?id=${MATERIA_ID}`);
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
                } else {
                    throw new Error(result.message || 'No se pudieron cargar los datos.');
                }
            } catch (error) {
                this.state.isLoading = false;
                this.render();
                showToast(error.message, 'error');
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
                        real: notaData.real,
                        simulacion: notaData.simulacion,
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
                    const notaActual = this.state.notas[key] ? (this.state.esModoSimulacion ? this.state.notas[key].simulacion : this.state.notas[key].real) : null;
                    const valor = notaActual !== null ? parseFloat(notaActual).toFixed(2) : '';

                    return `
                        <div class="evaluacion-row">
                            <label for="nota-${key}" class="evaluacion-label">${escapeHtml(criterio.nombre)} #${evalNum}</label>
                            <div class="evaluacion-input">
                                <input type="number" class="form-control ${this.state.esModoSimulacion ? 'simulacion' : ''}" 
                                       id="nota-${key}" 
                                       data-criterio-id="${criterio.id}" 
                                       data-eval-num="${evalNum}"
                                       placeholder="-" 
                                       min="0" max="100" step="0.01" 
                                       value="${valor}">
                            </div>
                        </div>
                    `;
                }).join('');

                return `
                    <div class="criterio-card">
                        <div class="criterio-card-header">
                            <h3>${escapeHtml(criterio.nombre)}</h3>
                            <span class="badge bg-primary-subtle text-primary-emphasis rounded-pill">${parseFloat(criterio.porcentaje)}%</span>
                        </div>
                        <div class="criterio-card-body">
                            ${evaluacionesHtml}
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
                    if (notaData) {
                        const nota = this.state.esModoSimulacion ? notaData.simulacion : notaData.real;
                        if (nota !== null && nota !== '') {
                            const valorNota = parseFloat(nota);
                            if (!isNaN(valorNota)) {
                                sumaNotas += valorNota;
                                notasValidas++;
                            }
                        }
                    }
                }

                const promedioCriterio = notasValidas > 0 ? sumaNotas / notasValidas : 0;
                const contribucionFinal = (promedioCriterio * parseFloat(criterio.porcentaje)) / 100;
                
                if (notasValidas > 0) {
                    notaFinalTotal += contribucionFinal;
                    porcentajeTotalCubierto += parseFloat(criterio.porcentaje);
                }

                return `
                    <div class="resumen-criterio-item">
                        <div class="nombre">${escapeHtml(criterio.nombre)} (${parseFloat(criterio.porcentaje)}%)</div>
                        <div class="nota-promedio">${promedioCriterio.toFixed(2)}</div>
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
            let badgeClass = '';

            if (notaFinalTotal >= 91) {
                letra = 'A';
                badgeClass = 'text-success';
            } else if (notaFinalTotal >= 81) {
                letra = 'B';
                badgeClass = 'text-primary';
            } else if (notaFinalTotal >= 71) {
                letra = 'C';
                badgeClass = 'text-info';
            } else if (notaFinalTotal >= 61) {
                letra = 'D';
                badgeClass = 'text-warning';
            } else {
                letra = 'F';
                badgeClass = 'text-danger';
            }

            this.elements.notaFinalTotal.innerHTML = `${notaFinalTotal.toFixed(2)} <span class="badge bg-white ${badgeClass} ms-2" style="font-size: 0.5em; vertical-align: middle;">${letra}</span>`;
            
            // La barra de progreso representa la nota sobre el máximo posible (100).
            const widthPorcentaje = (notaFinalTotal / 100) * 100;
            this.elements.barraNotaFinal.style.width = `${widthPorcentaje}%`;
        },

        // Manejadores de eventos
        handleModoSimulacionToggle(e) {
            this.state.esModoSimulacion = e.target.checked;
            // Si activamos simulación, copiamos las notas reales a las de simulación si están vacías
            if (this.state.esModoSimulacion) {
                for (const key in this.state.notas) {
                    if (this.state.notas[key].simulacion === null || this.state.notas[key].simulacion === undefined) {
                        this.state.notas[key].simulacion = this.state.notas[key].real;
                    }
                }
            }
            this.renderCriterios();
            this.updateSummary();
        },

        handleNotaInputChange(e) {
            if (e.target.matches('input.form-control')) {
                const input = e.target;
                const criterioId = input.dataset.criterioId;
                const evalNum = input.dataset.evalNum;
                const key = `${criterioId}-${evalNum}`;
                
                // Limitar la entrada para evitar un exceso de decimales.
                let value = input.value;
                const parts = value.split('.');
                if (parts.length > 1 && parts[1].length > 2) {
                    // Si hay más de 2 decimales, truncar la cadena.
                    value = `${parts[0]}.${parts[1].substring(0, 2)}`;
                    input.value = value;
                }
                
                let valor = value === '' ? null : parseFloat(value);
                
                // Validar y limitar el valor de la nota entre 0 y 100
                if (valor !== null && !isNaN(valor)) {
                    if (valor > 100) {
                        valor = 100;
                        input.value = valor.toFixed(2); // Actualiza visualmente el input
                    } else if (valor < 0) {
                        valor = 0;
                        input.value = valor.toFixed(2); // Actualiza visualmente el input
                    }
                }
 
                if (!this.state.notas[key]) {
                    this.state.notas[key] = { real: null, simulacion: null };
                }

                if (this.state.esModoSimulacion) {
                    this.state.notas[key].simulacion = valor;
                } else {
                    this.state.notas[key].real = valor;
                }
                
                this.updateSummary();
            }
        },

        handleNotaInputKeydown(e) {
            // Prevenir la entrada de la letra 'e' en los campos numéricos, que es válida para notación científica.
            if (e.target.matches('input[type="number"]') && e.key.toLowerCase() === 'e') {
                e.preventDefault();
            }
        },

        async handleGuardarNotas() {
            this.state.isSaving = true;
            const originalButtonHTML = this.elements.btnGuardar.innerHTML;
            this.elements.btnGuardar.disabled = true;
            this.elements.btnGuardar.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...`;

            const notasPayload = [];
            for (const [key, value] of Object.entries(this.state.notas)) {
                const [criterioId, evalNum] = key.split('-');
                const nota = this.state.esModoSimulacion ? value.simulacion : value.real;
                
                notasPayload.push({
                    criterio_id: parseInt(criterioId, 10),
                    numero_evaluacion: parseInt(evalNum, 10),
                    calificacion: nota,
                });
            }

            try {
                const response = await fetch('../backend/guardar_notas.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        materia_id: MATERIA_ID,
                        notas: notasPayload,
                        es_simulacion: this.state.esModoSimulacion,
                    }),
                });

                const result = await response.json();

                if (response.ok) {
                    showToast(result.message, 'success');
                    // Si se guardaron notas reales, actualizamos el estado base
                    if (!this.state.esModoSimulacion) {
                        this.populateInitialNotas(); // Esto es para re-sincronizar, pero ya está actualizado.
                    }
                } else {
                    throw new Error(result.message || 'Error al guardar las notas.');
                }

            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                this.state.isSaving = false;
                this.elements.btnGuardar.disabled = false;
                this.elements.btnGuardar.innerHTML = originalButtonHTML;
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