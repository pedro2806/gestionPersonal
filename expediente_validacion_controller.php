<?php
// expediente_validacion_controller.php - Controlador Exclusivo para Jefaturas, Calidad y RRHH (MESS)
header('Content-Type: application/json; charset=utf-8');
require_once 'conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Método no autorizado.']);
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';

switch ($action) {

    case 'obtener_catalogos_auxiliares':
        try {
            // 1. Obtener laboratorios / departamentos activos
            $query_deptos = "SELECT id, departamento FROM departamento ORDER BY departamento ASC";
            $res_deptos = $conn->query($query_deptos);
            $departamentos = [];
            while ($r = $res_deptos->fetch_assoc()) { $departamentos[] = $r; }

            // 2. Obtener ingenieros / jefes asignados en la tabla de control
            $query_jefes = "SELECT DISTINCT u.noEmpleado, u.nombre 
                            FROM usuarios u 
                            INNER JOIN expediente_jefes_tecnicos jt ON u.noEmpleado = jt.id_usuario_jefe
                            ORDER BY u.nombre ASC";
            $res_jefes = $conn->query($query_jefes);
            $jefes = [];
            while ($r = $res_jefes->fetch_assoc()) { $jefes[] = $r; }

            echo json_encode([
                'status' => 'success',
                'departamentos' => $departamentos,
                'jefes' => $jefes
            ]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'listar_personal_auditoria':
        $id_departamento = isset($_POST['id_departamento']) ? intval($_POST['id_departamento']) : 0;
        $no_jefe_tecnico = isset($_POST['no_jefe_tecnico']) ? intval($_POST['no_jefe_tecnico']) : 0;

        // Recuperamos quién es el auditor en sesión (enviado desde la cookie en el front)
        $no_auditor_sesion = isset($_COOKIE['noEmpleadoGP']) ? intval($_COOKIE['noEmpleadoGP']) : 0;

        try {
            // 1. IDENTIFICAR ROL DEL AUDITOR EN SESIÓN
            $q_rol = "SELECT 
                        CASE 
                            WHEN u.noEmpleado = 403 THEN 'RRHH' -- Tu regla específica de RRHH
                            WHEN p.puesto LIKE '%Calidad%' THEN 'Calidad'
                            WHEN p.puesto LIKE '%Jefe Técnico%' THEN 'Jefe Técnico'
                            ELSE 'Jefe Administrativo'
                        END AS rol_auditor,
                        u.departamento AS depto_auditor
                      FROM usuarios u
                      INNER JOIN puesto p ON u.puesto = p.id
                      WHERE u.noEmpleado = ? LIMIT 1";
            
            $stmt_rol = $conn->prepare($q_rol);
            $stmt_rol->bind_param("i", $no_auditor_sesion);
            $stmt_rol->execute();
            $res_rol = $stmt_rol->get_result()->fetch_assoc();
            
            $rol_auditor = $res_rol['rol_auditor'] ?? 'Jefe Administrativo';
            $depto_auditor = $res_rol['depto_auditor'] ?? 0;
            $stmt_rol->close();

            // 2. CONSTRUCCIÓN DE FILTROS DE PRIVACIDAD DE ACUERDO AL ROL
            $condiciones = ["1=1"];
            $params = [];
            $types = "";

            // REGLA DE PRIVACIDAD AUTOMÁTICA: Si NO es RRHH y tampoco es Calidad, limitamos su universo
            if ($rol_auditor !== 'RRHH' && $rol_auditor !== 'Calidad') {
                if ($rol_auditor === 'Jefe Técnico') {
                    // El Jefe Técnico solo ve a los empleados asignados a él en la tabla puente
                    $condiciones[] = "u.noEmpleado IN (SELECT id_usuario_empleado FROM expediente_jefes_tecnicos WHERE id_usuario_jefe = ?)";
                    $params[] = $no_auditor_sesion;
                    $types .= "i";
                } else {
                    // El Jefe Administrativo solo ve al personal de su propio departamento base
                    $condiciones[] = "u.departamento = ?";
                    $params[] = $depto_auditor;
                    $types .= "i";
                }
            }

            // 3. ACOPLAR FILTROS MANUALES DE LOS COMBOS SUPERIERES (Solo si el rol tiene permiso de verlos)
            // Si un jefe técnico intenta filtrar un departamento que no es suyo, las condiciones anteriores lo bloquean
            if ($id_departamento > 0) {
                $condiciones[] = "u.departamento = ?";
                $params[] = $id_departamento;
                $types .= "i";
            }

            if ($no_jefe_tecnico > 0) {
                $condiciones[] = "u.noEmpleado IN (SELECT id_usuario_empleado FROM expediente_jefes_tecnicos WHERE id_usuario_jefe = ?)";
                $params[] = $no_jefe_tecnico;
                $types .= "i";
            }

            $where_clause = implode(" AND ", $condiciones);

            // 4. QUERY MIGRADO CON FILTRO DE PRIVACIDAD INTEGRADO
            $query = "SELECT 
                        u.noEmpleado,
                        u.nombre,
                        p.puesto AS puesto_nombre,
                        d.departamento AS departamento_nombre,
                        ROUND(
                            (COUNT(CASE WHEN ed.estatus_general = 'Aprobado' THEN 1 END) * 100.0) / 
                            NULLIF(COUNT(td.id), 0)
                        , 0) AS avance
                      FROM usuarios u
                      INNER JOIN puesto p ON u.puesto = p.id
                      LEFT JOIN departamento d ON u.departamento = d.id
                      CROSS JOIN expediente_tipos_documentos td
                      LEFT JOIN expediente_jefes_tecnicos ejt ON u.noEmpleado = ejt.id_usuario_empleado
                      LEFT JOIN expediente_documentos ed ON td.id = ed.id_tipo_documento AND ed.noEmpleado = u.noEmpleado
                      WHERE $where_clause 
                        AND (
                            td.perfil_puesto = 'Todos'
                            OR (td.perfil_puesto = 'Solo Técnico' AND (p.puesto LIKE '%Metrólogo%' OR p.puesto LIKE '%Signatario%' OR p.puesto LIKE '%Jefe Técnico%' OR ejt.id IS NOT NULL))
                            OR (td.perfil_puesto = 'Solo Administrativo' AND NOT (p.puesto LIKE '%Metrólogo%' OR p.puesto LIKE '%Signatario%' OR p.puesto LIKE '%Jefe Técnico%' OR ejt.id IS NOT NULL))
                        )
                      GROUP BY u.noEmpleado
                      ORDER BY u.nombre ASC";

            $stmt = $conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $row['avance'] = $row['avance'] !== null ? intval($row['avance']) : 0;
                $data[] = $row;
            }

            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Fallo en consulta de privacidad: ' . $e->getMessage()]);
        }
        break;

    case 'obtener_detalle_expediente_colaborador':
        $noEmpleado = isset($_POST['noEmpleado']) ? intval($_POST['noEmpleado']) : 0;

        if ($noEmpleado === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Número de empleado ausente.']);
            exit;
        }

        try {
            // Identificar perfil base e inyectar el nombre real de su departamento
            $query_base = "SELECT u.departamento, d.departamento AS nombre_depto, p.puesto 
                           FROM usuarios u 
                           INNER JOIN puesto p ON u.puesto = p.id
                           LEFT JOIN departamento d ON u.departamento = d.id 
                           WHERE u.noEmpleado = ? LIMIT 1";
            $stmt_b = $conn->prepare($query_base);
            $stmt_b->bind_param("i", $noEmpleado);
            $stmt_b->execute();
            $res_base = $stmt_b->get_result()->fetch_assoc();

            $puesto = $res_base['puesto'] ?? '';
            $nombre_depto_base = $res_base['nombre_depto'] ?? 'Administración';

            // Revisar si cuenta con laboratorios extras
            $jefe_check = $conn->prepare("SELECT id_departamento FROM expediente_jefes_tecnicos WHERE id_usuario_empleado = ? LIMIT 1");
            $jefe_check->bind_param("i", $noEmpleado);
            $jefe_check->execute();
            $res_jefe = $jefe_check->get_result();
            $tiene_jefe_tecnico = ($res_jefe->num_rows > 0);
            $id_depto_extra = $tiene_jefe_tecnico ? intval($res_jefe->fetch_assoc()['id_departamento']) : 0;

            if (strpos($puesto, 'Metrólogo') !== false || strpos($puesto, 'Signatario') !== false || strpos($puesto, 'Jefe Técnico') !== false || $tiene_jefe_tecnico) {
                $condicion_perfil = "td.perfil_puesto IN ('Todos', 'Solo Técnico')";
            } else {
                $condicion_perfil = "td.perfil_puesto IN ('Todos', 'Solo Administrativo')";
            }

            // Desplegar la matriz completa cruzando el catálogo con lo que se ha subido físicamente
            $query = "SELECT 
                        IFNULL(ed.id, 0) AS id_documento,
                        td.id AS id_tipo_documento,
                        td.nombre_tipo,
                        td.tipo_alcance,
                        td.categoria_funcion,
                        td.subido_por,
                        td.requiere_rrhh,
                        td.requiere_jefe_tecnico,
                        td.requiere_calidad,
                        td.requiere_jefe_admin,
                        COALESCE(ed.estatus_general, 'Pendiente de Subir') AS estatus_general,
                        ed.archivo_url,
                        IFNULL(dep_doc.departamento, IF(td.categoria_funcion = 'Técnico', IFNULL(dep_jefe.departamento, 'Área Técnica'), ?)) AS area_especifica,
                        IFNULL(ed.val_jefe_admin, 0) AS val_jefe_admin,
                        IFNULL(ed.val_jefe_tecnico, 0) AS val_jefe_tecnico,
                        IFNULL(ed.val_calidad, 0) AS val_calidad,
                        IFNULL(ed.val_rrhh, 0) AS val_rrhh
                      FROM expediente_tipos_documentos td
                      LEFT JOIN expediente_documentos ed ON td.id = ed.id_tipo_documento AND ed.noEmpleado = ?
                      LEFT JOIN departamento dep_doc ON ed.id_departamento_alcance = dep_doc.id
                      LEFT JOIN departamento dep_jefe ON dep_jefe.id = ?
                      WHERE $condicion_perfil
                      ORDER BY td.id ASC";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("sii", $nombre_depto_base, $noEmpleado, $id_depto_extra);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) { $data[] = $row; }

            echo json_encode(['status' => 'success', 'data' => $data]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        break;

    case 'procesar_firma_documento':
        if (ob_get_length()) ob_clean();

        $id_documento = isset($_POST['id_documento']) ? intval($_POST['id_documento']) : 0;
        $dictamen = isset($_POST['dictamen']) ? trim($_POST['dictamen']) : '';
        $comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';
        $no_auditor = isset($_POST['no_auditor']) ? intval($_POST['no_auditor']) : 0;

        if ($id_documento === 0 || empty($dictamen) || $no_auditor === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Parámetros obligatorios de validación incompletos.']);
            exit;
        }

        try {
            // 1. IDENTIFICAR QUÉ ROL TIENE EL AUDITOR LOGUEADO EN EL SISTEMA
            $q_auditor = "SELECT 
                            CASE 
                                WHEN u.noEmpleado = 403 THEN 'RRHH' -- Asignación directa por tu regla o puesto
                                WHEN p.puesto LIKE '%Jefe Técnico%' THEN 'Jefe Técnico'
                                WHEN p.puesto LIKE '%Calidad%' THEN 'Calidad'
                                ELSE 'Jefe Administrativo'
                            END AS rol_auditor
                          FROM usuarios u
                          INNER JOIN puesto p ON u.puesto = p.id
                          WHERE u.noEmpleado = ? LIMIT 1";
            
            $stmt_a = $conn->prepare($q_auditor);
            $stmt_a->bind_param("i", $no_auditor);
            $stmt_a->execute();
            $rol_auditor = $stmt_a->get_result()->fetch_assoc()['rol_auditor'] ?? 'Jefe Administrativo';

            // 2. DETERMINAR EL VALOR DEL VOTO (3 = Aprobado, 4 = Rechazado)
            $voto_valor = ($dictamen === 'Aprobar') ? 3 : 4;
            $nuevo_estatus_general = ($dictamen === 'Aprobar') ? 'En Revisión' : 'Rechazado';

            // 3. MAPEAR A QUÉ COLUMNA IMPACTA SU ROL
            $columna_firma = 'val_jefe_admin';
            if ($rol_auditor === 'Jefe Técnico') $columna_firma = 'val_jefe_tecnico';
            if ($rol_auditor === 'Calidad') $columna_firma = 'val_calidad';
            if ($rol_auditor === 'RRHH') $columna_firma = 'val_rrhh';

            // Si es RECHAZO, limpiamos todas las firmas previas para obligar a re-subida limpia
            if ($dictamen === 'Rechazar') {
                $query_update = "UPDATE expediente_documentos 
                                 SET $columna_firma = ?, estatus_general = ?, 
                                     val_jefe_admin = IF('$columna_firma'='val_jefe_admin', 4, 0),
                                     val_jefe_tecnico = IF('$columna_firma'='val_jefe_tecnico', 4, 0),
                                     val_calidad = IF('$columna_firma'='val_calidad', 4, 0),
                                     val_rrhh = IF('$columna_firma'='val_rrhh', 4, 0),
                                     comentarios = ?, fecha_registro = NOW() 
                                 WHERE id = ?";
                $stmt_u = $conn->prepare($query_update);
                $stmt_u->bind_param("issi", $voto_valor, $nuevo_estatus_general, $comentario, $id_documento);
            } else {
                // Si es APROBACIÓN, asentamos solo su columna
                $query_update = "UPDATE expediente_documentos SET $columna_firma = ?, fecha_registro = NOW() WHERE id = ?";
                $stmt_u = $conn->prepare($query_update);
                $stmt_u->bind_param("ii", $voto_valor, $id_documento);
            }
            
            $stmt_u->execute();
            $stmt_u->close();

            // 4. COMPUERTA INTELIGENTE AUTOMÁTICA: Si fue aprobación, revisamos si el documento ya completó 
            // todas las firmas que exigía su configuración para promoverlo a 'Aprobado' de forma definitiva
            if ($dictamen === 'Aprobar') {
                $query_check = "SELECT 
                                    ed.id,
                                    td.requiere_rrhh, td.requiere_jefe_tecnico, td.requiere_calidad, td.requiere_jefe_admin,
                                    ed.val_rrhh, ed.val_jefe_tecnico, ed.val_calidad, ed.val_jefe_admin
                                FROM expediente_documentos ed
                                INNER JOIN expediente_tipos_documentos td ON ed.id_tipo_documento = td.id
                                WHERE ed.id = ? LIMIT 1";
                
                $stmt_c = $conn->prepare($query_check);
                $stmt_c->bind_param("i", $id_documento);
                $stmt_c->execute();
                $doc_data = $stmt_c->get_result()->fetch_assoc();
                $stmt_c->close();

                $completado = true;
                if (intval($doc_data['requiere_rrhh']) === 1 && intval($doc_data['val_rrhh']) !== 3) $completado = false;
                if (intval($doc_data['requiere_jefe_tecnico']) === 1 && intval($doc_data['val_jefe_tecnico']) !== 3) $completado = false;
                if (intval($doc_data['requiere_calidad']) === 1 && intval($doc_data['val_calidad']) !== 3) $completado = false;
                if (intval($doc_data['requiere_jefe_admin']) === 1 && intval($doc_data['val_jefe_admin']) !== 3) $completado = false;

                if ($completado) {
                    $stmt_f = $conn->prepare("UPDATE expediente_documentos SET estatus_general = 'Aprobado' WHERE id = ?");
                    $stmt_f->bind_param("i", $id_documento);
                    $stmt_f->execute();
                    $stmt_f->close();
                    $message = "¡Firma asentada! El documento ha cumplido con todas las compuertas y quedó 'Aprobado' de forma definitiva.";
                } else {
                    $stmt_f = $conn->prepare("UPDATE expediente_documentos SET estatus_general = 'En Revisión' WHERE id = ?");
                    $stmt_f->bind_param("i", $id_documento);
                    $stmt_f->execute();
                    $stmt_f->close();
                    $message = "Dictamen de aprobación guardado. El documento permanece en revisión hasta recolectar las firmas restantes.";
                }
            } else {
                $message = "El documento ha sido rechazado y devuelto al empleado con las observaciones guardadas.";
            }

            echo json_encode(['status' => 'success', 'message' => $message]);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error SQL: ' . $e->getMessage()]);
        }
        break;

    case 'jefe_guardar_documento_subordinado':
        if (ob_get_length()) ob_clean();

        // Recuperamos el número de empleado al que se le asignará el documento
        $noEmpleado_destino = isset($_POST['noEmpleado_destino']) ? intval($_POST['noEmpleado_destino']) : 0;
        $id_tipo_documento = isset($_POST['id_tipo_documento']) ? intval($_POST['id_tipo_documento']) : 0;

        if ($noEmpleado_destino === 0 || $id_tipo_documento === 0) {
            echo json_encode(['status' => 'error', 'message' => 'Parámetros obligatorios ausentes en la carga corporativa.']);
            exit;
        }

        if (!isset($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'No se recibió el documento PDF en el servidor.']);
            exit;
        }

        $file = $_FILES['archivo_pdf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            echo json_encode(['status' => 'error', 'message' => 'El archivo debe poseer strictly una extensión válida .PDF']);
            exit;
        }

        try {
            // Estructura física estricta indexada por el NoEmpleado destino
            $dir_destino = 'uploads/expedientes/' . $noEmpleado_destino . '/';
            if (!is_dir($dir_destino)) {
                mkdir($dir_destino, 0755, true);
            }

            $nombre_limpio = time() . '_INSTITUCIONAL_' . preg_replace('/[^A-Za-z0-9\-._]/', '', $file['name']);
            $ruta_final = $dir_destino . $nombre_limpio;

            if (move_uploaded_file($file['tmp_name'], $ruta_final)) {
                
                // Obtener el departamento base o técnico asignado para id_departamento_alcance
                $depto_check = $conn->prepare("SELECT id_departamento FROM expediente_jefes_tecnicos WHERE id_usuario_empleado = ? LIMIT 1");
                $depto_check->bind_param("i", $noEmpleado_destino);
                $depto_check->execute();
                $res_depto = $depto_check->get_result();
                $id_depto_alcance = null;
                if ($r_depto = $res_depto->fetch_assoc()) {
                    $id_depto_alcance = intval($r_depto['id_departamento']);
                }

                if ($id_depto_alcance === null) {
                    $base_check = $conn->prepare("SELECT departamento FROM usuarios WHERE noEmpleado = ? LIMIT 1");
                    $base_check->bind_param("i", $noEmpleado_destino);
                    $base_check->execute();
                    if ($r_base = $base_check->get_result()->fetch_assoc()) {
                        $id_depto_alcance = !empty($r_base['departamento']) ? intval($r_base['departamento']) : null;
                    }
                }

                // Verificar si ya existe registro previo
                $check = $conn->prepare("SELECT id FROM expediente_documentos WHERE noEmpleado = ? AND id_tipo_documento = ?");
                $check->bind_param("ii", $noEmpleado_destino, $id_tipo_documento);
                $check->execute();
                $existe = ($check->get_result()->num_rows > 0);

                // Estatus inicial al ser inyectado por jefatura: entra en cola de revisión colectiva
                $estatus_inicial = 'En Revisión';

                if ($existe) {
                    $update = $conn->prepare("UPDATE expediente_documentos 
                                              SET archivo_url = ?, id_departamento_alcance = ?, estatus_general = ?,
                                                  val_jefe_admin = 0, val_jefe_tecnico = 0, val_calidad = 0, val_rrhh = 0, fecha_registro = NOW() 
                                              WHERE noEmpleado = ? AND id_tipo_documento = ?");
                    $update->bind_param("siiii", $ruta_final, $id_depto_alcance, $estatus_inicial, $noEmpleado_destino, $id_tipo_documento);
                    $update->execute();
                } else {
                    $insert = $conn->prepare("INSERT INTO expediente_documentos 
                                              (noEmpleado, id_tipo_documento, id_departamento_alcance, archivo_url, estatus_general, val_jefe_admin, val_jefe_tecnico, val_calidad, val_rrhh, fecha_registro) 
                                              VALUES (?, ?, ?, ?, ?, 0, 0, 0, 0, NOW())");
                    $insert->bind_param("iiiss", $noEmpleado_destino, $id_tipo_documento, $id_depto_alcance, $ruta_final, $estatus_inicial);
                    $insert->execute();
                }

                echo json_encode(['status' => 'success', 'message' => '¡Documento institucional anexado al expediente de forma exitosa!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No se pudo mover el archivo al repositorio físico de la empresa.']);
            }
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'Error operativo en BD: ' . $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Acción interna no definida en validaciones.']);
        break;
}