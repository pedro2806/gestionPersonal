<?php 
// dashboard_personal.php - Panel de Analítica Avanzada y Distribución Numérica (MESS)
require_once 'conn.php'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MESS - Analítica de Personal</title>
    
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">    
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
</head>

<body id="page-top" class="bg-light">

    <div id="wrapper">
        <?php include 'menu.php'; ?>

        <div id="content-wrapper" class="d-flex flex-column bg-light">
            <div id="content">
                <?php include 'encabezado.php'; ?>

                <div class="container-fluid px-4 mt-3">
                    
                    <div class="d-flex align-items-center justify-content-between mb-4 py-2 border-bottom border-light">
                        <div>
                            <h5 class="font-weight-bold text-dark mb-1">Analítica y Distribución de Personal</h5>
                            <p class="text-muted small mb-0">Auditoría de personal activo, fuerza contractual, equilibrio de género y antigüedad operativa.</p>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4 col-sm-6">
                            <div class="card border-0 bg-white shadow-sm rounded-3 py-2">
                                <div class="card-body">
                                    <div class="row align-items-center no-gutters">
                                        <div class="col ps-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1" style="letter-spacing: 0.05em;">Plantilla Activa</div>
                                            <div class="h4 mb-0 font-weight-bold text-dark" id="num_total_personal">0</div>
                                        </div>
                                        <div class="col-auto pe-2"><i class="fas fa-users fa-2x text-light"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6">
                            <div class="card border-0 bg-white shadow-sm rounded-3 py-2">
                                <div class="card-body">
                                    <div class="row align-items-center no-gutters">
                                        <div class="col ps-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1" style="letter-spacing: 0.05em;">Personal de Planta</div>
                                            <div class="h4 mb-0 font-weight-bold text-dark" id="num_planta">0</div>
                                        </div>
                                        <div class="col-auto pe-2"><i class="fas fa-id-card fa-2x text-light"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-6">
                            <div class="card border-0 bg-white shadow-sm rounded-3 py-2">
                                <div class="card-body">
                                    <div class="row align-items-center no-gutters">
                                        <div class="col ps-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1" style="letter-spacing: 0.05em;">Personal Eventual</div>
                                            <div class="h4 mb-0 font-weight-bold text-dark" id="num_contrato">0</div>
                                        </div>
                                        <div class="col-auto pe-2"><i class="fas fa-clock fa-2x text-light"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mb-4">
                        <div class="col-xl-8 col-lg-7">
                            <div class="card border-0 bg-white shadow-sm rounded-3 p-3">
                                <div class="card-header bg-transparent border-0 pt-2 px-2 pb-1">
                                    <h6 class="m-0 font-weight-bold text-dark text-uppercase small" style="letter-spacing: 0.03em;">
                                        <i class="fas fa-table text-muted me-2"></i>Desglose Numérico por Área Base
                                    </h6>
                                </div>
                                <div class="card-body p-0 pt-2">
                                    <div class="table-responsive">
                                        <table id="tabla_analitica_areas" class="table table-hover align-middle mb-0 small text-secondary" width="100%">
                                            <thead class="table-light text-uppercase text-muted" style="font-size: 0.7rem; letter-spacing: 0.03em;">
                                                <tr>
                                                    <th class="py-3 ps-3 text-start">Departamento / Laboratorio</th>
                                                    <th class="py-3 text-center">Total</th>
                                                    <th class="py-3 text-center">Filtro Género</th>
                                                    <th class="py-3 text-center">Tipo Contrato</th>
                                                    <th class="py-3 text-center pe-3">Prom. Antigüedad</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody_analitica_areas" class="border-0">
                                                </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-lg-5">
                            <div class="card border-0 bg-white shadow-sm rounded-3 h-100">
                                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                    <h6 class="m-0 font-weight-bold text-dark text-uppercase small" style="letter-spacing: 0.03em;">
                                        <i class="fas fa-chart-pie text-muted me-2"></i>Proporción de Género
                                    </h6>
                                </div>
                                <div class="card-body p-4 pt-2">
                                    <div style="position: relative; height: 210px;">
                                        <canvas id="canvas_grafica_genero"></canvas>
                                    </div>
                                    <div class="d-flex justify-content-center gap-4 mt-3 pt-2 border-top border-light text-center small">
                                        <div>
                                            <span class="text-muted small d-block">Mujeres</span>
                                            <strong class="text-dark fs-6" id="num_mujeres">0</strong>
                                        </div>
                                        <div class="border-start border-light"></div>
                                        <div>
                                            <span class="text-muted small d-block">Hombres</span>
                                            <strong class="text-dark fs-6" id="num_hombres">0</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="card border-0 bg-white shadow-sm rounded-3">
                                <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                                    <h6 class="m-0 font-weight-bold text-dark text-uppercase small" style="letter-spacing: 0.03em;">
                                        <i class="fas fa-user-tag text-muted me-2"></i>Censo de Colaboradores por Puesto Estructural
                                    </h6>
                                </div>
                                <div class="card-body p-4">
                                    <div style="position: relative; height: 280px;">
                                        <canvas id="canvas_puestos_maestro"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <footer class="sticky-footer bg-light mt-auto border-top-0">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto text-muted small"><span>Copyright &copy; MESS 2026</span></div>
                </div>
            </footer>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>    
    <script src="js/sb-admin-2.min.js"></script>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script src="app_dashboard_personal.js"></script>
</body>
</html>