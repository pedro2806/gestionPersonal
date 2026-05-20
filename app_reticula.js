// app_reticula.js - Controlador JS en snake_case e invocaciones onclick

$(document).ready(function() {
    cargar_lista_usuarios();
});

// Carga asíncrona inicial de todos los colaboradores
function cargar_lista_usuarios() {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_usuarios' },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let html_tabla = '';
                response.data.forEach(function(usuario) {
                    html_tabla += `
                        <tr>
                            <td class="font-weight-bold text-gray-800">${usuario.noEmpleado}</td>
                            <td>${usuario.nombre}</td>
                            <td>${usuario.departamento || '<span class="text-muted">Sin Asignar</span>'}</td>
                            <td>${usuario.puesto || '<span class="text-muted">Sin Asignar</span>'}</td>
                            <td>
                                <span class="badge badge-${usuario.estatus == 1 ? 'success' : 'danger'} p-2">
                                    ${usuario.estatus == 1 ? 'Activo' : 'Inactivo'}
                                </span>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-primary btn-sm shadow-sm font-weight-bold" onclick="ver_expediente_usuario(${usuario.id})">
                                    <i class="fas fa-folder-open mr-1"></i> Ver Expediente
                                </button>
                            </td>
                        </tr>
                    `;
                });
                $('#tabla_usuarios_body').html(html_tabla);
            } else {
                console.error('Error al mapear usuarios: ' + response.message);
            }
        },
        error: function() {
            console.error('Error de comunicación con action_controller.php');
        }
    });
}

// Carga de CV interno y retícula individualizada
function ver_expediente_usuario(id_usuario) {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { 
            action: 'obtener_cv_usuario',
            id_usuario: id_usuario
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let perfil = response.perfil;
                let reticula = response.reticula;

                // Mapeo en la interfaz del CV
                $('#cv_nombre').text(perfil.nombre);
                $('#cv_puesto').text(perfil.puesto || 'Puesto no asignado');
                $('#cv_departamento').text(perfil.departamento || 'General');
                $('#cv_empleado_num').text(perfil.noEmpleado);
                $('#cv_correo').text(perfil.correo || 'S/C');
                $('#cv_ingreso').text(perfil.fechaIngreso || 'N/A');
                $('#cv_contrato').text(perfil.tipoContrato || 'Planta');
                $('#cv_rfc').text(perfil.rfc || 'S/R');
                
                // Pasar id al modal de inserción
                $('#modal_id_usuario').val(perfil.id);

                // Renderizado de la lista de cursos asignados
                let html_reticula = '';
                if (reticula.length === 0) {
                    html_reticula = `<tr><td colspan="6" class="text-center text-muted py-3">El colaborador no cuenta con historial de capacitación registrado.</td></tr>`;
                } else {
                    reticula.forEach(function(item) {
                        let badge_clase = 'badge-warning';
                        if (item.estatus_curso === 'Completado') badge_clase = 'badge-success';
                        if (item.estatus_curso === 'Pendiente') badge_clase = 'badge-secondary';

                        html_reticula += `
                            <tr>
                                <td><strong>${item.nombre_curso}</strong></td>
                                <td>${item.institucion}</td>
                                <td><span class="badge badge-light border text-dark">${item.horas} hrs</span></td>
                                <td>${item.fecha_finalizacion}</td>
                                <td><span class="badge ${badge_clase} p-2">${item.estatus_curso}</span></td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-circle btn-outline-danger" onclick="eliminar_curso_reticula(${item.id}, ${perfil.id})">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#tabla_reticula_body').html(html_reticula);

                // Conmutación visual de secciones
                $('#seccion_lista_usuarios').addClass('d-none');
                $('#seccion_expediente_cv').removeClass('d-none');
            } else {
                alert(response.message);
            }
        }
    });
}

// Retornar al menú principal de usuarios
function regresar_a_lista() {
    $('#seccion_expediente_cv').addClass('d-none');
    $('#seccion_lista_usuarios').removeClass('d-none');
    cargar_lista_usuarios();
}

// Inserción de nuevos elementos de capacitación
function guardar_curso_reticula() {
    let form_data = {
        action: 'agregar_curso_reticula',
        id_usuario: $('#modal_id_usuario').val(),
        id_curso: $('#modal_select_curso').val(),
        fecha_finalizacion: $('#modal_fecha').val(),
        calificacion: $('#modal_calificacion').val(),
        estatus_curso: $('#modal_estatus').val()
    };

    if (!form_data.id_curso || !form_data.fecha_finalizacion) {
        alert('Por favor selecciona el curso e ingresa la fecha de finalización.');
        return;
    }

    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: form_data,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Esconder el modal usando la librería jQuery/Bootstrap integrada
                $('#modal_agregar_curso').modal('hide');
                // Forzar limpieza visual de backdrop de SB Admin
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                
                // Recargar el panel del colaborador activo
                ver_expediente_usuario(form_data.id_usuario);
            } else {
                alert(response.message);
            }
        }
    });
}

// Eliminación controlada de cursos de la retícula
function eliminar_curso_reticula(id_registro, id_usuario) {
    if (confirm('¿Estás seguro de remover esta capacitación del expediente del colaborador?')) {
        $.ajax({
            url: 'action_controller.php',
            type: 'POST',
            data: {
                action: 'eliminar_curso_reticula',
                id_registro: id_registro
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    ver_expediente_usuario(id_usuario);
                } else {
                    alert(response.message);
                }
            }
        });
    }
}
