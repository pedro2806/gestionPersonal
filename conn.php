<?php
// conn.php - Conexión unificada para base de datos mess_rrhh
$conn = mysqli_connect("localhost", "mess_incidencias", "Pipmytrade123", "mess_rrhh");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");
?>