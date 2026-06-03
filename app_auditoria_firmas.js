// app_auditoria_firmas.js - Controlador Asíncrono de Auditoría Colectiva (MESS)
let tablaAuditoriaPersonal = null;

$(document).ready(function() {
    // Inicializar Select2 con el tema nativo de Bootstrap 5
    $('.select2-custom').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });

    cargar_filtros_cabecera();
    listar_personal_auditoria();

    // Recargar DataTables al alterar los filtros superiores
    $('#filtro_departamento, #filtro_ingeniero').on('change', function() {
        listar_personal_auditoria();
    });
});

/**
 * Carga los filtros superiores apuntando al nuevo controlador específico
 */
function cargar_filtros_cabecera() {
    $.ajax({
        url: 'expediente_validacion_controller.php', // <- CAMBIADO: Nuevo controlador de validación
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

/**
 * Carga el personal global de la empresa y muestra el avance del expediente
 */
function listar_personal_auditoria() {
    if (tablaAuditoriaPersonal) {
        tablaAuditoriaPersonal.destroy();
    }

    let idDepto = $('#filtro_departamento').val() || 0;
    let noJefe = $('#filtro_ingeniero').val() || 0;

    tablaAuditoriaPersonal = $('#tabla_auditoria_personal').DataTable({
        "ajax": {
            "url": "expediente_validacion_controller.php", // <- CAMBIADO: Nuevo controlador de validación
            "type": "POST",
            "data": {
                "action": "listar_personal_auditoria",
                "id_departamento": idDepto,
                "no_jefe_tecnico": noJefe
            },
            "dataSrc": function(res) {
                return res.status === 'success' ? res.data : [];
            }
        },
        "columns": [
            { "data": "noEmpleado", "className": "font-weight-bold text-dark ps-3 py-3" },
            { "data": "nombre", "className": "font-weight-bold text-dark" },
            { "data": "puesto_nombre", "className": "text-center text-muted" },
            { "data": "departamento_nombre", "className": "text-center" },
            { 
                "data": "avance",
                "className": "text-center",
                "render": function(data) {
                    return `
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <div class="progress w-100" style="height: 6px;">
                                <div class="progress-bar bg-success shadow-none" role="progressbar" style="width: ${data}%" aria-valuenow="${data}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <span class="font-weight-bold text-dark small">${data}%</span>
                        </div>
                    `;
                }
            },
            {
                "data": null,
                "className": "text-center pe-3",
                "orderable": false,
                "render": function(data, type, row) {
                    let nombreEscapado = row.nombre.replace(/'/g, "\\'");
                    return `
                        <button class="btn btn-sm btn-dark font-weight-bold text-uppercase px-2.5 shadow-none" onclick="ver_expediente_detalle_auditoria(${row.noEmpleado}, '${nombreEscapado}')" style="font-size:0.72rem;">
                            <i class="fas fa-folder-open mr-1"></i>Auditar
                        </button>
                    `;
                }
            }
        ],
        "responsive": true,
        "pageLength": 10,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });
}

/**
 * Abre el modal e inyecta la matriz interactiva del colaborador seleccionado
 */
/**
 * Abre el modal e inyecta la matriz interactiva incluyendo el botón inteligente de Carga
 */
function ver_expediente_detalle_auditoria(noEmpleado, nombreCompleto) {
    $('#modal_auditoria_titulo').text(`Expediente Digital de: ${nombreCompleto}`);
    $('#modal_auditoria_subtitulo').text(`Nómina Corporativa: No. ${noEmpleado}`);
    
    $.ajax({
        url: 'expediente_validacion_controller.php',
        type: 'POST',
        data: {
            action: 'obtener_detalle_expediente_colaborador',
            noEmpleado: noEmpleado
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                let html = '';
                
                res.data.forEach(function(row) {
                    let badgeEstatus = '<span class="badge bg-danger-subtle text-danger border-0 px-2 py-1">Pendiente</span>';
                    if (row.estatus_general === 'En Revisión') badgeEstatus = '<span class="badge bg-warning-subtle text-warning-emphasis border-0 px-2 py-1">En Revisión</span>';
                    if (row.estatus_general === 'Aprobado') badgeEstatus = '<span class="badge bg-success-subtle text-success border-0 px-2 py-1">Aprobado</span>';
                    if (row.estatus_general === 'Rechazado') badgeEstatus = '<span class="badge bg-danger-subtle text-danger border-0 px-2 py-1">Rechazado</span>';

                    let b_adm = (parseInt(row.requiere_jefe_admin) === 1) ? (parseInt(row.val_jefe_admin) === 3 ? 'bg-success text-white' : 'bg-light text-muted border') : 'd-none';
                    let b_teco = (parseInt(row.requiere_jefe_tecnico) === 1) ? (parseInt(row.val_jefe_tecnico) === 3 ? 'bg-dark text-white' : 'bg-light text-muted border') : 'd-none';
                    let b_calif = (parseInt(row.requiere_calidad) === 1) ? (parseInt(row.val_calidad) === 3 ? 'bg-warning text-dark' : 'bg-light text-muted border') : 'd-none';
                    let b_rrhh = (parseInt(row.requiere_rrhh) === 1) ? (parseInt(row.val_rrhh) === 3 ? 'bg-primary text-white' : 'bg-light text-muted border') : 'd-none';

                    // --- ACCIONES INTELIGENTES PARA EL EVALUADOR ---
                    let cell_accion = '';
                    if (row.archivo_url) {
                        cell_accion = `
                            <div class="d-flex align-items-center gap-1">
                                <a href="${row.archivo_url}" target="_blank" class="btn btn-sm btn-link text-secondary p-1" title="Ver PDF"><i class="fas fa-eye"></i></a>
                                <button class="btn btn-sm btn-link text-success p-1" onclick="ejecutar_firma_auditor(${row.id_documento}, 'Aprobar', ${noEmpleado}, '${nombreCompleto}')" title="Firmar / Autorizar"><i class="fas fa-check-circle"></i></button>
                                <button class="btn btn-sm btn-link text-danger p-1" onclick="ejecutar_firma_auditor(${row.id_documento}, 'Rechazar', ${noEmpleado}, '${nombreCompleto}')" title="Rechazar Documento"><i class="fas fa-times-circle"></i></button>
                            </div>
                        `;
                    } else {
                        // REGLA DE CARGA ADMINISTRATIVA: Si no hay archivo pero el creador configurado es el Jefe o RH, le permitimos subirlo
                        // Hacemos un reemplazo simple de comillas para evitar rupturas de string en el onclick
                        let nombreDocEscapado = row.nombre_tipo.replace(/'/g, "\\'");
                        let nombreEmpEscapado = nombreCompleto.replace(/'/g, "\\'");
                        
                        if (row.subido_por !== 'Empleado') {
                            cell_accion = `
                                <button class="btn btn-sm btn-outline-dark font-weight-bold py-0.5 px-2 rounded" onclick="abrir_modal_carga_jefe(${row.id_tipo_documento}, ${noEmpleado}, '${nombreEmpEscapado}', '${nombreDocEscapado}')" style="font-size:0.7rem;">
                                    <i class="fas fa-cloud-upload-alt mr-1"></i>Cargar
                                </button>
                            `;
                        } else {
                            cell_accion = '<span class="text-muted small italic" style="font-size:0.75rem;">Espera de Empleado</span>';
                        }
                    }

                    html += `
                        <tr>
                            <td class="py-3 ps-3 font-weight-bold text-dark">${row.nombre_tipo}</td>
                            <td class="text-center"><span class="badge ${row.categoria_funcion === 'Técnico' ? 'bg-warning-subtle text-warning-emphasis' : 'bg-primary-subtle text-primary-emphasis'} px-2 py-1">${row.categoria_funcion}</span></td>
                            <td class="text-center text-muted">${row.tipo_alcance}</td>
                            <td class="text-center">${badgeEstatus}</td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1" style="font-size: 0.65rem;">
                                    <span class="badge ${b_adm} py-1 px-1.5" title="Jefe Administrativo">A</span>
                                    <span class="badge ${b_teco} py-1 px-1.5" title="Jefe Técnico">T</span>
                                    <span class="badge ${b_calif} py-1 px-1.5" title="Calidad">C</span>
                                    <span class="badge ${b_rrhh} py-1 px-1.5" title="Recursos Humanos">R</span>
                                </div>
                            </td>
                            <td class="pe-3">${cell_accion}</td>
                        </tr>`;
                });
                
                $('#tbody_detalle_requisitos_auditoria').html(html);
                $('#modal_ver_expediente_empleado').modal('show');
            }
        }
    });
}

/**
 * Abre el mini modal de inyección de archivos
 */
function abrir_modal_carga_jefe(idTipoDoc, noEmpleadoDestino, nombreEmpleado, nombreDocumento) {
    $('#modal_jefe_id_tipo_documento').val(idTipoDoc);
    $('#modal_jefe_noEmpleado_destino').val(noEmpleadoDestino);
    $('#modal_jefe_nombre_empleado').val(nombreEmpleado);
    $('#modal_jefe_nombre_documento').val(nombreDocumento);
    
    // Limpiar input file
    $('#modal_jefe_subir_archivo input[type="file"]').val('');
    
    $('#modal_jefe_subir_archivo').modal('show');
}

// Evento submit para el formulario de carga del jefe
$(document).on('submit', '#form_jefe_subir_requisito', function(e) {
    e.preventDefault();
    
    let formData = new FormData(this);
    formData.append('action', 'jefe_guardar_documento_subordinado');
    
    let noEmpleadoDestino = $('#modal_jefe_noEmpleado_destino').val();
    let nombreEmpleadoDestino = $('#modal_jefe_nombre_empleado').val();

    $.ajax({
        url: 'expediente_validacion_controller.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        beforeSend: function() {
            Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
        },
        success: function(res) {
            Swal.close();
            if (res.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Completado', text: res.message, timer: 1500, showConfirmButton: false });
                $('#modal_jefe_subir_archivo').modal('hide');
                
                // Recargar el modal de auditoría interno para que pinte los botones de firma y el archivo cargado
                ver_expediente_detalle_auditoria(noEmpleadoDestino, nombreEmpleadoDestino);
                
                // Refrescar avance en la tabla de fondo
                if (tablaAuditoriaPersonal) tablaAuditoriaPersonal.ajax.reload(null, false);
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        },
        error: function() {
            Swal.close();
            Swal.fire('Error', 'No se pudo completar la subida.', 'error');
        }
    });
});

/**
 * Procesa la firma del auditor y gestiona retroalimentación interactiva en caso de rechazo
 */
function ejecutar_firma_auditor(id_documento, dictamen, noEmpleado, nombreCompleto) {
    let idAuditor = $('#auditor_no_empleado').val();

    // <- CAMBIADO: Ahora si es 'Rechazar', pide de forma obligatoria el motivo en un prompt minimalista de Swal
    if (dictamen === 'Rechazar') {
        Swal.fire({
            title: 'Rechazar Requisito',
            text: 'Escribe el motivo del rechazo para que el colaborador sepa qué corregir:',
            input: 'text',
            inputPlaceholder: 'Ej: El documento está incompleto o ilegible...',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Confirmar Rechazo',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value) {
                    return '¡Es obligatorio escribir un motivo para rechazar el documento!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                enviar_voto_firma(id_documento, 'Rechazar', result.value, idAuditor, noEmpleado, nombreCompleto);
            }
        });
    } else {
        enviar_voto_firma(id_documento, 'Aprobar', '', idAuditor, noEmpleado, nombreCompleto);
    }
}

/**
 * Envía el dictamen final al nuevo controlador y refresca las tablas en caliente
 */
function enviar_voto_firma(id_documento, dictamen, comentario, idAuditor, noEmpleado, nombreCompleto) {
    $.ajax({
        url: 'expediente_validacion_controller.php', // <- CAMBIADO: Enrutamiento al archivo de validación
        type: 'POST',
        data: {
            action: 'procesar_firma_documento',
            id_documento: id_documento,
            dictamen: dictamen,
            comentario: comentario,
            no_auditor: idAuditor
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Dictamen Guardado',
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                });
                // Recargar dinámicamente el modal manteniendo la persistencia visual
                ver_expediente_detalle_auditoria(noEmpleado, nombreCompleto);
                // Refrescar el avance porcentual del index de fondo sin alterar la paginación
                if (tablaAuditoriaPersonal) tablaAuditoriaPersonal.ajax.reload(null, false);
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
}

function limpiar_filtros_auditoria() {
    $('#filtro_departamento').val('0').trigger('change.select2');
    $('#filtro_ingeniero').val('0').trigger('change.select2');
    listar_personal_auditoria();
}