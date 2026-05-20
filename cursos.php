<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MESS - Catálogo General de Cursos</title>

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
                        <h1 class="h3 mb-0 text-gray-800">Catálogo General de Cursos</h1>
                        <button class="btn btn-primary btn-sm shadow-sm font-weight-bold text-uppercase" data-toggle="modal" data-target="#modal_nuevo_curso">
                            <i class="fas fa-plus fa-sm mr-2"></i> Crear Nuevo Curso
                        </button>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-header py-3 bg-gradient-primary">
                            <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-book-open mr-2"></i> Cursos Registrados para Grupo MESS</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover align-middle shadow-sm" width="100%">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre del Curso</th>
                                            <th>Institución / Certificador</th>
                                            <th>Duración (Horas)</th>
                                            <th>Descripción</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tabla_catalogo_cursos_body">
                                        <!-- Inyección dinámica mediante AJAX -->
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

    <!-- MODAL: CREAR NUEVO CURSO -->
    <div class="modal fade" id="modal_nuevo_curso" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold text-uppercase small"><i class="fas fa-plus-circle mr-2"></i> Agregar Curso al Catálogo</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-uppercase text-muted">Nombre del Curso / Capacitación</label>
                        <input type="text" id="add_nombre_curso" class="form-control" placeholder="Ej: Metrología Avanzada de Masas">
                    </div>
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-uppercase text-muted">Institución Instructora / Certificadora</label>
                        <input type="text" id="add_institucion" class="form-control" placeholder="Ej: CENAM o Entidad Interna">
                    </div>
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-uppercase text-muted">Horas Totales (Duración)</label>
                        <input type="number" id="add_horas" class="form-control" placeholder="Ej: 40" min="1">
                    </div>
                    <div class="form-group mb-3">
                        <label class="small font-weight-bold text-uppercase text-muted">Breve Descripción u Objetivo</label>
                        <textarea id="add_descripcion" class="form-control" rows="3" placeholder="Opcional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary px-3 font-weight-bold" onclick="guardar_curso_catalogo()">Guardar Curso</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>    
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="app_cursos.js"></script>
</body>
</html>
