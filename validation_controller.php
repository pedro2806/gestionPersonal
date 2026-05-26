<?php
// validation_controller.php - Controlador exclusivo para la vista de Validación y Firmas
header('Content-Type: application/json');
$conn = mysqli_connect("localhost", "mess_incidencias", "Pipmytrade123", "mess_rrhh");
mysqli_set_charset($conn, "utf8mb4");

$response = ['status' => 'error', 'message' => 'Acción no válida o sesión nula.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    // Recuperamos los datos del firmante/revisor en sesión
    $id_sesion = isset($_POST['id_usuario_sesion']) ? intval($_POST['id_usuario_sesion']) : 0;
    $no_empleado_sesion = isset($_POST['no_empleado_sesion']) ? intval($_POST['no_empleado_sesion']) : 0;

    // DEFINICIÓN DE ROLES EN BASE A TU REGLA DE NEGOCIO (Numérico Puro)
    $es_rrhh = ($no_empleado_sesion === 403);
    $es_calidad = ($no_empleado_sesion === 5);
    $es_super_user = ($es_rrhh || $es_calidad); // Calidad y RRHH tienen visibilidad total

    switch ($action) {
        
        // 1. CARGAR PERSONAL FILTRADO POR ROLES Y SELECTS DE BÚSQUEDA
        case 'listar_personal_auditoria':
            $id_depto_filtro = isset($_POST['id_departamento']) ? intval($_POST['id_departamento']) : 0;
            $no_empleado_filtro = isset($_POST['no_empleado']) ? intval($_POST['no_empleado']) : 0;

            // Cláusula base obligatoria
            $where_clauses = ["u.estatus = 1"];

            // A) Aplicar la restricción estricta de jerarquía si no es Calidad o RRHH
            if (!$es_super_user) {
                // El usuario en revisión debe tenerte como jefe administrativo (u.jefe) 
                // O debes estar asignado como su jefe técnico para ese u otro laboratorio en la tabla intermedia
                $where_clauses[] = "(u.jefe = $id_sesion OR u.noEmpleado IN (SELECT id_usuario_empleado FROM expediente_jefes_tecnicos WHERE id_usuario_jefe_tecnico = $id_sesion))";
            }

            // B) Aplicar filtros selectivos si fueron accionados desde la vista superior
            if ($id_depto_filtro > 0) {
                $where_clauses[] = "u.departamento = $id_depto_filtro";
            }
            if ($no_empleado_filtro > 0) {
                $where_clauses[] = "u.noEmpleado = $no_empleado_filtro";
            }

            // Unimos todas las condiciones de manera segura
            $where_sql = implode(" AND ", $where_clauses);

            // Query estructurado para calcular los contadores por fila de forma óptima
            $query = "SELECT u.id, u.noEmpleado, u.nombre AS nombreCompleto, d.departamento AS depto_base, p.puesto,
                        (SELECT COUNT(*) FROM expediente_documentos WHERE id_usuario = u.noEmpleado AND estatus_general = 'En Revisión') AS pendientes,
                        (SELECT COUNT(*) FROM expediente_documentos WHERE id_usuario = u.noEmpleado AND estatus_general = 'Aprobado') AS aprobados,
                        (SELECT COUNT(*) FROM expediente_documentos WHERE id_usuario = u.noEmpleado AND estatus_general = 'Rechazado') AS rechazados
                    FROM usuarios u
                    LEFT JOIN departamento d ON u.departamento = d.id
                    LEFT JOIN puesto p ON u.puesto = p.id
                    WHERE $where_sql 
                    ORDER BY u.nombre ASC";

            $result = mysqli_query($conn, $query);
            $data = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }
            $response = ['status' => 'success', 'data' => $data];
            break;

        // 2. ALIMENTAR DINÁMICAMENTE LOS SELECTS SUPERIORES SEGÚN EL ROL DE ACCESO
        case 'obtener_filtros_busqueda':
            $where_depto = "";
            $where_ing = "WHERE u.estatus = 1";

            if (!$es_super_user) {
                // $id_sesion es entero puro, va sin comillas
                $where_depto = "WHERE id IN (SELECT departamento FROM usuarios WHERE jefe = $id_sesion) OR id IN (SELECT id_departamento FROM expediente_jefes_tecnicos WHERE id_usuario_jefe_tecnico = $id_sesion)";
                $where_ing .= " AND (u.jefe = $id_sesion OR u.noEmpleado IN (SELECT id_usuario_empleado FROM expediente_jefes_tecnicos WHERE id_usuario_jefe_tecnico = $id_sesion))";
            }
            
            // Consultar Laboratorios
            $q_deptos = "SELECT id, departamento FROM departamento $where_depto ORDER BY departamento ASC";
            $res_deptos = mysqli_query($conn, $q_deptos);
            $deptos = [];
            while($r = mysqli_fetch_assoc($res_deptos)) { $deptos[] = $r; }

            // Consultar Ingenieros
            $q_ings = "SELECT u.noEmpleado, u.nombre FROM usuarios u $where_ing ORDER BY u.nombre ASC";
            $res_ings = mysqli_query($conn, $q_ings);
            $ings = [];
            while($r = mysqli_fetch_assoc($res_ings)) { $ings[] = $r; }

            $response = ['status' => 'success', 'departamentos' => $deptos, 'ingenieros' => $ings];
            break;

        // 3. ASENTAR DICTAMEN DE FIRMA COLECTIVA Y RECALCULAR STATUS GENERAL (Solución in_array)
        case 'procesar_firma_documento':
            $id_documento = intval($_POST['id_documento']);
            $columna_firma = mysqli_real_escape_string($conn, $_POST['columna_firma']); // val_jefe_admin, val_jefe_tecnico, val_calidad, val_rrhh
            $nuevo_estado = intval($_POST['estado_firma']); // 1 = Aprobado, 2 = Rechazado

            // Actualizar la celda correspondiente al rol
            $q_update = "UPDATE expediente_documentos SET $columna_firma = $nuevo_estado WHERE id = $id_documento";
            
            if (mysqli_query($conn, $q_update)) {
                
                // Traer los estados de las 4 casillas de este documento en particular
                $q_check = "SELECT val_jefe_admin, val_jefe_tecnico, val_calidad, val_rrhh FROM expediente_documentos WHERE id = $id_documento";
                $res_check = mysqli_query($conn, $q_check);
                
                if ($res_check && mysqli_num_rows($res_check) > 0) {
                    $doc = mysqli_fetch_assoc($res_check);

                    // Mapeo seguro a enteros para blindar la lectura de in_array
                    $firmas = [
                        intval($doc['val_jefe_admin']), 
                        intval($doc['val_jefe_tecnico']), 
                        intval($doc['val_calidad']), 
                        intval($doc['val_rrhh'])
                    ];

                    // Recalcular lógica global
                    if (in_array(2, $firmas)) {
                        // Si un solo departamento rechaza, el archivo se cataloga como rechazado
                        $nuevo_global = 'Rechazado';
                    } else if (in_array(0, $firmas) || in_array(4, $firmas)) {
                        // Si quedan firmas obligatorias pendientes (0), permanece en revisión
                        $nuevo_global = 'En Revisión';
                    } else {
                        // Si todas son aprobadas (1) u omitidas/no aplica (3), el archivo se libera
                        $nuevo_global = 'Aprobado';
                    }

                    mysqli_query($conn, "UPDATE expediente_documentos SET estatus_general = '$nuevo_global' WHERE id = $id_documento");
                }

                $response = ['status' => 'success', 'message' => 'Firma asentada de forma conforme en el repositorio.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Fallo al asentar firma técnica: ' . mysqli_error($conn)];
            }
            break;
    }
}

echo json_encode($response);
exit;
?>