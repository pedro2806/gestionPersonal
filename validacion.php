<?php
// validacion.php - Panel de Auditoría y Firmas para Jefaturas, Calidad y RRHH
require_once 'conn.php';

// Supongamos que aquí recuperas los datos reales del usuario que inició sesión
$id_usuario_sesion = isset($_COOKIE['id_usuario']) ? intval($_COOKIE['id_usuario']) : 45; 
$no_empleado_sesion = isset($_COOKIE['noEmpleado']) ? intval($_COOKIE['noEmpleado']) : 45; // Ejemplo: 5 para Calidad, 403 para RRHH
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MESS - Validación Documental</title>
    
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">    
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <input type="hidden" id="usuario_sesion_id" value="<?php echo $id_usuario_sesion; ?>">
    <input type="hidden" id="usuario_sesion_no_empleado" value="<?php echo $no_empleado_sesion; ?>">

    <div id="wrapper">
        <?php include 'menu.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Panel de Auditoría y Validación Documental</h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body bg-light">
                            <div class="row align-items-end">
                                <div class="col-md-5 mb-2">
                                    <label class="small font-weight-bold text-uppercase text-muted">Filtrar por Área / Laboratorio</label>
                                    <select id="filtro_departamento" class="form-control select2-custom"></select>
                                </div>
                                <div class="col-md-5 mb-2">
                                    <label class="small font-weight-bold text-uppercase text-muted">Buscar por Metrólogo / Ingeniero</label>
                                    <select id="filtro_ingeniero" class="form-control select2-custom"></select>
                                </div>
                                <div class="col-md-2 mb-2 d-grid">
                                    <button class="btn btn-dark font-weight-bold text-uppercase shadow-sm" onclick="limpiar_filtros_auditoria()">
                                        <i class="fas fa-undo mr-1"></i> Limpiar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="seccion_tabla_validacion" class="card shadow mb-4">
                        <div class="card-header py-3 bg-gradient-dark">
                            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-user-check mr-2"></i> Estado de Expedientes del Personal Asignado</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover align-middle text-center small" id="tabla_auditoria_personal" width="100%">
                                    <thead class="table-dark text-uppercase">
                                        <tr>
                                            <th>No. Empleado</th>
                                            <th class="text-start">Colaborador</th>
                                            <th>Área Base</th>
                                            <th>Puesto</th>
                                            <th style="width: 150px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody_auditoria_personal">
                                        </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div></div></div></div><div class="modal fade" id="modal_detalle_checklist" tabindex="-1" role="dialog" aria-labelledby="titulo_modal_checklist" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title font-weight-bold text-uppercase small" id="titulo_modal_checklist">
                        <i class="fas fa-folder-open mr-2"></i> Matriz de Requisitos Obligatorios: <span id="txt_nombre_ingeniero_auditar" class="text-warning font-weight-bold"></span>
                    </h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-center small mb-0" width="100%">
                            <thead class="table-secondary text-uppercase font-weight-bold">
                                <tr>
                                    <th class="text-start">Documento / Requisito Obligatorio</th>
                                    <th>Área Aplicable</th>
                                    <th>Estatus Documento</th>
                                    <th>Firmas Colectivas (A / T / C / R)</th>
                                    <th style="width: 180px;">Acción / Dictamen</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_detalle_requisitos_auditoria">
                                </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary font-weight-bold text-uppercase" data-bs-dismiss="modal">Cerrar Ventana</button>
                </div>
            </div>
        </div>
    </div>

                </div>
            </div>
            <footer class="sticky-footer bg-white mt-auto">
                <div class="container my-auto"><div class="copyright text-center my-auto"><span>Copyright &copy; MESS 2026</span></div></div>
            </footer>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>    
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    
    <script src="app_auditoria_firmas.js"></script>
</body>
</html>