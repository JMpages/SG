document.addEventListener('DOMContentLoaded', () => {
    const app = {
        state: {
            tareas: [],
            materias: [],
            filtroMateria: '',
            filtroEstado: 'pendientes'
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
            idTareaEliminar: document.getElementById('idTareaEliminar')
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
            document.querySelector('.btn-agregar').addEventListener('click', () => {
                this.elements.formTarea.reset();
                document.getElementById('tareaId').value = '';
                this.elements.modalTitle.textContent = 'Nueva Tarea';
                this.elements.btnGuardar.textContent = 'Guardar Tarea';
            });
        },

        async loadMaterias() {
            try {
                const response = await fetch('../backend/obtener_materias.php');
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

                const response = await fetch(`../backend/obtener_tareas.php?${params}`);
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
                
                let cardClass = 'tarea-card';
                if (isCompletada) cardClass += ' completada';
                else if (isVencida) cardClass += ' vencida';

                return `
                <div class="col-md-6 col-lg-4">
                    <div class="${cardClass}">
                        <div class="tarea-header">
                            <span class="tarea-materia">${this.escapeHtml(tarea.materia_nombre)}</span>
                            <div class="form-check form-switch">
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
                const response = await fetch('../backend/tareas_proceso.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.status === 'success') {
                    this.elements.modalTarea.hide();
                    this.showToast(result.message, 'success');
                    this.loadTareas();
                } else {
                    this.showToast(result.message, 'error');
                }
            } catch (error) {
                this.showToast('Error al guardar la tarea.', 'error');
            }
        },

        async toggleCompletada(id, isChecked) {
            try {
                const response = await fetch('../backend/marcar_tarea.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id, completada: isChecked })
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    // Recargar solo si estamos filtrando por estado, para que desaparezca/aparezca
                    if (this.state.filtroEstado !== 'todas') {
                        this.loadTareas();
                    } else {
                        // Si estamos en "todas", solo actualizar visualmente sin recargar todo
                        // Pero por simplicidad y para actualizar estilos (tachado), recargamos o manipulamos DOM.
                        // Recargar es más seguro para mantener consistencia.
                        this.loadTareas();
                    }
                    this.showToast(isChecked ? 'Tarea completada' : 'Tarea pendiente', 'success');
                }
            } catch (error) {
                this.showToast('Error al actualizar estado.', 'error');
            }
        },

        editTarea(id) {
            const tarea = this.state.tareas.find(t => t.id == id);
            if (!tarea) return;

            document.getElementById('tareaId').value = tarea.id;
            document.getElementById('tareaTitulo').value = tarea.titulo;
            document.getElementById('tareaMateria').value = tarea.materia_id;
            document.getElementById('tareaFecha').value = tarea.fecha_entrega;
            document.getElementById('tareaDescripcion').value = tarea.descripcion || '';
            
            this.elements.modalTitle.textContent = 'Editar Tarea';
            this.elements.btnGuardar.textContent = 'Actualizar';
            this.elements.modalTarea.show();
        },

        deleteTarea(id) {
            const tarea = this.state.tareas.find(t => t.id == id);
            if (!tarea) return;

            this.elements.nombreTareaEliminar.textContent = tarea.titulo;
            this.elements.idTareaEliminar.value = id;
            this.elements.modalEliminar.show();
        },

        async executeDelete() {
            const id = this.elements.idTareaEliminar.value;
            try {
                const response = await fetch('../backend/eliminar_tarea.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                });
                const result = await response.json();

                if (result.status === 'success') {
                    this.elements.modalEliminar.hide();
                    this.showToast(result.message, 'success');
                    this.loadTareas();
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
        }
    };

    // Exponer app globalmente para los onclick del HTML generado
    window.app = app;
    app.init();
});