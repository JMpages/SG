document.addEventListener('DOMContentLoaded', () => {
    const app = {
        state: {
            tareas: [],
            materias: [],
            filtroMateria: '',
            filtroEstado: 'pendientes',
            currentDate: new Date(),
            calendarTasks: [],
            currentView: 'list',
            selectedDate: null, // Para saber qué día se seleccionó en el calendario
            calendarMode: 'month' // 'month' or 'week'
        },

        elements: {
            listaTareas: document.getElementById('lista-tareas'),
            filtroMateria: document.getElementById('filtroMateria'),
            filtroEstado: document.getElementById('filtroEstado'),
            modalTarea: new bootstrap.Modal(document.getElementById('modalTarea')),
            formTarea: document.getElementById('formTarea'),
            selectMateriaForm: document.getElementById('tareaMateria'),
            btnGuardar: document.getElementById('btnGuardarTarea'),
            modalTitle: document.getElementById('modalTareaTitle'),
            modalEliminar: new bootstrap.Modal(document.getElementById('modalEliminarTarea')),
            btnConfirmarEliminar: document.getElementById('btnConfirmarEliminar'),
            nombreTareaEliminar: document.getElementById('nombreTareaEliminar'),
            idTareaEliminar: document.getElementById('idTareaEliminar'),
            // Elementos Calendario
            btnVistaLista: document.getElementById('btnVistaLista'),
            btnVistaCalendario: document.getElementById('btnVistaCalendario'),
            calendarView: document.getElementById('calendar-view'),
            filtrosContainer: document.getElementById('filtros-container'),
            calendarGrid: document.getElementById('calendar-grid'),
            calendarMonthYear: document.getElementById('calendar-month-year'),
            prevMonth: document.getElementById('prevMonth'),
            nextMonth: document.getElementById('nextMonth'),
            todayBtn: document.getElementById('todayBtn'),
            btnCalMes: document.getElementById('btnCalMes'),
            btnCalSemana: document.getElementById('btnCalSemana'),
            // Modal Detalle Día
            modalDetalleDia: new bootstrap.Modal(document.getElementById('modalDetalleDia')),
            btnAgregarTareaDia: document.getElementById('btnAgregarTareaDia'),
            // Nuevos elementos de fecha
            tareaDia: document.getElementById('tareaDia'),
            tareaMes: document.getElementById('tareaMes'),
            tareaAnio: document.getElementById('tareaAnio'),
            tareaFechaHidden: document.getElementById('tareaFecha')
        },

        init() {
            this.addEventListeners();
            this.loadMaterias();
            this.loadTareas();
        },

        addEventListeners() {
            this.elements.filtroMateria.addEventListener('change', (e) => {
                this.state.filtroMateria = e.target.value;
                this.loadTareas();
            });

            this.elements.filtroEstado.addEventListener('change', (e) => {
                this.state.filtroEstado = e.target.value;
                this.loadTareas();
            });

            this.elements.btnGuardar.addEventListener('click', () => this.saveTarea());

            this.elements.btnConfirmarEliminar.addEventListener('click', () => this.executeDelete());

            // Limpiar formulario al abrir modal para crear
            document.querySelector('.btn-agregar').addEventListener('click', () => this.prepareCreateTask());

            // Listeners Calendario
            if(this.elements.btnVistaLista) {
                this.elements.btnVistaLista.addEventListener('click', () => this.switchView('list'));
            }
            if(this.elements.btnVistaCalendario) {
                this.elements.btnVistaCalendario.addEventListener('click', () => this.switchView('calendar'));
            }
            if(this.elements.prevMonth) {
                this.elements.prevMonth.addEventListener('click', () => this.changeMonth(-1));
            }
            if(this.elements.nextMonth) {
                this.elements.nextMonth.addEventListener('click', () => this.changeMonth(1));
            }
            if(this.elements.todayBtn) {
                this.elements.todayBtn.addEventListener('click', () => {
                    this.state.currentDate = new Date();
                    this.renderCalendar();
                });
            }
            if(this.elements.btnCalMes) {
                this.elements.btnCalMes.addEventListener('click', () => {
                    this.state.calendarMode = 'month';
                    this.elements.btnCalMes.classList.add('active');
                    this.elements.btnCalSemana.classList.remove('active');
                    this.renderCalendar();
                });
            }
            if(this.elements.btnCalSemana) {
                this.elements.btnCalSemana.addEventListener('click', () => {
                    this.state.calendarMode = 'week';
                    this.elements.btnCalSemana.classList.add('active');
                    this.elements.btnCalMes.classList.remove('active');
                    this.renderCalendar();
                });
            }
            
            if(this.elements.btnAgregarTareaDia) {
                this.elements.btnAgregarTareaDia.addEventListener('click', () => {
                    this.elements.modalDetalleDia.hide();
                    this.prepareCreateTask(this.state.selectedDate);
                });
            }

            // Sincronización de fecha
            const updateHiddenDate = () => {
                const diaRaw = this.elements.tareaDia.value;
                const mes = this.elements.tareaMes.value;
                const anio = this.elements.tareaAnio.value;
                
                if (diaRaw && mes && anio) {
                    const dia = diaRaw.toString().padStart(2, '0');
                    this.elements.tareaFechaHidden.value = `${anio}-${mes}-${dia}`;
                } else {
                    this.elements.tareaFechaHidden.value = '';
                }
            };

            this.elements.tareaDia.addEventListener('input', (e) => {
                if (e.target.value.length > 2) e.target.value = e.target.value.slice(0, 2);
                updateHiddenDate();
            });
            this.elements.tareaMes.addEventListener('change', updateHiddenDate);
            this.elements.tareaAnio.addEventListener('input', (e) => {
                if (e.target.value.length > 4) e.target.value = e.target.value.slice(0, 4);
                updateHiddenDate();
            });
        },

        prepareCreateTask(date = null) {
            this.elements.formTarea.reset();
            document.getElementById('tareaId').value = '';
            this.elements.modalTitle.textContent = 'Nueva Tarea';
            this.elements.btnGuardar.textContent = 'Guardar Tarea';
            
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            const [anioToday, mesToday, diaToday] = todayStr.split('-');

            if(date) {
                const [anio, mes, dia] = date.split('-');
                this.elements.tareaDia.value = dia;
                this.elements.tareaMes.value = mes;
                this.elements.tareaAnio.value = anio;
                this.elements.tareaFechaHidden.value = date;
            } else {
                this.elements.tareaDia.value = diaToday;
                this.elements.tareaMes.value = mesToday;
                this.elements.tareaAnio.value = anioToday;
                this.elements.tareaFechaHidden.value = todayStr;
            }
            
            this.elements.modalTarea.show();
        },

        async loadMaterias() {
            try {
                const response = await fetch('../backend/materias/materias_controller.php?accion=listar');
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.state.materias = result.data;
                    this.populateMateriaSelects();
                }
            } catch (error) {
                console.error('Error cargando materias:', error);
            }
        },

        populateMateriaSelects() {
            const options = this.state.materias.map(m => 
                `<option value="${m.id}">${this.escapeHtml(m.nombre)}</option>`
            ).join('');

            // Filtro
            this.elements.filtroMateria.innerHTML = '<option value="">Todas las materias</option>' + options;
            
            // Formulario Modal
            this.elements.selectMateriaForm.innerHTML = '<option value="">Selecciona una materia</option>' + options;
        },

        async loadTareas() {
            this.elements.listaTareas.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>`;

            try {
                const params = new URLSearchParams({
                    materia_id: this.state.filtroMateria,
                    estado: this.state.filtroEstado
                });

                const response = await fetch(`../backend/tareas/tareas_controller.php?accion=listar&${params}`);
                const result = await response.json();

                if (result.status === 'success') {
                    this.state.tareas = result.data;
                    this.renderTareas();
                } else {
                    this.showToast(result.message, 'error');
                }
            } catch (error) {
                this.elements.listaTareas.innerHTML = '<div class="col-12 text-center text-danger">Error al cargar tareas</div>';
            }
        },

        renderTareas() {
            if (this.state.tareas.length === 0) {
                this.elements.listaTareas.innerHTML = `
                    <div class="col-12 text-center py-5 text-muted">
                        <i class="fas fa-clipboard-check fa-3x mb-3 opacity-50"></i>
                        <p>No hay tareas que coincidan con los filtros.</p>
                    </div>`;
                return;
            }

            this.elements.listaTareas.innerHTML = this.state.tareas.map(tarea => {
                const isVencida = !tarea.completada && new Date(tarea.fecha_entrega) < new Date().setHours(0,0,0,0);
                const isCompletada = tarea.completada == 1;
                const isCalificada = tarea.es_calificada == 1;
                
                let cardClass = 'tarea-card';
                if (isCompletada) cardClass += ' completada';
                else if (isVencida) cardClass += ' vencida';

                return `
                <div class="col-md-6 col-lg-4" id="tarea-col-${tarea.id}">
                    <div class="${cardClass}">
                        <div class="tarea-header">
                            <div class="d-flex align-items-center overflow-hidden">
                                <span class="tarea-materia text-truncate">${this.escapeHtml(tarea.materia_nombre)}</span>
                                ${isCalificada ? `<span class="badge bg-warning text-dark ms-2 flex-shrink-0" style="font-size: 0.65rem; max-width: 45%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="Vinculado a: ${this.escapeHtml((tarea.criterio_nombre || 'Evaluación') + ' ' + (tarea.numero_evaluacion || ''))}"><i class="fas fa-star fa-xs me-1"></i>${this.escapeHtml((tarea.criterio_nombre || 'Evaluación') + ' ' + (tarea.numero_evaluacion || ''))}</span>` : ''}
                            </div>
                            <div class="form-check form-switch ms-2">
                                <input class="form-check-input" type="checkbox" 
                                       onchange="app.toggleCompletada(${tarea.id}, this.checked)" 
                                       ${isCompletada ? 'checked' : ''} 
                                       title="Marcar como completada">
                            </div>
                        </div>
                        <h3 class="tarea-titulo">${this.escapeHtml(tarea.titulo)}</h3>
                        <p class="tarea-descripcion">${this.escapeHtml(tarea.descripcion || 'Sin descripción')}</p>
                        
                        <div class="tarea-fecha mb-3">
                            <i class="far fa-calendar-alt me-1"></i>
                            ${this.formatDate(tarea.fecha_entrega)}
                            ${isVencida ? '<span class="badge bg-danger ms-2">Vencida</span>' : ''}
                        </div>
                        
                        <div class="tarea-footer">
                            ${isCalificada ? `
                            <a href="materia_detalle.php?id=${tarea.materia_id}&criterio=${tarea.criterio_id}&num=${tarea.numero_evaluacion}" class="btn btn-sm btn-success text-white" title="Ir a agregar calificación">
                                <i class="fas fa-plus-circle"></i><span class="d-none d-sm-inline ms-1">Agregar Nota</span>
                            </a>` : ''}
                            <button class="btn btn-sm btn-outline-primary" onclick="app.editTarea(${tarea.id})">
                                <i class="fas fa-edit"></i><span class="d-none d-sm-inline ms-1">Editar</span>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="app.deleteTarea(${tarea.id})">
                                <i class="fas fa-trash-alt"></i><span class="d-none d-sm-inline ms-1">Eliminar</span>
                            </button>
                        </div>
                    </div>
                </div>`;
            }).join('');
        },

        async saveTarea() {
            const formData = new FormData(this.elements.formTarea);
            const data = Object.fromEntries(formData.entries());

            if (!data.titulo || !data.fecha_entrega || !data.materia_id) {
                this.showToast('Por favor completa los campos obligatorios.', 'error');
                return;
            }

            try {
                data.accion = 'guardar';
                const response = await fetch('../backend/tareas/tareas_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.status === 'success') {
                    this.elements.modalTarea.hide();
                    this.showToast(result.message, 'success');
                    if (this.state.currentView === 'calendar') {
                        this.loadCalendarTasks();
                    } else {
                        this.loadTareas();
                    }
                } else {
                    this.showToast(result.message, 'error');
                }
            } catch (error) {
                this.showToast('Error al guardar la tarea.', 'error');
            }
        },

        async toggleCompletada(id, isChecked) {
            try {
                const response = await fetch('../backend/tareas/tareas_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accion: 'marcar', id: id, completada: isChecked })
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    this.showToast(isChecked ? 'Tarea completada' : 'Tarea pendiente', 'success');
                    
                    if (this.state.currentView === 'calendar') {
                        await this.loadCalendarTasks();
                        // Si el modal de detalle está abierto, refrescarlo
                        const modalEl = document.getElementById('modalDetalleDia');
                        if (modalEl && modalEl.classList.contains('show')) {
                            this.openDayDetails(this.state.selectedDate);
                        }
                    } else {
                        this.loadTareas();
                    }
                }
            } catch (error) {
                this.showToast('Error al actualizar estado.', 'error');
            }
        },

        editTarea(id) {
            // Si venimos del modal de detalle, cerrarlo primero
            this.elements.modalDetalleDia.hide();

            const tarea = this.state.tareas.find(t => t.id == id) || this.state.calendarTasks.find(t => t.id == id);
            if (!tarea) return;

            document.getElementById('tareaId').value = tarea.id;
            document.getElementById('tareaTitulo').value = tarea.titulo;
            document.getElementById('tareaMateria').value = tarea.materia_id;
            document.getElementById('tareaDescripcion').value = tarea.descripcion || '';
            
            // Poblar campos de fecha desglosados
            if (tarea.fecha_entrega) {
                const [anio, mes, dia] = tarea.fecha_entrega.split('-');
                this.elements.tareaDia.value = dia;
                this.elements.tareaMes.value = mes;
                this.elements.tareaAnio.value = anio;
                this.elements.tareaFechaHidden.value = tarea.fecha_entrega;
            }

            this.elements.modalTitle.textContent = 'Editar Tarea';
            this.elements.btnGuardar.textContent = 'Actualizar';
            this.elements.modalTarea.show();
        },

        deleteTarea(id) {
            // Si venimos del modal de detalle, cerrarlo primero
            this.elements.modalDetalleDia.hide();

            const tarea = this.state.tareas.find(t => t.id == id) || this.state.calendarTasks.find(t => t.id == id);
            if (!tarea) return;

            this.elements.nombreTareaEliminar.textContent = tarea.titulo;
            this.elements.idTareaEliminar.value = id;
            this.elements.modalEliminar.show();
        },

        async executeDelete() {
            const id = this.elements.idTareaEliminar.value;
            try {
                const response = await fetch('../backend/tareas/tareas_controller.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ accion: 'eliminar', id: id })
                });
                const result = await response.json();

                if (result.status === 'success') {
                    this.elements.modalEliminar.hide();
                    this.showToast(result.message, 'success');
                    
                    if (this.state.currentView === 'calendar') {
                        this.loadCalendarTasks();
                    } else {
                        this.loadTareas();
                    }
                } else {
                    this.showToast(result.message, 'error');
                }
            } catch (error) {
                this.showToast('Error al eliminar tarea.', 'error');
            }
        },

        showToast(msg, type = 'info') {
            document.getElementById('toastMessage').textContent = msg;
            const toastEl = document.getElementById('liveToast');
            new bootstrap.Toast(toastEl).show();
        },

        escapeHtml(text) {
            if (!text) return '';
            return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        },

        formatDate(dateString) {
            const options = { year: 'numeric', month: 'short', day: 'numeric' };
            // Ajustar zona horaria para evitar desfase de día
            const date = new Date(dateString + 'T00:00:00'); 
            return date.toLocaleDateString('es-ES', options);
        },

        // ===== LÓGICA CALENDARIO =====
        switchView(view) {
            this.state.currentView = view;
            if (view === 'list') {
                this.elements.btnVistaLista.classList.add('active');
                this.elements.btnVistaCalendario.classList.remove('active');
                this.elements.listaTareas.classList.remove('d-none');
                if(this.elements.filtrosContainer) this.elements.filtrosContainer.classList.remove('d-none');
                this.elements.calendarView.classList.add('d-none');
                this.loadTareas();
            } else {
                this.elements.btnVistaCalendario.classList.add('active');
                this.elements.btnVistaLista.classList.remove('active');
                this.elements.calendarView.classList.remove('d-none');
                this.elements.listaTareas.classList.add('d-none');
                if(this.elements.filtrosContainer) this.elements.filtrosContainer.classList.add('d-none');
                this.loadCalendarTasks();
            }
        },

        async loadCalendarTasks() {
            try {
                // Cargar todas las tareas (sin filtros de estado) para el calendario
                const response = await fetch('../backend/tareas/tareas_controller.php?accion=listar&estado=todas');
                const result = await response.json();
                
                if(result.status === 'success') {
                    this.state.calendarTasks = result.data;
                    this.renderCalendar();
                }
            } catch (error) {
                console.error('Error cargando tareas para calendario:', error);
            }
        },

        changeMonth(delta) {
            if (this.state.calendarMode === 'month') {
                // Fix: Establecer día a 1 para evitar saltos de mes (ej: 30 Ene -> Feb -> Mar)
                this.state.currentDate.setDate(1);
                this.state.currentDate.setMonth(this.state.currentDate.getMonth() + delta);
            } else {
                this.state.currentDate.setDate(this.state.currentDate.getDate() + (delta * 7));
            }
            this.renderCalendar();
        },

        renderCalendar() {
            const grid = this.elements.calendarGrid;
            const monthYear = this.elements.calendarMonthYear;
            const container = this.elements.calendarView.querySelector('.calendar-container');
            
            if(!grid || !monthYear) return;

            grid.innerHTML = '';
            
            const currentDate = this.state.currentDate;
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            const dayNamesShort = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];
            
            const monthNames = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
            ];
            
            let startDate, endDate;

            if (this.state.calendarMode === 'month') {
                if(container) {
                    container.classList.remove('mode-week');
                    container.classList.add('mode-month');
                }
                grid.classList.remove('week-view');
                monthYear.textContent = `${monthNames[month]} ${year}`;
                
                // Primer día del mes
                const firstDayOfMonth = new Date(year, month, 1);
                const startingDayOfWeek = firstDayOfMonth.getDay(); // 0 = Domingo
                
                // Calcular fecha de inicio (retroceder al domingo anterior)
                startDate = new Date(firstDayOfMonth);
                startDate.setDate(startDate.getDate() - startingDayOfWeek);
                
                // Calcular fecha fin (mostrar 6 semanas completas para cubrir mes + adyacentes)
                endDate = new Date(startDate);
                endDate.setDate(endDate.getDate() + 41);
            } else {
                if(container) {
                    container.classList.remove('mode-month');
                    container.classList.add('mode-week');
                }
                grid.classList.add('week-view');
                // Modo Semana
                const dayOfWeek = currentDate.getDay();
                startDate = new Date(currentDate);
                startDate.setDate(currentDate.getDate() - dayOfWeek);
                
                endDate = new Date(startDate);
                endDate.setDate(endDate.getDate() + 6);
                
                // Texto del header para semana
                const startMonth = monthNames[startDate.getMonth()];
                const endMonth = monthNames[endDate.getMonth()];
                
                if (startDate.getMonth() === endDate.getMonth()) {
                    monthYear.textContent = `${startMonth} ${startDate.getDate()} - ${endDate.getDate()}, ${endDate.getFullYear()}`;
                } else {
                    monthYear.textContent = `${startMonth} ${startDate.getDate()} - ${endMonth} ${endDate.getDate()}, ${endDate.getFullYear()}`;
                }
            }
            
            const today = new Date();
            let loopDate = new Date(startDate);
            
            while (loopDate <= endDate) {
                const dayCell = document.createElement('div');
                dayCell.className = 'calendar-day';
                
                const loopYear = loopDate.getFullYear();
                const loopMonth = loopDate.getMonth();
                const loopDay = loopDate.getDate();
                
                if (loopDay === today.getDate() && loopMonth === today.getMonth() && loopYear === today.getFullYear()) {
                    dayCell.classList.add('today');
                }
                
                if (this.state.calendarMode === 'month' && loopMonth !== month) {
                    dayCell.classList.add('other-month');
                }
                
                const dayNumber = document.createElement('div');
                dayNumber.className = 'day-number';
                dayNumber.textContent = loopDay;
                dayCell.appendChild(dayNumber);
                
                // Eventos
                const dateStr = `${loopYear}-${String(loopMonth + 1).padStart(2, '0')}-${String(loopDay).padStart(2, '0')}`;
                
                // Click en el día para crear tarea
                dayCell.addEventListener('click', () => this.openDayDetails(dateStr));
                dayCell.style.cursor = 'pointer';

                const dayTasks = this.state.calendarTasks.filter(t => t.fecha_entrega === dateStr);
                
                if (dayTasks.length > 0) {
                    const indicatorsContainer = document.createElement('div');
                    indicatorsContainer.className = 'calendar-indicators';
                    
                    dayTasks.forEach(task => {
                        const dot = document.createElement('div');
                        dot.className = 'calendar-dot';
                        if(task.completada == 1) dot.classList.add('completada');
                        else if(new Date(task.fecha_entrega + 'T23:59:59') < today) dot.classList.add('vencida');
                        indicatorsContainer.appendChild(dot);
                    });
                    dayCell.appendChild(indicatorsContainer);
                }
                
                grid.appendChild(dayCell);
                
                // Siguiente día
                loopDate.setDate(loopDate.getDate() + 1);
            }
        },

        openDayDetails(dateStr) {
            this.state.selectedDate = dateStr;
            const tasks = this.state.calendarTasks.filter(t => t.fecha_entrega === dateStr);
            
            // Actualizar título del modal
            const dateObj = new Date(dateStr + 'T00:00:00');
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const fechaFormateada = dateObj.toLocaleDateString('es-ES', options);
            document.getElementById('modalDetalleDiaTitle').textContent = fechaFormateada.charAt(0).toUpperCase() + fechaFormateada.slice(1);
            
            // Renderizar lista
            const container = document.getElementById('detalleDiaLista');
            container.innerHTML = '';
            
            if (tasks.length === 0) {
                container.innerHTML = '<div class="text-center empty-day-message py-4"><i class="far fa-calendar-check fa-2x mb-2"></i><p class="m-0 small">No hay tareas para este día.</p></div>';
            } else {
                tasks.forEach(task => {
                    const isCompletada = task.completada == 1;
                    const item = document.createElement('div');
                    item.className = `dia-tarea-item ${isCompletada ? 'completada' : ''}`;
                    item.innerHTML = `
                        <div class="form-check m-0">
                            <input class="form-check-input" type="checkbox" 
                                   onchange="app.toggleCompletada(${task.id}, this.checked)" 
                                   ${isCompletada ? 'checked' : ''}>
                        </div>
                        <div class="dia-tarea-info">
                            <div class="dia-tarea-titulo">
                                ${this.escapeHtml(task.titulo)}
                                ${task.es_calificada == 1 ? `<span class="badge bg-warning text-dark ms-1" style="font-size: 0.6em; vertical-align: middle;">${this.escapeHtml((task.criterio_nombre || 'Eval.') + ' ' + (task.numero_evaluacion || ''))}</span>` : ''}
                            </div>
                            <div class="dia-tarea-materia">${this.escapeHtml(task.materia_nombre)}</div>
                        </div>
                        <div class="dia-tarea-actions">
                            ${task.es_calificada == 1 ? `
                            <a href="materia_detalle.php?id=${task.materia_id}&criterio=${task.criterio_id}&num=${task.numero_evaluacion}" class="btn btn-sm btn-success text-white p-1 px-2" style="text-decoration:none;" title="Agregar Nota"><i class="fas fa-plus-circle"></i></a>
                            ` : ''}
                            <button class="btn btn-sm btn-link text-primary p-1" onclick="app.editTarea(${task.id})"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-link text-danger p-1" onclick="app.deleteTarea(${task.id})"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    `;
                    container.appendChild(item);
                });
            }
            
            this.elements.modalDetalleDia.show();
        }
    };

    // Exponer app globalmente para los onclick del HTML generado
    window.app = app;
    app.init();
});