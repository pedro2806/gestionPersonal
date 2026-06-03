<?php
// config_documentos.php - Panel de Control del Catálogo Documental (MESS)
include 'conn.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MESS - Configuración de Requisitos Documentales</title>

    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet">

    <style>
        .table-dark-custom { 
    background-color: #2c3e50 !important; 
    color: #ffffff; 
}
.badge-tecnico { 
    background-color: #fef9c3 !important; /* bg-warning-subtle */
    color: #a16207 !important;            /* text-warning-emphasis */
    font-weight: bold; 
}
.badge-administrativo { 
    background-color: #e0f2fe !important; /* bg-info-subtle / bg-primary-subtle */
    color: #0369a1 !important;            /* text-info-emphasis */
    font-weight: bold; 
}
.badge-universal { 
    background-color: #f1f5f9 !important; /* bg-light-subtle */
    color: #475569 !important;            /* text-secondary-emphasis */
}
.badge-scope { 
    background-color: #dcfce7 !important; /* bg-success-subtle oficial */
    color: #15803d !important;            /* text-success-emphasis oficial */
}
    </style>
</head>

<body id="page-top">

    <div id="wrapper">
        <?php include 'menu.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>

                <div class="container-fluid">
                    
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-cogs mr-2"></i>Matriz de Requisitos Documentales</h1>
                        <button class="btn btn-primary btn-sm shadow-sm font-weight-bold text-uppercase" onclick="abrirModalNuevoDocumento()">
                            <i class="fas fa-plus-circle mr-2"></i>Configurar Nuevo Documento
                        </button>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-primary d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-folder mr-2"></i>Catálogo Operativo de Documentos</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="tabla_config_docs" class="table table-bordered table-striped table-hover align-middle" width="100%" cellspacing="0">
                                    <thead class="table-dark table-sm">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre del Requisito</th>
                                            <th>Responsable Subida</th>
                                            <th>Alcance</th>
                                            <th>Perfil Destino</th>
                                            <th>Función</th>
                                            <th>Firmas / Validaciones Requeridas</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla_config_docs_body">
                                        </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <footer class="sticky-footer bg-white mt-auto">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; MESS 2026</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <div class="modal fade" id="modal_config_doc" tabindex="-1" role="dialog" aria-labelledby="modal_titulo" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content border-0 shadow">
                <div id="modal_header_style" class="modal-header bg-primary text-white pt-4 px-4 pb-2">
                    <h5 class="modal-title font-weight-bold text-uppercase small" id="modal_titulo"></h5>
                    <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="cfg_id_documento">
                    
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-uppercase text-muted">Nombre del Documento / Requisito</label>
                        <input type="text" id="cfg_nombre_tipo" class="form-control" placeholder="Ej: Certificado de Competencia Técnica o Perfil del Puesto" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="small font-weight-bold text-uppercase text-muted">Responsable de Subida</label>
                            <select id="cfg_subido_por" class="form-control">
                                <option value="Empleado">Colaborador / Empleado</option>
                                <option value="Jefe Técnico">Jefe de Área / Técnico</option>
                                <option value="RH">Recursos Humanos (RH)</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="small font-weight-bold text-uppercase text-muted">Tipo de Alcance</label>
                            <select id="cfg_tipo_alcance" class="form-control">
                                <option value="Por Puesto">Por Puesto (Universal / Se carga una vez)</option>
                                <option value="Por Alcance">Por Alcance (Específico por Magnitud / Área)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group mb-3">
                            <label class="small font-weight-bold text-uppercase text-muted">Perfil de Puesto Destino</label>
                            <select id="cfg_perfil_puesto" class="form-control">
                                <option value="Todos">Todos los Puestos</option>
                                <option value="Solo Técnico">Solo Personal Técnico / Metrólogos</option>
                                <option value="Solo Administrativo">Solo Personal Administrativo / Soporte</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group mb-3">
                            <label class="small font-weight-bold text-uppercase text-muted">Categoría Funcional</label>
                            <select id="cfg_categoria_funcion" class="form-control">
                                <option value="Administrativo">Administrativo (Gestión / RH)</option>
                                <option value="Técnico">Técnico (Acreditaciones / ISO 17025)</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <label class="small font-weight-bold text-uppercase text-muted mb-3 d-block"><i class="fas fa-signature mr-1"></i>Flujo Colectivo de Firmas (Votos Requeridos)</label>
                    <div class="row bg-light p-3 rounded">
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="cfg_req_rrhh">
                                <label class="form-check-label small font-weight-bold text-dark" for="cfg_req_rrhh">Recursos Humanos</label>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="cfg_req_jefe_tecnico">
                                <label class="form-check-label small font-weight-bold text-dark" for="cfg_req_jefe_tecnico">Jefe Técnico</label>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="cfg_req_calidad">
                                <label class="form-check-label small font-weight-bold text-dark" for="cfg_req_calidad">Calidad</label>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="cfg_req_jefe_admin">
                                <label class="form-check-label small font-weight-bold text-dark" for="cfg_req_jefe_admin">Jefe Admin</label>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary px-3 font-weight-bold" onclick="guardarConfiguracionDocumento()">Guardar Cambios</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>    
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="app_config_documentos.js"></script>
</body>
</html>