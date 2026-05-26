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
            // AJUSTE: Forzamos a entero puro para noEmpleado
            $id_usuario = intval($_POST['id_usuario']);
            
            $q_base = "SELECT u.departamento AS id_depto_base, d.departamento AS nombre_depto_base FROM usuarios u INNER JOIN departamento d ON u.departamento = d.id WHERE u.noEmpleado = $id_usuario";
            $res_base = mysqli_query($conn, $q_base);
            $depto_base = mysqli_fetch_assoc($res_base);

            $q_adicionales = "SELECT ejt.id_departamento, d.departamento as nombre_departamento FROM expediente_jefes_tecnicos ejt INNER JOIN departamento d ON ejt.id_departamento = d.id WHERE ejt.id_usuario_empleado = $id_usuario GROUP BY ejt.id_departamento";
            $res_adicionales = mysqli_query($conn, $q_adicionales);
            $deptos_adicionales = [];
            while($row = mysqli_fetch_assoc($res_adicionales)) { $deptos_adicionales[] = $row; }
            
            $response = ['status' => 'success', 'base' => $depto_base, 'adicionales' => $deptos_adicionales];
            break;

        case 'obtener_expediente':
            // AJUSTE: Forzamos a entero puro para noEmpleado
            $id_usuario = intval($_POST['id_usuario']);

            // 1. Obtener el Departamento Base del usuario
            $q_u = "SELECT departamento FROM usuarios WHERE noEmpleado = $id_usuario";
            $r_u = mysqli_query($conn, $q_u);
            $user_info = mysqli_fetch_assoc($r_u);
            $depto_base = isset($user_info['departamento']) ? intval($user_info['departamento']) : 0;

            // 2. Obtener todos los alcances adicionales que tiene por Jefes Técnicos
            $alcances_tecnicos = [];
            $q_alc = "SELECT id_departamento FROM expediente_jefes_tecnicos WHERE id_usuario_empleado = $id_usuario GROUP BY id_departamento";
            $res_alc = mysqli_query($conn, $q_alc);
            while($row = mysqli_fetch_assoc($res_alc)) {
                $alcances_tecnicos[] = intval($row['id_departamento']);
            }

            // 3. Traer todo el catálogo de documentos obligatorios
            $q_cat = "SELECT * FROM expediente_tipos_documentos ORDER BY id ASC";
            $res_cat = mysqli_query($conn, $q_cat);
            
            $matriz_obligatoria = [];

            while($doc = mysqli_fetch_assoc($res_cat)) {
                if ($doc['tipo_alcance'] === 'Por Puesto') {
                    $matriz_obligatoria[] = [
                        'id_tipo' => $doc['id'],
                        'nombre_tipo' => $doc['nombre_tipo'],
                        'subido_por' => $doc['subido_por'],
                        'tipo_alcance' => 'Por Puesto',
                        'id_depto' => null,
                        'nombre_depto' => 'Universal'
                    ];
                } else {
                    // Cargar de cajón el depto base si existe
                    if ($depto_base > 0) {
                        $q_d1 = "SELECT departamento FROM departamento WHERE id = $depto_base";
                        $r_d1 = mysqli_fetch_assoc(mysqli_query($conn, $q_d1));
                        $matriz_obligatoria[] = [
                            'id_tipo' => $doc['id'],
                            'nombre_tipo' => $doc['nombre_tipo'],
                            'subido_por' => $doc['subido_por'],
                            'tipo_alcance' => 'Por Alcance',
                            'id_depto' => $depto_base,
                            'nombre_depto' => $r_d1['departamento'] ?? 'Área Base'
                        ];
                    }

                    // Replicar por cada área técnica extendida
                    foreach ($alcances_tecnicos as $id_depto_tecnico) {
                        if ($id_depto_tecnico === $depto_base) continue; 
                        
                        $q_d2 = "SELECT departamento FROM departamento WHERE id = $id_depto_tecnico";
                        $r_d2 = mysqli_fetch_assoc(mysqli_query($conn, $q_d2));
                        
                        $matriz_obligatoria[] = [
                            'id_tipo' => $doc['id'],
                            'nombre_tipo' => $doc['nombre_tipo'],
                            'subido_por' => $doc['subido_por'],
                            'tipo_alcance' => 'Por Alcance',
                            'id_depto' => $id_depto_tecnico,
                            'nombre_depto' => $r_d2['departamento'] ?? 'Área Técnica'
                        ];
                    }
                }
            }

            // 4. Buscar qué documentos ya han sido subidos (id_usuario es numérico puro)
            $documentos_subidos = [];
            $q_sub = "SELECT * FROM expediente_documentos WHERE id_usuario = $id_usuario";
            $res_sub = mysqli_query($conn, $q_sub);
            while($sub = mysqli_fetch_assoc($res_sub)) {
                $llave = $sub['id_tipo_documento'] . '_' . ($sub['id_departamento_alcance'] ?? 'NULL');
                $documentos_subidos[$llave] = $sub;
            }

            // 5. Unificar el mapa obligatorio con las subidas reales
            $reticula_final = [];
            foreach ($matriz_obligatoria as $requisito) {
                $llave_busca = $requisito['id_tipo'] . '_' . ($requisito['id_depto'] ?? 'NULL');
                
                if (isset($documentos_subidos[$llave_busca])) {
                    $file = $documentos_subidos[$llave_busca];
                    $requisito['subido'] = true;
                    $requisito['id_documento_real'] = $file['id'];
                    $requisito['archivo_url'] = $file['archivo_url'];
                    $requisito['val_jefe_admin'] = $file['val_jefe_admin'];
                    $requisito['val_jefe_tecnico'] = $file['val_jefe_tecnico'];
                    $requisito['val_calidad'] = $file['val_calidad'];
                    $requisito['val_rrhh'] = $file['val_rrhh'];
                    $requisito['estatus_general'] = $file['estatus_general'];
                } else {
                    $requisito['subido'] = false;
                    $requisito['estatus_general'] = 'Pendiente';
                }
                $reticula_final[] = $requisito;
            }

            echo json_encode(['status' => 'success', 'data' => $reticula_final]);
            exit;

        case 'subir_documento_expediente':
            // AJUSTE: El destino es un entero para noEmpleado
            $id_usuario_destino = intval($_POST['id_usuario_destino']);
            $id_tipo_doc = intval($_POST['id_tipo_documento']);
            $id_usuario_sesion = intval($_POST['id_usuario_sesion']);
            
            if (isset($_POST['id_departamento_alcance']) && $_POST['id_departamento_alcance'] !== '') {
                $id_depto_sql = intval($_POST['id_departamento_alcance']);
            } else {
                $id_depto_sql = "NULL"; 
            }
            
            $q_tipo = "SELECT * FROM expediente_tipos_documentos WHERE id = $id_tipo_doc";
            $res_tipo = mysqli_query($conn, $q_tipo);
            $tipo = mysqli_fetch_assoc($res_tipo);
            
            if (!$tipo) {
                $response = ['status' => 'error', 'message' => 'Tipo de documento inválido.'];
                break;
            }

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
                    // AJUSTE: Al remover las comillas simples de $id_usuario_destino, SQL lo lee como entero plano de forma óptima
                    $query = "INSERT INTO expediente_documentos (id_usuario, id_tipo_documento, id_departamento_alcance, archivo_url, val_jefe_admin, val_jefe_tecnico, val_calidad, val_rrhh, estatus_general) 
                            VALUES ($id_usuario_destino, $id_tipo_doc, $id_depto_sql, '$target_file', $v_admin, $v_tecnico, $v_calidad, $v_rrhh, 'En Revisión')";
                    
                    if (mysqli_query($conn, $query)) {
                        $response = ['status' => 'success', 'message' => 'Documento cargado con éxito en el expediente.'];
                    } else {
                        $response = ['status' => 'error', 'message' => 'Error en Query: ' . mysqli_error($conn)];
                    }
                } else {
                    $response = ['status' => 'error', 'message' => 'Error al mover el archivo físico al servidor.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'Archivo adjunto corrupto o ausente.'];
            }
            break;

        case 'listar_administracion_empleados':
            $query = "SELECT u.id, u.noEmpleado, u.nombre as nombreCompleto, d.departamento as depto_base,
                        (SELECT COUNT(*) FROM expediente_documentos WHERE id_usuario = u.noEmpleado) as total_docs,
                        (SELECT GROUP_CONCAT(CONCAT(jefes.nombre, ' (', depto_alcance.departamento, ')') SEPARATOR ', ') 
                            FROM expediente_jefes_tecnicos ejt
                            INNER JOIN usuarios jefes ON ejt.id_usuario_jefe_tecnico = jefes.id
                            INNER JOIN departamento depto_alcance ON ejt.id_departamento = depto_alcance.id
                        WHERE ejt.id_usuario_empleado = u.noEmpleado) as jefes_tecnicos,
                        (SELECT GROUP_CONCAT(jefes.noEmpleado SEPARATOR ',') 
                            FROM expediente_jefes_tecnicos ejt
                            INNER JOIN usuarios jefes ON ejt.id_usuario_jefe_tecnico = jefes.id
                            INNER JOIN departamento depto_alcance ON ejt.id_departamento = depto_alcance.id
                        WHERE ejt.id_usuario_empleado = u.noEmpleado) as id_jefes_tecnicos,
                        p.puesto,
                        ja.nombre as jefe_administrativo, ja.noEmpleado as id_jefe_directo, u.estatus, u.foto as url_foto,  
                        (SELECT GROUP_CONCAT(CONCAT(telefono, ' Ext. ', IFNULL(extension, 'N/A')) SEPARATOR ', ') FROM telefono WHERE noEmpleado = u.noEmpleado) AS telefonos
                    FROM usuarios u
                    LEFT JOIN departamento d ON u.departamento = d.id
                    LEFT JOIN puesto p ON u.puesto = p.id
                    LEFT JOIN usuarios ja ON u.jefe = ja.noEmpleado
                    ORDER BY u.nombre ASC";
            $result = mysqli_query($conn, $query);
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'obtener_catalogos_auxiliares':
            $q_jefes = "SELECT id, nombre, noEmpleado FROM usuarios WHERE estatus = 1 ORDER BY nombre ASC";
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
            // AJUSTE: Sanitizado como entero puro
            $id_empleado = intval($_POST['id_usuario_empleado']);
            $query = "SELECT id_usuario_jefe_tecnico, id_departamento FROM expediente_jefes_tecnicos WHERE id_usuario_empleado = $id_empleado";
            $result = mysqli_query($conn, $query);
            $data = [];
            while($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'guardar_jefes_tecnicos_empleado':
            // AJUSTE: Sanitizado como entero puro
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
            // AJUSTE: Sanitizado como entero puro
            $id_usuario = intval($_POST['id_usuario']);
            $query = "SELECT u.id, u.noEmpleado, u.nombre AS nombreCompleto, p.puesto, u.estatus, d.departamento,
                        (SELECT j.nombre FROM usuarios j WHERE j.noEmpleado = u.jefe) AS jefe_administrativo,
                        (SELECT GROUP_CONCAT(CONCAT(jt.nombre, ' (', da.departamento, ')') SEPARATOR ', ') 
                            FROM expediente_jefes_tecnicos ejt 
                            INNER JOIN usuarios jt ON ejt.id_usuario_jefe_tecnico = jt.id 
                            INNER JOIN departamento da ON ejt.id_departamento = da.id                            
                            WHERE ejt.id_usuario_empleado = u.noEmpleado) AS jefes_tecnicos
                    FROM usuarios u 
                    LEFT JOIN departamento d ON u.departamento = d.id 
                    LEFT JOIN puesto p ON u.puesto = p.id WHERE u.noEmpleado = $id_usuario";
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
// OPCIONES PARA EDITAR USUARIOS Y ASIGNAR JEFES TÉCNICOS
        // 1. Obtener los datos completos de un usuario para cargar el formulario de edición
        case 'obtener_datos_usuario_edicion':
            $noEmpleado = intval($_POST['noEmpleado']);
            $query = "SELECT noEmpleado, nombre, correo, departamento, puesto, jefe, tipoContrato, sexo, nss, rfc, curp, tipoSangre FROM usuarios WHERE noEmpleado = $noEmpleado";
            $res = mysqli_query($conn, $query);
            if ($res && mysqli_num_rows($res) > 0) {
                $response = ['status' => 'success', 'data' => mysqli_fetch_assoc($res)];
            } else {
                $response = ['status' => 'error', 'message' => 'No se encontró al colaborador.'];
            }
            break;

        // 2. Procesar la actualización/modificación de los datos
        case 'modificar_usuario_sistema':
            $noEmpleado = intval($_POST['mod_noEmpleado']);
            $nombre = mysqli_real_escape_string($conn, $_POST['mod_nombre']);
            $correo = mysqli_real_escape_string($conn, $_POST['mod_correo']);
            $departamento = intval($_POST['mod_departamento']);
            $puesto = intval($_POST['mod_puesto']);
            $jefe = intval($_POST['mod_jefe']);
            $tipoContrato = mysqli_real_escape_string($conn, $_POST['mod_tipoContrato']);
            $sexo = mysqli_real_escape_string($conn, $_POST['mod_sexo']);
            $nss = mysqli_real_escape_string($conn, $_POST['mod_nss']);
            $rfc = mysqli_real_escape_string($conn, $_POST['mod_rfc']);
            $curp = mysqli_real_escape_string($conn, $_POST['mod_curp']);
            $tipoSangre = mysqli_real_escape_string($conn, $_POST['mod_tipoSangre']);

            $query = "UPDATE usuarios SET 
                        nombre = '$nombre', 
                        correo = '$correo', 
                        departamento = $departamento, 
                        puesto = $puesto, 
                        jefe = $jefe, 
                        tipoContrato = '$tipoContrato', 
                        sexo = '$sexo', 
                        nss = '$nss', 
                        rfc = '$rfc', 
                        curp = '$curp', 
                        tipoSangre = '$tipoSangre' 
                      WHERE noEmpleado = $noEmpleado";

            if (mysqli_query($conn, $query)) {
                $response = ['status' => 'success', 'message' => 'Colaborador actualizado con éxito.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Error al actualizar: ' . mysqli_error($conn)];
            }
            break;

        // 3. BAJA LÓGICA: Cambiar estatus a 0 de forma segura
        case 'baja_logica_usuario':
            $noEmpleado = intval($_POST['noEmpleado']);
            
            // Cambiamos estatus a 0 (Inactivo) conservando la integridad referencial de los documentos
            $query = "UPDATE usuarios SET estatus = 0 WHERE noEmpleado = $noEmpleado";
            
            if (mysqli_query($conn, $query)) {
                $response = ['status' => 'success', 'message' => 'Colaborador desactivado correctamente en el sistema.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Error al procesar la baja: ' . mysqli_error($conn)];
            }
            break;

        case 'obtener_catalogos_usuarios':
            // 1. Obtener todos los departamentos activos
            $q_deptos = "SELECT id, departamento FROM departamento ORDER BY departamento ASC";
            $res_deptos = mysqli_query($conn, $q_deptos);
            $deptos = [];
            while($r = mysqli_fetch_assoc($res_deptos)) { $deptos[] = $r; }

            // 2. Obtener todos los puestos activos
            $q_puestos = "SELECT id, puesto FROM puesto ORDER BY puesto ASC";
            $res_puestos = mysqli_query($conn, $q_puestos);
            $puestos = [];
            while($r = mysqli_fetch_assoc($res_puestos)) { $puestos[] = $r; }

            // 3. Obtener todos los usuarios activos para la lista de Jefes Administrativos
            $q_jefes = "SELECT noEmpleado, nombre FROM usuarios WHERE estatus = 1 ORDER BY nombre ASC";
            $res_jefes = mysqli_query($conn, $q_jefes);
            $jefes = [];
            while($r = mysqli_fetch_assoc($res_jefes)) { $jefes[] = $r; }

            $response = [
                'status' => 'success',
                'departamentos' => $deptos,
                'puestos' => $puestos,
                'jefes' => $jefes
            ];
            break;

            case 'obtener_estructura_organigrama':
            // Aseguramos renglones únicos absolutos mediante DISTINCT y GROUP BY
            $query = "SELECT DISTINCT
                        u.noEmpleado AS id, 
                        u.nombre, 
                        p.puesto, 
                        d.departamento AS area_base,
                        u.jefe AS pid,
                        (SELECT GROUP_CONCAT(DISTINCT d2.departamento SEPARATOR ', ') 
                         FROM expediente_jefes_tecnicos ejt 
                         INNER JOIN departamento d2 ON ejt.id_departamento = d2.id 
                         WHERE ejt.id_usuario_empleado = u.noEmpleado) AS alcances_extra
                      FROM usuarios u
                      LEFT JOIN puesto p ON u.puesto = p.id
                      LEFT JOIN departamento d ON u.departamento = d.id
                      WHERE u.estatus = 1 AND u.departamento != 0 AND u.jefe != 0
                      GROUP BY u.noEmpleado
                      ORDER BY u.jefe ASC, u.nombre ASC";
                      
            $result = mysqli_query($conn, $query);
            $datos = [];
            
            while($row = mysqli_fetch_assoc($result)) {
                $id_limpio = intval($row['id']);
                $pid_limpio = intval($row['pid']);
                
                // Si el jefe es 0 o apunta a sí mismo, se vuelve NULL (raíz) para no romper la librería
                if ($pid_limpio === 0 || $pid_limpio === $id_limpio) {
                    $pid_limpio = null;
                }

                $datos[] = [
                    'id'             => $id_limpio,
                    'pid'            => $pid_limpio,
                    'nombre'         => !empty($row['nombre']) ? $row['nombre'] : "Colaborador",
                    'puesto'         => !empty($row['puesto']) ? $row['puesto'] : "Personal",
                    'area_base'      => !empty($row['area_base']) ? $row['area_base'] : "General",
                    'alcances_extra' => !empty($row['alcances_extra']) ? $row['alcances_extra'] : ""
                ];
            }
            
            echo json_encode(['status' => 'success', 'data' => $datos]);
            exit;
            break;

            case 'obtener_telefonos_usuario':
                $noEmpleado = intval($_POST['noEmpleado']);
                $query = "SELECT id, telefono, extension FROM telefono WHERE noEmpleado = $noEmpleado";
                $result = mysqli_query($conn, $query);
                $telefonos = [];
                while($row = mysqli_fetch_assoc($result)) { $telefonos[] = $row; }
                $response = ['status' => 'success', 'data' => $telefonos];
                break;
    }
}
echo json_encode($response);
exit;
?>