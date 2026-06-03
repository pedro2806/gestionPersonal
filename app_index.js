// app_index.js - Controlador Front-end del Expediente del Empleado (MESS)
let tablaExpedienteEmpleado = null;

$(document).ready(function() {
    inicializarTablaEmpleado();

    $('#form_subir_requisito_empleado').on('submit', function(e) {
        e.preventDefault();
        procesarCargaPdf();
    });
});

/**
 * 1. Inicializa y configura DataTables para el expediente completo
 */
function inicializarTablaEmpleado() {
    if (tablaExpedienteEmpleado) {
        tablaExpedienteEmpleado.destroy();
    }

    let idUsuario = $('#usuario_sesion_id').val();

    tablaExpedienteEmpleado = $('#tabla_expediente_empleado').DataTable({
        "ajax": {
            "url": "expediente_usuario_controller.php",
            "type": "POST",
            "data": { 
                "action": "listar_expediente_propio",
                "id_usuario": idUsuario
            },
            "dataSrc": function(res) {
                return res.status === 'success' ? res.data : [];
            }
        },
        "columns": [
            { 
                "data": "nombre_tipo", 
                "className": "font-weight-bold text-dark ps-3 py-3" 
            },
            // TIPO (Usando clases sutiles nativas de Bootstrap 5.3)
            {
                "data": "categoria_funcion",
                "className": "text-center",
                "render": function(data) {
                    let cls = (data === 'Técnico') ? 'bg-warning-subtle text-warning-emphasis' : 'bg-primary-subtle text-primary-emphasis';
                    return `<span class="badge ${cls} border-0 px-2 py-1 font-weight-medium">${data}</span>`;
                }
            },
            // ÁREA / DEPARTAMENTO (Texto limpio y desaturado con text-muted)
            {
                "data": "area_especifica",
                "className": "text-center text-muted font-weight-normal"
            },
            // TIPO ALCANCE
            { 
                "data": "tipo_alcance",
                "className": "text-center",
                "render": function(data) {
                    return `<span class="badge bg-light text-secondary border border-light px-2 py-1">${data}</span>`;
                }
            },
            // ESTATUS GENERAL (Flat colors nativos)
            { 
                "data": "estatus_general",
                "className": "text-center",
                "render": function(data) {
                    let cls = 'bg-danger-subtle text-danger-emphasis'; 
                    if (data === 'En Revisión') cls = 'bg-warning-subtle text-warning-emphasis';
                    if (data === 'Aprobado') cls = 'bg-success-subtle text-success-emphasis';
                    return `<span class="badge ${cls} border-0 px-2 py-1 font-weight-bold">${data}</span>`;
                }
            },
            // VALIDACIÓN DE FIRMAS (Formato miniatura nativo)
            { 
                "data": null,
                "className": "text-center",
                "render": function(data, type, row) {
                    if (row.estatus_general === 'Pendiente de Subir') {
                        return '<span class="text-muted small italic" style="font-size:0.75rem;">Sin cargar</span>';
                    }

                    let firmas = [];
                    if (parseInt(row.requiere_rrhh) === 1) {
                        let cls = (parseInt(row.val_rrhh) === 3) ? 'bg-success-subtle text-success' : 'bg-light text-muted border border-light-subtle';
                        firmas.push(`<span class="badge ${cls} py-1 px-1.5" style="font-size:0.65rem;">RH</span>`);
                    }
                    if (parseInt(row.requiere_jefe_tecnico) === 1) {
                        let cls = (parseInt(row.val_jefe_tecnico) === 3) ? 'bg-dark text-white' : 'bg-light text-muted border border-light-subtle';
                        firmas.push(`<span class="badge ${cls} py-1 px-1.5" style="font-size:0.65rem;">JT</span>`);
                    }
                    if (parseInt(row.requiere_calidad) === 1) {
                        let cls = (parseInt(row.val_calidad) === 3) ? 'bg-warning-subtle text-warning' : 'bg-light text-muted border border-light-subtle';
                        firmas.push(`<span class="badge ${cls} py-1 px-1.5" style="font-size:0.65rem;">CAL</span>`);
                    }
                    if (parseInt(row.requiere_jefe_admin) === 1) {
                        let cls = (parseInt(row.val_jefe_admin) === 3) ? 'bg-info-subtle text-info' : 'bg-light text-muted border border-light-subtle';
                        firmas.push(`<span class="badge ${cls} py-1 px-1.5" style="font-size:0.65rem;">ADM</span>`);
                    }
                    return firmas.length > 0 ? `<div class="d-flex justify-content-center gap-1">${firmas.join('')}</div>` : '<span class="text-muted small">-</span>';
                }
            },
            // ACCIÓN (Uso de .btn-link y .text-* para limpiar bordes pesados)
            {
                "data": null,
                "className": "text-center pe-3",
                "orderable": false,
                "render": function(data, type, row) {
                    if (row.archivo_url) {
                        return `
                            <div class="d-flex justify-content-center gap-1">
                                <a href="${row.archivo_url}" target="_blank" class="btn btn-sm btn-link text-secondary p-1" title="Ver PDF">
                                    <i class="fas fa-eye"></i>
                                </a>
                                ${(row.estatus_general !== 'Aprobado' && row.subido_por === 'Empleado') ? `
                                <button class="btn btn-sm btn-link text-dark p-1" onclick="abrirModalSubidaFila(${row.id_tipo_documento}, '${row.nombre_tipo}')" title="Actualizar">
                                    <i class="fas fa-sync-alt"></i>
                                </button>` : ''}
                            </div>
                        `;
                    } else if (row.subido_por === 'Empleado') {
                        return `
                            <button class="btn btn-sm btn-dark font-weight-bold text-uppercase px-3 rounded shadow-none" onclick="abrirModalSubidaFila(${row.id_tipo_documento}, '${row.nombre_tipo}')" style="font-size:0.72rem;">
                                Subir
                            </button>
                        `;
                    } else {
                        return `<span class="badge bg-light text-muted border-0 font-weight-medium" style="font-size: 0.68rem; padding: 0.3rem 0.5rem;">Por: ${row.subido_por}</span>`;
                    }
                }
            }
        ],
        "responsive": true,
        "order": [[0, "asc"]],
        "pageLength": 15,
        "dom": 'rtip', // Mantiene limpio el layout superior de la tabla removiendo controles redundantes
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });
}

function abrirModalSubidaFila(idTipo, nombreDoc) {
    $('#modal_id_tipo_documento').val(idTipo);
    $('#modal_nombre_documento').val(nombreDoc);
    $('input[type="file"]').val(''); 
    $('#modal_cargar_archivo_fila').modal('show');
}

function procesarCargaPdf() {
    let formData = new FormData($('#form_subir_requisito_empleado')[0]);
    formData.append('action', 'guardar_documento_empleado');
    formData.append('noEmpleado', $('#usuario_sesion_id').val()); 

    $.ajax({
        url: 'expediente_usuario_controller.php',
        type: 'POST',
        data: formData,
        contentType: false,
        processData: false,
        dataType: 'json',
        beforeSend: function() {
            Swal.fire({
                title: 'Cargando...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        },
        success: function(res) {
            Swal.close();
            if (res.status === 'success') {
                $('#modal_cargar_archivo_fila').modal('hide');
                tablaExpedienteEmpleado.ajax.reload(null, false);
            } else {
                Swal.fire('Atención', res.message, 'warning');
            }
        },
        error: function() {
            Swal.close();
            Swal.fire('Fallo de Red', 'No se pudo comunicar con el servidor.', 'error');
        }
    });
}