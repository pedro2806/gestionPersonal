<?php
// documentos_controller.php - Controlador Centralizado para el Catálogo de Requisitos (MESS)
header('Content-Type: application/json; charset=utf-8');
include 'conn.php';

// Validar que la petición venga por método POST y contenga una acción
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado o petición inválida.']);
    exit;
}

$action = $_POST['action'];

switch ($action) {

    /**
     * 1. ACCIÓN: Listar todo el catálogo para la tabla DataTable de la vista
     */
    case 'listar_config_documentos':
        $query = "SELECT id, nombre_tipo, subido_por, tipo_alcance, perfil_puesto, categoria_funcion, 
                         requiere_rrhh, requiere_jefe_tecnico, requiere_calidad, requiere_jefe_admin 
                  FROM `expediente_tipos_documentos` 
                  ORDER BY id DESC";
                  
        $resultado = mysqli_query($conn, $query);
        $datos = [];

        if ($resultado) {
            while ($row = mysqli_fetch_assoc($resultado)) {
                $datos[] = $row;
            }
            echo json_encode(['status' => 'success', 'data' => $datos]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Fallo al consultar el catálogo: ' . mysqli_error($conn)]);
        }
        break;

    /**
     * 2. ACCIÓN: Obtener un requisito individual para cargar los datos en el modal de edición
     */
    case 'obtener_config_documento':
        if (!isset($_POST['id']) || empty($_POST['id'])) {
            echo json_encode(['status' => 'error', 'message' => 'ID de documento no proporcionado.']);
            exit;
        }
        $id = intval($_POST['id']);

        $query = "SELECT * FROM `expediente_tipos_documentos` WHERE id = $id LIMIT 1";
        $resultado = mysqli_query($conn, $query);

        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $documento = mysqli_fetch_assoc($resultado);
            echo json_encode(['status' => 'success', 'data' => $documento]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Requisito no encontrado en el sistema.']);
        }
        break;

    /**
     * 3. ACCIÓN: Registrar un nuevo requisito documental en la base de datos
     */
    case 'guardar_config_documento':
        $nombre_tipo       = mysqli_real_escape_string($conn, trim($_POST['nombre_tipo']));
        $subido_por        = mysqli_real_escape_string($conn, $_POST['subido_por']);
        $tipo_alcance      = mysqli_real_escape_string($conn, $_POST['tipo_alcance']);
        $perfil_puesto     = mysqli_real_escape_string($conn, $_POST['perfil_puesto']);
        $categoria_funcion = mysqli_real_escape_string($conn, $_POST['categoria_funcion']);
        
        $requiere_rrhh         = intval($_POST['requiere_rrhh']);
        $requiere_jefe_tecnico = intval($_POST['requiere_jefe_tecnico']);
        $requiere_calidad      = intval($_POST['requiere_calidad']);
        $requiere_jefe_admin   = intval($_POST['requiere_jefe_admin']);

        $query = "INSERT INTO `expediente_tipos_documentos` 
                  (`nombre_tipo`, `subido_por`, `tipo_alcance`, `perfil_puesto`, `categoria_funcion`, `requiere_jefe_admin`, `requiere_jefe_tecnico`, `requiere_calidad`, `requiere_rrhh`) 
                  VALUES 
                  ('$nombre_tipo', '$subido_por', '$tipo_alcance', '$perfil_puesto', '$categoria_funcion', $requiere_jefe_admin, $requiere_jefe_tecnico, $requiere_calidad, $requiere_rrhh)";

        if (mysqli_query($conn, $query)) {
            echo json_encode(['status' => 'success', 'message' => 'El requisito se agregó correctamente al catálogo corporativo.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al insertar en la base de datos: ' . mysqli_error($conn)]);
        }
        break;

    /**
     * 4. ACCIÓN: Modificar y actualizar un requisito ya existente
     */
    case 'actualizar_config_documento':
        $id                = intval($_POST['id']);
        $nombre_tipo       = mysqli_real_escape_string($conn, trim($_POST['nombre_tipo']));
        $subido_por        = mysqli_real_escape_string($conn, $_POST['subido_por']);
        $tipo_alcance      = mysqli_real_escape_string($conn, $_POST['tipo_alcance']);
        $perfil_puesto     = mysqli_real_escape_string($conn, $_POST['perfil_puesto']);
        $categoria_funcion = mysqli_real_escape_string($conn, $_POST['categoria_funcion']);
        
        $requiere_rrhh         = intval($_POST['requiere_rrhh']);
        $requiere_jefe_tecnico = intval($_POST['requiere_jefe_tecnico']);
        $requiere_calidad      = intval($_POST['requiere_calidad']);
        $requiere_jefe_admin   = intval($_POST['requiere_jefe_admin']);

        $query = "UPDATE `expediente_tipos_documentos` SET 
                    `nombre_tipo` = '$nombre_tipo', 
                    `subido_por` = '$subido_por', 
                    `tipo_alcance` = '$tipo_alcance', 
                    `perfil_puesto` = '$perfil_puesto', 
                    `categoria_funcion` = '$categoria_funcion', 
                    `requiere_rrhh` = $requiere_rrhh, 
                    `requiere_jefe_tecnico` = $requiere_jefe_tecnico, 
                    `requiere_calidad` = $requiere_calidad, 
                    `requiere_jefe_admin` = $requiere_jefe_admin 
                  WHERE `id` = $id";

        if (mysqli_query($conn, $query)) {
            echo json_encode(['status' => 'success', 'message' => 'Los parámetros del requisito se actualizaron con éxito.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar en la base de datos: ' . mysqli_error($conn)]);
        }
        break;

    /**
     * 5. ACCIÓN: Eliminar físicamente un requisito si no tiene archivos amarrados
     */
    case 'eliminar_config_documento':
        $id = intval($_POST['id']);

        // Validar integridad antes de borrar para no dejar registros huérfanos
        $query_check = "SELECT COUNT(*) as cargados FROM `expediente_documentos` WHERE `id_tipo_documento` = $id";
        $res_check = mysqli_query($conn, $query_check);
        $check = mysqli_fetch_assoc($res_check);

        if (intval($check['cargados']) > 0) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'No puedes eliminar este requisito. Existen ' . $check['cargados'] . ' archivos cargados que dependen de este ID.'
            ]);
            exit;
        }

        $query_delete = "DELETE FROM `expediente_tipos_documentos` WHERE `id` = $id";
        
        if (mysqli_query($conn, $query_delete)) {
            echo json_encode(['status' => 'success', 'message' => 'Requisito removido del catálogo exitosamente.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al ejecutar la eliminación: ' . mysqli_error($conn)]);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'La acción solicitada no está mapeada en este controlador.']);
        break;
}

mysqli_close($conn);
exit;