<?php
// validaLoginMaster.php - Entrada desde el dashboard loginMaster al módulo Gestión Personal
include '../incidencias/conn.php';
$conn->select_db("mess_rrhh");

$id_usuario = isset($_POST['id_usuarioGP']) ? trim($_POST['id_usuarioGP']) : '';
$nombrePost = isset($_POST['nombredelusuarioGP']) ? trim($_POST['nombredelusuarioGP']) : '';
$noEmpleado = isset($_POST['noEmpleadoGP']) ? trim($_POST['noEmpleadoGP']) : '';
$correo     = isset($_POST['correoGP']) ? trim($_POST['correoGP']) : '';

if ($noEmpleado === '' || !ctype_digit($noEmpleado)) {
    header('Location: ../loginMaster/index.php?err=datos_incompletos');
    exit;
}
if ($id_usuario === '' || !ctype_digit($id_usuario)) {
    header('Location: ../loginMaster/index.php?err=datos_incompletos');
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);

$sql = "SELECT id, noEmpleado, nombre, correo, rol, departamento, puesto, foto,
               TIMESTAMPDIFF(YEAR, fechaIngreso, CURDATE()) AS antiguedad,
               diasdisponibles
        FROM usuarios
        WHERE noEmpleado = ? AND estatus = 1
        LIMIT 1";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $noEmpleado);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$row) {
    header('Location: ../loginMaster/index.php?err=usuario_invalido');
    exit;
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$_SESSION['id_usuario']   = (int)$id_usuario;
$_SESSION['noEmpleado']   = (int)$row['noEmpleado'];
$_SESSION['nombre']       = $row['nombre'];
$_SESSION['correo']       = $row['correo'] ?: $correo;
$_SESSION['rol']          = $row['rol'];
$_SESSION['departamento'] = $row['departamento'];
$_SESSION['puesto']       = $row['puesto'];
$_SESSION['foto']         = $row['foto'];

$cookieOpts = [
    'expires'  => time() + 86400,
    'path'     => '/',
    'samesite' => 'Lax'
];
setcookie('id_usuarioGP',       (string)$id_usuario,                 $cookieOpts);
setcookie('noEmpleadoGP',       (string)$row['noEmpleado'],          $cookieOpts);
setcookie('nombredelusuarioGP', (string)$row['nombre'],              $cookieOpts);
setcookie('correoGP',           (string)($row['correo'] ?: $correo), $cookieOpts);
setcookie('rolGP',              (string)$row['rol'],                 $cookieOpts);
setcookie('departamentoGP',     (string)$row['departamento'],        $cookieOpts);
setcookie('fotoGP',             (string)($row['foto'] ?? ''),        $cookieOpts);

$sesionOpts = ['expires' => time() + 99999000, 'path' => '/', 'samesite' => 'Lax'];
setcookie('SesionLogin', 'LoginMaster', $sesionOpts);

header('Location: index.php');
exit;
