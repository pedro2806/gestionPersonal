<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Requires también van arriba
require __DIR__ . '/libs/PHPMailer/src/Exception.php';
require __DIR__ . '/libs/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/libs/PHPMailer/src/SMTP.php';

    header('Content-Type: application/json');
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Correo inválido']);
        exit;
    }
    
    include 'conn.php';
    
    if (!isset($conn) || $conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a BD']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT password_restaurar FROM usuarios WHERE correo = ? AND estatus ='1' LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $row = $result->fetch_assoc();
    $passwordRecuperar = trim($row['password_restaurar'] ?? '');
    
    $mail =  new PHPMailer(true);
    
    try {
        // Configuración SMTP
        $mail->isSMTP();
        $mail->SMTPDebug = 0; // PONER EN 0 SI NO QUIERES QUE SALGA EL LOG EN LA PANTALLA
                          //PONER EN 2 PARA DEPURACION DETALLADA
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mess.programacion@gmail.com';
        $mail->Password   = 'lnevdigasjodzbrq';//mess.metrologia@gmail.com - hglidvwsxcbbefhe
        $mail->SMTPSecure =  PHPMailer::ENCRYPTION_SMTPS;//PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 465; //desarrollo 587, produccion 465 con SSL
        $mail->CharSet    = 'UTF-8';
        
        // Remitente y destinatario
        $mail->setFrom('mess.programacion@gmail.com', 'Messbook');
        $mail->addAddress($email);
        $mail->addReplyTo('sebastian.gutierrez@mess.com.mx', 'Soporte Messbook');
        
        // Contenido HTML - DISEÑO CORPORATIVO TIPO FACEBOOK
        $mail->isHTML(true);
$mail->Subject = 'Recuperación de contraseña - Messbook';

$mail->Body = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; background-color: #f0f2f5; font-family: Helvetica, Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f2f5; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%;">
                    <!-- Header azul sólido -->
                    <tr>
                        <td bgcolor="#1e3a8a" style="background-color: #1e3a8a; padding: 40px 30px; text-align: center; border-radius: 12px 12px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 32px; font-weight: 700; letter-spacing: -0.5px; font-family: Helvetica, Arial, sans-serif;">messbook</h1>
                            <p style="margin: 8px 0 0 0; color: #e4e6eb; font-size: 14px; font-family: Helvetica, Arial, sans-serif;">Central Identity and Access Management System</p>
                        </td>
                    </tr>

                    <!-- Card blanca -->
                    <tr>
                        <td bgcolor="#ffffff" style="background-color: #ffffff; padding: 40px 30px; border-radius: 0 0 12px 12px;">
                            <h2 style="margin: 0 0 20px 0; color: #1c1e21; font-size: 20px; font-weight: 600; font-family: Helvetica, Arial, sans-serif;">Bienvenido a messbook</h2>

                            <p style="margin: 0 0 24px 0; color: #1c1e21; font-size: 15px; line-height: 1.5; font-family: Helvetica, Arial, sans-serif;">
                                Tu plataforma de gestión de identidad y acceso. A continuación, encontrarás tu contraseña temporal para acceder a tu cuenta:
                            </p>

                            <!-- Box de contraseña -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f0f2f5; border: 1.5px solid #dddfe2; border-radius: 8px; margin: 24px 0;">
                                <tr>
                                    <td style="padding: 20px; text-align: center;">
                                        <p style="margin: 0 0 8px 0; color: #65676b; font-size: 13px; font-weight: 600; text-transform: uppercase; font-family: Helvetica, Arial, sans-serif;">Tu Usuario</p>
                                        <p style="margin: 0; color: #1877f2; font-size: 24px; font-weight: 700; letter-spacing: 2px; font-family: Courier, monospace;">'.$email.'</p>
                                    </td>
                                    <td style="padding: 20px; text-align: center;">
                                        <p style="margin: 0 0 8px 0; color: #65676b; font-size: 13px; font-weight: 600; text-transform: uppercase; font-family: Helvetica, Arial, sans-serif;">Tu contraseña</p>
                                        <p style="margin: 0; color: #1877f2; font-size: 24px; font-weight: 700; letter-spacing: 2px; font-family: Courier, monospace;">'.$passwordRecuperar.'</p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 24px 0; color: #1c1e21; font-size: 15px; line-height: 1.5; font-family: Helvetica, Arial, sans-serif;">
                                Ingresa con esta contraseña y cámbiala inmediatamente desde tu perfil para mantener tu cuenta segura.
                            </p>

                            <!-- Botón azul sólido -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="https://messbook.com.mx/loginMaster" style="display: inline-block; background-color: #1877f2; color: #ffffff; text-decoration: none; padding: 12px 32px; border-radius: 8px; font-size: 16px; font-weight: 700; font-family: Helvetica, Arial, sans-serif;">
                                            Iniciar sesión en Messbook
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <!-- imagen instrucciones cambio de contraseña -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 10px 0;">
                                <tr>
                                    <td align="center">
                                        <img src="https://messbook.com.mx/gestionPersonal/img/cambiarPass.png" alt="Instrucciones para cambiar contraseña" style="max-width: 75%; height: auto; border-radius: 8px;">
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; text-align: center;">
                                        <p style="margin: 0; color: #65676b; font-size: 12px; font-family: Helvetica, Arial, sans-serif;">*Da clic en el botón "Cambiar contraseña" para actualizar tu contraseña, se abrirá una ventana para ingresar tu nueva contraseña.</p>
                                    </td>
                                </tr>
                            </table>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #dadde1; margin-top: 30px; padding-top: 20px;">
                                <tr>
                                    <td style="text-align: center;">
                                        <p style="margin: 0 0 8px 0; color: #1c1e21; font-size: 13px; font-weight: 600; font-family: Helvetica, Arial, sans-serif;">Si tienes problemas para acceder a Messbook contacta a:</p>
                                        <p style="margin: 0; color: #65676b; font-size: 13px; line-height: 1.6; font-family: Helvetica, Arial, sans-serif;">
                                            <a href="mailto:pedro.martinez@mess.com.mx" style="color: #1877f2; text-decoration: none;">pedro.martinez@mess.com.mx</a><br>
                                            <a href="mailto:sebastian.gutierrez@mess.com.mx" style="color: #1877f2; text-decoration: none;">sebastian.gutierrez@mess.com.mx</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 30px; text-align: center;">
                            <p style="margin: 0; color: #8a8d91; font-size: 11px; line-height: 1.5; font-family: Helvetica, Arial, sans-serif;">
                                Este correo fue enviado por Messbook.<br>
                                Business Intelligence | Messbook ©️ '.date("Y").'
                            </p>
                            <p style="margin: 12px 0 0 0; color: #8a8d91; font-size: 11px; font-family: Helvetica, Arial, sans-serif;">
                                Si no solicitaste este correo, ignóralo de forma segura.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

// Versión texto plano
$mail->AltBody = "Hola,\n\nTu contraseña de recuperación es: $passwordRecuperar\n\nIngresa con esta contraseña y cámbiala desde tu perfil.\n\nSaludos,\nMessbook Development Team ©️ 2026";
        
     
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'Contraseña enviada a tu correo']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error al enviar correo: ' . $mail->ErrorInfo]);
    }
    
    $stmt->close();
    $conn->close();
    exit;

?>