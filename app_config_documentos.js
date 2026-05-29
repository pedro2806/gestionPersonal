// app_config_documentos.js - Controlador Front-end de Configuración Documental (MESS)

let tablaDocsInstance = null;

$(document).ready(function() {
    inicializarTablaConfigDocs();
});

/**
 * 1. Inicializa el motor de DataTables y jala los registros mediante AJAX
 */
function inicializarTablaConfigDocs() {
    if (tablaDocsInstance) {
        tablaDocsInstance.destroy();
    }

    tablaDocsInstance = $('#tabla_config_docs').DataTable({
        "ajax": {
            "url": "documentos_controller.php",
            "type": "POST",
            "data": { "action": "listar_config_documentos" },
            "dataSrc": function(res) {
                return res.status === 'success' ? res.data : [];
            }
        },
        "columns": [
            { "data": "id", "className": "text-center font-weight-bold text-secondary" },
            { "data": "nombre_tipo", "className": "font-weight-bold text-dark" },
            { 
                "data": "subido_por",
                "render": function(data) {
                    let badgeClass = 'bg-light text-dark border';
                    if (data === 'RH') badgeClass = 'bg-warning text-white';
                    if (data === 'Jefe Técnico') badgeClass = 'bg-dark text-white';
                    return `<span class="badge ${badgeClass}">${data}</span>`;
                }
            },
            { 
                "data": "tipo_alcance",
                "render": function(data) {
                    let cls = data === 'Por Puesto' ? 'badge-universal' : 'badge-scope';
                    return `<span class="badge ${cls}"><i class="fas ${data === 'Por Puesto' ? 'fa-user-tag' : 'fa-bullseye'} mr-1"></i>${data}</span>`;
                }
            },
            { 
                "data": "perfil_puesto",
                "render": function(data) {
                    let cls = 'bg-secondary text-white';
                    if (data === 'Solo Técnico') cls = 'bg-outline-danger border border-danger text-danger';
                    if (data === 'Solo Administrativo') cls = 'bg-outline-info border border-info text-info';
                    return `<span class="badge ${cls} font-weight-bold">${data}</span>`;
                }
            },
            { 
                "data": "categoria_funcion",
                "render": function(data) {
                    let cls = data === 'Técnico' ? 'badge-tecnico' : 'badge-administrativo';
                    return `<span class="badge ${cls}"><i class="fas ${data === 'Técnico' ? 'fa-flask' : 'fa-file-invoice-dollar'} mr-1"></i>${data}</span>`;
                }
            },
            { 
                "data": null,
                "render": function(data, type, row) {
                    let firmas = [];
                    if (parseInt(row.requiere_rrhh) === 1) firmas.push('<span class="badge bg-success p-1 m-1">RH</span>');
                    if (parseInt(row.requiere_jefe_tecnico) === 1) firmas.push('<span class="badge bg-secondary p-1 m-1">J. Técnico</span>');
                    if (parseInt(row.requiere_calidad) === 1) firmas.push('<span class="badge bg-warning text-dark p-1 m-1">Calidad</span>');
                    if (parseInt(row.requiere_jefe_admin) === 1) firmas.push('<span class="badge bg-info text-dark p-1 m-1">J. Admin</span>');
                    return firmas.length > 0 ? firmas.join(' ') : '<span class="text-muted small italic">Ninguna (Auto-aprueba)</span>';
                }
            },
            {
                "data": null,
                "className": "text-center",
                "orderable": false,
                "render": function(data, type, row) {
                    return `
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-outline-primary" onclick="abrirModalEditarDocumento(${row.id})" title="Editar Parámetros">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarTipoDocumento(${row.id})" title="Eliminar del Catálogo">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        "responsive": true,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });
}

/**
 * 2. Limpia el formulario y abre el modal en modo "Crear Nuevo"
 */
function abrirModalNuevoDocumento() {
    $('#cfg_id_documento').val('');
    $('#cfg_nombre_tipo').val('');
    $('#cfg_subido_por').val('Empleado');
    $('#cfg_tipo_alcance').val('Por Puesto');
    $('#cfg_perfil_puesto').val('Todos');
    $('#cfg_categoria_funcion').val('Administrativo');
    
    // Desmarcar todos los switches por defecto
    $('#cfg_req_rrhh').prop('checked', false);
    $('#cfg_req_jefe_tecnico').prop('checked', false);
    $('#cfg_req_calidad').prop('checked', false);
    $('#cfg_req_jefe_admin').prop('checked', false);

    $('#modal_header_style').removeClass('bg-warning text-dark').addClass('bg-primary text-white');
    $('#modal_titulo').html('<i class="fas fa-plus-circle mr-2"></i>Configurar Nuevo Requisito Documental');
    $('#modal_config_doc').modal('show');
}

/**
 * 3. Recupera los datos de un ID específico y abre el modal en modo "Edición"
 */
function abrirModalEditarDocumento(id) {
    $.ajax({
        url: 'documentos_controller.php',
        type: 'POST',
        data: { action: 'obtener_config_documento', id: id },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                let d = res.data;
                $('#cfg_id_documento').val(d.id);
                $('#cfg_nombre_tipo').val(d.nombre_tipo);
                $('#cfg_subido_por').val(d.subido_por);
                $('#cfg_tipo_alcance').val(d.tipo_alcance);
                $('#cfg_perfil_puesto').val(d.perfil_puesto);
                $('#cfg_categoria_funcion').val(d.categoria_funcion);
                
                // Asignar los valores a los switches booleanos
                $('#cfg_req_rrhh').prop('checked', parseInt(d.requiere_rrhh) === 1);
                $('#cfg_req_jefe_tecnico').prop('checked', parseInt(d.requiere_jefe_tecnico) === 1);
                $('#cfg_req_calidad').prop('checked', parseInt(d.requiere_calidad) === 1);
                $('#cfg_req_jefe_admin').prop('checked', parseInt(d.requiere_jefe_admin) === 1);

                $('#modal_header_style').removeClass('bg-primary text-white').addClass('bg-warning text-dark');
                $('#modal_titulo').html('<i class="fas fa-edit mr-2"></i>Modificar Parámetros del Requisito ID: ' + d.id);
                $('#modal_config_doc').modal('show');
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
}

/**
 * 4. Procesa la acción de guardar (Inserta o Actualiza en la BD según corresponda)
 */
function guardarConfiguracionDocumento() {
    let id = $('#cfg_id_documento').val();
    let nombre = $('#cfg_nombre_tipo').val().trim();
    let subidoPor = $('#cfg_subido_por').val();
    let tipoAlcance = $('#cfg_tipo_alcance').val();
    let perfilPuesto = $('#cfg_perfil_puesto').val();
    let categoriaFuncion = $('#cfg_categoria_funcion').val();
    
    // Obtener los valores binarios de los switches
    let reqRRHH = $('#cfg_req_rrhh').is(':checked') ? 1 : 0;
    let reqJefeTecnico = $('#cfg_req_jefe_tecnico').is(':checked') ? 1 : 0;
    let reqCalidad = $('#cfg_req_calidad').is(':checked') ? 1 : 0;
    let reqJefeAdmin = $('#cfg_req_jefe_admin').is(':checked') ? 1 : 0;

    if (!nombre) {
        Swal.fire('Campos Vacíos', 'Por favor, introduce el nombre del documento.', 'warning');
        return;
    }

    // Si el ID tiene valor, la acción cambia automáticamente hacia una actualización
    let accionProcesar = id === '' ? 'guardar_config_documento' : 'actualizar_config_documento';

    $.ajax({
        url: 'documentos_controller.php',
        type: 'POST',
        data: {
            action: accionProcesar,
            id: id,
            nombre_tipo: nombre,
            subido_por: subidoPor,
            tipo_alcance: tipoAlcance,
            perfil_puesto: perfilPuesto,
            categoria_funcion: categoriaFuncion,
            requiere_rrhh: reqRRHH,
            requiere_jefe_tecnico: reqJefeTecnico,
            requiere_calidad: reqCalidad,
            requiere_jefe_admin: reqJefeAdmin
        },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire('Éxito', res.message, 'success');
                $('#modal_config_doc').modal('hide');
                tablaDocsInstance.ajax.reload(null, false); // Refrescar la tabla manteniendo la paginación activa
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
}

/**
 * 5. Elimina un tipo de documento del catálogo con advertencia previa de SweetAlert
 */
function eliminarTipoDocumento(id) {
    Swal.fire({
        title: '¿Remover requisito del catálogo?',
        text: "¡Atención! Esta acción puede dejar huérfanos archivos ya cargados por los colaboradores que dependan de este ID.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'documentos_controller.php',
                type: 'POST',
                data: { action: 'eliminar_config_documento', id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Eliminado', res.message, 'success');
                        tablaDocsInstance.ajax.reload(null, false);
                    } else {
                        Swal.fire('Aviso', res.message, 'info');
                    }
                }
            });
        }
    });
}