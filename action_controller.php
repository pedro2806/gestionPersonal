<?php
header('Content-Type: application/json');
$conn = mysqli_connect("localhost", "mess_incidencias", "Pipmytrade123", "mess_rrhh");
mysqli_set_charset($conn, "utf8mb4");

$response = ''; ['status' => 'error', 'message' => 'Acción no válida.'];

///if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
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
                        'nombre_depto' => 'Personal'
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
            $query = "SELECT 
                        u.id, 
                        u.noEmpleado, 
                        u.nombre AS nombreCompleto, 
                        d.departamento AS depto_base, 
                        u.correo,
                        (SELECT COUNT(edoc.id) FROM expediente_documentos edoc WHERE edoc.noEmpleado = u.noEmpleado) AS total_docs,
                        IFNULL((
                            SELECT GROUP_CONCAT(CONCAT(jefes.nombre, ' (', depto_alcance.departamento, ')') SEPARATOR ', ') 
                            FROM expediente_jefes_tecnicos ejt
                            INNER JOIN usuarios jefes ON ejt.id_usuario_jefe_tecnico = jefes.id
                            INNER JOIN departamento depto_alcance ON ejt.id_departamento = depto_alcance.id
                            WHERE ejt.id_usuario_empleado = u.noEmpleado
                        ), 'N/A') AS jefes_tecnicos,
                        IFNULL((
                            SELECT GROUP_CONCAT(jefes.noEmpleado SEPARATOR ',') 
                            FROM expediente_jefes_tecnicos ejt
                            INNER JOIN usuarios jefes ON ejt.id_usuario_jefe_tecnico = jefes.id
                            INNER JOIN departamento depto_alcance ON ejt.id_departamento = depto_alcance.id
                            WHERE ejt.id_usuario_empleado = u.noEmpleado
                        ), 'N/A') AS id_jefes_tecnicos,
                        p.puesto,
                        ja.nombre AS jefe_administrativo, 
                        ja.noEmpleado AS id_jefe_directo, 
                        u.estatus, 
                        u.foto AS url_foto,  
                        IFNULL((
                            SELECT GROUP_CONCAT(CONCAT(t.telefono, ' Ext. ', IFNULL(t.extension, 'N/A')) SEPARATOR ', ') 
                            FROM telefono t 
                            WHERE t.noEmpleado = u.noEmpleado
                        ), 'N/A') AS telefonos,
                        
                        -- ==========================================================
                        -- CAMPOS DE VACACIONES OPTIMIZADOS Y CORREGIDOS
                        -- ==========================================================
                        vac.antiguedad,
                        vac.fechaIngreso,
                        vac.dias_ley_actual,
                        vac.diasSol,
                        vac.diasdisponibles

                    FROM usuarios u
                    LEFT JOIN departamento d ON u.departamento = d.id
                    LEFT JOIN puesto p ON u.puesto = p.id
                    LEFT JOIN usuarios ja ON u.jefe = ja.noEmpleado

                    LEFT JOIN (
                        SELECT 
                            v_sub.noEmpleado,
                            v_sub.antiguedad, 
                            v_sub.fechaIngreso,  
                            COALESCE(dv_actual.dias, 0) AS dias_ley_actual,  
                            
                            -- 1. Guardamos los días del periodo actual en una variable
                            @actuales := COALESCE(( 
                                SELECT SUM(s.dias) FROM solicitudes s 
                                WHERE s.empleado = v_sub.noEmpleado AND s.estatus = 2 AND s.autorizaRH = 2 AND s.tipo = 1 
                                AND s.fesolicitud BETWEEN v_sub.inicio_actual AND v_sub.fin_actual 
                            ), 0) AS diasSol,  

                            -- 2. Guardamos los días del periodo anterior en otra variable temporal
                            @anteriores := COALESCE(( 
                                SELECT SUM(s.dias) FROM solicitudes s 
                                WHERE s.empleado = v_sub.noEmpleado AND s.estatus = 2 AND s.autorizaRH = 2 AND s.tipo = 1 
                                AND s.fesolicitud BETWEEN v_sub.inicio_anterior AND v_sub.fin_anterior 
                            ), 0) AS dias_anterior,

                            -- 3. Cálculo de disponibles: Ley Actual - Gastados Actuales - Deuda Pasada (si existe)
                            (
                                COALESCE(dv_actual.dias, 0) 
                                - @actuales 
                                - GREATEST(0, @anteriores - COALESCE(dv_anterior.dias, 0))
                            ) AS diasdisponibles 

                        FROM ( 
                            SELECT 
                                noEmpleado, 
                                fechaIngreso, 
                                TIMESTAMPDIFF(YEAR, fechaIngreso, CURDATE()) AS antiguedad,  
                                -- Período Actual  
                                CASE WHEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) > CURDATE() 
                                    THEN MAKEDATE(YEAR(CURDATE()) - 1, DAYOFYEAR(fechaIngreso)) 
                                    ELSE MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) 
                                END AS inicio_actual, 
                                CASE WHEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) > CURDATE() 
                                    THEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) 
                                    ELSE MAKEDATE(YEAR(CURDATE()) + 1, DAYOFYEAR(fechaIngreso)) 
                                END AS fin_actual,  
                                -- Período Anterior  
                                CASE WHEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) > CURDATE() 
                                    THEN MAKEDATE(YEAR(CURDATE()) - 2, DAYOFYEAR(fechaIngreso)) 
                                    ELSE MAKEDATE(YEAR(CURDATE()) - 1, DAYOFYEAR(fechaIngreso)) 
                                END AS inicio_anterior, 
                                CASE WHEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) > CURDATE() 
                                    THEN MAKEDATE(YEAR(CURDATE()) - 1, DAYOFYEAR(fechaIngreso)) 
                                    ELSE MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) 
                                END AS fin_anterior  
                            FROM usuarios  
                        ) v_sub  
                        LEFT JOIN diasvacaciones dv_actual ON dv_actual.anio = v_sub.antiguedad  
                        LEFT JOIN diasvacaciones dv_anterior ON dv_anterior.anio = GREATEST(0, v_sub.antiguedad - 1)  
                    ) vac ON u.noEmpleado = vac.noEmpleado

                    ORDER BY u.nombre ASC";
            $result = mysqli_query($conn, $query);
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) { $data[] = $row; }
            $response = ['status' => 'success', 'data' => $data];
            break;

        case 'obtener_dias_vacaciones_usuario':
            $noEmpleado = intval($_POST['noEmpleado']);
            $query = "SELECT 
                        v.nombre,                         
                        v.noEmpleado,
                        v.antiguedad, 
                        v.fechaIngreso,  
                        COALESCE(dv_actual.dias, 0) AS dias_ley_actual,  
                        
                        -- 1. Días tomados en el periodo actual
                        @actuales := COALESCE(( 
                            SELECT SUM(s.dias) FROM solicitudes s 
                            WHERE s.empleado = v.noEmpleado AND s.estatus = 2 AND s.autorizaRH = 2 AND s.tipo = 1 
                            AND s.fesolicitud BETWEEN v.inicio_actual AND v.fin_actual 
                        ), 0) AS diasSol,  

                        -- 2. Días tomados en el periodo anterior
                        @anteriores := COALESCE(( 
                            SELECT SUM(s.dias) FROM solicitudes s 
                            WHERE s.empleado = v.noEmpleado AND s.estatus = 2 AND s.autorizaRH = 2 AND s.tipo = 1 
                            AND s.fesolicitud BETWEEN v.inicio_anterior AND v.fin_anterior 
                        ), 0) AS dias_anterior,

                        -- 3. CÁLCULO DE DÍAS DISPONIBLES (Descontando deudas si pidió de más)
                        (
                            COALESCE(dv_actual.dias, 0) -- Días por ley de este año
                            - @actuales                 -- Menos lo que ya pidió este año
                            - GREATEST(0, @anteriores - COALESCE(dv_anterior.dias, 0)) -- Menos la deuda del año pasado (si pidió de más)
                        ) AS diasdisponibles

                    FROM ( 
                        SELECT 
                            nombre,
                            noEmpleado, 
                            fechaIngreso, 
                            TIMESTAMPDIFF(YEAR, fechaIngreso, CURDATE()) AS antiguedad,   
                            
                            -- Período Actual  
                            CASE WHEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) > CURDATE() 
                                THEN MAKEDATE(YEAR(CURDATE()) - 1, DAYOFYEAR(fechaIngreso)) 
                                ELSE MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) 
                            END AS inicio_actual, 
                            CASE WHEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) > CURDATE() 
                                THEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) 
                                ELSE MAKEDATE(YEAR(CURDATE()) + 1, DAYOFYEAR(fechaIngreso)) 
                            END AS fin_actual,  
                            
                            -- Período Anterior  
                            CASE WHEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) > CURDATE() 
                                THEN MAKEDATE(YEAR(CURDATE()) - 2, DAYOFYEAR(fechaIngreso)) 
                                ELSE MAKEDATE(YEAR(CURDATE()) - 1, DAYOFYEAR(fechaIngreso))
                            END AS inicio_anterior, 
                            CASE WHEN MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) > CURDATE() 
                                THEN MAKEDATE(YEAR(CURDATE()) - 1, DAYOFYEAR(fechaIngreso)) 
                                ELSE MAKEDATE(YEAR(CURDATE()), DAYOFYEAR(fechaIngreso)) 
                            END AS fin_anterior 
                        FROM usuarios  
                        WHERE noEmpleado = $noEmpleado
                    ) v
                    LEFT JOIN diasvacaciones dv_actual ON dv_actual.anio = v.antiguedad
                    LEFT JOIN diasvacaciones dv_anterior ON dv_anterior.anio = GREATEST(0, v.antiguedad - 1)";
            
            $result = mysqli_query($conn, $query);
            $data = [];
            while($r = mysqli_fetch_assoc($result)) { $data[] = $r; }
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
            $query = "SELECT u.id, u.noEmpleado, u.nombre AS nombreCompleto, u.correo, p.puesto, u.estatus, d.departamento,
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
            $query = "SELECT noEmpleado, nombre, correo, departamento, puesto, jefe, tipoContrato, sexo, nss, rfc, curp, tipoSangre, fechaIngreso, foto FROM usuarios WHERE noEmpleado = $noEmpleado";
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
            $fechaIngreso = mysqli_real_escape_string($conn, $_POST['mod_fechaIngreso']);

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
                        tipoSangre = '$tipoSangre',
                        fechaIngreso = '$fechaIngreso'
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
            $query = "SELECT idTelefono AS id, telefono, extension FROM telefono WHERE noEmpleado = $noEmpleado ORDER BY idTelefono ASC";
            $result = mysqli_query($conn, $query);
            $telefonos = [];
            while($row = mysqli_fetch_assoc($result)) { $telefonos[] = $row; }
            $response = ['status' => 'success', 'data' => $telefonos];
            break;

        // Sincroniza la lista completa de teléfonos del empleado:
        // - UPDATE filas con idTelefono existente
        // - INSERT filas nuevas (idTelefono vacío o 0)
        // - DELETE filas del empleado que ya no aparezcan en la lista enviada
        case 'guardar_telefonos_usuario':
            $noEmpleado  = intval($_POST['noEmpleado']);
            $ids         = isset($_POST['id'])        && is_array($_POST['id'])        ? $_POST['id']        : [];
            $telefonos   = isset($_POST['telefono'])  && is_array($_POST['telefono'])  ? $_POST['telefono']  : [];
            $extensiones = isset($_POST['extension']) && is_array($_POST['extension']) ? $_POST['extension'] : [];

            if ($noEmpleado <= 0) {
                $response = ['status' => 'error', 'message' => 'Empleado inválido.'];
                break;
            }

            $stmtUpd = mysqli_prepare($conn, "UPDATE telefono SET telefono = ?, extension = ? WHERE idTelefono = ? AND noEmpleado = ?");
            $stmtIns = mysqli_prepare($conn, "INSERT INTO telefono (noEmpleado, telefono, extension, tipo) VALUES (?, ?, ?, 2)");

            $idsConservados = [];
            $insertados = 0;
            $actualizados = 0;

            foreach ($ids as $i => $idTel) {
                $idTel = intval($idTel);
                $tel   = trim((string)($telefonos[$i]   ?? ''));
                $ext   = trim((string)($extensiones[$i] ?? ''));

                // Omitir filas completamente vacías
                if ($tel === '' && $ext === '') continue;

                if ($idTel > 0) {
                    mysqli_stmt_bind_param($stmtUpd, 'ssii', $tel, $ext, $idTel, $noEmpleado);
                    mysqli_stmt_execute($stmtUpd);
                    $actualizados += mysqli_stmt_affected_rows($stmtUpd);
                    $idsConservados[] = $idTel;
                } else {
                    mysqli_stmt_bind_param($stmtIns, 'iss', $noEmpleado, $tel, $ext);
                    if (mysqli_stmt_execute($stmtIns)) {
                        $insertados++;
                        $idsConservados[] = mysqli_insert_id($conn);
                    }
                }
            }
            mysqli_stmt_close($stmtUpd);
            mysqli_stmt_close($stmtIns);

            // DELETE: borrar del empleado lo que no se conservó
            $eliminados = 0;
            if (!empty($idsConservados)) {
                $idsConservados = array_map('intval', $idsConservados);
                $whereExtra = " AND idTelefono NOT IN (" . implode(',', $idsConservados) . ")";
            } else {
                $whereExtra = '';
            }
            if (mysqli_query($conn, "DELETE FROM telefono WHERE noEmpleado = $noEmpleado $whereExtra")) {
                $eliminados = mysqli_affected_rows($conn);
            }

            $response = [
                'status' => 'success',
                'message' => "Teléfonos guardados."
            ];
            break;

        // Cambia la foto del colaborador.
        // Permisos: super-usuarios (5, 403) o el propio empleado actualizando su foto.
        case 'actualizar_foto_usuario':
            $noEmpleado = intval($_POST['noEmpleado']);
            $sesionEmp  = isset($_COOKIE['noEmpleadoGP']) ? intval($_COOKIE['noEmpleadoGP']) : 0;

            if ($noEmpleado <= 0) {
                $response = ['status' => 'error', 'message' => 'Empleado inválido.'];
                break;
            }
            if ($sesionEmp !== 5 && $sesionEmp !== 403 && $sesionEmp !== $noEmpleado) {
                $response = ['status' => 'error', 'message' => 'Sin permisos para cambiar esta foto.'];
                break;
            }
            if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $response = ['status' => 'error', 'message' => 'Archivo no recibido o corrupto.'];
                break;
            }

            $file = $_FILES['foto'];
            if ($file['size'] > 2 * 1024 * 1024) {
                $response = ['status' => 'error', 'message' => 'La imagen no debe superar 2MB.'];
                break;
            }

            // MIME real, no el reportado por el navegador
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $mapaExt = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
            if (!isset($mapaExt[$mime])) {
                $response = ['status' => 'error', 'message' => 'Solo se aceptan imágenes JPG o PNG.'];
                break;
            }
            $ext = $mapaExt[$mime];

            // Las fotos viven en loginMaster (compartidas con ese módulo).
            // En BD se guardan en formato relativo "img/ProfilePictures/X.jpg" (consistente
            // con las fotos viejas existentes). Quien consume la URL prepone "/loginMaster/"
            // o "../loginMaster/" según contexto.
            $db_prefix  = "img/ProfilePictures/";
            $target_dir = "../loginMaster/" . $db_prefix;
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Borrar foto anterior solo si vive dentro de ProfilePictures (incluye fotos
            // viejas tipo "img/ProfilePictures/123.jpg" y nuevas "..._timestamp.jpg").
            $resActual    = mysqli_query($conn, "SELECT foto FROM usuarios WHERE noEmpleado = $noEmpleado");
            $rowActual    = $resActual ? mysqli_fetch_assoc($resActual) : null;
            $fotoAnterior = $rowActual['foto'] ?? '';
            if ($fotoAnterior !== '' && strpos($fotoAnterior, $db_prefix) === 0) {
                $fsAnterior = '../loginMaster/' . $fotoAnterior;
                if (file_exists($fsAnterior)) {
                    @unlink($fsAnterior);
                }
            }

            $newName     = $noEmpleado . '_' . time() . '.' . $ext;
            $target_file = $target_dir . $newName;
            $db_value    = $db_prefix . $newName;

            if (!move_uploaded_file($file['tmp_name'], $target_file)) {
                $response = ['status' => 'error', 'message' => 'Error al guardar el archivo.'];
                break;
            }

            $db_value_sql = mysqli_real_escape_string($conn, $db_value);
            if (mysqli_query($conn, "UPDATE usuarios SET foto = '$db_value_sql' WHERE noEmpleado = $noEmpleado")) {
                // Si el usuario actualiza su propia foto, refrescamos la cookie del encabezado
                if ($sesionEmp === $noEmpleado) {
                    setcookie('fotoGP', $db_value, [
                        'expires'  => time() + 86400,
                        'path'     => '/',
                        'samesite' => 'Lax'
                    ]);
                }
                $response = [
                    'status'   => 'success',
                    'message'  => 'Foto actualizada.',
                    'foto_url' => $db_value
                ];
            } else {
                @unlink($target_file);
                $response = ['status' => 'error', 'message' => 'Error en BD: ' . mysqli_error($conn)];
            }
            break;

        // ============================================================================
        // 🆕 CASO ADICIONAL: ALTA COMPLETA DE COLABORADORES EN EL EXPEDIENTE
        // ============================================================================
        case 'registrar_nuevo_empleado_sistema':
            // Asegurar limpieza absoluta de datos obligatorios y numéricos
            $noEmpleado    = intval($_POST['nuevo_noEmpleado']);
            $nombre        = mysqli_real_escape_string($conn, $_POST['nuevo_nombre']);
            $correo        = mysqli_real_escape_string($conn, $_POST['nuevo_correo']);
            $sexo          = mysqli_real_escape_string($conn, $_POST['nuevo_sexo']);
            $fechaIngreso  = mysqli_real_escape_string($conn, $_POST['nuevo_fechaIngreso']);
            $departamento  = intval($_POST['nuevo_departamento']);
            $puesto        = intval($_POST['nuevo_puesto']);
            $jefe          = intval($_POST['nuevo_jefe']);
            
            // Campos opcionales o formateados en mayúsculas estrictas
            $curp          = strtoupper(mysqli_real_escape_string($conn, trim($_POST['nuevo_curp'])));
            $rfc           = strtoupper(mysqli_real_escape_string($conn, trim($_POST['nuevo_rfc'])));
            $nss           = mysqli_real_escape_string($conn, trim($_POST['nuevo_nss']));
            $tipoContrato  = mysqli_real_escape_string($conn, $_POST['nuevo_tipoContrato']);
            $tipoSangre = mysqli_real_escape_string($conn, $_POST['nuevo_tipoSangre'] ?? '');
            
            // Valor por defecto para la foto de perfil inicial institucional
            $foto_default  = "fotos_perfil/undraw_profile.svg";

            if ($noEmpleado === 0 || empty($nombre) || empty($correo)) {
                $response = ['status' => 'error', 'message' => 'Los campos esenciales (Nómina, Nombre y Correo) son obligatorios.'];
                break;
            }

            // Validación preventiva de duplicidad de número de nómina corporativa
            $check_duplicado = mysqli_query($conn, "SELECT noEmpleado FROM usuarios WHERE noEmpleado = $noEmpleado LIMIT 1");
            if (mysqli_num_rows($check_duplicado) > 0) {
                $response = ['status' => 'error', 'message' => "El número de empleado $noEmpleado ya se encuentra asignado a un colaborador activo."];
                break;
            }

            $usuario = $correo; // Asumimos que el correo es el usuario de inicio de sesión
            // Generar contraseña MD5 a partir del usuario (sin el dominio @mess.com.mx)
            $user_part = '';
            if (stripos($correo, '@mess.com.mx') !== false) {
                $user_part = str_ireplace('@mess.com.mx', '', $correo);
            } else {
                $user_part = strstr($correo, '@', true) ?: $correo;
            }
            $password = md5($user_part);
                
            // Query de inserción masiva clonando la estructura de tu tabla usuarios
            $q_insert = "INSERT INTO usuarios 
                            (noEmpleado, nombre, correo, sexo, fechaIngreso, departamento, puesto, jefe, curp, rfc, nss, tipoContrato, tipoSangre, foto, estatus, usuario, password, password_restaurar) 
                        VALUES 
                            ($noEmpleado, '$nombre', '$correo', '$sexo', '$fechaIngreso', $departamento, $puesto, $jefe, '$curp', '$rfc', '$nss', '$tipoContrato', '$tipoSangre', '$foto_default', 1, '$usuario', '$password', '$user_part')";

            if (mysqli_query($conn, $q_insert)) {
    
                // Si la inserción del nuevo empleado fue exitosa, procedemos a crear sus accesos a los sistemas
                $q_insert_accesos = "INSERT INTO `accesos` (`id`, `noEmpleado`, `sistema`, `estatus`) VALUES 
                                    (NULL, $noEmpleado, 'divIncidencias', '1'), 
                                    (NULL, $noEmpleado, 'divControlVehicular', '1'), 
                                    (NULL, $noEmpleado, 'divCapacitacion', '1'),
                                    (NULL, $noEmpleado, 'divVacaciones', '1');";
                
                // Validamos que la inserción de accesos también sea exitosa
                if (mysqli_query($conn, $q_insert_accesos)) {
                    $response = [
                        'status' => 'success', 
                        'title' => '¡Excelente!',
                        'message' => '¡Colaborador creado con éxito! Se ha habilitado su perfil en la matriz de expediente general.',
                        'correo' => $correo
                        ];  
                } else {
                    // Falló la inserción de accesos (puedes hacer un log de mysqli_error($conn) aquí si quieres)
                    $response = [
                        'status' => 'error',
                        'title' => 'Error de accesos',
                        'message' => 'El colaborador se creó, pero hubo un problema al asignar sus accesos automáticos.'
                    ];
                }

            } else {
                $response = ['status' => 'error', 'message' => 'Fallo operativo en la base de datos: ' . mysqli_error($conn)];
            }
            break;

        case 'obtener_ultimo_no_empleado':
            $query = "SELECT MAX(noEmpleado) AS max_noEmpleado FROM usuarios where noEmpleado < 1000";
            $result = mysqli_query($conn, $query);
            $row = mysqli_fetch_assoc($result);
            $response = ['status' => 'success', 'ultimo_no_empleado' => $row['max_noEmpleado']];
        break;

        case 'listar_empleados_filtro_cursos':
            $no_empleado_sesion = isset($_COOKIE['noEmpleadoGP']) ? intval($_COOKIE['noEmpleadoGP']) : 0;

            $q_admin = "SELECT id FROM accesos_especiales WHERE noEmpleado = $no_empleado_sesion AND sistema = 'gestionPersonal' AND opcion = 'adminCapacitacion' AND estatus = 1 LIMIT 1";
            $es_admin = mysqli_num_rows(mysqli_query($conn, $q_admin)) > 0;

            $rol = 'empleado';
            $empleados = [];

            if ($es_admin) {
                $rol = 'admin';
                $q = "SELECT noEmpleado, nombre, correo FROM usuarios WHERE estatus = 1 ORDER BY nombre ASC";
                $res = mysqli_query($conn, $q);
                while ($r = mysqli_fetch_assoc($res)) { $empleados[] = $r; }
            } else {
                $q_puesto = "SELECT p.puesto FROM usuarios u INNER JOIN puesto p ON u.puesto = p.id WHERE u.noEmpleado = $no_empleado_sesion LIMIT 1";
                $res_puesto = mysqli_query($conn, $q_puesto);
                $row_puesto = mysqli_fetch_assoc($res_puesto);
                $puesto = $row_puesto['puesto'] ?? '';

                if (stripos($puesto, 'Jefe') !== false) {
                    $rol = 'jefe';
                    $q = "SELECT u.noEmpleado, u.nombre, u.correo FROM usuarios u
                          WHERE u.estatus = 1 AND (
                              u.jefe = $no_empleado_sesion
                              OR u.noEmpleado IN (SELECT id_usuario_empleado FROM expediente_jefes_tecnicos WHERE id_usuario_jefe_tecnico = $no_empleado_sesion)
                          )
                          ORDER BY u.nombre ASC";
                    $res = mysqli_query($conn, $q);
                    while ($r = mysqli_fetch_assoc($res)) { $empleados[] = $r; }
                }
            }

            $response = ['status' => 'success', 'rol' => $rol, 'empleados' => $empleados];
            break;


    }
///}
echo json_encode($response);
exit;
?>