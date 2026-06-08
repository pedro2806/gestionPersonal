// app_controlador_maestro_docs.js - Administrador Maestro de Personal y Expedientes (MESS)
let catalogos_documentos_cache = [];
let cache_jefes_html = '';
let cache_deptos_html = '';
let cache_jefes_admin_html = ''; // <- NUEVO: Almacena la lista de líderes para el alta

// Inicia document.ready
$(document).ready(function() {
    let id_usuario_sesion = $('#usuario_sesion_id').val();
    let contexto = $('#contexto_vista_maestra').val();
    
    preparar_selects_catalogos_usuario();

    if (contexto === 'Empleado') {
        cargar_mi_expediente_propio(id_usuario_sesion);
        obtener_perfil_tarjeta_maestra(id_usuario_sesion);
    } else if (contexto === 'Administracion') {
        cargar_tabla_administracion_docs();
        
        /*$('#form_registrar_nuevo_empleado').on('submit', function(e) {
            e.preventDefault();
            guardar_nuevo_empleado_sistema();
        });*/
    } else if (contexto === 'Configuracion') {
        cargar_tabla_config_catalogo();
    }

    $(document).on('change', '#select_tipo_documento', function() {
        let id_seleccionado = $(this).val();
        let documento_config = catalogos_documentos_cache.find(d => d.id == id_seleccionado);
        if (documento_config && documento_config.tipo_alcance === 'Por Alcance') {
            $('#contenedor_depto_alcance').removeClass('d-none'); 
            $('#select_depto_alcance').attr('required', true);
        } else {
            $('#contenedor_depto_alcance').addClass('d-none');
            $('#select_depto_alcance').removeAttr('required').val('');
        }
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

    $(document).on('submit', '#form_subir_requisito_fila', function(e) {
        e.preventDefault();
        let id_destino = $('#usuario_destino_carga_id').val();
        let id_usuario_sesion = $('#usuario_sesion_id').val();
        
        let formData = new FormData(this);
        formData.append('action', 'subir_documento_expediente');
        formData.append('id_usuario_destino', id_destino);
        formData.append('id_usuario_sesion', id_usuario_sesion);

        $.ajax({
            url: 'action_controller.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#modal_subir_requisito').modal('hide');
                    Swal.fire('¡Cargado!', 'El documento entró a revisión.', 'success');
                    $('#form_subir_requisito_fila')[0].reset();
                    cargar_mi_expediente_propio(id_destino); 
                }
            }
        });
    });
});
// Finaliza document.ready

function cargar_mi_expediente_propio(id_usuario) {
    let contexto_rol = $('#contexto_vista_maestra').val(); 

    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_expediente', id_usuario: id_usuario },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let html = '';
                
                response.data.forEach(function(req) {
                    let badges_firmas = '---';
                    if (req.subido) {
                        badges_firmas = `
                            <div class="d-flex justify-content-center gap-2">
                                ${convertir_badge_estatus(req.val_jefe_admin)}
                                ${convertir_badge_estatus(req.val_jefe_tecnico)}
                                ${convertir_badge_estatus(req.val_calidad)}
                                ${convertir_badge_estatus(req.val_rrhh)}
                            </div>`;
                    }

                    let badge_estatus = `<span class="badge bg-secondary text-white p-2">Pendiente</span>`;
                    if (req.estatus_general === 'En Revisión') badge_estatus = `<span class="badge bg-warning text-dark p-2">En Revisión</span>`;
                    if (req.estatus_general === 'Aprobado') badge_estatus = `<span class="badge bg-success text-white p-2">Aprobado</span>`;
                    if (req.estatus_general === 'Rechazado') badge_estatus = `<span class="badge bg-danger text-white p-2">Rechazado</span>`;

                    let celda_accion = '';
                    
                    if (req.subido) {
                        celda_accion = `<a href="${req.archivo_url}" target="_blank" class="btn btn-sm btn-outline-info font-weight-bold shadow-sm"><i class="fas fa-file-pdf mr-1"></i> Ver PDF</a>`;
                        
                        if (req.estatus_general === 'Rechazado' && req.subido_por === contexto_rol) {
                            celda_accion += ` <button class="btn btn-sm btn-outline-warning font-weight-bold shadow-sm" onclick="abrir_modal_carga_directa(${req.id_tipo}, '${req.nombre_tipo}', '${req.nombre_depto}', '${req.id_depto}')"><i class="fas fa-sync-alt"></i> Reintentar</button>`;
                        }
                    } else {
                        if (req.subido_por === contexto_rol || (contexto_rol === 'Empleado' && req.subido_por === 'Empleado') || (contexto_rol === 'Administracion' && req.subido_por !== 'Empleado')) {
                            celda_accion = `<button type="button" class="btn btn-sm btn-outline-primary font-weight-bold shadow-sm" onclick="abrir_modal_carga_directa(${req.id_tipo}, '${req.nombre_tipo}', '${req.nombre_depto}', '${req.id_depto}')"><i class="fas fa-upload mr-1"></i> Subir</button>`;
                        } else {
                            celda_accion = `<span class="text-muted small italic"><i class="fas fa-lock mr-1"></i> Carga por ${req.subido_por}</span>`;
                        }
                    }

                    html += `
                        <tr class="${req.subido ? '' : 'table-light'}">
                            <td class="text-start font-weight-bold text-gray-800">${req.nombre_tipo}</td>
                            <td><span class="badge bg-light border text-dark small">${req.tipo_alcance}</span></td>
                            <td class="font-weight-bold text-uppercase text-muted small">${req.nombre_depto}</td>
                            <td>${badge_estatus}</td>
                            <td>${badges_firmas}</td>
                            <td>${celda_accion}</td>
                        </tr>`;
                });

                $('#tbody_cumplimiento_expediente').html(html);
            }
        }
    });
}

