$(document).ready(function() {
    // Inicializar el listado general de administración documental
    cargar_tabla_administracion_docs();
});

// 1. Cargar listado de empleados y su estatus de expediente
function cargar_tabla_administracion_docs() {
    $.ajax({
        url: 'action_controlador_docs.php',
        type: 'POST',
        data: { action: 'listar_administracion_empleados' },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let html_filas = '';
                response.data.forEach(function(emp) {
                    // Contar cuántos documentos tiene aprobados vs pendientes
                    let insignias_jefes = emp.jefes_tecnicos ? emp.jefes_tecnicos.split(',').map(j => `<span class="badge bg-light text-dark border small m-1"><i class="fas fa-microscope text-primary mr-1"></i>${j}</span>`).join('') : '<span class="text-muted small">Sin asignar</span>';
                    
                    html_filas += `
                        <tr>
                            <td><strong>${emp.noEmpleado}</strong></td>
                            <td>${emp.nombreCompleto}</td>
                            <td><span class="small font-weight-bold text-uppercase text-muted">${emp.departamento}</span></td>
                            <td><div class="d-flex flex-wrap">${insignias_jefes}</div></td>
                            <td class="text-center">
                                <span class="badge badge-info px-2 py-1">${emp.total_docs} Cargados</span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-primary font-weight-bold shadow-sm" onclick="ver_expediente_empleado(${emp.noEmpleado}, '${emp.nombreCompleto}')">
                                    <i class="fas fa-folder-open mr-1"></i> Expediente
                                </button>
                                <button class="btn btn-sm btn-dark font-weight-bold shadow-sm" onclick="abrir_modal_jefes_tecnicos(${emp.noEmpleado}, '${emp.nombreCompleto}')">
                                    <i class="fas fa-user-cog mr-1"></i> Jefes Téc.
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                // Destruir instancia previa si existe para evitar duplicados
                if ($.fn.DataTable.isDataTable('#tabla_admin_docs')) {
                    $('#tabla_admin_docs').DataTable().destroy();
                }
                
                $('#tbody_admin_docs').html(html_filas);
                
                // Inicializar DataTables en español
                $('#tabla_admin_docs').DataTable({
                    responsive: true,
                    searching: true,
                    pageLength: 10,
                    dom: 'rtip',
                    language: {
                        url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                    }
                });
            }
        }
    });
}

// 2. Abrir la gestión de Jefes Técnicos para el empleado
function abrir_modal_jefes_tecnicos(id_empleado, nombre_empleado) {
    $('#modal_jt_id_empleado').val(nombre_empleado);
    $('#modal_jt_nombre_empleado').text(nombre_empleado);
    
    // Cargar los jefes técnicos actuales que tiene asignados y limpiar el Select2
    $.ajax({
        url: 'action_controlador_docs.php',
        type: 'POST',
        data: { action: 'obtener_jefes_tecnicos_asignados', id_usuario_empleado: id_empleado },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // response.data debe ser un arreglo con los IDs de los jefes asignados
                $('#select_jefes_tecnicos_multi').val(response.data).trigger('change');
                $('#modal_gestion_jefes_tecnicos').modal('show');
            }
        }
    });
}

// 3. Guardar la asignación múltiple de Jefes Técnicos
function guardar_asignacion_jefes_tecnicos() {
    let id_empleado = $('#modal_jt_id_empleado').val();
    let jefes_seleccionados = $('#select_jefes_tecnicos_multi').val(); // Esto devuelve un array de IDs

    $.ajax({
        url: 'action_controlador_docs.php',
        type: 'POST',
        data: { 
            action: 'guardar_jefes_tecnicos_empleado', 
            id_usuario_empleado: id_empleado, 
            jefes_ids: jefes_seleccionados 
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire('¡Asignado!', response.message, 'success');
                $('#modal_gestion_jefes_tecnicos').modal('hide');
                cargar_tabla_administracion_docs();
            } else {
                Swal.fire('Error', response.message, 'error');
            }
        }
    });
}

// 4. Redirección o cambio de vista simulado para auditar el expediente de un usuario
function ver_expediente_empleado(id_usuario, nombre_usuario) {
    // Aquí puedes redirigir a tu vista de expediente_digital pasando el ID por GET
    // o cargar un modal grande. Lo ideal para SB Admin es abrir la vista del expediente:
    window.location.href = `expediente_digital.php?id_colaborador=${id_usuario}`;
}