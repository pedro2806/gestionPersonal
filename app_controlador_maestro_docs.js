// app_controlador_maestro_docs.js
let catalogos_documentos_cache = [];
let cache_jefes_html = '';
let cache_deptos_html = '';

//inicia document.ready
$(document).ready(function() {
    let id_usuario_sesion = $('#usuario_sesion_id').val();
    let contexto = $('#contexto_vista_maestra').val();
    
    // Cargar los catálogos necesarios para los selects (tipos de documento y alcances disponibles) en la vista administración de empleado
    preparar_selects_catalogos_usuario();

    if (contexto === 'Empleado') {
        cargar_mi_expediente_propio(id_usuario_sesion);
        obtener_perfil_tarjeta_maestra(id_usuario_sesion);
    } else if (contexto === 'Administracion') {
        cargar_tabla_administracion_docs();
    } else if (contexto === 'Configuracion') {
        cargar_tabla_config_catalogo();
    }

    // Al cambiar el tipo de documento, mostrar u ocultar el select de departamento de alcance según corresponda
    $(document).on('change', '#select_tipo_documento', function() {
        let id_seleccionado = $(this).val();
        let documento_config = catalogos_documentos_cache.find(d => d.id == id_seleccionado);
        if (documento_config && documento_config.tipo_alcance === 'Por Alcance') {
            $('#contenedor_depto_alcance').removeClass('d-none'); // <-- CORREGIDO
            $('#select_depto_alcance').attr('required', true);
        } else {
            $('#contenedor_depto_alcance').addClass('d-none');
            $('#select_depto_alcance').removeAttr('required').val('');
        }
    });

    // Manejar el envío del formulario de nuevo tipo de documento en la vista de configuración
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

    // Manejar el envío del formulario de subir requisito desde la tabla de expediente
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
                    cargar_mi_expediente_propio(id_destino); // Refresca el checklist al momento
                }
            }
        });
    });
});
//finaliza document.ready