function abrir_modal_carga_directa(id_tipo, nombre_tipo, nombre_depto, id_depto) {
    $('#modal_upload_id_tipo').val(id_tipo);
    $('#modal_upload_id_depto').val((id_depto && id_depto !== 'null') ? id_depto : '');
    $('#modal_upload_nombre_doc').val(nombre_tipo);
    $('#modal_upload_nombre_depto').val(nombre_depto);
    
    $('#modal_subir_requisito').modal('show');
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
                let nombreEscapado = emp.nombreCompleto.replace(/'/g, "\\'");
                
                let badgeEstatus = emp.estatus == 1 ? 
                    '<span class="badge bg-success-subtle text-success-emphasis border-0 px-2 py-1 font-weight-medium">Activo</span>' : 
                    '<span class="badge bg-danger-subtle text-danger-emphasis border-0 px-2 py-1 font-weight-medium">Inactivo</span>';

                html += `<tr>
                    <td class="font-weight-bold text-dark ps-3"><strong>${emp.noEmpleado}</strong></td>
                    <td class="font-weight-bold text-dark"><strong>${emp.nombreCompleto} / ${emp.correo}</strong></td>
                    <td>${emp.jefe_administrativo || 'No asignado'}</td>
                    <td>${emp.telefonos}</td>   
                    <td><span class="small font-weight-bold text-uppercase text-muted">${emp.depto_base}</span></td>
                    <td>${emp.jefes_tecnicos || '<span class="text-muted small italic">Solo Base</span>'}</td>
                    <td class="text-center">${badgeEstatus}</td>
                    <td class="text-center font-weight-bold">${emp.total_docs}</td>
                    <td class="text-center pe-3">
                        <div class="d-flex justify-content-center gap-1">
                            <button class="btn btn-sm btn-link text-warning p-1" onclick="abrir_modal_editar_usuario(${emp.noEmpleado})" title="Editar Colaborador">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-link text-danger p-1" onclick="confirmar_baja_logica(${emp.noEmpleado}, '${nombreEscapado}')" title="Baja Lógica">
                                <i class="fas fa-user-slash"></i>
                            </button>
                            <button class="btn btn-sm btn-link text-dark p-1" onclick="abrir_modal_jefes_tecnicos(${emp.noEmpleado}, '${nombreEscapado}')" title="Gestionar Habilidades">
                                <i class="fas fa-microscope"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
            });
            $('#tbody_admin_docs').html(html);
            
            if (!$.fn.DataTable.isDataTable('#tabla_admin_personal')) {
                $('#tabla_admin_personal').DataTable({
                    "responsive": true,
                    "pageLength": 10,
                    "dom": 'frtip',
                    "language": { 
                        "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" 
                    }
                });
            }
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

function preparar_selects_catalogos_usuario() {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_catalogos_usuarios' },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                let html_dep = '<option value="">-- Seleccionar Departamento --</option>';
                res.departamentos.forEach(function(d) { html_dep += `<option value="${d.id}">${d.departamento}</option>`; });
                $('#mod_departamento').html(html_dep);
                cache_deptos_html = html_dep;

                let html_pue = '<option value="">-- Seleccionar Puesto --</option>';
                res.puestos.forEach(function(p) { html_pue += `<option value="${p.id}">${p.puesto}</option>`; });
                $('#mod_puesto').html(html_pue);
                cache_jefes_html = html_pue;

                let html_jef = '<option value="0">-- Sin Jefe Asignado / Es Dirección --</option>';
                res.jefes.forEach(function(j) { html_jef += `<option value="${j.noEmpleado}">${j.nombre}</option>`; });
                $('#mod_jefe').html(html_jef);
                
                // GUARDAMOS EN CACHÉ: Toda la lista de líderes para alimentar el alta completo
                cache_jefes_admin_html = html_jef;
            }
        }
    });
}

function abrir_modal_editar_usuario(noEmpleado) {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_datos_usuario_edicion', noEmpleado: noEmpleado },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                let u = res.data;
                $('#mod_noEmpleado').val(u.noEmpleado).data('foto-actual', u.foto || '');
                $('#modal_telefonos_noEmpleado').val(u.noEmpleado);
                $('#mod_nombre').val(u.nombre);
                $('#mod_correo').val(u.correo);
                $('#mod_departamento').val(u.departamento);
                $('#mod_puesto').val(u.puesto);
                $('#mod_jefe').val(u.jefe);
                $('#mod_sexo').val(u.sexo);
                $('#mod_curp').val(u.curp);
                $('#mod_nss').val(u.nss);
                $('#mod_rfc').val(u.rfc);
                $('#mod_tipoContrato').val(u.tipoContrato);
                $('#mod_tipoSangre').val(u.tipoSangre);
                $('#mod_fechaIngreso').val(u.fechaIngreso);
                $('#modal_editar_usuario').modal('show');
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
}

$(document).on('submit', '#form_modificar_usuario_maestro', function(e) {
    e.preventDefault();
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: $(this).serialize() + '&action=modificar_usuario_sistema',
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                $('#modal_editar_usuario').modal('hide');
                Swal.fire('¡Actualizado!', res.message, 'success');
                cargar_tabla_administracion_docs(); 
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
});

function confirmar_baja_logica(noEmpleado, nombreCompleto) {
    Swal.fire({
        title: '¿Dar de baja al colaborador?',
        text: `El usuario "${nombreCompleto}" (No. ${noEmpleado}) se marcará como inactivo. No podrá acceder al sistema, pero se conservará todo su expediente e historial de firmas de forma segura.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonColor: '#858796',
        confirmButtonText: 'Sí, Desactivar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'action_controller.php',
                type: 'POST',
                data: { action: 'baja_logica_usuario', noEmpleado: noEmpleado },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Desactivado', res.message, 'success');
                        cargar_tabla_administracion_docs(); 
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        }
    });
}

