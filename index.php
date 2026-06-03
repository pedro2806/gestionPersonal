<?php
// index.php - Vista Principal del Expediente Digital Minimalista (MESS)
require_once 'conn.php';
$id_usuario_sesion = isset($_COOKIE['noEmpleadoGP']) ? intval($_COOKIE['noEmpleadoGP']) : 276;
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
    
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css" rel="stylesheet">
</head>

<body id="page-top" class="bg-light">

    <div id="wrapper">
        <?php include 'menu.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include 'encabezado.php'; ?>

                <div class="container-fluid px-4 mt-0">
                    
                    <input type="hidden" id="usuario_sesion_id" value="<?php echo $id_usuario_sesion; ?>">

                    <div id="contenedor_tarjeta_credencial" class="mb-0">
                        <?php include 'tarjeta_perfil.php'; ?>
                    </div>

                    <div class="card border-0 bg-transparent mb-0">
                        <div class="card-body p-0">                            
                            <div class="d-flex align-items-center justify-content-between mb-0 py-0 border-bottom border-light">
                                <div>                                    
                                    <p class="text-muted small mb-0"> <b>Expediente Digital</b> -  Cumplimiento y estatus de requisitos documentales homologados.</p>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table id="tabla_expediente_empleado" class="table table-hover align-middle bg-white rounded-3 overflow-hidden shadow-sm small text-secondary" width="100%">
                                    <thead class="table-secondary text-uppercase text-muted border-bottom" style="font-size: 0.72rem; letter-spacing: 0.03em;">
                                        <tr>
                                            <th class="py-3 ps-3 text-start">Requisito / Documento</th>
                                            <th class="py-3 text-center">Tipo</th>
                                            <th class="py-3 text-center">Área / Departamento</th>
                                            <th class="py-3 text-center">Alcance</th>
                                            <th class="py-3 text-center">Estatus</th>
                                            <th class="py-3 text-center">Validación</th>
                                            <th class="py-3 text-center pe-3" style="width: 140px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbody_expediente_empleado" class="border-0">
                                        </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <footer class="sticky-footer bg-light mt-auto border-top-0 py-3">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto text-muted small">
                        <span>Copyright &copy; MESS 2026</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <div class="modal fade" id="modal_cargar_archivo_fila" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow rounded-3">
                <div class="modal-header border-0 pt-4 px-4 pb-0">
                    <h6 class="modal-title font-weight-bold text-dark text-uppercase small" id="modal_titulo">
                        Cargar Requisito
                    </h6>
                    <button type="button" class="btn-close small shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="form_subir_requisito_empleado" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id_tipo_documento" id="modal_id_tipo_documento">
                        
                        <div class="mb-3">
                            <label class="small text-muted font-weight-bold d-block mb-1">Documento seleccionado</label>
                            <input type="text" id="modal_nombre_documento" class="form-control-plaintext form-control-sm font-weight-bold text-dark py-0" readonly>
                        </div>
                        
                        <div class="mb-2">
                            <label class="small text-muted font-weight-bold d-block mb-2">Seleccionar PDF</label>
                            <input type="file" class="form-control form-control-sm bg-light border-0 py-2 rounded" name="archivo_pdf" accept=".pdf" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-3 bg-light rounded-bottom">
                        <button type="button" class="btn btn-sm text-secondary font-weight-bold bg-transparent border-0" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-dark px-4 rounded font-weight-bold shadow-none">Subir archivo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>    
    <script src="js/sb-admin-2.min.js"></script>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="app_index.js"></script>

    <script>
        $(document).ready(function() {
            const id_usuario_sesion = $('#usuario_sesion_id').val();
            obtener_perfil_tarjeta_maestra(id_usuario_sesion);
            cargar_expediente_empleado(id_usuario_sesion);
        });
        // Obtener los datos del perfil del usuario para mostrar en la tarjeta de perfil en la vista de empleado
        function obtener_perfil_tarjeta_maestra(id_usuario) {
            $.ajax({
                url: 'action_controller.php',
                type: 'POST',
                data: { action: 'obtener_datos_perfil_tarjeta', id_usuario: id_usuario },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') { renderizar_tarjeta_perfil_usuario(response.data); }
                }
            });
        }

        // Renderizar los datos del perfil del usuario en la tarjeta de perfil en la vista de empleado
        function renderizar_tarjeta_perfil_usuario(d) {
            $('#tarjeta_nombre_completo').text(d.nombreCompleto);
            $('#tarjeta_puesto_subtitulo').text(d.puesto);
            $('#tarjeta_no_empleado').text(d.noEmpleado);
            $('#tarjeta_departamento').text(d.departamento);
            $('#tarjeta_jefe_admin').text(d.jefe_administrativo || 'No asignado');
            let zona = $('#tarjeta_jefes_tecnicos_zona').empty();
            if (d.jefes_tecnicos) {
                d.jefes_tecnicos.split(',').forEach(j => { zona.append(`<span class="badge bg-white text-dark border shadow-sm m-1"><i class="fas fa-microscope text-primary"></i> ${j}</span>`); });
            } else { zona.html('<span class="text-muted small">Sin alcances</span>'); }
        }
    </script>    
</body>
</html>