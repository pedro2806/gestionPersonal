<?php
// dashboard_controller.php - Controlador Exclusivo de Analítica Numérica de Personal (MESS)
header('Content-Type: application/json');

$conn = mysqli_connect("localhost", "mess_incidencias", "Pipmytrade123", "mess_rrhh");
if (!$conn) {
    echo json_encode(['status' => 'error', 'message' => 'Fallo crítico de conexión con la base de datos.']);
    exit;
}
mysqli_set_charset($conn, "utf8mb4");

$response = ['status' => 'error', 'message' => 'Petición analítica no mapeada.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'obtener_analitica_detallada_personal':
            
            // 1. Resumen global de la fuerza laboral activa
            $q_global = "SELECT 
                            COUNT(*) as total_activos,
                            SUM(CASE WHEN sexo = 'F' THEN 1 ELSE 0 END) as total_mujeres,
                            SUM(CASE WHEN sexo = 'M' THEN 1 ELSE 0 END) as total_hombres,
                            SUM(CASE WHEN tipoContrato = 'PLANTA' THEN 1 ELSE 0 END) as total_planta,
                            SUM(CASE WHEN tipoContrato = 'CONTRATO' THEN 1 ELSE 0 END) as total_contrato
                         FROM usuarios WHERE estatus = 1";
            $res_global = mysqli_query($conn, $q_global);
            $meta_global = mysqli_fetch_assoc($res_global);

            // 2. Detalle por área: Personal, desglose de género, contratos y antigüedad
            $q_areas = "SELECT 
                            IFNULL(d.departamento, 'SIN ÁREA ASIGNADA') as nombre_area, 
                            COUNT(u.noEmpleado) as total_personal,
                            SUM(CASE WHEN u.sexo = 'F' THEN 1 ELSE 0 END) as mujeres,
                            SUM(CASE WHEN u.sexo = 'M' THEN 1 ELSE 0 END) as hombres,
                            SUM(CASE WHEN u.tipoContrato = 'PLANTA' THEN 1 ELSE 0 END) as planta,
                            SUM(CASE WHEN u.tipoContrato = 'CONTRATO' THEN 1 ELSE 0 END) as contrato,
                            ROUND(AVG(DATEDIFF(NOW(), u.fechaIngreso) / 365), 1) as promedio_antiguedad
                        FROM usuarios u
                        LEFT JOIN departamento d ON u.departamento = d.id
                        WHERE u.estatus = 1
                        GROUP BY u.departamento, d.departamento
                        ORDER BY total_personal DESC";
            $res_areas = mysqli_query($conn, $q_areas);
            
            $distribucion_areas = [];
            while ($row = mysqli_fetch_assoc($res_areas)) {
                $distribucion_areas[] = [
                    'area'       => $row['nombre_area'],
                    'total'      => intval($row['total_personal']),
                    'mujeres'    => intval($row['mujeres']),
                    'hombres'    => intval($row['hombres']),
                    'planta'     => intval($row['planta']),
                    'contrato'   => intval($row['contrato']),
                    'antiguedad' => floatval($row['promedio_antiguedad'])
                ];
            }

            // 3. Detalle por puesto para la gráfica de barras
            $q_puestos = "SELECT 
                            IFNULL(p.puesto, 'PUESTO NO DEFINIDO') as nombre_puesto, 
                            COUNT(u.noEmpleado) as total_puesto
                          FROM usuarios u
                          LEFT JOIN puesto p ON u.puesto = p.id
                          WHERE u.estatus = 1
                          GROUP BY u.puesto, p.puesto
                          ORDER BY total_puesto DESC";
            $res_puestos = mysqli_query($conn, $q_puestos);
            
            $labels_puestos = [];
            $valores_puestos = [];
            while ($row = mysqli_fetch_assoc($res_puestos)) {
                $labels_puestos[]  = $row['nombre_puesto'];
                $valores_puestos[] = intval($row['total_puesto']);
            }

            // 4. Estructuración final de la respuesta JSON
            $response = [
                'status' => 'success',
                'resumen_general' => [
                    'total'    => intval($meta_global['total_activos']),
                    'mujeres'  => intval($meta_global['total_mujeres']),
                    'hombres'  => intval($meta_global['total_hombres']),
                    'planta'   => intval($meta_global['total_planta']),
                    'contrato' => intval($meta_global['total_contrato'])
                ],
                'detalle_areas'   => $distribucion_areas,
                'grafico_puestos' => [
                    'labels' => $labels_puestos,
                    'values' => $valores_puestos
                ]
            ];
            break;
    }
}

echo json_encode($response);
mysqli_close($conn);