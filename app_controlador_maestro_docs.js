// app_controlador_maestro_docs.js
let catalogos_documentos_cache = [];
let cache_jefes_html = '';
let cache_deptos_html = '';

$(document).ready(function() {
    let id_usuario_sesion = $('#usuario_sesion_id').val();
    let contexto = $('#contexto_vista_maestra').val();

    if (contexto === 'Empleado') {
        cargar_mi_expediente_propio(id_usuario_sesion);
        obtener_perfil_tarjeta_maestra(id_usuario_sesion);
    } else if (contexto === 'Administracion') {
        cargar_tabla_administracion_docs();
    } else if (contexto === 'Configuracion') {
        cargar_tabla_config_catalogo();
    }

    $(document).on('change', '#select_tipo_documento', function() {
        let id_seleccionado = $(this).val();
        let documento_config = catalogos_documentos_cache.find(d => d.id == id_seleccionado);
        if (documento_config && documento_config.tipo_alcance === 'Por Alcance') {
            $('#contenedor_depto_alcance').refresh().removeClass('d-none');
            $('#select_depto_alcance').attr('required', true);
        } else {
            $('#contenedor_depto_alcance').addClass('d-none');
            $('#select_depto_alcance').removeAttr('required').val('');
        }
    });

    $('#form_subir_documento').on('submit', function(e) {
        e.preventDefault();
        let id_destino = $('#usuario_destino_carga_id').val();
        let formData = new FormData(this);
        formData.append('action', 'subir_documento_expediente');
        formData.append('id_usuario_destino', id_destino);
        $.ajax({
            url: 'action_controller.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('¡Cargado!', response.message, 'success');
                    $('#form_subir_documento')[0].reset();
                    cargar_mi_expediente_propio(id_destino);
                }
            }
        });
    });

    $('#form_config_catalogo_doc').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            url: 'action_controller.php',
            type: 'POST',
            data: $(this).serialize() + '&action=guardar_nuevo_tipo_documento',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('¡Catálogo Guardado!', response.message, 'success');
                    $('#form_config_catalogo_doc')[0].reset();
                    cargar_tabla_config_catalogo();
                }
            }
        });
    });
});

function cargar_mi_expediente_propio(id_usuario) {
    llenar_select_documentos('Empleado');
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_expediente', id_usuario: id_usuario },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let html = '';
                response.data.forEach(function(doc) {
                    let area = doc.nombre_area_afectada ? `<span class="badge bg-warning text-dark">${doc.nombre_area_afectada}</span>` : '<span class="text-muted small">Universal</span>';
                    html += `<tr>
                        <td class="text-start"><strong>${doc.nombre_tipo}</strong></td>
                        <td>${area}</td>
                        <td><a href="${doc.archivo_url}" target="_blank" class="btn btn-xs btn-info"><i class="fas fa-file-pdf"></i></a></td>
                        <td>${convertir_badge_estatus(doc.val_jefe_admin)}</td>
                        <td>${convertir_badge_estatus(doc.val_jefe_tecnico)}</td>
                        <td>${convertir_badge_estatus(doc.val_calidad)}</td>
                        <td>${convertir_badge_estatus(doc.val_rrhh)}</td>
                        <td><span class="badge p-1 bg-dark text-white">${doc.estatus_general}</span></td>
                    </tr>`;
                });
                $('#tbody_mi_expediente').html(html);
            }
        }
    });
}

function llenar_select_documentos(contexto_rol) {
    let id_usuario_destino = $('#usuario_destino_carga_id').val();
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_tipos_documentos_disponibles', rol_contexto: contexto_rol },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                catalogos_documentos_cache = response.data;
                let options = '<option value="">-- Seleccione --</option>';
                response.data.forEach(function(t) { options += `<option value="${t.id}">${t.nombre_tipo}</option>`; });
                $('#select_tipo_documento').html(options);
            }
        }
    });

    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_todos_los_alcances_usuario', id_usuario: id_usuario_destino },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let options = '<option value="">-- Selecciona Laboratorio Afectado --</option>';
                if (response.base) { options += `<option value="${response.base.id_depto_base}">⭐ ${response.base.nombre_depto_base} (Depto Base)</option>`; }
                if (response.adicionales) { response.adicionales.forEach(function(l) { options += `<option value="${l.id_departamento}">🔬 ${l.nombre_departamento}</option>`; }); }
                $('#select_depto_alcance').html(options);
            }
        }
    });
}

function obtener_perfil_tarjeta_maestra(id_usuario) {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_datos_perfil_tarjeta', id_usuario: id_usuario },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') { renderizar_tarjeta_perfil_usuario(response.data); }
        }
    });
}

