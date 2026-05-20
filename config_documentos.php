<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MESS - Configuración de Catálogo</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">    
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Configuración General del Catálogo</h1>
                    </div>

                    <input type="hidden" id="contexto_vista_maestra" value="Configuracion">

                    <div class="row">
                        <div class="col-xl-4 col-lg-5 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-gradient-dark"><h6 class="m-0 font-weight-bold text-white"><i class="fas fa-plus-circle mr-2"></i>Nuevo Tipo de Documento</h6></div>
                                <div class="card-body">
                                    <form id="form_config_catalogo_doc">
                                        <div class="form-group mb-3">
                                            <label class="small font-weight-bold text-muted text-uppercase">Nombre del Documento</label>
                                            <input type="text" class="form-control" name="nombre_tipo" id="config_nombre_tipo" placeholder="Ej: Certificado ISO" required>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="small font-weight-bold text-muted text-uppercase">¿Quién lo sube?</label>
                                            <select class="form-control" name="subido_por" id="config_subido_por" required>
                                                <option value="Empleado">Empleado</option>
                                                <option value="Jefe Admin">Jefe Administrativo</option>
                                                <option value="Jefe Técnico">Jefe Técnico</option>
                                            </select>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label class="small font-weight-bold text-muted text-uppercase">Criterio / Tipo de Alcance</label>
                                            <select class="form-control border-primary" name="tipo_alcance" id="config_tipo_alcance" required>
                                                <option value="Por Puesto">Por Puesto (Universal, Único por Empleado)</option>
                                                <option value="Por Alcance">Por Alcance (Multi-Laboratorio, 1 por cada Área relacionada)</option>
                                            </select>
                                        </div>
                                        <label class="small font-weight-bold text-muted text-uppercase d-block mb-2">Firmas Obligatorias</label>
                                        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="req_admin" id="config_req_admin" value="1"><label class="form-check-label small font-weight-bold" for="config_req_admin">Jefe Administrativo</label></div>
                                        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="req_tecnico" id="config_req_tecnico" value="1"><label class="form-check-label small font-weight-bold" for="config_req_tecnico">Jefe Técnico</label></div>
                                        <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="req_calidad" id="config_req_calidad" value="1"><label class="form-check-label small font-weight-bold" for="config_req_calidad">Calidad</label></div>
                                        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="req_rrhh" id="config_req_rrhh" value="1"><label class="form-check-label small font-weight-bold" for="config_req_rrhh">Recursos Humanos</label></div>
                                        <button type="submit" class="btn btn-success btn-block font-weight-bold shadow-sm">Guardar en Catálogo</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-8 col-lg-7 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-gradient-success"><h6 class="m-0 font-weight-bold text-white"><i class="fas fa-list mr-2"></i>Requerimientos Registrados</h6></div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped align-middle text-center small" id="tabla_config_catalogo" width="100%">
                                            <thead class="table-dark text-uppercase">
                                                <tr>
                                                    <th class="text-start">Nombre del Documento</th>
                                                    <th>Tipo Alcance</th>
                                                    <th>Creador</th>
                                                    <th>J.Adm</th>
                                                    <th>J.Tec</th>
                                                    <th>Cal</th>
                                                    <th>RRHH</th>
                                                    <th>Acción</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody_config_catalogo"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="app_controlador_maestro_docs.js"></script>
</body>
</html>