function abrir_modal_telefonos() {
    cargar_telefonos_usuario();
    $('#modal_telefonos').modal('show');
}

function abrir_modal_cambiar_foto() {
    let noEmpleado = $('#mod_noEmpleado').val();
    let fotoRaw    = $('#mod_noEmpleado').data('foto-actual') || '';
    let fotoActual = fotoRaw ? '../loginMaster/' + fotoRaw : '/incidencias/img/undraw_profile.svg';

    $('#modal_foto_noEmpleado').val(noEmpleado);
    $('#modal_foto_actual').attr('src', fotoActual);
    $('#modal_foto_preview').attr('src', fotoActual).css('opacity', '0.4');
    $('#modal_foto_archivo').val('');
    $('#modal_cambiar_foto').modal('show');
}

$(document).on('change', '#modal_foto_archivo', function() {
    let file = this.files[0];
    if (!file) return;

    if (!['image/jpeg', 'image/png'].includes(file.type)) {
        Swal.fire('Tipo no permitido', 'Solo se aceptan imágenes JPG o PNG.', 'warning');
        this.value = '';
        return;
    }
    if (file.size > 2 * 1024 * 1024) {
        Swal.fire('Archivo grande', 'La imagen no debe superar 2MB.', 'warning');
        this.value = '';
        return;
    }

    let reader = new FileReader();
    reader.onload = function(e) { $('#modal_foto_preview').attr('src', e.target.result).css('opacity', '1'); };
    reader.readAsDataURL(file);
});

$(document).on('submit', '#form_cambiar_foto', function(e) {
    e.preventDefault();
    let file = $('#modal_foto_archivo')[0].files[0];
    if (!file) {
        Swal.fire('Sin archivo', 'Selecciona una imagen primero.', 'warning');
        return;
    }

    let formData = new FormData();
    formData.append('action', 'actualizar_foto_usuario');
    formData.append('noEmpleado', $('#modal_foto_noEmpleado').val());
    formData.append('foto', file);

    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire('Guardado', res.message, 'success');
                $('#modal_cambiar_foto').modal('hide');
                $('#mod_noEmpleado').data('foto-actual', res.foto_url);
                cargar_tabla_administracion_docs();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
});

function cargar_telefonos_usuario() {
    let noEmpleado = $('#modal_telefonos_noEmpleado').val() || $('#mod_noEmpleado').val();
    $('#contenedor_telefonos').html('<div class="text-center text-muted small py-2">Cargando...</div>');

    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_telefonos_usuario', noEmpleado: noEmpleado },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                let html = '';
                res.data.forEach(function(t) { html += render_fila_telefono(t.id, t.telefono, t.extension); });
                if (html === '') { html = '<div class="text-center text-muted small py-2">Sin teléfonos registrados.</div>'; }
                $('#contenedor_telefonos').html(html);
            } else {
                $('#contenedor_telefonos').html('<div class="text-center text-danger small py-2">Error al cargar teléfonos.</div>');
            }
        }
    });
}

