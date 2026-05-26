<?php require_once 'conn.php'; ?>
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
    
    <!-- Ligeros ajustes minimalistas usando CSS puro sobre las tablas de DataTables -->
    <style>
        .form-control:focus, .form-select:focus {
            box-shadow: none;
            border-color: #6c757d;
        }
        table.dataTable border-bottom {
            border-bottom: 1px solid #dee2e6 !important;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #f8f9fa !not-important;
            border-color: #dee2e6 !important;
        }
    </style>
</head>
<body id="page-top" class="bg-light">
    <div id="wrapper">
        <?php include 'menu.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include 'encabezado.php'; ?>
                
                <div class="container-fluid px-4">
                    <!-- Título de sección más limpio -->
                    <div class="d-sm-flex align-items-center justify-content-between my-4">
                        <h1 class="h4 mb-0 text-secondary fw-semibold">Administración de Personal</h1>
                    </div>

                    <input type="hidden" id="contexto_vista_maestra" value="Administracion">

                    <!-- Contenedor Principal Plano (Card Estilo Flat) -->
                    <div class="card border-0 rounded-3 mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-3">
                                <span class="text-secondary me-2"><i class="fas fa-paste"></i></span>
                                <h6 class="m-0 fw-semibold text-secondary">Matriz General de Control</h6>
                            </div>
                            
                            <div class="table-responsive">
                                <!-- Tabla limpia sin bordes verticales, alineación sutil -->
                                <table class="table table-hover align-middle small text-secondary" id="tabla_admin_docs" width="100%">
                                    <thead class="table-secondary text-uppercase fs-7">
                                        <tr>
                                            <th class="border-bottom-2 py-3 text-center">No. Emp</th>
                                            <th class="border-bottom-2 py-3">Nombre Completo</th>
                                            <th class="border-bottom-0 py-3 text-center">Tel.</th>
                                            <th class="border-bottom-2 py-3">Depto Base</th>
                                            <th class="border-bottom-2 py-3">Áreas Técnicas</th>
                                            <th class="border-bottom-2 py-3 text-center">Estatus</th>
                                            <th class="border-bottom-2 py-3 text-center">Docs</th>
                                            <th class="border-bottom-2 py-3 text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody_admin_docs" class="border-top-0">
                                        <!-- Cargado dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: GESTIÓN DE ALCANCES -->
            <div class="modal fade" id="modal_gestion_jefes_tecnicos" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content border-0">
                        <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
                            <h5 class="modal-title fw-semibold text-secondary h6 text-uppercase"><i class="fas fa-user-shield me-2"></i> Alcances y Jefaturas</h5>
                            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body px-4">
                            <input type="hidden" id="modal_jt_id_empleado">
                            
                            <div class="p-3 bg-light rounded-3 mb-4 border-0">
                                <span class="small text-muted d-block mb-1">Colaborador seleccionado:</span>
                                <strong id="modal_jt_nombre_empleado" class="text-dark fw-semibold fs-6"></strong>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="small fw-semibold text-uppercase text-muted mb-0">Matriz de Habilidades</label>
                                <button type="button" class="btn btn-sm btn-outline-dark fw-medium rounded-2" onclick="agregar_fila_alcance_dinamica()">
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
                            <button type="button" class="btn btn-sm btn-dark fw-medium px-4" onclick="guardar_asignacion_compuesta_jefes()"><i class="fas fa-save me-1"></i> Guardar</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: EDITAR USUARIO -->
            <div class="modal fade" id="modal_editar_usuario" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content border-0">
                        <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
                            <h5 class="modal-title fw-semibold text-secondary h6 text-uppercase"><i class="fas fa-user-edit me-2"></i> Modificar Ficha</h5>
                            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="form_modificar_usuario_maestro">
                            <div class="modal-body px-4">
                                <input type="hidden" name="mod_noEmpleado" id="mod_noEmpleado">
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="small fw-medium text-muted mb-1">Nombre Completo</label>
                                        <input type="text" class="form-control bg-light border-0 fw-semibold text-dark rounded-2" name="mod_nombre" id="mod_nombre" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-medium text-muted mb-1">Correo Institucional</label>
                                        <input type="email" class="form-control bg-light border-0 rounded-2" name="mod_correo" id="mod_correo" required>
                                    </div>
                                </div>
                                
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="small fw-medium text-muted mb-1">Departamento Base</label>
                                        <select class="form-select bg-light border-0 rounded-2" name="mod_departamento" id="mod_departamento" required></select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-medium text-muted mb-1">Puesto</label>
                                        <select class="form-select bg-light border-0 rounded-2" name="mod_puesto" id="mod_puesto" required></select>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="small fw-medium text-muted mb-1"><i class="fas fa-user-shield me-1"></i> Jefe Directo</label>
                                    <select class="form-select bg-light border-0 rounded-2" name="mod_jefe" id="mod_jefe" required></select>
                                </div>                
                                
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="small fw-medium text-muted mb-1">Sexo</label>
                                        <select class="form-select bg-light border-0 rounded-2" name="mod_sexo" id="mod_sexo" required>
                                            <option value="M">MASCULINO</option>
                                            <option value="F">FEMENINO</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-medium text-muted mb-1">NSS</label>
                                        <input type="text" class="form-control bg-light border-0 rounded-2" name="mod_nss" id="mod_nss" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-medium text-muted mb-1">RFC</label>
                                        <input type="text" class="form-control bg-light border-0 rounded-2" name="mod_rfc" id="mod_rfc" required>
                                    </div>
                                </div>
                                
                                <div class="row g-3 mt-1">                                    
                                    <div class="col-md-4">
                                        <label class="small fw-medium text-muted mb-1">CURP</label>
                                        <input type="text" class="form-control bg-light border-0 rounded-2" name="mod_curp" id="mod_curp" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-medium text-muted mb-1">Contrato</label>
                                        <select class="form-select bg-light border-0 rounded-2" name="mod_tipoContrato" id="mod_tipoContrato" required>
                                            <option value="PLANTA">PLANTA</option>
                                            <option value="CONTRATO">CONTRATO</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small fw-medium text-muted mb-1">Sangre</label>
                                        <select class="form-select bg-light border-0 rounded-2" name="mod_tipoSangre" id="mod_tipoSangre" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="A+">A+</option>
                                            <option value="A-">A-</option>
                                            <option value="B+">B+</option>
                                            <option value="B-">B-</option>
                                            <option value="AB+">AB+</option>
                                            <option value="AB-">AB-</option>
                                            <option value="O+">O+</option>
                                            <option value="O-">O-</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-6">
                                        <label class="small fw-medium text-muted mb-1">Telefonos</label>
                                        <!--BOTON PARA ABRIR MODAL DE TELEFONOS-->
                                        <button type="button" class="btn btn-sm btn-outline-secondary fw-medium rounded-2" onclick="abrir_modal_telefonos()">
                                            <i class="fas fa-phone me-1"></i> Editar Teléfonos
                                        </button>
                                    </div>                                        
                                </div>
                            </div>
                            <div class="modal-footer border-top-0 p-4">
                                <button type="button" class="btn btn-sm btn-light fw-medium px-3 text-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-sm btn-dark fw-medium px-4"><i class="fas fa-save me-1"></i> Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!--MODAL TELEFONOS-->
            <div class="modal fade" id="modal_telefonos" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-md modal-dialog-centered" role="document">
                    <div class="modal-content border-0">
                        <div class="modal-header bg-success border-bottom-0 pt-0 px-4 pb-0">
                            <h5 class="modal-title fw-semibold text-white h6 text-uppercase"><i class="fas fa-phone me-2"></i> Editar Teléfonos</h5>
                            <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form id="form_modificar_telefonos">
                            <div class="modal-body px-4">
                                <input type="hidden" id="modal_telefonos_noEmpleado">
                                <div id="contenedor_telefonos" class="d-flex flex-column gap-3">
                                    <!-- Campos de teléfono generados dinámicamente -->
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary fw-medium rounded-2 mt-3" onclick="agregar_campo_telefono()">
                                    <i class="fas fa-plus me-1"></i> Agregar Teléfono
                                </button>
                            </div>
                            <div class="modal-footer border-top-0 p-4">
                                <button type="button" class="btn btn-sm btn-light fw-medium px-3 text-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-sm btn-dark fw-medium px-4"><i class="fas fa-save me-1"></i> Guardar Teléfonos</button>
                            </div>
                        </form>
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