// Cargar el expediente del usuario logueado y renderizar la tabla de requisitos
function cargar_mi_expediente_propio(id_usuario) {
    let contexto_rol = $('#contexto_vista_maestra').val(); // 'Empleado' o 'Administracion'

    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_expediente', id_usuario: id_usuario },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let html = '';
                
                response.data.forEach(function(req) {
                    // 1. Gestionar las insignias de firmas colectivas
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

                    // 2. Determinar el Badge del Estatus General
                    let badge_estatus = `<span class="badge bg-secondary text-white p-2">Pendiente</span>`;
                    if (req.estatus_general === 'En Revisión') badge_estatus = `<span class="badge bg-warning text-dark p-2">En Revisión</span>`;
                    if (req.estatus_general === 'Aprobado') badge_estatus = `<span class="badge bg-success text-white p-2">Aprobado</span>`;
                    if (req.estatus_general === 'Rechazado') badge_estatus = `<span class="badge bg-danger text-white p-2">Rechazado</span>`;

                    // 3. Columna de Acciones Dinámicas (Aquí está el truco de tu propuesta)
                    let celda_accion = '';
                    
                    if (req.subido) {
                        // Si ya se subió, mostramos el botón para ver el PDF
                        celda_accion = `<a href="${req.archivo_url}" target="_blank" class="btn btn-sm btn-outline-info font-weight-bold shadow-sm"><i class="fas fa-file-pdf mr-1"></i> Ver PDF</a>`;
                        
                        // Si está rechazado, le permitimos volver a subirlo (reemplazar)
                        if (req.estatus_general === 'Rechazado' && req.subido_por === contexto_rol) {
                            celda_accion += ` <button class="btn btn-sm btn-outline-warning font-weight-bold shadow-sm" onclick="abrir_modal_carga_directa(${req.id_tipo}, '${req.nombre_tipo}', '${req.nombre_depto}', '${req.id_depto}')"><i class="fas fa-sync-alt"></i> Reintentar</button>`;
                        }
                    } else {
                        // Si está pendiente, evaluamos si le corresponde subirlo a la vista actual
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

// Abrir el modal de carga inyectando los datos del requisito seleccionado
function abrir_modal_carga_directa(id_tipo, nombre_tipo, nombre_depto, id_depto) {
    $('#modal_upload_id_tipo').val(id_tipo);
    // Si el depto es 'null' (string de JS) o vacío, mandamos blanco para que PHP asuma NULL
    $('#modal_upload_id_depto').val((id_depto && id_depto !== 'null') ? id_depto : '');
    $('#modal_upload_nombre_doc').val(nombre_tipo);
    $('#modal_upload_nombre_depto').val(nombre_depto);
    
    $('#modal_subir_requisito').modal('show');
}

// Cargar los catálogos necesarios para los selects (tipos de documento y alcances disponibles) en la vista administración de empleado
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

// Obtener los datos del perfil del usuario para mostrar en la tarjeta de perfil en la vista de empleado
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

// Renderizar los datos del perfil del usuario en la tarjeta de perfil en la vista de empleado
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

// Función auxiliar para convertir el estatus de revisión en un badge visual
function convertir_badge_estatus(v) {
    if (v == 3) return `<span class="badge text-muted border">N/A</span>`;
    if (v == 1) return `<span class="badge bg-success text-white"><i class="fas fa-check"></i></span>`;
    if (v == 2) return `<span class="badge bg-danger text-white"><i class="fas fa-times"></i></span>`;
    return `<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i></span>`;
}

// Cargar la tabla de administración de empleados con sus datos y acciones disponibles
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
                    <td><strong>${emp.noEmpleado}</strong><img src="../loginMaster/${emp.url_foto}" alt="Foto" class="img-thumbnail" style="max-width: 50px; max-height: 40px; "></td>
                    <td><strong>${emp.nombreCompleto}</strong></td>
                    <td>${emp.telefonos}</td>   
                    <td><span class="small font-weight-bold text-uppercase text-muted">${emp.depto_base}</span></td>
                    <td>${emp.jefes_tecnicos || '<span class="text-muted">Solo Base</span>'}</td>
                    <td>${emp.estatus == 1 ? '<span class="badge bg-success text-white">Activo</span>' : '<span class="badge bg-danger text-white">Inactivo</span>'}</td>
                    <td class="text-center">${emp.total_docs}</td>
                    <td>
                        <div class="btn-group" role="group" aria-label="Acciones de Personal">
                            <button class="btn btn-sm btn-outline-warning font-weight-bold shadow-sm p-1 px-2" 
                                    onclick="abrir_modal_editar_usuario(${emp.noEmpleado})" 
                                    title="Editar Colaborador">
                                <i class="fas fa-edit fa-sm"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger font-weight-bold shadow-sm p-1 px-2" 
                                    onclick="confirmar_baja_logica(${emp.noEmpleado}, '${emp.nombreCompleto}')" 
                                    title="Baja Lógica">
                                <i class="fas fa-user-slash fa-sm"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-dark font-weight-bold shadow-sm p-1 px-2 small" 
                                    onclick="abrir_modal_jefes_tecnicos(${emp.noEmpleado}, '${emp.nombreCompleto}')" 
                                    title="Gestionar Habilidades">
                                <i class="fas fa-microscope fa-sm mr-1"></i>
                            </button>
                        </div>
                    </td>
                </tr>`;
            });
            $('#tbody_admin_docs').html(html);
            $('#tabla_admin_docs').DataTable();
        }
    });
}

// Abrir el modal para gestionar los jefes técnicos asignados a un empleado, cargando dinámicamente los selects con los datos de jefes y departamentos disponibles, así como los jefes técnicos actualmente asignados al empleado
function agregar_fila_alcance_dinamica(id_jefe = '', id_depto = '') {
    let row = `<tr class="fila-alcance">
        <td><select class="form-control form-control-sm select-jefe-tabla">${cache_jefes_html}</select></td>
        <td><select class="form-control form-control-sm select-depto-tabla">${cache_deptos_html}</select></td>
        <td><button class="btn btn-xs btn-danger" onclick="$(this).closest('tr').remove();">X</button></td>
    </tr>`;
    $('#tbody_modal_alcances').append(row);
}

// Abrir el modal para gestionar los jefes técnicos asignados a un empleado, cargando dinámicamente los selects con los datos de jefes y departamentos disponibles, así como los jefes técnicos actualmente asignados al empleado
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

// Guardar la asignación de jefes técnicos a un empleado, leyendo los selects dinámicos del modal y enviando la información al backend para su procesamiento y almacenamiento
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

// Cargar la tabla de configuración de catálogo de documentos con sus datos y acciones disponibles
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

// Cargar los catálogos necesarios para los selects (tipos de documento y alcances disponibles) en la vista administración de empleado, 
// para que estén listos al momento de abrir el modal de carga directa desde la tabla de expediente
function preparar_selects_catalogos_usuario() {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_catalogos_usuarios' },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                // 1. Llenar Select de Departamentos
                let html_dep = '<option value="">-- Seleccionar Departamento --</option>';
                res.departamentos.forEach(function(d) {
                    html_dep += `<option value="${d.id}">${d.departamento}</option>`;
                });
                $('#mod_departamento').html(html_dep);

                // 2. Llenar Select de Puestos
                let html_pue = '<option value="">-- Seleccionar Puesto --</option>';
                res.puestos.forEach(function(p) {
                    html_pue += `<option value="${p.id}">${p.puesto}</option>`;
                });
                $('#mod_puesto').html(html_pue);

                // 3. Llenar Select de Jefes Administrativos
                let html_jef = '<option value="0">-- Sin Jefe Asignado / Es Dirección --</option>';
                res.jefes.forEach(function(j) {
                    html_jef += `<option value="${j.noEmpleado}">${j.nombre}</option>`;
                });
                $('#mod_jefe').html(html_jef);
            } else {
                console.error("No se pudieron cargar los catálogos del personal.");
            }
        },
        error: function(xhr, status, error) {
            console.error("Fallo crítico de red al traer catálogos:", error);
        }
    });
}
//FUNCIUONES DE ADMINISTRACIÓN DE USUARIOS (EDICIÓN Y BAJA LÓGICA)
//FUNCIUONES DE ADMINISTRACIÓN DE USUARIOS (EDICIÓN Y BAJA LÓGICA)
// 1. LEER DATOS Y ABRIR MODAL DE EDICIÓN
function abrir_modal_editar_usuario(noEmpleado) {
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_datos_usuario_edicion', noEmpleado: noEmpleado },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                let u = res.data;
                
                // Inyectar valores al formulario del modal
                $('#mod_noEmpleado').val(u.noEmpleado);
                $('#mod_nombre').val(u.nombre);
                $('#mod_correo').val(u.correo);
                
                $('#mod_departamento').val(u.departamento);
                $('#mod_puesto').val(u.puesto);
                $('#mod_jefe').val(u.jefe);

                $('#mod_sexo').val(u.sexo);
                $('#mod_curp').val(u.curp);
                $('#mod_nss').val(u.nss);
                $('#mod_rfc').val(u.rfc);
                $('#mod_tipoContrato').val(u.tipo_contrato);
                $('#mod_tipoSangre').val(u.tipo_sangre);

                // Mostrar la ventana flotante
                $('#modal_editar_usuario').modal('show');
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
}

// 2. ENVIAR MODIFICACIÓN DE DATOS VÍA AJAX
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
                cargar_tabla_administracion_docs(); // Refresca tu DataTable general de personal
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
});

// 3. PROCESAR BAJA LÓGICA CON CONFIRMACIÓN ALERTA SWEETALERT2
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
                        cargar_tabla_administracion_docs(); // Recarga la cuadrícula
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        }
    });
}

function abrir_modal_telefonos() {
    $('#modal_telefonos').modal('show');
}

function cargar_telefonos_usuario() {
    let id_usuario = $('#usuario_destino_carga_id').val();
    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'obtener_telefonos_usuario', id_usuario: id_usuario },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                let html = '';
                res.data.forEach(function(t) {
                    html += `<tr>
                        <td>${t.tipo}</td>
                        <td>${t.numero}</td>
                        <td><button class="btn btn-sm btn-danger" onclick="eliminar_telefono(${t.id})">Eliminar</button></td>
                    </tr>`;
                });
                $('#tbody_telefonos').html(html);
            }
        }
    });
}

function eliminar_telefono(id_telefono) {
    Swal.fire({
        title: '¿Eliminar este teléfono?',
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonColor: '#858796',
        confirmButtonText: 'Sí, Eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: 'action_controller.php',
                type: 'POST',
                data: { action: 'eliminar_telefono_usuario', id_telefono: id_telefono },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        Swal.fire('Eliminado', res.message, 'success');
                        cargar_telefonos_usuario();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }
            });
        }
    });
}

$(document).on('submit', '#form_agregar_telefono', function(e) {
    e.preventDefault();
    let id_usuario = $('#usuario_destino_carga_id').val();
    let tipo = $('#nuevo_tipo_telefono').val();
    let numero = $('#nuevo_numero_telefono').val();

    $.ajax({
        url: 'action_controller.php',
        type: 'POST',
        data: { action: 'agregar_telefono_usuario', id_usuario: id_usuario, tipo: tipo, numero: numero },
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                Swal.fire('Agregado', res.message, 'success');
                $('#form_agregar_telefono')[0].reset();
                cargar_telefonos_usuario();
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }
    });
});

function agregar_campo_telefono() {
    let row = `<tr>
        <td><input type="text" class="form-control form-control-sm" name="nuevo_tipo_telefono" placeholder="Tipo (Ej: Móvil, Casa)"></td>
        <td><input type="text" class="form-control form-control-sm" name="nuevo_numero_telefono" placeholder="Número de Teléfono"></td>
        <td><button class="btn btn-sm btn-danger" onclick="$(this).closest('tr').remove();">X</button></td>
    </tr>`;
    $('#tbody_nuevo_telefono').append(row);
}