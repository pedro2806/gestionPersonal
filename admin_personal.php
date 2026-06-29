<?php 
// admin_personal.php - Panel de Administración General de Personal e Historial (MESS)
require_once 'conn.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MESS - Administración General</title>
    
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">    
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet">
</head>

<body id="page-top" class="bg-light">

    <div id="wrapper">
        <?php include 'menu.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include 'encabezado.php'; ?>

                <div class="container-fluid px-4 mt-3">
                    
                    <input type="hidden" id="usuario_sesion_id" value="1">
                    <input type="hidden" id="contexto_vista_maestra" value="Administracion">

                    <!-- SECCIÓN PRINCIPAL: CONTROL DE PERSONAL (ESTILO MINIMALISTA NATIVO) -->
                    <div class="card border-0 bg-transparent mb-5">
                        <div class="card-body p-0">
                            <div class="d-flex align-items-center justify-content-between mb-4 py-2 border-bottom border-light">
                                <div>
                                    <h5 class="font-weight-bold text-dark mb-1">Administración de Personal</h5>
                                    <p class="text-muted small mb-0">Gestión de colaboradores/Asignación de laboratorios técnicos</p>
                                </div>
                                <!-- dashhboard_personal.php es la vista de resumen general, admin_personal.php es la vista de gestión detallada -->
                                <button class="btn btn-sm btn-outline-secondary font-weight-bold" onclick="window.location.href='dashboard_personal.php'">
                                    <i class="fas fa-chart-pie mr-2"></i> Ver Resumen General
                                </button>                            
                                <button class="btn btn-sm btn-dark font-weight-bold text-uppercase px-3 py-2 rounded shadow-none" onclick="abrir_modal_nuevo_empleado()">
                                    <i class="fas fa-user-plus mr-2"></i>Nuevo Empleado
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table id="tabla_admin_personal" class="table table-hover align-middle bg-white rounded-3 overflow-hidden shadow-sm small text-secondary" width="100%">
                                    <thead class="table-light text-uppercase text-muted border-bottom" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                                        <tr>                                            
                                            <th class="py-1">Nombre Completo / Correo</th>
                                            <th class="py-1">Jefe Inmediato</th>
                                            <th class="py-1">Teléfonos</th>
                                            <th class="py-1">Departamento Base</th>
                                            <th class="py-1">Laboratorios Extras</th>
                                            <th class="py-1 text-center">Estatus</th>
                                            <th class="py-1 text-center">Días Disp</th>
                                            <th class="py-1 text-center pe-3" style="width: 140px;">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody_admin_docs" class="border-0">
                                        <!-- Renderizado dinámico vía app_controlador_maestro_docs.js -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ============================================================================ -->
            <!-- 🆕 FORMULARIO DE ALTA COMPLETO (BASADO EN EL DE MODIFICAR)      -->
            <!-- ============================================================================ -->
            <div class="modal fade" id="modal_nuevo_empleado" tabindex="-1" aria-labelledby="modal_nuevo_empleadoLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title font-weight-bold text-dark" id="modal_nuevo_empleadoLabel"><i class="fas fa-user-plus text-secondary mr-2"></i> Registrar Nuevo Colaborador</h5>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="form_registrar_nuevo_empleado">
                            <div class="modal-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="small text-muted font-weight-bold mb-1">No. Empleado</label>
                                        <input type="number" class="form-control shadow-none" name="nuevo_noEmpleado"  id="nuevo_noEmpleado" required placeholder="Ej: 276">
                                    </div>
                                    <div class="col-md-9">
                                        <label class="small text-muted font-weight-bold mb-1">Nombre Completo</label>
                                        <input type="text" class="form-control shadow-none" name="nuevo_nombre" required placeholder="Nombre(s) y Apellidos">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-muted font-weight-bold mb-1">Correo Electrónico</label>
                                        <input type="email" class="form-control shadow-none" name="nuevo_correo" required placeholder="correo@mess.com.mx">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small text-muted font-weight-bold mb-1">Sexo</label>
                                        <select class="form-select shadow-none" name="nuevo_sexo" required>
                                            <option value="" selected disabled>Seleccionar...</option>
                                            <option value="M">Masculino</option>
                                            <option value="F">Femenino</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small text-muted font-weight-bold mb-1">Fecha de Ingreso</label>
                                        <input type="date" class="form-control shadow-none" name="nuevo_fechaIngreso" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">Departamento Base</label>
                                        <select class="form-select shadow-none" name="nuevo_departamento" id="select_nuevo_departamento" required></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">Puesto Asignado</label>
                                        <select class="form-select shadow-none" name="nuevo_puesto" id="select_nuevo_puesto" required></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">Jefe Administrativo Directo</label>
                                        <select class="form-select shadow-none" name="nuevo_jefe" id="select_nuevo_jefe" required></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">CURP</label>
                                        <input type="text" class="form-control text-uppercase shadow-none" name="nuevo_curp" maxlength="18" placeholder="18 caracteres">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">RFC</label>
                                        <input type="text" class="form-control text-uppercase shadow-none" name="nuevo_rfc" maxlength="13" placeholder="13 caracteres">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">NSS (Seguro Social)</label>
                                        <input type="text" class="form-control shadow-none" name="nuevo_nss" maxlength="11" placeholder="11 dígitos">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-muted font-weight-bold mb-1">Tipo de Contrato</label>
                                        <select class="form-select shadow-none" name="nuevo_tipoContrato" required>
                                            <option value="PLANTA">PLANTA</option>
                                            <option value="CONTRATO">CONTRATO</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-muted font-weight-bold mb-1">Tipo de Sangre</label>
                                        <select class="form-select shadow-none" name="nuevo_tipoSangre">
                                            <option value="" selected disabled>Seleccionar...</option>
                                            <option value="ARH+">ARH+</option>
                                            <option value="ARH-">ARH-</option>
                                            <option value="BRH+">BRH+</option>
                                            <option value="BRH-">BRH-</option>
                                            <option value="ABRH+">ABRH+</option>
                                            <option value="ABRH-">ABRH-</option>
                                            <option value="ORH+">ORH+</option>
                                            <option value="ORH-">ORH-</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-sm btn-secondary font-weight-bold" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" onclick="guardar_nuevo_empleado_sistema()" class="btn btn-sm btn-dark font-weight-bold px-4 shadow-none">Guardar Registro</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ============================================================================ -->
            <!-- 📄 FORMULARIO MAESTRO DE EDICIÓN DE USUARIO          -->
            <!-- ============================================================================ -->
            <div class="modal fade" id="modal_editar_usuario" tabindex="-1" aria-labelledby="modal_editar_usuarioLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header bg-light">
                            <h5 class="modal-title font-weight-bold text-dark" id="modal_editar_usuarioLabel"><i class="fas fa-user-edit text-secondary mr-2"></i> Modificar Datos de Colaborador</h5>
                            <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="form_modificar_usuario_maestro">
                            <div class="modal-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="small text-muted font-weight-bold mb-1">No. Empleado (Fijo)</label>
                                        <input type="number" class="form-control bg-gray-200 fw-bold shadow-none" name="mod_noEmpleado" id="mod_noEmpleado" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-muted font-weight-bold mb-1">Nombre Completo</label>
                                        <input type="text" class="form-control shadow-none" name="mod_nombre" id="mod_nombre" required>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end">
                                        <button type="button" class="btn btn-sm btn-outline-dark w-100 py-2 font-weight-bold shadow-none" onclick="abrir_modal_cambiar_foto()"><i class="fas fa-camera mr-1"></i> Fotografía</button>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-muted font-weight-bold mb-1">Correo Electrónico</label>
                                        <input type="email" class="form-control shadow-none" name="mod_correo" id="mod_correo" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small text-muted font-weight-bold mb-1">Sexo</label>
                                        <select class="form-select shadow-none" name="mod_sexo" id="mod_sexo" required>
                                            <option value="M">Masculino</option>
                                            <option value="F">Femenino</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="small text-muted font-weight-bold mb-1">Fecha de Ingreso</label>
                                        <input type="date" class="form-control shadow-none" name="mod_fechaIngreso" id="mod_fechaIngreso" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">Departamento Base</label>
                                        <select class="form-select shadow-none" name="mod_departamento" id="mod_departamento" required></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">Puesto Asignado</label>
                                        <select class="form-select shadow-none" name="mod_puesto" id="mod_puesto" required></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">Jefe Administrativo Directo</label>
                                        <select class="form-select shadow-none" name="mod_jefe" id="mod_jefe" required></select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">CURP</label>
                                        <input type="text" class="form-control text-uppercase shadow-none" name="mod_curp" id="mod_curp" maxlength="18">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">RFC</label>
                                        <input type="text" class="form-control text-uppercase shadow-none" name="mod_rfc" id="mod_rfc" maxlength="13">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted font-weight-bold mb-1">NSS (Seguro Social)</label>
                                        <input type="text" class="form-control shadow-none" name="mod_nss" id="mod_nss" maxlength="11">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-muted font-weight-bold mb-1">Tipo de Contrato</label>
                                        <input type="text" class="form-control shadow-none" name="mod_tipoContrato" id="mod_tipoContrato">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small text-muted font-weight-bold mb-1">Tipo de Sangre</label>
                                        <input type="text" class="form-control shadow-none" name="mod_tipoSangre" id="mod_tipoSangre" maxlength="5">
                                    </div>
                                </div>
                                <div class="row g-3 mt-2 border-top pt-3">
                                    <div class="col-md-6">
                                        <label class="small text-muted font-weight-bold mb-1">Teléfonos de Contacto</label>
                                        <button type="button" class="btn btn-sm btn-outline-secondary font-weight-bold d-block rounded shadow-none" onclick="abrir_modal_telefonos()">
                                            <i class="fas fa-phone mr-1"></i> Editar Teléfonos
                                        </button>
                                    </div>                                        
                                </div>
                            </div>
                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-sm btn-secondary font-weight-bold" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-sm btn-success font-weight-bold px-4 shadow-none">Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ============================================================================ -->
            <!-- 📄 MODAL TELÉFONOS CORPORATIVOS DE USUARIO                 -->
            <!-- ============================================================================ -->
            <div class="modal fade" id="modal_telefonos" tabindex="-1" aria-labelledby="modal_telefonosLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border border-dark border-2 shadow">
                        <div class="modal-header bg-success border-bottom-0 p-3 pb-2">
                            <h5 class="modal-title fw-bold text-white small text-uppercase" id="modal_telefonosLabel"><i class="fas fa-phone me-2"></i> Editar Teléfonos</h5>
                            <button type="button" class="btn-close btn-close-white small shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="form_modificar_telefonos">
                            <div class="modal-body p-4">
                                <input type="hidden" id="modal_telefonos_noEmpleado">
                                <div id="contenedor_telefonos" class="d-flex flex-column gap-3"></div>
                                <button type="button" class="btn btn-sm btn-outline-secondary fw-medium rounded-2 mt-3 shadow-none" onclick="agregar_campo_telefono()">
                                    <i class="fas fa-plus me-1"></i> Agregar Teléfono
                                </button>
                            </div>
                            <div class="modal-footer border-top-0 p-4">
                                <button type="button" class="btn btn-sm btn-light fw-medium px-3 text-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-sm btn-dark fw-medium px-4 shadow-none"><i class="fas fa-save me-1"></i> Guardar Teléfonos</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ============================================================================ -->
            <!-- 📄 FORMULARIO DE ASIGNACIÓN DE JEFES TÉCNICOS / HABILIDADES LABORAT -->
            <!-- ============================================================================ -->
            <div class="modal fade" id="modal_gestion_jefes_tecnicos" tabindex="-1" aria-labelledby="modal_gestion_jefes_tecnicosLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
                            <h5 class="modal-title fw-semibold text-secondary h6 text-uppercase" id="modal_gestion_jefes_tecnicosLabel"><i class="fas fa-user-shield me-2"></i> Alcances y Jefaturas</h5>
                            <button type="button" class="btn-close small shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-4">
                            <input type="hidden" id="modal_jt_id_empleado">
                            <div class="p-3 bg-light rounded-3 mb-4 border-0">
                                <span class="small text-muted d-block mb-1">Colaborador seleccionado:</span>
                                <strong id="modal_jt_nombre_empleado" class="text-dark fw-semibold fs-6"></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="small fw-semibold text-uppercase text-muted mb-0">Matriz de Habilidades</label>
                                <button type="button" class="btn btn-sm btn-outline-dark fw-medium rounded-2 shadow-none" onclick="agregar_fila_alcance_dinamica()">
                                    <i class="fas fa-plus me-1"></i> Agregar Alcance
                                </button>
                            </div>
                            <div class="table-responsive border rounded-3" style="max-height: 300px;">
                                <table class="table table-hover align-middle mb-0 small text-secondary">
                                    <thead class="table-light text-uppercase">
                                        <tr>
                                            <th class="py-2">Jefe Técnico Responsable</th>
                                            <th class="py-2">Departamento / Laboratorio</th>
                                            <th class="text-center py-2" style="width: 80px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody_modal_alcances"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 p-4">
                            <button type="button" class="btn btn-sm btn-light fw-medium px-3 text-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-sm btn-dark fw-medium px-4 shadow-none" onclick="guardar_assignacion_compuesta_jefes()"><i class="fas fa-save me-1"></i> Guardar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================================ -->
            <!-- 📄 FORMULARIO MAESTRO DE CAMBIO DE FOTO FÍSICA DE USUARIO                    -->
            <!-- ============================================================================ -->
            <div class="modal fade" id="modal_cambiar_foto" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" style="max-width: 450px;">
                    <div class="modal-content border border-dark border-2 shadow-lg">
                        <div class="modal-header bg-secondary border-bottom-0 pt-0 px-4 pb-0">
                            <h5 class="modal-title fw-semibold text-white h6 text-uppercase"><i class="fas fa-camera me-2"></i> Cambiar Foto del Colaborador</h5>
                            <button type="button" class="btn-close btn-close-white small shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="form_cambiar_foto" enctype="multipart/form-data">
                            <div class="modal-body px-4 pt-3">
                                <input type="hidden" id="modal_foto_noEmpleado" name="noEmpleado">
                                <div class="row g-3 text-center">
                                    <div class="col-6">
                                        <label class="small fw-medium text-muted mb-2 d-block">Foto Actual</label>
                                        <img id="modal_foto_actual" src="/incidencias/img/undraw_profile.svg" class="img-thumbnail rounded-circle" style="width:120px;height:120px;object-fit:cover;" onerror="this.onerror=null;this.src='/incidencias/img/undraw_profile.svg';">
                                    </div>
                                    <div class="col-6">
                                        <label class="small fw-medium text-muted mb-2 d-block">Vista Previa</label>
                                        <img id="modal_foto_preview" src="/incidencias/img/undraw_profile.svg" class="img-thumbnail rounded-circle" style="width:120px;height:120px;object-fit:cover;opacity:0.4;">
                                    </div>
                                </div>
                                <div class="mt-3 text-start">
                                    <label class="small text-muted font-weight-bold mb-1">Selecciona una imagen (JPG/PNG, máx 2MB)</label>
                                    <input type="file" id="modal_foto_archivo" name="foto" accept="image/jpeg,image/png" class="form-control form-control-sm shadow-none">
                                </div>
                            </div>
                            <div class="modal-footer border-top-0 p-4">
                                <button type="button" class="btn btn-sm btn-light fw-medium px-3 text-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-sm btn-dark fw-medium px-4 shadow-none"><i class="fas fa-save me-1"></i> Guardar Foto</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ============================================================================ -->
             <!-- 📄 MODAL DE CONFIGURACIÓN DE DÍAS DE VACACIONES DISPONIBLES                 -->
            <!-- ============================================================================ -->
            <div class="modal fade" id="modal_gestion_dias_vacaciones" tabindex="-1" aria-labelledby="modal_dias_vacacionesLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border border-dark border-2 shadow">
                        <div class="modal-header bg-primary border-bottom-0 p-3 pb-2">
                            <h5 class="modal-title fw-bold text-white small text-uppercase" id="modal_dias_vacacionesLabel"><i class="fas fa-suitcase-rolling me-2"></i> Ver Detalle Vacaciones</h5>
                            <button type="button" class="btn-close btn-close-white small shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>                        
                        <div class="modal-body p-4">
                            <div class="border-bottom pb-2 mb-3">
                                <h5 class="text-primary mb-0 fw-bold" id="modal_dv_nombre">---</h5>
                                <small class="text-muted">Empleado: #<span id="modal_dv_noEmpleado">---</span> | Ingreso: <span id="modal_dv_fechaIngreso">---</span></small>
                            </div>

                            <table class="table table-sm table-borderless align-middle mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-muted py-2">Antigüedad:</td>
                                        <td class="text-end fw-bold py-2" id="modal_dv_antiguedad">---</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted py-2">Días por Ley (Año Actual):</td>
                                        <td class="text-end fw-bold py-2" id="modal_dv_dias_ley_actual">---</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted py-2">Días Solicitados:</td>
                                        <td class="text-end fw-bold text-warning py-2" id="modal_dv_diasSol">---</td>
                                    </tr>
                                    <tr class="table-success rounded">
                                        <td class="text-success fw-bold py-2 ps-2">Días Disponibles:</td>
                                        <td class="text-end text-success fw-black fs-5 py-2 pe-2" id="modal_dv_diasdisponibles">---</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <footer class="sticky-footer bg-light mt-auto border-top-0"><div class="container my-auto"><div class="copyright text-center my-auto text-muted small"><span>Copyright &copy; MESS 2026</span></div></div></footer>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>    
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <script src="app_controlador_maestro_docs.js"></script>
</body>
</html>