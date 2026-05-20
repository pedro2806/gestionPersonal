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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Administración de Expedientes y Habilidades</h1>
                    </div>

                    <input type="hidden" id="contexto_vista_maestra" value="Administracion">

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-gradient-success">
                            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-paste mr-2"></i>Matriz General de Control y Habilidades por Laboratorio</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped align-middle" id="tabla_admin_docs" width="100%">
                                    <thead class="table-dark small text-uppercase">
                                        <tr>
                                            <th>No. Emp</th>
                                            <th>Nombre Completo</th>
                                            <th>Depto Base</th>
                                            <th>Áreas Técnicas y Jefes Asignados</th>
                                            <th class="text-center">Docs</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody_admin_docs"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MODAL: GESTIÓN DE ALCANCES Y JEFATURAS TÉCNICAS COMPUESTAS -->
            <div class="modal fade" id="modal_gestion_jefes_tecnicos" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-dark text-white">
                            <h5 class="modal-title text-uppercase small font-weight-bold"><i class="fas fa-user-shield mr-2"></i> Alcances y Jefaturas Técnicas por Departamento</h5>
                            <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" id="modal_jt_id_empleado">
                            <div class="alert alert-info py-2 shadow-sm small mb-3">
                                <i class="fas fa-info-circle mr-1"></i> Define los laboratorios adicionales a los que tiene alcance el colaborador: <br>
                                <strong id="modal_jt_nombre_empleado" class="text-primary font-weight-bold"></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="small font-weight-bold text-uppercase text-muted mb-0">Matriz de Habilidades Extra-Curriculares</label>
                                <button type="button" class="btn btn-xs btn-success font-weight-bold shadow-sm" onclick="agregar_fila_alcance_dinamica()">
                                    <i class="fas fa-plus mr-1"></i> Agregar Alcance
                                </button>
                            </div>
                            <div class="table-responsive border rounded" style="max-height: 300px;">
                                <table class="table table-sm table-striped align-middle mb-0">
                                    <thead class="table-secondary small font-weight-bold text-uppercase">
                                        <tr>
                                            <th>Jefe Técnico Responsable</th>
                                            <th>Departamento / Laboratorio del Alcance</th>
                                            <th class="text-center" style="width: 80px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody_modal_alcances"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-sm btn-success px-3 font-weight-bold shadow-sm" onclick="guardar_asignacion_compuesta_jefes()"><i class="fas fa-save mr-1"></i> Guardar Habilidades</button>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="sticky-footer bg-white mt-auto"><div class="container my-auto"><div class="copyright text-center my-auto"><span>Copyright &copy; MESS 2026</span></div></div></footer>
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
