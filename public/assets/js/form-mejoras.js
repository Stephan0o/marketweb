// Ciudades de Perú para autocompletar
const ciudadesPeru = [
    'Lima', 'Arequipa', 'Trujillo', 'Chiclayo', 'Piura', 'Iquitos', 'Cusco',
    'Huancayo', 'Tacna', 'Ica', 'Pucallpa', 'Juliaca', 'Chimbote', 'Sullana',
    'Ayacucho', 'Cajamarca', 'Puno', 'Huaraz', 'Tarapoto', 'Tumbes', 'Talara',
    'Chincha Alta', 'Huánuco', 'Huacho', 'Moquegua', 'Cerro de Pasco',
    'Moyobamba', 'Jaén', 'Paita', 'Lambayeque', 'Bagua Grande'
];

// Referencias a elementos
const form = document.getElementById('empresaForm');
const rubroSelect = document.getElementById('rubroSelect');
const rubroInput = document.getElementById('rubroInput');
const ubicacionInput = document.getElementById('ubicacion');
const sugerenciasBox = document.getElementById('ubicacionSugerencias');
const descripcionTextarea = document.getElementById('descripcion');
const charCount = document.getElementById('charCount');
const progressBar = document.getElementById('progressBar');
const submitBtn = document.getElementById('submitBtn');
const btnText = document.getElementById('btnText');
const btnSpinner = document.getElementById('btnSpinner');

// ========== MANEJO DEL CAMPO "OTROS" EN RUBRO (CORREGIDO) ==========
if (rubroSelect && rubroInput) {
    rubroSelect.addEventListener('change', function() {
        if (this.value === 'otros') {
            rubroSelect.style.display = 'none';
            rubroInput.style.display = 'block';
            rubroInput.required = true;
            rubroInput.focus();
            rubroSelect.required = false;
            rubroSelect.name = '';
            rubroInput.name = 'rubro'; 
        }
    });

    // Permitir volver al select si borra todo
    rubroInput.addEventListener('blur', function() {
        if (this.value.trim() === '') {
            rubroInput.style.display = 'none';
            rubroSelect.style.display = 'block';
            rubroSelect.required = true;
            rubroInput.required = false;
            rubroSelect.name = 'rubro'; 
            rubroInput.name = ''; 
        }
    });
}

// ========== AUTOCOMPLETADO DE UBICACIÓN ==========
if (ubicacionInput && sugerenciasBox) {
    ubicacionInput.addEventListener('input', function() {
        const valor = this.value.toLowerCase();
        
        if (valor.length < 2) {
            sugerenciasBox.style.display = 'none';
            return;
        }
        
        const coincidencias = ciudadesPeru.filter(ciudad => 
            ciudad.toLowerCase().includes(valor)
        );
        
        if (coincidencias.length > 0) {
            sugerenciasBox.innerHTML = '';
            coincidencias.forEach(ciudad => {
                const div = document.createElement('div');
                div.className = 'sugerencia-item';
                div.textContent = ciudad;
                div.addEventListener('click', function() {
                    ubicacionInput.value = ciudad;
                    sugerenciasBox.style.display = 'none';
                    ubicacionInput.classList.add('is-valid');
                });
                sugerenciasBox.appendChild(div);
            });
            sugerenciasBox.style.display = 'block';
        } else {
            sugerenciasBox.style.display = 'none';
        }
    });

    // Cerrar sugerencias al hacer clic fuera
    document.addEventListener('click', function(e) {
        if (e.target !== ubicacionInput) {
            sugerenciasBox.style.display = 'none';
        }
    });
}

// ========== CONTADOR DE CARACTERES ==========
if (descripcionTextarea && charCount) {
    descripcionTextarea.addEventListener('input', function() {
        const count = this.value.length;
        charCount.textContent = count;
        
        // Cambiar color según cercanía al límite
        if (count > 450) {
            charCount.style.color = '#e74a3b';
            charCount.style.fontWeight = 'bold';
        } else if (count > 400) {
            charCount.style.color = '#f6c23e';
        } else {
            charCount.style.color = '#1cc88a';
        }
    });
}

// ========== BARRA DE PROGRESO ==========
function actualizarProgreso() {
    if (!form) return;
    
    const campos = form.querySelectorAll('input[required], select[required], textarea[required]');
    let completados = 0;
    
    campos.forEach(campo => {
        if (campo.value.trim() !== '' && campo.offsetParent !== null && campo.name !== '') {
            completados++;
        }
    });
    
    const porcentaje = (completados / campos.length) * 100;
    if (progressBar) {
        progressBar.style.width = porcentaje + '%';
    }
}

// Escuchar cambios en todos los campos
if (form) {
    form.addEventListener('input', actualizarProgreso);
    form.addEventListener('change', actualizarProgreso);
}

// ========== VALIDACIÓN EN TIEMPO REAL ==========
const camposValidar = document.querySelectorAll('.form-control, .form-select');

camposValidar.forEach(campo => {
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

// ========== ATAJOS DE TECLADO ==========
document.addEventListener('keydown', function(e) {
    // Ctrl + Enter para enviar formulario
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        if (form && form.checkValidity()) {
            form.submit();
        } else {
            alert('⚠️ Por favor completa todos los campos requeridos');
        }
    }
    
    // Escape para limpiar campo actual
    if (e.key === 'Escape') {
        if (document.activeElement.tagName === 'INPUT' || 
            document.activeElement.tagName === 'TEXTAREA') {
            document.activeElement.value = '';
            document.activeElement.classList.remove('is-valid', 'is-invalid');
            actualizarProgreso();
        }
    }
});

// ========== ANIMACIÓN AL ENVIAR ==========
if (form) {
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            alert('⚠️ Por favor completa todos los campos requeridos (*)');
            return;
        }
        
        // Animación de envío
        if (btnText && btnSpinner && submitBtn) {
            btnText.textContent = 'Guardando...';
            btnSpinner.style.display = 'inline-block';
            submitBtn.disabled = true;
        }
    });
}

// ========== VALIDACIÓN MEJORADA PARA AÑOS ==========
const anosInput = document.getElementById('anos');
if (anosInput) {
    anosInput.addEventListener('input', function() {
        if (this.value < 0) {
            this.value = 0;
        }
        if (this.value > 150) {
            this.value = 150;
        }
    });
}

// ========== AUTO-FOCUS EN EL PRIMER CAMPO ==========
window.addEventListener('load', function() {
    const nombreInput = document.getElementById('nombre');
    if (nombreInput) {
        nombreInput.focus();
    }
    actualizarProgreso();
});

console.log('%c✅ Formulario de Empresa Cargado', 'color: #1cc88a; font-weight: bold; font-size: 14px');
console.log('%c💡 Atajos disponibles:', 'color: #4e73df; font-weight: bold;');
console.log('   Tab: Navegar entre campos');
console.log('   Ctrl + Enter: Enviar formulario');
console.log('   Escape: Limpiar campo actual');