function render_fila_telefono(id, telefono, extension) {
    let idAttr = id ? `data-id="${id}"` : '';
    let valTel = telefono != null ? String(telefono).replace(/"/g, '&quot;') : '';
    let valExt = extension != null ? String(extension).replace(/"/g, '&quot;') : '';
    return `
        <div class="row g-2 align-items-end fila-telefono" ${idAttr}>
            <div class="col-7">
                <label class="small text-muted mb-1">Teléfono</label>
                <input type="text" class="form-control form-control-sm shadow-none" name="telefono[]" value="${valTel}">
            </div>
            <div class="col-4">
                <label class="small text-muted mb-1">Extensión</label>
                <input type="text" class="form-control form-control-sm shadow-none" name="extension[]" value="${valExt}">
            </div>
            <div class="col-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger shadow-none" onclick="$(this).closest('.fila-telefono').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>`;
}

$(document).on('submit', '#form_modificar_telefonos', function(e) {
    e.preventDefault();
    let noEmpleado = $('#modal_telefonos_noEmpleado').val();
    let ids = [];
    let telefonos = [];
    let extensiones = [];

    $('#contenedor_telefonos .fila-telefono').each(function() {
        let id  = parseInt($(this).data('id')) || 0;
        let tel = $(this).find('input[name="telefono[]"]').val().trim();
        let ext = $(this).find('input[name="extension[]"]').val().trim();
        if (id === 0 && tel === '' && ext === '') return;
        ids.push(id);
        telefonos.push(tel);
        extensiones.push(ext);
    });

    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'guardar_telefonos_usuario', noEmpleado: noEmpleado, id: ids, telefono: telefonos, extension: extensiones },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire('Guardado', res.message, 'success');
                $('#modal_telefonos').modal('hide');
                cargar_tabla_administracion_docs();
            } else { Swal.fire('Error', res.message, 'error'); }
        }
    });
});

function agregar_campo_telefono() { $('#contenedor_telefonos').append(render_fila_telefono(null, '', '')); }

// ============================================================================
// 🆕 NUEVAS FUNCIONES: ALTA DE EMPLEADOS COMPLETA CON CATÁLOGOS INTEGRADOS
// ============================================================================

function abrir_modal_nuevo_empleado() {
    $('#form_registrar_nuevo_empleado')[0].reset();
    
    obtener_ultimo_no_empleado();
    // Inyectamos de forma cruzada toda la matriz de catálogos maestros que ya tenemos en memoria
    $('#select_nuevo_puesto').html(cache_jefes_html || '<option value="">No se encontraron puestos activos</option>');
    $('#select_nuevo_departamento').html(cache_deptos_html || '<option value="">No se encontraron departamentos activos</option>');
    $('#select_nuevo_jefe').html(cache_jefes_admin_html || '<option value="0">-- Sin Jefe Asignado --</option>');
    
    $('#modal_nuevo_empleado').modal('show');
}

function guardar_nuevo_empleado_sistema() {
    let formData = $('#form_registrar_nuevo_empleado').serialize();
    formData += '&action=registrar_nuevo_empleado_sistema';

    email = $('#nuevo_correo').val(); // Capturamos el correo para usarlo después en el envío del correo de bienvenida

    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function() {
            Swal.fire({ title: 'Creando ficha de personal...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        },
        success: function(res) {
            Swal.close();
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: '¡Guardado!', text: res.message, timer: 1500, showConfirmButton: false });
                $('#modal_nuevo_empleado').modal('hide');
                cargar_tabla_administracion_docs();
                //enviar correo de bienvenida al nuevo empleado
                $.ajax({
                    url: 'correoNuevoUsuario.php',
                    type: 'POST',
                    data: { 
                        action: 'recover_password', 
                        email: res.correo 
                    },
                    dataType: 'json',
                    success: function(res) {
                        //alert('Correo de bienvenida enviado al nuevo empleado.');
                    },
                    error: function() {
                        alert('Error al enviar correo de bienvenida.');
                    }
                });

            } else {
                Swal.fire('Atención', res.message, 'warning');
            }
        },
        error: function() {
            Swal.close();
            Swal.fire('Fallo de Conexión', 'No se detectó el caso en action_controller.php, verifica tu backend.', 'error');
        }
    });
}

function guardar_assignacion_compuesta_jefes () {
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

//obtener  ultimo NoEmpleado omitiendo los mayores a 614
function obtener_ultimo_no_empleado() {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_ultimo_no_empleado' },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                $('#nuevo_noEmpleado').val(res.ultimo_no_empleado + 1);
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
}