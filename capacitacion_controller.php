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

            $stmt = mysqli_prepare($conn_cap,
                "SELECT
                    p.post_title AS nombre_curso,
                    MAX(t.name) AS area_departamento,
                    ua.activity_status AS estatus,
                    ua.completed_at
                FROM wp_users u
                INNER JOIN wp_masteriyo_user_activities ua
                    ON ua.user_id = u.ID
                INNER JOIN wp_posts p
                    ON p.ID = ua.item_id
                LEFT JOIN wp_term_relationships tr
                    ON tr.object_id = p.ID
                LEFT JOIN wp_term_taxonomy tt
                    ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'course_cat'
                LEFT JOIN wp_terms t
                    ON t.term_id = tt.term_id
                WHERE u.user_email = ?
                  AND ua.activity_type = 'course_progress'
                  AND ua.activity_status = 'completed'
                GROUP BY p.ID, p.post_title, ua.activity_status, ua.completed_at
                ORDER BY ua.completed_at DESC"
            );

            if (!$stmt) {
                $response = ['status' => 'error', 'message' => 'Error en la preparación de la consulta.'];
                break;
            }

            mysqli_stmt_bind_param($stmt, 's', $correo);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = [
                    'nombre_curso'      => $row['nombre_curso'],
                    'area_departamento' => $row['area_departamento'] ?? 'Sin categoría',
                    'estatus'           => $row['estatus']
                ];
            }

            mysqli_stmt_close($stmt);
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'obtener_cursos_por_nivel':
            $correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';

            if ($correo === '') {
                $response = ['status' => 'error', 'message' => 'El correo electrónico es obligatorio.'];
                break;
            }

            $stmt = mysqli_prepare($conn_cap,
                "SELECT
                    p.post_title AS nombre_curso,
                    MAX(t.name) AS nivel,
                    ua.activity_status AS estatus
                FROM wp_users u
                INNER JOIN wp_masteriyo_user_activities ua
                    ON ua.user_id = u.ID
                INNER JOIN wp_posts p
                    ON p.ID = ua.item_id
                LEFT JOIN wp_term_relationships tr
                    ON tr.object_id = p.ID
                LEFT JOIN wp_term_taxonomy tt
                    ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'course_cat'
                LEFT JOIN wp_terms t
                    ON t.term_id = tt.term_id
                WHERE u.user_email = ?
                  AND ua.activity_type = 'course_progress'
                  AND ua.activity_status IN ('completed', 'failed')
                GROUP BY p.ID, p.post_title, ua.activity_status
                ORDER BY nivel ASC, p.post_title ASC"
            );

            if (!$stmt) {
                $response = ['status' => 'error', 'message' => 'Error en la preparación de la consulta.'];
                break;
            }

            mysqli_stmt_bind_param($stmt, 's', $correo);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $niveles = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $nivel_nombre = $row['nivel'] ?? 'Sin categoría';
                $resultado = '';
                if ($row['estatus'] === 'completed') $resultado = 'APROBADO';
                if ($row['estatus'] === 'failed') $resultado = 'REPROBADO';

                if (!isset($niveles[$nivel_nombre])) {
                    $niveles[$nivel_nombre] = [];
                }
                $niveles[$nivel_nombre][] = [
                    'nombre_curso' => $row['nombre_curso'],
                    'resultado'    => $resultado
                ];
            }

            mysqli_stmt_close($stmt);
            $response = ['status' => 'success', 'niveles' => $niveles];
            break;
    }
}

echo json_encode($response);
mysqli_close($conn_cap);
