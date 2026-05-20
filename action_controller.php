<?php
header('Content-Type: application/json');
$conn = mysqli_connect("localhost", "mess_incidencias", "Pipmytrade123", "mess_rrhh");
mysqli_set_charset($conn, "utf8mb4");

$response = ['status' => 'error', 'message' => 'Acción no válida.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'obtener_tipos_documentos_disponibles':
            $rol_contexto = mysqli_real_escape_string($conn, $_POST['rol_contexto']);
            if ($rol_contexto === 'Jefe') {
                $query = "SELECT * FROM expediente_tipos_documentos WHERE subido_por IN ('Jefe Admin', 'Jefe Técnico') ORDER BY nombre_tipo ASC";
            } else {
                $query = "SELECT * FROM expediente_tipos_documentos WHERE subido_por = 'Empleado' ORDER BY nombre_tipo ASC";
            }
            $result = mysqli_query($conn, $query);
            $data = [];
            while($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'obtener_todos_los_alcances_usuario':
            $id_usuario = intval($_POST['id_usuario']);
            $q_base = "SELECT u.departamento AS id_depto_base, d.departamento AS nombre_depto_base FROM usuarios u INNER JOIN departamento d ON u.departamento = d.id WHERE u.id = $id_usuario";
            $res_base = mysqli_query($conn, $q_base);
            $depto_base = mysqli_fetch_assoc($res_base);

            $q_adicionales = "SELECT ejt.id_departamento, d.departamento as nombre_departamento FROM expediente_jefes_tecnicos ejt INNER JOIN departamento d ON ejt.id_departamento = d.id WHERE ejt.id_usuario_empleado = $id_usuario GROUP BY ejt.id_departamento";
            $res_adicionales = mysqli_query($conn, $q_adicionales);
            $deptos_adicionales = [];
            while($row = mysqli_fetch_assoc($res_adicionales)) { $deptos_adicionales[] = $row; }
            
            $response = ['status' => 'success', 'base' => $depto_base, 'adicionales' => $deptos_adicionales];
            break;

        case 'obtener_expediente':
            $id_usuario = intval($_POST['id_usuario']);
            $query = "SELECT ed.*, td.nombre_tipo, td.tipo_alcance, td.requiere_jefe_admin, td.requiere_jefe_tecnico, td.requiere_calidad, td.requiere_rrhh, d.departamento as nombre_area_afectada
                      FROM expediente_documentos ed
                      INNER JOIN expediente_tipos_documentos td ON ed.id_tipo_documento = td.id
                      LEFT JOIN departamento d ON ed.id_departamento_alcance = d.id
                      WHERE ed.id_usuario = $id_usuario ORDER BY ed.fecha_subida DESC";
            $result = mysqli_query($conn, $query);
            $documentos = [];
            while ($row = mysqli_fetch_assoc($result)) { $documentos[] = $row; }
            $response = ['status' => 'success', 'data' => $documentos];
            break;

        case 'subir_documento_expediente':
            $id_usuario_destino = intval($_POST['id_usuario_destino']);
            $id_tipo_doc = intval($_POST['id_tipo_documento']);
            $id_depto_alcance = !empty($_POST['id_departamento_alcance']) ? intval($_POST['id_departamento_alcance']) : "NULL";
            
            $q_tipo = "SELECT * FROM expediente_tipos_documentos WHERE id = $id_tipo_doc";
            $res_tipo = mysqli_query($conn, $q_tipo);
            $tipo = mysqli_fetch_assoc($res_tipo);

            $v_admin   = ($tipo['requiere_jefe_admin'] == 1)   ? 0 : 3;
            $v_tecnico = ($tipo['requiere_jefe_tecnico'] == 1) ? 0 : 3;
            $v_calidad = ($tipo['requiere_calidad'] == 1)      ? 0 : 3;
            $v_rrhh    = ($tipo['requiere_rrhh'] == 1)         ? 0 : 3;

            if ($tipo['subido_por'] === 'Jefe Admin' && $v_admin == 0)   { $v_admin = 1; }
            if ($tipo['subido_por'] === 'Jefe Técnico' && $v_tecnico == 0) { $v_tecnico = 1; }

            if (isset($_FILES['archivo_doc']) && $_FILES['archivo_doc']['error'] === UPLOAD_ERR_OK) {
                $file_name = time() . "_" . basename($_FILES['archivo_doc']['name']);
                $target_dir = "uploads/expedientes/";
                if (!is_dir($target_dir)) { mkdir($target_dir, 0777, true); }
                $target_file = $target_dir . $file_name;
                
                if (move_uploaded_file($_FILES['archivo_doc']['tmp_name'], $target_file)) {
                    $query = "INSERT INTO expediente_documentos (id_usuario, id_tipo_documento, id_departamento_alcance, archivo_url, val_jefe_admin, val_jefe_tecnico, val_calidad, val_rrhh, estatus_general) 
                              VALUES ($id_usuario_destino, $id_tipo_doc, $id_depto_alcance, '$target_file', $v_admin, $v_tecnico, $v_calidad, $v_rrhh, 'En Revisión')";
                    if (mysqli_query($conn, $query)) {
                        $response = ['status' => 'success', 'message' => 'Documento adjuntado de forma conforme.'];
                    }
                }
            }
            break;

        case 'listar_administracion_empleados':
            $query = "SELECT u.id, u.noEmpleado, u.nombre as nombreCompleto, d.departamento as depto_base,
                        (SELECT COUNT(*) FROM expediente_documentos WHERE id_usuario = u.id) as total_docs,
                        (SELECT GROUP_CONCAT(CONCAT(jefes.nombre, ' (', depto_alcance.departamento, ')') SEPARATOR ', ') 
                         FROM expediente_jefes_tecnicos ejt
                         INNER JOIN usuarios jefes ON ejt.id_usuario_jefe_tecnico = jefes.id
                         INNER JOIN departamento depto_alcance ON ejt.id_departamento = depto_alcance.id
                         WHERE ejt.id_usuario_empleado = u.id) as jefes_tecnicos
                      FROM usuarios u
                      LEFT JOIN departamento d ON u.departamento = d.id
                      WHERE u.estatus = 1 ORDER BY u.nombre ASC";
            $result = mysqli_query($conn, $query);
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'obtener_catalogos_auxiliares':
            $q_jefes = "SELECT id, nombre FROM usuarios WHERE estatus = 1 ORDER BY nombre ASC";
            $res_jefes = mysqli_query($conn, $q_jefes);
            $jefes = [];
            while($r = mysqli_fetch_assoc($res_jefes)) { $jefes[] = $r; }

            $q_deptos = "SELECT id, departamento FROM departamento ORDER BY departamento ASC";
            $res_deptos = mysqli_query($conn, $q_deptos);
            $deptos = [];
            while($r = mysqli_fetch_assoc($res_deptos)) { $deptos[] = $r; }

            $response = ['status' => 'success', 'jefes' => $jefes, 'departamentos' => $deptos];
            break;

        case 'obtener_jefes_tecnicos_asignados':
            $id_empleado = intval($_POST['id_usuario_empleado']);
            $query = "SELECT id_usuario_jefe_tecnico, id_departamento FROM expediente_jefes_tecnicos WHERE id_usuario_empleado = $id_empleado";
            $result = mysqli_query($conn, $query);
            $data = [];
            while($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'guardar_jefes_tecnicos_empleado':
            $id_empleado = intval($_POST['id_usuario_empleado']);
            $alcances = isset($_POST['alcances']) ? json_decode($_POST['alcances'], true) : [];
            mysqli_begin_transaction($conn);
            mysqli_query($conn, "DELETE FROM expediente_jefes_tecnicos WHERE id_usuario_empleado = $id_empleado");
            foreach ($alcances as $alcance) {
                $id_jefe  = intval($alcance['id_jefe_tecnico']);
                $id_depto = intval($alcance['id_departamento']);
                mysqli_query($conn, "INSERT INTO expediente_jefes_tecnicos (id_usuario_empleado, id_usuario_jefe_tecnico, id_departamento) VALUES ($id_empleado, $id_jefe, $id_depto)");
            }
            mysqli_commit($conn);
            $response = ['status' => 'success', 'message' => 'Habilidades registradas con éxito.'];
            break;

        case 'obtener_datos_perfil_tarjeta':
            $id_usuario = intval($_POST['id_usuario']);
            $query = "SELECT u.id, u.noEmpleado, u.nombre AS nombreCompleto, u.puesto, u.estatus, d.departamento,
                        (SELECT j.nombre FROM usuarios j WHERE j.id = u.jefe) AS jefe_administrativo,
                        (SELECT GROUP_CONCAT(CONCAT(jt.nombre, ' (', da.departamento, ')') SEPARATOR ', ') FROM expediente_jefes_tecnicos ejt INNER JOIN usuarios jt ON ejt.id_usuario_jefe_tecnico = jt.id INNER JOIN departamento da ON ejt.id_departamento = da.id WHERE ejt.id_usuario_empleado = u.id) AS jefes_tecnicos
                      FROM usuarios u LEFT JOIN departamento d ON u.departamento = d.id WHERE u.id = $id_usuario";
            $res = mysqli_query($conn, $query);
            $response = ['status' => 'success', 'data' => mysqli_fetch_assoc($res)];
            break;

        case 'listar_catalogo_completo':
            $query = "SELECT * FROM expediente_tipos_documentos ORDER BY nombre_tipo ASC";
            $result = mysqli_query($conn, $query);
            $data = [];
            while($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'guardar_nuevo_tipo_documento':
            $nombre_tipo = mysqli_real_escape_string($conn, $_POST['nombre_tipo']);
            $subido_por = mysqli_real_escape_string($conn, $_POST['subido_por']);
            $tipo_alcance = mysqli_real_escape_string($conn, $_POST['tipo_alcance']);
            $req_admin = isset($_POST['req_admin']) ? 1 : 0;
            $req_tecnico = isset($_POST['req_tecnico']) ? 1 : 0;
            $req_calidad = isset($_POST['req_calidad']) ? 1 : 0;
            $req_rrhh = isset($_POST['req_rrhh']) ? 1 : 0;
            mysqli_query($conn, "INSERT INTO expediente_tipos_documentos (nombre_tipo, subido_por, tipo_alcance, requiere_jefe_admin, requiere_jefe_tecnico, requiere_calidad, requiere_rrhh) VALUES ('$nombre_tipo', '$subido_por', '$tipo_alcance', $req_admin, $req_tecnico, $req_calidad, $req_rrhh)");
            $response = ['status' => 'success', 'message' => 'Catálogo actualizado.'];
            break;
    }
}
echo json_encode($response);
exit;
?>