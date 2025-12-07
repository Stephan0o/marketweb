// Objetivo
const objetivoSelect = document.getElementById('objetivoSelect');
const objetivoInput = document.getElementById('objetivoInput');

if (objetivoSelect && objetivoInput) {
    objetivoSelect.addEventListener('change', function() {
        if (this.value === 'otros') {
            objetivoSelect.style.display = 'none';
            objetivoInput.style.display = 'block';
            objetivoInput.required = true;
            objetivoInput.focus();
            objetivoSelect.required = false;
            objetivoSelect.name = '';
            objetivoInput.name = 'objetivo';
        }
    });

    objetivoInput.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            objetivoInput.style.display = 'none';
            objetivoSelect.style.display = 'block';
            objetivoSelect.required = true;
            objetivoInput.required = false;
            objetivoSelect.name = 'objetivo';
            objetivoInput.name = '';
        }
    });
}

// Público objetivo
const publicoSelect = document.getElementById('publicoSelect');
const publicoInput = document.getElementById('publicoInput');

if (publicoSelect && publicoInput) {
    publicoSelect.addEventListener('change', function() {
        if (this.value === 'otros') {
            publicoSelect.style.display = 'none';
            publicoInput.style.display = 'block';
            publicoInput.required = true;
            publicoInput.focus();
            publicoSelect.required = false;
            publicoSelect.name = '';
            publicoInput.name = 'publico';
        }
    });

    publicoInput.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            publicoInput.style.display = 'none';
            publicoSelect.style.display = 'block';
            publicoSelect.required = true;
            publicoInput.required = false;
            publicoSelect.name = 'publico';
            publicoInput.name = '';
        }
    });
}

// Presupuesto
const presupuestoSelect = document.getElementById('presupuestoSelect');
const presupuestoInput = document.getElementById('presupuestoInput');

if (presupuestoSelect && presupuestoInput) {
    presupuestoSelect.addEventListener('change', function() {
        if (this.value === 'otros') {
            presupuestoSelect.style.display = 'none';
            presupuestoInput.style.display = 'block';
            presupuestoInput.required = true;
            presupuestoInput.focus();
            presupuestoSelect.required = false;
            presupuestoSelect.name = '';
            presupuestoInput.name = 'presupuesto';
        }
    });

    presupuestoInput.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            presupuestoInput.style.display = 'none';
            presupuestoSelect.style.display = 'block';
            presupuestoSelect.required = true;
            presupuestoInput.required = false;
            presupuestoSelect.name = 'presupuesto';
            presupuestoInput.name = '';
        }
    });
}

// MULTI-SELECT PARA CANALES DE DIFUSIÓN
const canalesContainer = document.getElementById('canalesContainer');
const canalesSelected = document.getElementById('canalesSelected');
const canalesOptions = document.getElementById('canalesOptions');
const canalesCheckboxes = canalesOptions ? canalesOptions.querySelectorAll('input[type="checkbox"]') : [];

if (canalesSelected) {
    canalesSelected.addEventListener('click', function() {
        canalesOptions.classList.toggle('show');
        canalesSelected.classList.toggle('active');
    });
}

canalesCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        actualizarSeleccion('canales', canalesSelected, canalesCheckboxes);
    });
});

// MULTI-SELECT PARA REDES SOCIALES
const redesContainer = document.getElementById('redesContainer');
const redesSelected = document.getElementById('redesSelected');
const redesOptions = document.getElementById('redesOptions');
const redesCheckboxes = redesOptions ? redesOptions.querySelectorAll('input[type="checkbox"]') : [];

if (redesSelected) {
    redesSelected.addEventListener('click', function() {
        redesOptions.classList.toggle('show');
        redesSelected.classList.toggle('active');
    });
}

redesCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        actualizarSeleccion('redes', redesSelected, redesCheckboxes);
    });
});

