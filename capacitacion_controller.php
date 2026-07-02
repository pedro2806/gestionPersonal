<?php
// capacitacion_controller.php - Consulta de cursos aprobados en el sistema de capacitación Masteriyo (MESS)
header('Content-Type: application/json; charset=utf-8');

$conn_cap = mysqli_connect("localhost", "mess_incidencias", "Pipmytrade123", "mess_capacitacion");
if (!$conn_cap) {
    echo json_encode(['status' => 'error', 'message' => 'Fallo de conexión con la base de datos de capacitación.']);
    exit;
}
mysqli_set_charset($conn_cap, "utf8mb4");

$response = ['status' => 'error', 'message' => 'Acción no válida.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {

        case 'obtener_cursos_aprobados':
            $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';

            if ($correo === '') {
                $response = ['status' => 'error', 'message' => 'El correo electrónico es obligatorio.'];
                break;
            }

            $correo_esc = mysqli_real_escape_string($conn_cap, $correo);
            $q = "SELECT
                    p.post_title AS nombre_curso,
                    MAX(t.name) AS area_departamento,
                    ua.activity_status AS estatus,
                    ua.completed_at
                FROM wp_users u
                INNER JOIN wp_masteriyo_user_activities ua ON ua.user_id = u.ID
                INNER JOIN wp_posts p ON p.ID = ua.item_id
                LEFT JOIN wp_term_relationships tr ON tr.object_id = p.ID
                LEFT JOIN wp_term_taxonomy tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'course_cat'
                LEFT JOIN wp_terms t ON t.term_id = tt.term_id
                WHERE u.user_email = '$correo_esc'
                  AND ua.activity_type = 'course_progress'
                  AND ua.activity_status = 'completed'
                GROUP BY p.ID, p.post_title, ua.activity_status, ua.completed_at
                ORDER BY ua.completed_at DESC";
            $result = mysqli_query($conn_cap, $q);

            $data = [];
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = [
                        'nombre_curso'      => $row['nombre_curso'],
                        'area_departamento' => $row['area_departamento'] ?? 'Sin categoría',
                        'estatus'           => $row['estatus']
                    ];
                }
            }
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'obtener_cursos_por_nivel':
            $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';

            if ($correo === '') {
                $response = ['status' => 'error', 'message' => 'El correo electrónico es obligatorio.'];
                break;
            }

            $puesto = isset($_POST['puesto']) ? trim($_POST['puesto']) : '';
            $col_rol = '';
            $puesto_lower = strtolower($puesto);
            if (strpos($puesto_lower, 'ingeniero') !== false || strpos($puesto_lower, 'metrólogo') !== false || strpos($puesto_lower, 'signatario') !== false) {
                $col_rol = '`Ingeniero de Servicio`';
            } elseif (strpos($puesto_lower, 'jefe') !== false && strpos($puesto_lower, 'laboratorio') !== false) {
                $col_rol = '`Jefe de Laboratorio`';
            } elseif (strpos($puesto_lower, 'aftermarket') !== false) {
                $col_rol = '`Aftermarket`';
            } elseif (strpos($puesto_lower, 'comercial') !== false || strpos($puesto_lower, 'ventas') !== false) {
                $col_rol = '`Comercial`';
            } elseif (strpos($puesto_lower, 'admin') !== false || strpos($puesto_lower, 'contab') !== false || strpos($puesto_lower, 'recursos') !== false) {
                $col_rol = '`Administracion`';
            }

            $where_rol = $col_rol !== '' ? "WHERE $col_rol = 1" : '';
            $res_mc = mysqli_query($conn_cap, "SELECT id_registro, Competencia, Nivel FROM Matriz_Competencias $where_rol ORDER BY id_registro ASC");
            $competencias = [];
            if ($res_mc) {
                while ($mc = mysqli_fetch_assoc($res_mc)) { $competencias[] = $mc; }
            } else {
                $response = ['status' => 'error', 'message' => 'Error matriz_competencias: ' . mysqli_error($conn_cap)];
                break;
            }

            $correo_esc = mysqli_real_escape_string($conn_cap, $correo);
            $q_cursos = "SELECT
                    p.post_title AS nombre_curso,
                    p.ID AS course_id,
                    u.display_name,
                    u.user_login,
                    ua.activity_status AS estatus,
                    ua.completed_at,
                    MAX(pm_cert.meta_value) AS certificate_id
                FROM wp_users u
                INNER JOIN wp_masteriyo_user_activities ua ON ua.user_id = u.ID
                INNER JOIN wp_posts p ON p.ID = ua.item_id
                LEFT JOIN wp_postmeta pm_cert ON pm_cert.post_id = p.ID AND pm_cert.meta_key = '_certificate_id'
                WHERE u.user_email = '$correo_esc'
                  AND ua.activity_type = 'course_progress'
                  AND ua.activity_status IN ('completed', 'failed')
                GROUP BY p.ID, p.post_title, u.display_name, u.user_login, ua.activity_status, ua.completed_at";
            $res_cursos = mysqli_query($conn_cap, $q_cursos);

            $cursos_usuario = [];
            if ($res_cursos) {
                while ($row = mysqli_fetch_assoc($res_cursos)) {
                    $cursos_usuario[strtolower(trim($row['nombre_curso']))] = $row;
                }
            }

            // Obtener fechas de cierre para cursos sin actividad del usuario
            $fechas_cierre = [];
            $res_end = mysqli_query($conn_cap, "SELECT p.post_title, pm.meta_value FROM wp_posts p INNER JOIN wp_postmeta pm ON pm.post_id = p.ID AND pm.meta_key = '_end_date' WHERE p.post_type = 'mto-course'");
            while ($res_end && $row = mysqli_fetch_assoc($res_end)) {
                $fecha = '';
                if (preg_match('/\"(\d{4}-\d{2}-\d{2})/', $row['meta_value'], $m)) {
                    $fecha = $m[1];
                }
                if ($fecha) $fechas_cierre[strtolower(trim($row['post_title']))] = $fecha;
            }

            $niveles = [];
            $display_name = '';
            foreach ($competencias as $comp) {
                $nivel = $comp['Nivel'];
                if (!isset($niveles[$nivel])) $niveles[$nivel] = [];

                $key = strtolower(trim($comp['Competencia']));
                $resultado = '';
                $cert_url = '';

                $fecha = '';
                if (isset($cursos_usuario[$key])) {
                    $cu = $cursos_usuario[$key];
                    if ($cu['estatus'] === 'completed') $resultado = 'APROBADO';
                    if ($cu['estatus'] === 'failed') $resultado = 'REPROBADO';
                    if ($display_name === '' && !empty($cu['display_name'])) $display_name = $cu['display_name'];

                    if ($resultado === 'APROBADO' && !empty($cu['completed_at'])) {
                        $fecha = date('d/m/Y', strtotime($cu['completed_at']));
                    }

                    if (!empty($cu['certificate_id']) && $resultado === 'APROBADO') {
                        $cert_url = 'https://messbook.com.mx/capacitacion/?course_id=' . $cu['course_id'] . '&certificate_id=' . $cu['certificate_id'] . '&username=' . urlencode($cu['user_login']);
                    }
                } else {
                    if (isset($fechas_cierre[$key])) {
                        $fecha = date('d/m/Y', strtotime($fechas_cierre[$key]));
                    }
                }

                $niveles[$nivel][] = [
                    'nombre_curso' => $comp['Competencia'],
                    'resultado'    => $resultado,
                    'certificado'  => $cert_url,
                    'fecha'        => $fecha
                ];
            }

            $response = ['status' => 'success', 'niveles' => $niveles];
            break;

        case 'obtener_procedimientos_empleado':
            $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';

            $result = mysqli_query($conn_cap, "SELECT codigo_metodo, descripcion FROM matriz_procedimientos ORDER BY codigo_metodo ASC");
            if (!$result) {
                $response = ['status' => 'error', 'message' => 'Error matriz_procedimientos: ' . mysqli_error($conn_cap)];
                break;
            }

            $lab_nombres = [
                'HU' => 'Humedad', 'TE' => 'Temperatura', 'PR' => 'Presión',
                'EL' => 'Eléctrica', 'DU' => 'Dureza', 'MA' => 'Masa',
                'PT' => 'Par Torsional', 'FZ' => 'Fuerza', 'DI' => 'Dimensional'
            ];

            $laboratorios = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $partes = explode('-', $row['codigo_metodo']);
                $lab_code = $partes[1] ?? '??';
                $lab_label = isset($lab_nombres[$lab_code]) ? $lab_nombres[$lab_code] . ' (' . $lab_code . ')' : $lab_code;

                if (!isset($laboratorios[$lab_label])) {
                    $laboratorios[$lab_label] = [];
                }
                $laboratorios[$lab_label][] = [
                    'codigo'      => $row['codigo_metodo'],
                    'descripcion' => $row['descripcion'],
                    'resultado'   => ''
                ];
            }

            $response = ['status' => 'success', 'laboratorios' => $laboratorios];
            break;

        case 'obtener_especialidades_laboratorio':
            $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
            $noEmpleado = isset($_POST['noEmpleado']) ? intval($_POST['noEmpleado']) : 0;

            if ($correo === '' || $noEmpleado === 0) {
                $response = ['status' => 'error', 'message' => 'Correo y noEmpleado son obligatorios.'];
                break;
            }

            // Conexión a mess_rrhh para obtener datos del usuario
            $conn_rrhh = new mysqli("localhost", "mess_incidencias", "Pipmytrade123", "mess_rrhh");
            if ($conn_rrhh->connect_error) {
                $response = ['status' => 'error', 'message' => 'Error conectando a BD RRHH'];
                break;
            }

            // Obtener departamento y puesto del usuario
            $query_user = "SELECT u.departamento, p.puesto FROM usuarios u
                          LEFT JOIN puesto p ON u.puesto = p.id
                          WHERE u.noEmpleado = ? LIMIT 1";
            $stmt = $conn_rrhh->prepare($query_user);
            $stmt->bind_param("i", $noEmpleado);
            $stmt->execute();
            $res_user = $stmt->get_result();

            $es_jefe_lab = false;
            $departamento_usuario = 0;

            if ($row_user = $res_user->fetch_assoc()) {
                $departamento_usuario = intval($row_user['departamento']);
                $puesto_lower = strtolower($row_user['puesto']);

                // Verificar si es Jefe de Laboratorio (puesto 52 o 61)
                if (strpos($puesto_lower, 'jefe') !== false && strpos($puesto_lower, 'laboratorio') !== false) {
                    $es_jefe_lab = true;
                }
            }

            $conn_rrhh->close();

            // Si NO es jefe de lab, no mostrar esta sección
            if (!$es_jefe_lab || $departamento_usuario === 0) {
                $response = ['status' => 'success', 'es_jefe' => false, 'especialidades' => []];
                break;
            }

            // Mapeo de departamentos a cursos de especialidad
            // Depto 15 = CALIBRACIONES, Depto 16 = DIMENSIONAL
            // Placeholder: especialidades sin ID mapeado aún = array vacío
            $cursos_por_depto = [
                16 => [6166, 6236, 6269, 5605],      // DIMENSIONAL: Interpretación, Tolerancias, Rugosidad, Calypso
                15 => [5626, 6340, 0, 0, 0]          // CALIBRACIONES: Incertidumbres I, II + 3 placeholders
            ];

            // Nombres de especialidades por departamento (para placeholders)
            $nombres_especialidades = [
                16 => ['Interpretación de planos', 'Tolerancias geométricas y dimensionales', 'Rugosidad de superficies', 'Calypso Básico'],
                15 => ['Uso y manejo de máquina unidimensional', 'Calibración de bloques patrón (incluido el cálculo de incertidumbre)', 'Medición en perfilómetro (avanzado)', 'Medición en rugosímetro (avanzado)', 'Evaluación/ Interpretación de la incertidumbre']
            ];

            if (!isset($cursos_por_depto[$departamento_usuario])) {
                $response = ['status' => 'success', 'es_jefe' => true, 'especialidades' => []];
                break;
            }

            $ids_cursos = $cursos_por_depto[$departamento_usuario];
            $ids_str = implode(',', $ids_cursos);

            // Obtener cursos de Masteriyo
            $correo_esc = mysqli_real_escape_string($conn_cap, $correo);
            $q_cursos_esp = "SELECT
                    p.ID,
                    p.post_title AS nombre_curso,
                    ua.activity_status AS estatus,
                    ua.completed_at
                FROM wp_posts p
                LEFT JOIN wp_masteriyo_user_activities ua ON ua.item_id = p.ID
                    AND ua.activity_type = 'course_progress'
                    AND ua.user_id = (SELECT ID FROM wp_users WHERE user_email = '$correo_esc' LIMIT 1)
                WHERE p.ID IN ($ids_str)
                AND p.post_type = 'mto-course'
                ORDER BY p.post_title";

            $res_esp = mysqli_query($conn_cap, $q_cursos_esp);
            $especialidades = [];
            $cursos_encontrados = [];

            if ($res_esp) {
                while ($row = mysqli_fetch_assoc($res_esp)) {
                    $resultado = '';
                    $fecha = '';

                    if ($row['estatus'] === 'completed') {
                        $resultado = 'APROBADO';
                        if (!empty($row['completed_at'])) {
                            $fecha = date('d/m/Y', strtotime($row['completed_at']));
                        }
                    } elseif ($row['estatus'] === 'failed') {
                        $resultado = 'REPROBADO';
                    } else {
                        $resultado = 'PENDIENTE';
                    }

                    $especialidades[] = [
                        'id_curso' => $row['ID'],
                        'nombre_curso' => $row['nombre_curso'],
                        'resultado' => $resultado,
                        'fecha' => $fecha
                    ];
                    $cursos_encontrados[] = $row['ID'];
                }
            }

            // Agregar placeholders para cursos sin ID aún
            if (isset($nombres_especialidades[$departamento_usuario])) {
                foreach ($ids_cursos as $idx => $id_curso) {
                    if ($id_curso === 0 && isset($nombres_especialidades[$departamento_usuario][$idx])) {
                        $especialidades[] = [
                            'id_curso' => 0,
                            'nombre_curso' => $nombres_especialidades[$departamento_usuario][$idx],
                            'resultado' => '',
                            'fecha' => ''
                        ];
                    }
                }
            }

            // Nombre del lab
            $nombres_labs = [
                16 => 'Especialidades Dimensional',
                15 => 'Especialidades Calibración'
            ];

            $response = [
                'status' => 'success',
                'es_jefe' => true,
                'nombre_lab' => $nombres_labs[$departamento_usuario] ?? 'Especialidades',
                'especialidades' => $especialidades
            ];
            break;
    }
}

echo json_encode($response);
mysqli_close($conn_cap);
