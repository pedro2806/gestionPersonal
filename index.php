<?php
// index.php - Vista Individual del Empleado con Selección Unificada de Depto Base y Alcances
require_once 'conn.php';
$id_usuario_sesion = isset($_COOKIE['id_usuario']) ? intval($_COOKIE['id_usuario']) : 239; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MESS - Mi Expediente Digital</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">    
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body id="page-top">
    <div id="wrapper">
        <?php include 'menu.php'; ?>
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include 'encabezado.php'; ?>
                <div class="container-fluid">
                    
                    <input type="hidden" id="usuario_sesion_id" value="<?php echo $id_usuario_sesion; ?>">
                    <input type="hidden" id="usuario_destino_carga_id" value="<?php echo $id_usuario_sesion; ?>">
                    <input type="hidden" id="contexto_vista_maestra" value="Empleado">

                    <!-- TARJETA CREDENCIAL CORPORATIVA MESS (COMPONENTE REUSABLE DINÁMICO) -->
                    <div id="contenedor_tarjeta_credencial"><?php include 'tarjeta_perfil.php'; ?></div>

                    <!-- FORMULARIO DE CARGA INTELIGENTE Y RETÍCULA -->
                    <div class="row">
                        <div class="col-xl-4 col-lg-5 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-gradient-primary">
                                    <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-upload mr-2"></i>Cargar Mi Documento</h6>
                                </div>
                                <div class="card-body">
                                    <form id="form_subir_documento" enctype="multipart/form-data">
                                        <div class="form-group mb-3">
                                            <label class="small font-weight-bold text-muted text-uppercase">Tipo de Requerimiento</label>
                                            <select class="form-control" name="id_tipo_documento" id="select_tipo_documento" required></select>
                                        </div>

                                        <!-- CONTENEDOR DE SELECCIÓN DE LABORATORIO (DE CAJÓN O ALCANCE ADICIONAL) -->
                                        <div class="form-group mb-3 d-none" id="contenedor_depto_alcance">
                                            <label class="small font-weight-bold text-warning text-uppercase">
                                                <i class="fas fa-microscope mr-1"></i> ¿Para qué Laboratorio / Área aplica?
                                            </label>
                                            <select class="form-control border-warning text-dark font-weight-bold" name="id_departamento_alcance" id="select_depto_alcance"></select>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="small font-weight-bold text-muted text-uppercase">Seleccionar Archivo (PDF)</label>
                                            <input type="file" class="form-control" name="archivo_doc" accept=".pdf" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-block font-weight-bold shadow-sm">
                                            <i class="fas fa-cloud-upload-alt mr-1"></i> Enviar a Validación
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-8 col-lg-7 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3 bg-gradient-dark d-flex justify-content-between align-items-center">
                                    <h6 class="m-0 font-weight-bold text-white"><i class="fas fa-file-invoice mr-2"></i>Estatus de Mis Validaciones</h6>
                                    <div id="zona_boton_regresar"></div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped align-middle text-center small" width="100%">
                                            <thead class="table-dark text-uppercase">
                                                <tr>
                                                    <th class="text-start">Documento</th>
                                                    <th>Área Afectada</th>
                                                    <th>Archivo</th>
                                                    <th>Jefe Admin</th>
                                                    <th>Jefe Técnico</th>
                                                    <th>Calidad</th>
                                                    <th>RRHH</th>
                                                    <th>Estatus</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody_mi_expediente"></tbody>
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
    <script src="app_controlador_maestro_docs.js"></script>
</body>
</html>