// Función para actualizar la selección visual
function actualizarSeleccion(tipo, container, checkboxes) {
    const seleccionados = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);

    if (seleccionados.length === 0) {
        container.innerHTML = '<span class="placeholder-text">Selecciona uno o varios ' + 
            (tipo === 'canales' ? 'canales' : 'redes') + '...</span>';
    } else {
        container.innerHTML = '';
        seleccionados.forEach(item => {
            const tag = document.createElement('span');
            tag.className = 'selected-tag';
            tag.innerHTML = `${item} <span class="remove-tag">×</span>`;
            
            tag.querySelector('.remove-tag').addEventListener('click', function(e) {
                e.stopPropagation();
                const checkbox = Array.from(checkboxes).find(cb => cb.value === item);
                if (checkbox) {
                    checkbox.checked = false;
                    actualizarSeleccion(tipo, container, checkboxes);
                }
            });
            
            container.appendChild(tag);
        });
    }
}

// Cerrar dropdowns al hacer clic fuera
document.addEventListener('click', function(e) {
    if (canalesContainer && !canalesContainer.contains(e.target)) {
        if (canalesOptions) canalesOptions.classList.remove('show');
        canalesSelected.classList.remove('active');
    }
    if (redesContainer && !redesContainer.contains(e.target)) {
        if (redesOptions) redesOptions.classList.remove('show');
        redesSelected.classList.remove('active');
    }
});

// CONTADOR DE CARACTERES PARA ESTRATEGIA
const estrategiaTextarea = document.getElementById('estrategiaCampania');
const charCountEstrategia = document.getElementById('charCountEstrategia');

if (estrategiaTextarea && charCountEstrategia) {
    estrategiaTextarea.addEventListener('input', function() {
        const count = this.value.length;
        charCountEstrategia.textContent = count;
        
        if (count > 450) {
            charCountEstrategia.style.color = '#e74a3b';
            charCountEstrategia.style.fontWeight = 'bold';
        } else if (count > 400) {
            charCountEstrategia.style.color = '#f6c23e';
        } else {
            charCountEstrategia.style.color = '#1cc88a';
        }
    });
}

// CONTADOR DE CARACTERES PARA COMENTARIOS
const comentariosTextarea = document.getElementById('comentariosCampania');
const charCountComentarios = document.getElementById('charCountComentarios');

if (comentariosTextarea && charCountComentarios) {
    comentariosTextarea.addEventListener('input', function() {
        const count = this.value.length;
        charCountComentarios.textContent = count;
        
        if (count > 270) {
            charCountComentarios.style.color = '#e74a3b';
            charCountComentarios.style.fontWeight = 'bold';
        } else if (count > 250) {
            charCountComentarios.style.color = '#f6c23e';
        } else {
            charCountComentarios.style.color = '#1cc88a';
        }
    });
}

// VALIDACIÓN Y CÁLCULO DE DURACIÓN DE CAMPAÑA
const fechaInicio = document.getElementById('fechaInicio');
const fechaFin = document.getElementById('fechaFin');
const duracionCampania = document.getElementById('duracionCampania');

function obtenerFechaActual() {
    const hoy = new Date();
    return hoy.toISOString().split('T')[0];
}

function calcularDuracion() {
    if (fechaInicio && fechaFin && fechaInicio.value && fechaFin.value) {
        const inicio = new Date(fechaInicio.value);
        const fin = new Date(fechaFin.value);
        const diferencia = fin - inicio;
        const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
        
        if (dias < 30) {
            duracionCampania.innerHTML = '<span style="color: #e74a3b;">⚠️ La campaña debe durar mínimo 1 mes (30 días)</span>';
            fechaFin.classList.add('is-invalid');
            fechaFin.classList.remove('is-valid');
        } else if (dias < 0) {
            duracionCampania.innerHTML = '<span style="color: #e74a3b;">⚠️ La fecha de fin debe ser posterior a la de inicio</span>';
            fechaFin.classList.add('is-invalid');
            fechaFin.classList.remove('is-valid');
        } else {
            duracionCampania.innerHTML = `<span style="color: #1cc88a;">✓ Duración: ${dias} día${dias !== 1 ? 's' : ''}</span>`;
            fechaFin.classList.remove('is-invalid');
            fechaFin.classList.add('is-valid');
        }
    }
}

if (fechaInicio && fechaFin) {
    const fechaActual = obtenerFechaActual();
    fechaInicio.min = fechaActual;
    
    fechaInicio.addEventListener('change', function() {
        const fechaInicioSeleccionada = this.value;
        const inicioDate = new Date(fechaInicioSeleccionada);
        const minFechaFin = new Date(inicioDate);
        minFechaFin.setDate(minFechaFin.getDate() + 30);
        
        fechaFin.min = minFechaFin.toISOString().split('T')[0];
        fechaFin.value = '';
        duracionCampania.innerHTML = '';
    });
    
    fechaFin.addEventListener('change', calcularDuracion);
}

