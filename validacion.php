<?php
// validacion.php - Panel de Auditoría y Firmas para Jefaturas, Calidad y RRHH (MESS)
require_once 'conn.php';

$id_usuario_sesion = isset($_COOKIE['id_usuarioGP']) ? intval($_COOKIE['id_usuarioGP']) : 45;
$no_empleado_sesion = isset($_COOKIE['noEmpleadoGP']) ? intval($_COOKIE['noEmpleadoGP']) : 45; 
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    
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
                    
                    <input type="hidden" id="auditor_no_empleado" value="<?php echo $no_empleado_sesion; ?>">

                    <div class="card border-0 bg-transparent mb-5">
                        <div class="card-body p-0">
                            
                            <div class="d-flex align-items-center justify-content-between mb-4 py-2 border-bottom border-light">
                                <div>
                                    <h5 class="font-weight-bold text-dark mb-1">Módulo de Auditoría y Validación</h5>
                                    <p class="text-muted small mb-0">Control colectivo de firmas, firmas extras y autorización de expedientes digitales.</p>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <label class="small text-muted font-weight-bold mb-1">Laboratorio o Departamento</label>
                                    <select id="filtro_departamento" class="form-select select2-custom border-0 shadow-sm"></select>
                                </div>
                                <div class="col-md-4">
                                    <label class="small text-muted font-weight-bold mb-1">Responsable Técnico (Ingeniero)</label>
                                    <select id="filtro_ingeniero" class="form-select select2-custom border-0 shadow-sm"></select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button class="btn btn-sm btn-dark w-100 font-weight-bold py-2 shadow-none" onclick="limpiar_filtros_auditoria()">
                                        <i class="fas fa-undo-alt mr-2"></i>Restablecer Filtros
                                    </button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="tabla_auditoria_personal" class="table table-hover align-middle bg-white rounded-3 overflow-hidden shadow-sm small text-secondary" width="100%">
                                    <thead class="table-light text-uppercase text-muted border-bottom" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                                        <tr>
                                            <th class="py-3 ps-3">No. Empleado</th>
                                            <th class="py-3">Nombre Colaborador</th>
                                            <th class="py-3 text-center">Puesto</th>
                                            <th class="py-3 text-center">Área / Depto</th>
                                            <th class="py-3 text-center">Avance Expediente</th>
                                            <th class="py-3 text-center pe-3" style="width: 120px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody_auditoria_personal" class="border-0">
                                        </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                    <div class="modal fade" id="modal_ver_expediente_empleado" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-xl">
                            <div class="modal-content border-0 shadow rounded-3">
                                <div class="modal-header border-0 pt-4 px-4 pb-0">
                                    <div>
                                        <h6 class="modal-title font-weight-bold text-dark text-uppercase small" id="modal_auditoria_titulo">
                                            Expediente de Auditoría
                                        </h6>
                                        <p class="text-muted small mb-0" id="modal_auditoria_subtitulo"></p>
                                    </div>
                                    <button type="button" class="btn-close small shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle bg-white shadow-sm small text-secondary border rounded" width="100%">
                                            <thead class="table-light text-uppercase text-muted border-bottom" style="font-size: 0.7rem;">
                                                <tr>
                                                    <th class="py-3 ps-3">Requisito Documental</th>
                                                    <th class="py-3 text-center">Tipo</th>
                                                    <th class="py-3 text-center">Alcance</th>
                                                    <th class="py-3 text-center">Estatus</th>
                                                    <th class="py-3 text-center">Banderas de Firma (A / T / C / R)</th>
                                                    <th class="py-3 text-center pe-3" style="width: 200px;">Acción / Dictamen</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody_detalle_requisitos_auditoria" class="border-0">
                                                </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer border-0 p-3 bg-light rounded-bottom">
                                    <button type="button" class="btn btn-sm btn-secondary font-weight-bold text-uppercase px-3 rounded shadow-none" data-bs-dismiss="modal">Cerrar Ventana</button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <footer class="sticky-footer bg-light mt-auto border-top-0 py-3">
                <div class="container my-auto"><div class=\"copyright text-center my-auto text-muted small\"><span>Copyright &copy; MESS 2026</span></div></div>
            </footer>
        </div>
    </div>

    <div class="modal fade" id="modal_jefe_subir_archivo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header border-0 pt-4 px-4 pb-0">
                    <h6 class="modal-title font-weight-bold text-dark text-uppercase small">
                        Cargar Documento Institucional
                    </h6>
                    <button type="button" class="btn-close small shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="form_jefe_subir_requisito" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_tipo_documento" id="modal_jefe_id_tipo_documento">
                        <input type="hidden" name="noEmpleado_destino" id="modal_jefe_noEmpleado_destino">
                        
                        <div class="mb-3">
                            <label class="small text-muted font-weight-bold d-block mb-1">Colaborador</label>
                            <input type="text" id="modal_jefe_nombre_empleado" class="form-control-plaintext form-control-sm font-weight-bold text-dark py-0" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted font-weight-bold d-block mb-1">Requisito</label>
                            <input type="text" id="modal_jefe_nombre_documento" class="form-control-plaintext form-control-sm font-weight-bold text-dark py-0" readonly>
                        </div>
                        
                        <div class="mb-2">
                            <label class="small text-muted font-weight-bold d-block mb-2">Seleccionar PDF</label>
                            <input type="file" class="form-control form-control-sm bg-light border-0 py-2 rounded" name="archivo_pdf" accept=".pdf" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-light rounded-bottom">
                        <button type="button" class="btn btn-sm text-secondary font-weight-bold bg-transparent border-0" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-dark px-4 rounded font-weight-bold shadow-none">Subir al Expediente</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>    
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    
    <script src="app_auditoria_firmas.js"></script>
</body>
</html>