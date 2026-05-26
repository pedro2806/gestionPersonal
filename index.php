<?php
require_once 'conn.php';
$id_usuario_sesion = isset($_COOKIE['id_usuario']) ? intval($_COOKIE['id_usuario']) : 276; 
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
    
    <!-- Evitar el resplandor grueso por defecto y estilizar la carga de archivos nativa -->
    <style>
        .form-control:focus, .form-select:focus {
            box-shadow: none;
            border-color: #6c757d;
        }
        input[type="file"]::file-selector-button {
            background-color: #212529;
            color: white;
            border: none;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            margin-right: 10px;
            cursor: pointer;
            font-size: 0.875rem;
        }
        input[type="file"]::file-selector-button:hover {
            background-color: #343a40;
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
                    
                    <input type="hidden" id="usuario_sesion_id" value="<?php echo $id_usuario_sesion; ?>">
                    <input type="hidden" id="usuario_destino_carga_id" value="<?php echo $id_usuario_sesion; ?>">
                    <input type="hidden" id="contexto_vista_maestra" value="Empleado">

                    <!-- TARJETA CREDENCIAL CORPORATIVA MESS (COMPONENTE REUSABLE DINÁMICO) -->
                    <div id="contenedor_tarjeta_credencial" class="mb-4"><?php include 'tarjeta_perfil.php'; ?></div>

                    <div class="row">
                        <div class="col-12 mb-4">
                            <!-- Contenedor Plano Estilo Flat -->
                            <div class="card border-0 rounded-3">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <span class="text-secondary me-2"><i class="fas fa-clipboard-list"></i></span>
                                            <h6 class="m-0 fw-semibold text-secondary">Matriz de Cumplimiento y Requisitos de mi Expediente</h6>
                                        </div>
                                        <div id="zona_boton_regresar"></div>
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <!-- Tabla limpia sin líneas verticales con tipografía suavizada -->
                                        <table class="table table-hover align-middle small text-secondary" id="tabla_cumplimiento_expediente" width="100%">
                                            <thead class="table-light text-uppercase fs-7">
                                                <tr>
                                                    <th class="border-bottom-2 py-3 text-start">Requisito / Documento</th>
                                                    <th class="border-bottom-2 py-3 text-center">Tipo / Origen</th>
                                                    <th class="border-bottom-2 py-3 text-center">Laboratorio / Área</th>
                                                    <th class="border-bottom-2 py-3 text-center">Estatus</th>
                                                    <th class="border-bottom-2 py-3 text-center">Validaciones (JA / LT / CAL / RRHH)</th>
                                                    <th class="border-bottom-2 py-3 text-center" style="width: 200px;">Acción / Archivo</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody_cumplimiento_expediente" class="border-top-0">
                                                <!-- Cargado dinámicamente -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MODAL: SUBIR REQUISITO (DISEÑO FLAT) -->
                    <div class="modal fade" id="modal_subir_requisito" tabindex="-1" role="dialog" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content border-0">
                                <div class="modal-header border-bottom-0 pt-4 px-4 pb-2">
                                    <h5 class="modal-title fw-semibold text-secondary h6 text-uppercase"><i class="fas fa-cloud-upload-alt me-2"></i> Subir Documento</h5>
                                    <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="form_subir_requisito_fila" enctype="multipart/form-data">
                                    <div class="modal-body px-4">
                                        <input type="hidden" name="id_tipo_documento" id="modal_upload_id_tipo">
                                        <input type="hidden" name="id_departamento_alcance" id="modal_upload_id_depto">
                                        
                                        <div class="mb-3">
                                            <label class="small fw-medium text-muted mb-1">Documento a Cargar</label>
                                            <input type="text" id="modal_upload_nombre_doc" class="form-control bg-light border-0 fw-semibold text-dark rounded-2" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="small fw-medium text-muted mb-1">Área Aplicable</label>
                                            <input type="text" id="modal_upload_nombre_depto" class="form-control bg-light border-0 rounded-2" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="small fw-medium text-muted mb-1">Seleccionar PDF</label>
                                            <input type="file" class="form-control bg-light border-0 rounded-2" name="archivo_doc" accept=".pdf" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top-0 p-4">
                                        <button type="button" class="btn btn-sm btn-light fw-medium px-3 text-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-sm btn-dark fw-medium px-4">Iniciar Validación</button>
                                    </div>
                                </form>
                            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="app_controlador_maestro_docs.js"></script>
</body>
</html>