function renderizar_tarjeta_perfil_usuario(d) {
    $('#tarjeta_nombre_completo').text(d.nombreCompleto);
    $('#tarjeta_puesto_subtitulo').text(d.puesto);
    $('#tarjeta_no_empleado').text(d.noEmpleado);
    $('#tarjeta_departamento').text(d.departamento);
    $('#tarjeta_jefe_admin').text(d.jefe_administrativo || 'No asignado');
    let zona = $('#tarjeta_jefes_tecnicos_zona').empty();
    if (d.jefes_tecnicos) {
        d.jefes_tecnicos.split(',').forEach(j => { zona.append(`<span class="badge bg-white text-dark border shadow-sm m-1"><i class="fas fa-microscope text-primary"></i> ${j}</span>`); });
    } else { zona.html('<span class="text-muted small">Sin alcances</span>'); }
}

function convertir_badge_estatus(v) {
    if (v == 3) return `<span class="badge text-muted border">N/A</span>`;
    if (v == 1) return `<span class="badge bg-success text-white"><i class="fas fa-check"></i></span>`;
    if (v == 2) return `<span class="badge bg-danger text-white"><i class="fas fa-times"></i></span>`;
    return `<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i></span>`;
}

function cargar_tabla_administracion_docs() {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'listar_administracion_empleados' },
        dataType: 'json',
        success: function(response) {
            let html = '';
            response.data.forEach(function(emp) {
                html += `<tr>
                    <td><strong>${emp.noEmpleado}</strong></td>
                    <td>${emp.nombreCompleto}</td>
                    <td>${emp.depto_base}</td>
                    <td>${emp.jefes_tecnicos || '<span class="text-muted">Solo Base</span>'}</td>
                    <td class="text-center">${emp.total_docs}</td>
                    <td>
                        <button class="btn btn-xs btn-dark" onclick="abrir_modal_jefes_tecnicos(${emp.id}, '${emp.nombreCompleto}')">Habilidades</button>
                    </td>
                </tr>`;
            });
            $('#tbody_admin_docs').html(html);
            $('#tabla_admin_docs').DataTable();
        }
    });
}

function agregar_fila_alcance_dinamica(id_jefe = '', id_depto = '') {
    let row = `<tr class="fila-alcance">
        <td><select class="form-control form-control-sm select-jefe-tabla">${cache_jefes_html}</select></td>
        <td><select class="form-control form-control-sm select-depto-tabla">${cache_deptos_html}</select></td>
        <td><button class="btn btn-xs btn-danger" onclick="$(this).closest('tr').remove();">X</button></td>
    </tr>`;
    $('#tbody_modal_alcances').append(row);
}

function abrir_modal_jefes_tecnicos(id, nombre) {
    $('#modal_jt_id_empleado').val(id);
    $('#modal_jt_nombre_empleado').text(nombre);
    $('#tbody_modal_alcances').empty();
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_catalogos_auxiliares' },
        dataType: 'json',
        success: function(res) {
            cache_jefes_html = res.jefes.map(j => `<option value="${j.id}">${j.nombre}</option>`).join('');
            cache_deptos_html = res.departamentos.map(d => `<option value="${d.id}">${d.departamento}</option>`).join('');
            $.ajax({
                url: 'action_controller.php',
                type: 'POST',
                data: { action: 'obtener_jefes_tecnicos_asignados', id_usuario_empleado: id },
                dataType: 'json',
                success: function(curr) {
                    curr.data.forEach(a => { agregar_fila_alcance_dinamica(a.id_usuario_jefe_tecnico, a.id_departamento); });
                    if(curr.data.length === 0) { agregar_fila_alcance_dinamica(); }
                    $('#modal_gestion_jefes_tecnicos').modal('show');
                }
            });
        }
    });
}

function guardar_asignacion_compuesta_jefes() {
    let arr = [];
    $('.fila-alcance').each(function() {
        arr.push({ id_jefe_tecnico: $(this).find('.select-jefe-tabla').val(), id_departamento: $(this).find('.select-depto-tabla').val() });
    });
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'guardar_jefes_tecnicos_empleado', id_usuario_empleado: $('#modal_jt_id_empleado').val(), alcances: JSON.stringify(arr) },
        dataType: 'json',
        success: function() {
            $('#modal_gestion_jefes_tecnicos').modal('hide');
            Swal.fire('Guardado', 'Habilidades actualizadas.', 'success');
            cargar_tabla_administracion_docs();
        }
    });
}

function cargar_tabla_config_catalogo() {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'listar_catalogo_completo' },
        dataType: 'json',
        success: function(res) {
            let html = '';
            res.data.forEach(i => {
                html += `<tr>
                    <td class="text-start">${i.nombre_tipo}</td>
                    <td>${i.tipo_alcance}</td>
                    <td>${i.subido_por}</td>
                    <td>${i.requiere_jefe_admin == 1 ? 'Sí' : 'No'}</td>
                    <td>${i.requiere_jefe_tecnico == 1 ? 'Sí' : 'No'}</td>
                    <td>${i.requiere_calidad == 1 ? 'Sí' : 'No'}</td>
                    <td>${i.requiere_rrhh == 1 ? 'Sí' : 'No'}</td>
                    <td><button class="btn btn-xs btn-danger">X</button></td>
                </tr>`;
            });
            $('#tbody_config_catalogo').html(html);
        }
    });
}
