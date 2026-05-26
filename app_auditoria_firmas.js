// app_auditoria_firmas.js

$(document).ready(function() {
    $('.select2-custom').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    cargar_filtros_cabecera();
    listar_personal_auditoria();

    $('#filtro_departamento, #filtro_ingeniero').on('change', function() {
        listar_personal_auditoria();
    });
});

function cargar_filtros_cabecera() {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_catalogos_auxiliares' },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                let html_dep = '<option value="0">-- Todos los Laboratorios --</option>';
                res.departamentos.forEach(d => html_dep += `<option value="${d.id}">${d.departamento}</option>`);
                $('#filtro_departamento').html(html_dep).trigger('change.select2');

                let html_ing = '<option value="0">-- Todos los Ingenieros --</option>';
                res.jefes.forEach(i => html_ing += `<option value="${i.noEmpleado}">${i.nombre}</option>`);
                $('#filtro_ingeniero').html(html_ing).trigger('change.select2');
            }
        }
    });
}

function listar_personal_auditoria() {
    let depto_filtro = parseInt($('#filtro_departamento').val()) || 0;
    let ing_filtro = parseInt($('#filtro_ingeniero').val()) || 0;

    let id_sesion_usuario = parseInt($('#usuario_sesion_id').val()) || 0;
    let no_emp_sesion = parseInt($('#usuario_sesion_no_empleado').val()) || 0;
    let es_super_user = (no_emp_sesion === 5 || no_emp_sesion === 403);

    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'listar_administracion_empleados' },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                // Destruir el DataTable viejo si es que ya existía antes de redibujar por filtro
                if ($.fn.DataTable.isDataTable('#tabla_auditoria_personal')) {
                    $('#tabla_auditoria_personal').DataTable().destroy();
                }

                let html = '';
                
                res.data.forEach(function(emp) {
                    let noEmpleado = parseInt(emp.noEmpleado);                    
                    if (!es_super_user) {
                        let es_jefe_admin = (emp.id_jefe_directo && parseInt(emp.id_jefe_directo) === id_sesion_usuario);
                        let tiene_relacion_tecnica = emp.jefes_tecnicos && emp.jefes_tecnicos.includes(`(${emp.depto_base})`);
                        let es_jefe_tecnico = emp.id_jefes_tecnicos && emp.id_jefes_tecnicos.includes(id_sesion_usuario.toString());
                        console.log(`Empleado ${emp.noEmpleado} - ${emp.nombreCompleto} pasa filtro: Jefe Admin: ${es_jefe_admin}, Relación Técnica: ${tiene_relacion_tecnica}`);
                        if (!es_jefe_admin && !es_jefe_tecnico) return; 
                        
                    }

                    if (depto_filtro > 0 && emp.departamento_id && parseInt(emp.departamento_id) !== depto_filtro) return;
                    if (ing_filtro > 0 && noEmpleado !== ing_filtro) return;

                    
                    html += `
                        <tr>
                            <td class="font-weight-bold text-gray-800">${emp.noEmpleado}</td>
                            <td class="text-start font-weight-bold">${emp.nombreCompleto}</td>
                            <td class="text-uppercase small font-weight-bold text-muted">${emp.depto_base || 'General'}</td>
                            <td><span class="badge bg-light text-dark border">${emp.puesto || 'Metrólogo'}</span></td>
                            <td>
                                <button class="btn btn-xs btn-primary font-weight-bold shadow-sm" onclick="ver_expediente_detalle_auditoria(${emp.noEmpleado}, '${emp.nombreCompleto}')">
                                    <i class="fas fa-search-plus mr-1"></i> Revisar
                                </button>
                            </td>
                        </tr>`;
                });

                $('#tbody_auditoria_personal').html(html);

                // Inicializar DataTables con traducción al español y paginación limpia
                $('#tabla_auditoria_personal').DataTable({
                    responsive: true,
                    pageLength: 10,
                    lengthMenu: [10, 25, 50, 100],
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                    }
                });
            }
        }
    });
}

