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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <style>
        #tabsPrincipal .nav-link {
            color: #212529;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
        }
        #tabsPrincipal .nav-link.active {
            color: #fff;
            background: #074480;
            border-color: #074480;
            border-radius: 0.375rem 0.375rem 0 0;
        }
    </style>
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

                    <!-- PESTAÑAS PRINCIPALES -->
                    <ul class="nav nav-tabs border-bottom-0 mt-2" id="tabsPrincipal" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold text-uppercase px-4" id="tabExpediente-tab" data-bs-toggle="tab" data-bs-target="#tabExpediente" type="button" role="tab" style="font-size:0.75rem; letter-spacing:0.03em;">
                                <i class="fas fa-folder-open me-1"></i> Expediente Digital
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold text-uppercase px-4" id="tabCapacitacion-tab" data-bs-toggle="tab" data-bs-target="#tabCapacitacion" type="button" role="tab" style="font-size:0.75rem; letter-spacing:0.03em;">
                                <i class="fas fa-graduation-cap me-1"></i> Capacitación
                                <span class="badge bg-light text-muted border ms-1 small" id="badge_total_cursos">0</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="tabsPrincipalContent">

                        <!-- TAB: EXPEDIENTE DIGITAL -->
                        <div class="tab-pane fade show active" id="tabExpediente" role="tabpanel">
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
                                    <tbody id="tbody_expediente_empleado" class="border-0"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB: CAPACITACIÓN -->
                        <div class="tab-pane fade" id="tabCapacitacion" role="tabpanel">
                            <div class="pt-3">
                                <div id="filtro_empleado_cursos_wrapper" class="mb-3" style="display:none;">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="small text-muted font-weight-bold mb-1">Consultar colaborador</label>
                                            <select id="select_filtro_empleado_cursos" class="form-select form-select-sm shadow-none">
                                                <option value="">-- Seleccionar Colaborador --</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div id="contenedor_niveles_cursos" class="row g-3">
                                    <div class="col-12 text-center text-muted py-3 small">Cargando cursos...</div>
                                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script src="app_index.js"></script>

    <script>
        $(document).ready(function() {
            const id_usuario_sesion = $('#usuario_sesion_id').val();
            obtener_perfil_tarjeta_maestra(id_usuario_sesion);
            inicializar_filtro_empleados_cursos();

            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function() {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
            });
        });

        function obtener_perfil_tarjeta_maestra(id_usuario) {
            $.ajax({
                url: 'action_controller.php',
                type: 'POST',
                data: { action: 'obtener_datos_perfil_tarjeta', id_usuario: id_usuario },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        renderizar_tarjeta_perfil_usuario(response.data);
                        if (response.data.correo) {
                            cargar_cursos_por_nivel(response.data.correo);
                        }
                    }
                }
            });
        }

        function inicializar_filtro_empleados_cursos() {
            $.ajax({
                url: 'action_controller.php',
                type: 'POST',
                data: { action: 'listar_empleados_filtro_cursos' },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success' && (res.rol === 'admin' || res.rol === 'jefe')) {
                        let opciones = '<option value="">-- Seleccionar Colaborador --</option>';
                        res.empleados.forEach(function(emp) {
                            opciones += `<option value="${emp.noEmpleado}" data-correo="${emp.correo}">${emp.nombre} (${emp.noEmpleado})</option>`;
                        });
                        $('#select_filtro_empleado_cursos').html(opciones);
                        $('#filtro_empleado_cursos_wrapper').show();
                        $('#select_filtro_empleado_cursos').select2({
                            theme: 'bootstrap-5',
                            placeholder: '-- Seleccionar Colaborador --',
                            allowClear: true,
                            width: '100%'
                        });

                        $('#select_filtro_empleado_cursos').on('change', function() {
                            let noEmp = $(this).val();
                            if (noEmp) {
                                obtener_perfil_tarjeta_maestra(noEmp);
                            } else {
                                let id_sesion = $('#usuario_sesion_id').val();
                                obtener_perfil_tarjeta_maestra(id_sesion);
                            }
                        });
                    }
                }
            });
        }

        function cargar_cursos_por_nivel(correo) {
            $.ajax({
                url: 'capacitacion_controller.php',
                type: 'POST',
                data: { action: 'obtener_cursos_por_nivel', correo: correo },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        let niveles = res.niveles;
                        let claves = Object.keys(niveles);
                        let total = 0;

                        if (claves.length === 0) {
                            $('#contenedor_niveles_cursos').html('<div class="col-12 text-center text-muted py-3 small">No se encontraron cursos para este colaborador.</div>');
                            $('#badge_total_cursos').text('0 cursos');
                            return;
                        }

                        let html = '';
                        claves.forEach(function(nivel) {
                            let cursos = niveles[nivel];
                            total += cursos.length;
                            let filas = '';
                            cursos.forEach(function(c) {
                                let badge = '';
                                if (c.resultado === 'APROBADO') {
                                    badge = '<span class="badge bg-success text-white border-0 px-2 py-1 font-weight-bold">APROBADO</span>';
                                } else if (c.resultado === 'REPROBADO') {
                                    badge = '<span class="badge bg-danger text-white border-0 px-2 py-1 font-weight-bold">REPROBADO</span>';
                                }
                                filas += `<tr>
                                    <td class="ps-3 py-2 text-dark">${c.nombre_curso}</td>
                                    <td class="text-center py-2">${badge}</td>
                                </tr>`;
                            });

                            html += `
                            <div class="col-md-6 col-lg-4">
                                <div class="card shadow-sm border h-100">
                                    <div class="card-header bg-white border-bottom py-2">
                                        <h6 class="mb-0 font-weight-bold text-dark small text-uppercase">${nivel}</h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <table class="table table-sm table-hover mb-0 small">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-3 py-2 text-muted" style="font-size:0.72rem;">Competencia</th>
                                                    <th class="text-center py-2 text-muted" style="font-size:0.72rem;">Resultado</th>
                                                </tr>
                                            </thead>
                                            <tbody>${filas}</tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>`;
                        });

                        $('#contenedor_niveles_cursos').html(html);
                        $('#badge_total_cursos').text(total + ' curso' + (total !== 1 ? 's' : ''));
                    }
                }
            });
        }

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