// BARRA DE PROGRESO PARA CAMPAÑA
const formCampania = document.getElementById('campaniaForm');

function actualizarProgresoCampania() {
    if (!formCampania) return;
    
    const campos = formCampania.querySelectorAll('input[required], select[required], textarea[required]');
    const canalesSeleccionados = document.querySelectorAll('input[name="canales[]"]:checked').length;
    
    let completados = 0;
    
    campos.forEach(campo => {
        if (campo.value.trim() !== '' && campo.offsetParent !== null && campo.name !== '') {
            completados++;
        }
    });
    
    if (canalesSeleccionados > 0) {
        completados += 0.5;
    }
    
    const porcentaje = Math.min(100, (completados / campos.length) * 100);
    const progressBar = document.getElementById('progressBar');
    if (progressBar) {
        progressBar.style.width = porcentaje + '%';
    }
}

// Escuchar cambios en el formulario de campaña
if (formCampania) {
    formCampania.addEventListener('input', actualizarProgresoCampania);
    formCampania.addEventListener('change', actualizarProgresoCampania);
}

// VALIDACIÓN AL ENVIAR
if (formCampania) {
    formCampania.addEventListener('submit', function(e) {
        const canalesSeleccionados = document.querySelectorAll('input[name="canales[]"]:checked').length;
        
        if (canalesSeleccionados === 0) {
            e.preventDefault();
            alert('⚠️ Por favor selecciona al menos un canal de difusión');
            canalesSelected.style.borderColor = '#e74a3b';
            canalesSelected.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        
        if (fechaInicio && fechaFin && fechaInicio.value && fechaFin.value) {
            const inicio = new Date(fechaInicio.value);
            const fin = new Date(fechaFin.value);
            const diferencia = fin - inicio;
            const dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
            
            if (dias < 30) {
                e.preventDefault();
                alert('⚠️ La campaña debe durar mínimo 1 mes (30 días)');
                fechaFin.focus();
                return;
            }
            
            if (fin < inicio) {
                e.preventDefault();
                alert('⚠️ La fecha de fin debe ser posterior a la fecha de inicio');
                fechaFin.focus();
                return;
            }
        }
        
        if (!formCampania.checkValidity()) {
            e.preventDefault();
            alert('⚠️ Por favor completa todos los campos requeridos (*)');
            return;
        }
        
        const btnText = document.getElementById('btnText');
        const btnSpinner = document.getElementById('btnSpinner');
        const submitBtn = document.getElementById('submitBtn');
        
        if (btnText && btnSpinner && submitBtn) {
            btnText.textContent = 'Guardando campaña...';
            btnSpinner.style.display = 'inline-block';
            submitBtn.disabled = true;
        }
    });
}

// AUTO-FOCUS EN EL PRIMER CAMPO
window.addEventListener('load', function() {
    const nombreCampania = document.getElementById('nombreCampania');
    if (nombreCampania) {
        nombreCampania.focus();
    }
    actualizarProgresoCampania();
});

// VALIDACIÓN EN TIEMPO REAL
const camposCampania = document.querySelectorAll('#campaniaForm .form-control, #campaniaForm .form-select');

camposCampania.forEach(campo => {
    campo.addEventListener('blur', function() {
        if (this.value.trim() !== '' && this.checkValidity()) {
            this.classList.add('is-valid');
            this.classList.remove('is-invalid');
        } else if (this.value.trim() !== '') {
            this.classList.add('is-invalid');
            this.classList.remove('is-valid');
        }
    });
});

console.log('%c✅ Formulario de Campaña Cargado', 'color: #1cc88a; font-weight: bold; font-size: 14px');
console.log('%c💡 Funciones activas:', 'color: #4e73df; font-weight: bold;');
console.log('   ✓ Campos "Otros" dinámicos (Objetivo, Público, Presupuesto)');
console.log('   ✓ Multi-selección de Canales y Redes Sociales');
console.log('   ✓ Cálculo automático de duración de campaña');
console.log('   ✓ Contadores de caracteres');
console.log('   ✓ Validación en tiempo real');