function ver_expediente_detalle_auditoria(noEmpleado, nombreCompleto) {
    $('#txt_nombre_ingeniero_auditar').text(noEmpleado + " - " + nombreCompleto);
    
    // CAMBIO: Ahora abrimos el modal de forma inmediata
    $('#modal_detalle_checklist').modal('show');
    
    let no_emp_firmante = parseInt($('#usuario_sesion_no_empleado').val()) || 0;
    let columna_firma_activa = '';
    
    if (no_emp_firmante === 403) columna_firma_activa = 'val_rrhh';
    else if (no_emp_firmante === 5) columna_firma_activa = 'val_calidad';
    else columna_firma_activa = 'val_jefe_tecnico';

    $('#tbody_detalle_requisitos_auditoria').html('<tr><td colspan="5" class="text-center py-3"><i class="fas fa-spinner fa-spin mr-2"></i> Cruzando matriz de obligaciones...</td></tr>');

    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_expediente', id_usuario: noEmpleado },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let html = '';
                
                if (!response.data || response.data.length === 0) {
                    $('#tbody_detalle_requisitos_auditoria').html('<tr><td colspan="5" class="text-muted italic py-3">Este perfil no cuenta con requisitos configurados.</td></tr>');
                    return;
                }

                response.data.forEach(function(req) {
                    let badge_estatus = `<span class="badge bg-secondary p-1 text-uppercase small">Pendiente</span>`;
                    if (req.estatus_general === 'En Revisión') badge_estatus = `<span class="badge bg-warning text-dark p-1 text-uppercase small">En Revisión</span>`;
                    if (req.estatus_general === 'Aprobado') badge_estatus = `<span class="badge bg-success p-1 text-uppercase small">Aprobado</span>`;
                    if (req.estatus_general === 'Rechazado') badge_estatus = `<span class="badge bg-danger p-1 text-uppercase small">Rechazado</span>`;

                    let cell_accion = '<span class="text-muted italic small"><i class="fas fa-exclamation-triangle mr-1"></i> Faltante</span>';
                    
                    if (req.subido || req.archivo_url) {
                        cell_accion = `
                            <div class="d-flex justify-content-center gap-1">
                                <a href="${req.archivo_url}" target="_blank" class="btn btn-xs btn-info font-weight-bold" title="Ver PDF"><i class="fas fa-file-pdf"></i> PDF</a>
                                <button class="btn btn-xs btn-success font-weight-bold" onclick="ejecutar_firma_auditor(${req.id_documento_real || req.id}, '${columna_firma_activa}', 1, ${noEmpleado}, '${nombreCompleto}')" title="Aprobar"><i class="fas fa-check"></i></button>
                                <button class="btn btn-xs btn-danger font-weight-bold" onclick="ejecutar_firma_auditor(${req.id_documento_real || req.id}, '${columna_firma_activa}', 2, ${noEmpleado}, '${nombreCompleto}')" title="Rechazar"><i class="fas fa-times"></i></button>
                            </div>`;
                    }

                    let b_admin = (req.val_jefe_admin == 1 || req.val_jefe_admin == 3) ? 'bg-success' : (req.val_jefe_admin == 2 ? 'bg-danger' : 'bg-light text-dark border');
                    let b_teco  = (req.val_jefe_tecnico == 1 || req.val_jefe_tecnico == 3) ? 'bg-success' : (req.val_jefe_tecnico == 2 ? 'bg-danger' : 'bg-light text-dark border');
                    let b_calif = (req.val_calidad == 1 || req.val_calidad == 3) ? 'bg-success' : (req.val_calidad == 2 ? 'bg-danger' : 'bg-light text-dark border');
                    let b_rrhh  = (req.val_rrhh == 1 || req.val_rrhh == 3) ? 'bg-success' : (req.val_rrhh == 2 ? 'bg-danger' : 'bg-light text-dark border');

                    html += `
                        <tr class="${(req.subido || req.archivo_url) ? 'table-success bg-opacity-10' : ''}">
                            <td class="text-start font-weight-bold text-gray-800">${req.nombre_tipo || 'Documento'}</td>
                            <td class="text-uppercase small text-muted font-weight-bold">${req.nombre_depto || 'General'}</td>
                            <td>${badge_estatus}</td>
                            <td>
                                <span class="badge ${b_admin} p-1">A</span>
                                <span class="badge ${b_teco} p-1">T</span>
                                <span class="badge ${b_calif} p-1">C</span>
                                <span class="badge ${b_rrhh} p-1">R</span>
                            </td>
                            <td>${cell_accion}</td>
                        </tr>`;
                });
                
                $('#tbody_detalle_requisitos_auditoria').html(html);
            }
        }
    });
}

function ejecutar_firma_auditor(id_documento, columna_firma, estado_voto, noEmpleado, nombreCompleto) {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: {
            action: 'procesar_firma_documento',
            id_documento: id_documento,
            columna_firma: columna_firma,
            estado_firma: estado_voto
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire('Dictamen Guardado', 'La firma colectiva fue asentada de forma conforme.', 'success');
                ver_expediente_detalle_auditoria(noEmpleado, nombreCompleto); // Recarga el modal por dentro al momento
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
}

function limpiar_filtros_auditoria() {
    $('#filtro_departamento').val(0).trigger('change.select2');
    $('#filtro_ingeniero').val(0).trigger('change.select2');
}