<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>MESS - Estructura Organizacional</title>
    
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    
    <style>
        #chart_container {
            width: 100%;
            height: 650px;
            min-height: 650px;
            background-color: #f8f9fc;
            border-radius: 8px;
            overflow: hidden;
            display: block;
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
                        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-sitemap mr-2"></i>Organigrama de Operaciones</h1>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body bg-light py-3">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <label class="small font-weight-bold text-uppercase text-muted mb-1">Filtrar por Área Base</label>
                                    <select id="filtro_area" class="form-control form-control-sm shadow-sm" onchange="filtrarEstructuraOrganigrama()">
                                        <option value="COMPLETO">-- Ver Estructura Completa --</option>
                                    </select>
                                </div>
                                <div class="col-md-8 text-right pt-4">
                                    <span class="badge bg-primary text-white p-2 small shadow-sm">
                                        <i class="fas fa-info-circle mr-1"></i> Doble clic en un nodo para colapsar o expandir sus ramas.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body p-0">
                            <div id="chart_container"></div>
                        </div>
                    </div>

                </div>
            </div>
            
            <footer class="sticky-footer bg-white">
                <div class="container my-auto"><div class="copyright text-center my-auto"><span>Copyright &copy; MESS 2026</span></div></div>
            </footer>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="js/sb-admin-2.min.js"></script>
    
    <script src="app_organigrama.js"></script>
</body>
</html> 