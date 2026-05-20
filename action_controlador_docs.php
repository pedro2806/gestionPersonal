<?php
// action_controlador_docs.php - Controlador Centralizado de Control Documental y Personal
header('Content-Type: application/json');
require_once 'conn.php';

$response = ['status' => 'error', 'message' => 'Acción no válida o no permitida.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        
        // A) MI EXPEDIENTE: Obtener la matriz documental de un usuario específico
        case 'obtener_expediente':
            $id_usuario = intval($_POST['id_usuario']);
            
            $query = "SELECT ed.*, td.nombre_tipo, td.requiere_jefe_admin, td.requiere_jefe_tecnico, td.requiere_calidad, td.requiere_rrhh
                      FROM expediente_documentos ed
                      INNER JOIN tipos_documentos td ON ed.id_tipo_documento = td.id
                      WHERE ed.id_usuario = $id_usuario
                      ORDER BY ed.fecha_subida DESC";
            
            $result = mysqli_query($conn, $query);
            $documentos = [];
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $documentos[] = $row;
                }
                $response = ['status' => 'success', 'data' => $documentos];
            } else {
                $response = ['status' => 'error', 'message' => 'Error al obtener el expediente documental.'];
            }
            break;

        // B) SUBIR DOCUMENTO AL EXPEDIENTE PROPIO DESDE EL FORMULARIO
        case 'subir_documento_expediente':
            $id_usuario = intval($_POST['id_usuario']);
            $id_tipo_doc = intval($_POST['id_tipo_documento']);
            
            $q_tipo = "SELECT * FROM tipos_documentos WHERE id = $id_tipo_doc";
            $res_tipo = mysqli_query($conn, $q_tipo);
            $tipo = mysqli_fetch_assoc($res_tipo);
            
            if (!$tipo) {
                $response = ['status' => 'error', 'message' => 'Tipo de documento inválido.'];
                break;
            }

            // Si el documento no lo requiere, pasa por defecto como 3 (No Aplica), sino inicia en 0 (Pendiente)
            $v_admin   = ($tipo['requiere_jefe_admin'] == 1)   ? 0 : 3;
            $v_tecnico = ($tipo['requiere_jefe_tecnico'] == 1) ? 0 : 3;
            $v_calidad = ($tipo['requiere_calidad'] == 1)      ? 0 : 3;
            $v_rrhh    = ($tipo['requiere_rrhh'] == 1)         ? 0 : 3;

            if (isset($_FILES['archivo_doc']) && $_FILES['archivo_doc']['error'] === UPLOAD_ERR_OK) {
                $file_tmp  = $_FILES['archivo_doc']['tmp_name'];
                $file_name = time() . "_" . basename($_FILES['archivo_doc']['name']);
                $target_dir = "uploads/expedientes/";
                
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                
                $target_file = $target_dir . $file_name;
                
                if (move_uploaded_file($file_tmp, $target_file)) {
                    $query = "INSERT INTO expediente_documentos (id_usuario, id_tipo_documento, archivo_url, val_jefe_admin, val_jefe_tecnico, val_calidad, val_rrhh, estatus_general) 
                              VALUES ($id_usuario, $id_tipo_doc, '$target_file', $v_admin, $v_tecnico, $v_calidad, $v_rrhh, 'En Revisión')";
                    
                    if (mysqli_query($conn, $query)) {
                        $response = ['status' => 'success', 'message' => 'Documento cargado con éxito, en espera de aprobaciones de área.'];
                    } else {
                        $response = ['status' => 'error', 'message' => 'Error al guardar el documento en el sistema.'];
                    }
                } else {
                    $response = ['status' => 'error', 'message' => 'Error al mover el archivo físico al servidor.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'Archivo adjunto corrupto o ausente.'];
            }
            break;

        // C) ADMINISTRACIÓN: Listar colaboradores y concatenar sus Jefes Técnicos mapeados directo de la tabla usuarios
        case 'listar_administracion_empleados':
            $query = "SELECT u.id, u.noEmpleado, u.nombre as nombreCompleto, d.departamento,
                        (SELECT COUNT(*) FROM expediente_documentos WHERE id_usuario = u.id) as total_docs,
                        (SELECT GROUP_CONCAT(jefes.nombre SEPARATOR ', ') 
                         FROM usuario_jefes_tecnicos ujt
                         INNER JOIN usuarios jefes ON ujt.id_usuario_jefe_tecnico = jefes.id
                         WHERE ujt.id_usuario_empleado = u.id) as jefes_tecnicos
                      FROM usuarios u
                      LEFT JOIN departamento d ON u.departamento = d.id
                      WHERE u.estatus = 1
                      ORDER BY u.nombre ASC";
            
            $result = mysqli_query($conn, $query);
            $data = [];
            if ($result) {
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                $response = ['status' => 'success', 'data' => $data];
            } else {
                $response = ['status' => 'error', 'message' => 'Error al consultar personal administrativo.'];
            }
            break;

        // D) ADMINISTRACIÓN: Obtener IDs de jefes técnicos actuales asignados para pre-cargar el Select2
        case 'obtener_jefes_tecnicos_asignados':
            $id_empleado = intval($_POST['id_usuario_empleado']);
            $query = "SELECT id_usuario_jefe_tecnico FROM usuario_jefes_tecnicos WHERE id_usuario_empleado = $id_empleado";
            $result = mysqli_query($conn, $query);
            $ids = [];
            if ($result) {
                while($row = mysqli_fetch_assoc($result)) {
                    $ids[] = $row['id_usuario_jefe_tecnico'];
                }
                $response = ['status' => 'success', 'data' => $ids];
            }
            break;

        // E) ADMINISTRACIÓN: Reemplazar / Guardar la asignación múltiple de Jefes Técnicos para un empleado
        case 'guardar_jefes_tecnicos_empleado':
            $id_empleado = intval($_POST['id_usuario_empleado']);
            $jefes_ids = isset($_POST['jefes_ids']) ? $_POST['jefes_ids'] : [];

            mysqli_begin_transaction($conn);
            mysqli_query($conn, "DELETE FROM usuario_jefes_tecnicos WHERE id_usuario_empleado = $id_empleado");

            $errores = 0;
            foreach ($jefes_ids as $id_jefe) {
                $id_jefe = intval($id_jefe);
                $q_ins = "INSERT INTO usuario_jefes_tecnicos (id_usuario_empleado, id_usuario_jefe_tecnico) VALUES ($id_empleado, $id_jefe)";
                if (!mysqli_query($conn, $q_ins)) {
                    $errores++;
                }
            }

            if ($errores === 0) {
                mysqli_commit($conn);
                $response = ['status' => 'success', 'message' => 'Jefaturas técnicas actualizadas con éxito.'];
            } else {
                mysqli_rollback($conn);
                $response = ['status' => 'error', 'message' => 'Ocurrió un error al procesar las asignaciones múltiples.'];
            }
            break;

        // F) EVALUADOR/ADMIN: Registrar firma / Dictamen de aprobación o rechazo por área
        case 'validar_documento_area':
            $id_documento = intval($_POST['id_documento']);
            $columna_area = mysqli_real_escape_string($conn, $_POST['area_rol']); // 'val_jefe_admin', 'val_jefe_tecnico', etc.
            $estatus_voto = intval($_POST['voto']); // 1 = Aprobado, 2 = Rechazado
            $comentarios  = mysqli_real_escape_string($conn, $_POST['comentarios']);

            $columnas_permitidas = ['val_jefe_admin', 'val_jefe_tecnico', 'val_calidad', 'val_rrhh'];
            if (!in_array($columna_area, $columnas_permitidas)) {
                $response = ['status' => 'error', 'message' => 'Columna inyectada no válida.'];
                break;
            }

            $query_update = "UPDATE expediente_documentos SET $columna_area = $estatus_voto, comentarios = '$comentarios' WHERE id = $id_documento";
            
            if (mysqli_query($conn, $query_update)) {
                $q_check = "SELECT * FROM expediente_documentos WHERE id = $id_documento";
                $res_check = mysqli_query($conn, $q_check);
                $doc = mysqli_fetch_assoc($res_check);

                // Si alguna firma necesaria rechaza (2), el estatus global cae a Rechazado
                if ($doc['val_jefe_admin'] == 2 || $doc['val_jefe_tecnico'] == 2 || $doc['val_calidad'] == 2 || $doc['val_rrhh'] == 2) {
                    mysqli_query($conn, "UPDATE expediente_documentos SET estatus_general = 'Rechazado' WHERE id = $id_documento");
                } 
                // Si ya no quedan casillas pendientes (0) en firmas requeridas, pasa a Aprobado
                else if ($doc['val_jefe_admin'] != 0 && $doc['val_jefe_tecnico'] != 0 && $doc['val_calidad'] != 0 && $doc['val_rrhh'] != 0) {
                    mysqli_query($conn, "UPDATE expediente_documentos SET estatus_general = 'Aprobado' WHERE id = $id_documento");
                } else {
                    mysqli_query($conn, "UPDATE expediente_documentos SET estatus_general = 'En Revisión' WHERE id = $id_documento");
                }

                $response = ['status' => 'success', 'message' => 'Dictamen y firma guardados con éxito.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Error al guardar la revisión en el sistema.'];
            }
            break;
    }
}

echo json_encode($response);
exit;
?>