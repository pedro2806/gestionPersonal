<?php
// expediente_usuario_controller.php - Mapeo con campo corregido "departamento" (MESS)
header('Content-Type: application/json; charset=utf-8');
require_once 'conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no autorizado.']);
    exit;
}

// Si se envía id_usuario por POST lo usamos, sino caemos a la cookie
$noEmpleado = isset($_POST['id_usuario']) && intval($_POST['id_usuario']) > 0
    ? intval($_POST['id_usuario'])
    : (isset($_COOKIE['noEmpleadoGP']) ? intval($_COOKIE['noEmpleadoGP']) : 276);
$action = isset($_POST['action']) ? trim($_POST['action']) : '';

switch ($action) {

    case 'listar_expediente_propio':
        try {
            // 1. OBTENER EL PERFIL BASE Y EL DEPARTAMENTO REAL USANDO EL CAMPO CORREGIDO 'u.departamento'
            $query_perfil = "SELECT 
                                u.departamento AS id_depto_base, -- <- CORREGIDO AQUÍ
                                d.departamento AS nombre_depto_base,
                                CASE 
                                    WHEN p.puesto LIKE '%Metrólogo%' OR p.puesto LIKE '%Signatario%' OR p.puesto LIKE '%Jefe Técnico%' THEN 'Solo Técnico'
                                    ELSE 'Solo Administrativo'
                                END as perfil_sistema
                             FROM usuarios u
                             INNER JOIN puesto p ON u.puesto = p.id
                             LEFT JOIN departamento d ON u.departamento = d.id -- <- CORREGIDO AQUÍ
                             WHERE u.noEmpleado = ? LIMIT 1";
            
            $stmt_perfil = $conn->prepare($query_perfil);
            $stmt_perfil->bind_param("i", $noEmpleado);
            $stmt_perfil->execute();
            $res_perfil = $stmt_perfil->get_result();
            
            $perfil_sistema = 'Solo Administrativo';
            $nombre_depto_base = 'Administración'; 
            if ($row_perfil = $res_perfil->fetch_assoc()) {
                $perfil_sistema = $row_perfil['perfil_sistema'];
                if (!empty($row_perfil['nombre_depto_base'])) {
                    $nombre_depto_base = $row_perfil['nombre_depto_base'];
                }
            }

            // 2. REVISAR SI TIENE UN JEFE TÉCNICO ASIGNADO EN LA TABLA PUENTE
            $query_jefe_tecnico = "SELECT id_departamento FROM expediente_jefes_tecnicos WHERE id_usuario_empleado = ? LIMIT 1";
            $stmt_jefe = $conn->prepare($query_jefe_tecnico);
            $stmt_jefe->bind_param("i", $noEmpleado);
            $stmt_jefe->execute();
            $res_jefe = $stmt_jefe->get_result();
            
            $tiene_jefe_tecnico = ($res_jefe->num_rows > 0);
            $id_depto_extra = 0;
            if ($row_jefe = $res_jefe->fetch_assoc()) {
                $id_depto_extra = intval($row_jefe['id_departamento']);
            }

            // 3. SELECCIONAR VISIBILIDAD DEL CATÁLOGO
            if ($perfil_sistema === 'Solo Técnico' || $tiene_jefe_tecnico) {
                $condicion_perfil = "td.perfil_puesto IN ('Todos', 'Solo Técnico')";
            } else {
                $condicion_perfil = "td.perfil_puesto IN ('Todos', 'Solo Administrativo')";
            }

            // 4. QUERY MAESTRA REVISADA
            $query = "SELECT 
                        td.id AS id_tipo_documento,
                        td.nombre_tipo,
                        td.tipo_alcance,
                        td.subido_por,
                        td.categoria_funcion, 
                        td.requiere_rrhh,
                        td.requiere_jefe_tecnico,
                        td.requiere_calidad,
                        td.requiere_jefe_admin,
                        COALESCE(ed.estatus_general, 'Pendiente de Subir') AS estatus_general,
                        ed.archivo_url,
                        IFNULL(
                            dep_doc.departamento, 
                            IF(td.categoria_funcion = 'Técnico', IFNULL(dep_jefe.departamento, 'Área Técnico'), ?)
                        ) AS area_especifica,
                        IFNULL(ed.val_jefe_admin, 0) AS val_jefe_admin,
                        IFNULL(ed.val_jefe_tecnico, 0) AS val_jefe_tecnico,
                        IFNULL(ed.val_calidad, 0) AS val_calidad,
                        IFNULL(ed.val_rrhh, 0) AS val_rrhh
                      FROM expediente_tipos_documentos td
                      LEFT JOIN expediente_documentos ed 
                        ON td.id = ed.id_tipo_documento AND ed.noEmpleado = ?
                      LEFT JOIN departamento dep_doc 
                        ON ed.id_departamento_alcance = dep_doc.id
                      LEFT JOIN departamento dep_jefe 
                        ON dep_jefe.id = ?
                      WHERE $condicion_perfil
                      ORDER BY td.id ASC";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("sii", $nombre_depto_base, $noEmpleado, $id_depto_extra);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error SQL: ' . $e->getMessage()]);
        }
        break;



        case 'guardar_documento_empleado':
        // Asegurar que no haya basura, espacios o errores previos en el búfer de salida
        if (ob_get_length()) ob_clean();

        $noEmpleado_post = isset($_POST['noEmpleado']) ? intval($_POST['noEmpleado']) : 0;
        $id_tipo_documento = isset($_POST['id_tipo_documento']) ? intval($_POST['id_tipo_documento']) : 0;

        if ($noEmpleado_post === 0 || $id_tipo_documento === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Parámetros obligatorios ausentes en la petición.']);
            exit;
        }

        if (!isset($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'No se recibió el archivo binario PDF o la carga fue interrumpida.']);
            exit;
        }

        $file = $_FILES['archivo_pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            echo json_encode(['status' => 'error', 'message' => 'El formato del archivo debe ser estrictamente extensión .PDF']);
            exit;
        }

        try {
            // Definición e inicialización segura del directorio físico
            $dir_destino = 'uploads/expedientes/' . $noEmpleado_post . '/';
            if (!is_dir($dir_destino)) {
                if (!mkdir($dir_destino, 0755, true)) {
                    throw new Exception("No se tienen permisos para crear la carpeta del empleado en el servidor.");
                }
            }

            // Nombre limpio en formato Unix Timestamp para evitar colisiones
            $nombre_limpio = time() . '_' . preg_replace('/[^A-Za-z0-9\-._]/', '', $file['name']);
            $ruta_final = $dir_destino . $nombre_limpio;

            // Intentar mover el archivo físico
            if (!move_uploaded_file($file['tmp_name'], $ruta_final)) {
                throw new Exception("Fallo al mover el archivo temporal al repositorio físico de la empresa.");
            }

            // A) Buscar si cuenta con jefatura técnica extra para asociar el departamento correspondiente
            $depto_check = $conn->prepare("SELECT id_departamento FROM expediente_jefes_tecnicos WHERE id_usuario_empleado = ? LIMIT 1");
            if (!$depto_check) {
                throw new Exception("Error al preparar consulta de jefes técnicos: " . $conn->error);
            }
            $depto_check->bind_param("i", $noEmpleado_post);
            $depto_check->execute();
            $res_depto = $depto_check->get_result();
            
            $id_departamento_final = null;
            if ($r_depto = $res_depto->fetch_assoc()) {
                $id_departamento_final = intval($r_depto['id_departamento']);
            }
            $depto_check->close();

            // B) Si no tiene jefatura extra (es administrativo), buscamos el id del campo 'departamento' en usuarios
            if ($id_departamento_final === null || $id_departamento_final === 0) {
                $base_check = $conn->prepare("SELECT departamento FROM usuarios WHERE noEmpleado = ? LIMIT 1");
                if (!$base_check) {
                    throw new Exception("Error al preparar consulta de departamento base: " . $conn->error);
                }
                $base_check->bind_param("i", $noEmpleado_post);
                $base_check->execute();
                $res_base = $base_check->get_result();
                if ($r_base = $res_base->fetch_assoc()) {
                    // Si el campo viene vacío, le asignamos nulo para evitar violaciones de FK
                    $id_departamento_final = !empty($r_base['departamento']) ? intval($r_base['departamento']) : null;
                }
                $base_check->close();
            }

            // C) Verificar si ya existía un registro previo para decidir si es INSERT o UPDATE
            $check = $conn->prepare("SELECT id FROM expediente_documentos WHERE noEmpleado = ? AND id_tipo_documento = ?");
            if (!$check) {
                throw new Exception("Error al preparar verificación de documento: " . $conn->error);
            }
            $check->bind_param("ii", $noEmpleado_post, $id_tipo_documento);
            $check->execute();
            $res_check = $check->get_result();
            $existe_registro = ($res_check->num_rows > 0);
            $check->close();

            $estatus_inicial = 'En Revisión';

            if ($existe_registro) {
                // UPDATE: Forzamos la reiniciación de todas las firmas operativas a 0
                $update = $conn->prepare("UPDATE expediente_documentos 
                                          SET archivo_url = ?, id_departamento_alcance = ?, estatus_general = ?,
                                              val_jefe_admin = 0, val_jefe_tecnico = 0, val_calidad = 0, val_rrhh = 0, fecha_registro = NOW()
                                          WHERE noEmpleado = ? AND id_tipo_documento = ?");
                if (!$update) {
                    throw new Exception("Error al preparar actualización: " . $conn->error);
                }
                // Ligamos los parámetros correspondientes (s = string, i = integer)
                $update->bind_param("siiii", $ruta_final, $id_departamento_final, $estatus_inicial, $noEmpleado_post, $id_tipo_documento);
                $update->execute();
                $update->close();
            } else {
                // INSERT: Pasamos todas las columnas explícitas para evitar fallos por configuraciones del motor SQL
                $insert = $conn->prepare("INSERT INTO expediente_documentos 
                                          (noEmpleado, id_tipo_documento, id_departamento_alcance, archivo_url, estatus_general, val_jefe_admin, val_jefe_tecnico, val_calidad, val_rrhh, fecha_registro) 
                                          VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0, NOW())");
                if (!$insert) {
                    throw new Exception("Error al preparar inserción: " . $conn->error);
                }
                $insert->bind_param("iiiss", $noEmpleado_post, $id_tipo_documento, $id_departamento_final, $ruta_final, $estatus_inicial);
                $insert->execute();
                $insert->close();
            }

            // Si todo salió bien, respondemos con éxito absoluto
            echo json_encode(['status' => 'success', 'message' => '¡Documento cargado con éxito y enviado a la cola de validación!']);

        } catch (Exception $e) {
            // En caso de cualquier error interno de SQL, devolvemos un JSON controlado en lugar de un error fatal roto
            echo json_encode(['status' => 'error', 'message' => 'Error operativo en el servidor: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción inválida.']);
        break;
}