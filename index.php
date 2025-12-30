<?php

require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//echo json_encode(['msg'=>'?']);
$mail = new PHPMailer(true);
$fn = $_POST['nombre'] ?? null;
$fe = $_POST['email'] ?? null;
$ft = $_POST['telefono'] ?? null;
$fc = $_POST['consulta'] ?? null;
$ftipo = $_POST['check_tipo'] ?? null;
$farchivo = $_FILES['archivo'] ?? null;
$ip = $_SERVER['REMOTE_ADDR']; // Get client IP
//$nombre, $email, $telefono, $consulta
//$mail->SMTPDebug = 2; // Enable debugging (3 for more details)
//$mail->Debugoutput = 'html'; // Display debug output in the browser
try {
    if(is_null($fn) || is_null($fe) || is_null($ft) || is_null($fc) || is_null($ftipo)){
        throw new Exception("Los datos del formulario no pueden venir vacios", 400);
    }
    if($fn == "" || $fe == "" || $ft == "" || $fc == "" || $ftipo == ""){
        throw new Exception("Los datos del formulario no pueden venir vacios", 400);
    }
    if(!filter_var($fe, FILTER_VALIDATE_EMAIL)){
        throw new Exception("El email ingresado no es valido", 400);
    }
    if($ftipo == "2" && is_null($farchivo)){
        throw new Exception("Debe adjuntar un archivo", 400);
    }
    if($ftipo == "2" && $farchivo['error'] != UPLOAD_ERR_OK){
        throw new Exception("Error al cargar el archivo, intente nuevamente", 400);
    }
    if($ftipo == "2" && $farchivo['size'] > 5 * 1024 * 1024){
        throw new Exception("El archivo adjuntado supera el tamaño máximo permitido de 5MB", 400);
    }
    //Validar que sea un archivo pdf
    if($ftipo == "2"){
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $farchivo['tmp_name']);
        finfo_close($finfo);
        if($mimeType != 'application/pdf'){
            throw new Exception("El archivo adjuntado debe ser un PDF", 400);
        }
    }

/*    if (isIPFromArgentina($ip) == false) {
        throw new Exception("La dirección IP no proviene de ARG, si tiene VPN desactivelo", 400);
    }*/

    // SMTP Configuration
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->isHTML(true);
    $mail->Host = '<host_url|ip>'; // Replace with your SMTP server (e.g., smtp.gmail.com)
    $mail->SMTPAuth = true;
    $mail->Username = 'usr@something.com'; // Your SMTP email
    $mail->Password = '<email-password>'; // Your SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Encryption (TLS/SSL) ENCRYPTION_STARTTLS / ENCRYPTION_SMTPS
    $mail->Port = 465; // SMTP port (587 for TLS, 465 for SSL)

    // Agregar archivo adjunto si corresponde
    if($ftipo == "2" && !is_null($farchivo)){
        $mail->addAttachment($farchivo['tmp_name'], $farchivo['name']);
    }

    // Email Details
    $asunto = $ftipo == "1" ? "¡Nueva consulta de un cliente!" : "¡Quieren trabajar con nosotros!";
    $mail->Subject = $asunto;
    $mail->setFrom('tickets@correoflash.com', 'Mantenor notificaciones');
    $mail->addAddress('aldo.rissi@crearmantenimiento.com.ar', 'Destino');
    //$mail->addAddress('cristiangramajo015@gmail.com', 'Destino');
    // $mail->Subject = '¡Nueva consulta de un cliente!';
    $mail->Body = formarMail($fn, $fe, $ft, $fc);
    
    // Send email
    $mail->send();

    header('Content-Type: application/json');
    http_response_code(200);
    $json = json_encode(['msg'=>'Consulta generada correctamente']);
    echo $json;
} catch (Exception $e) {
    error_log(print_r($e->getMessage(), true));
    header('Content-Type: application/json');
    http_response_code(500);
    $json = json_encode(['msg'=>'Error al procesar la consulta ó enviar mail: '.$mail->ErrorInfo]);
    echo $json;
}

function ipInRange($ip, $range) {
    list($subnet, $bits) = explode('/', $range);
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $bits);
    return (($ip & $mask) === ($subnet & $mask));
}

function isIPFromArgentina($ip) {
    // List of Argentina's IP ranges (CIDR notation)
    $argentinaRanges = [
        '190.0.0.0/8',
        '200.0.0.0/7',
        '201.0.0.0/8',
        // Add more IP ranges as needed
    ];

    foreach ($argentinaRanges as $range) {
        if (ipInRange($ip, $range)) {
            return true;
        }
    }
    return false;
}
function formarMail($nombre, $email, $telefono, $consulta){
    return '
    <html>
    <head>
        <style>
            .container {
                font-family: Arial, sans-serif;
                padding: 20px;
                border: 1px solid #ddd;
                background-color: #f9f9f9;
            }
            .header {
                background-color: #25704F;
                color: white;
                padding: 24px;
                text-align: center;
                font-size: 32px;
            }
            .brand{
                font-size: 34px;
            }
            .brand-sm{
                font-size: 20px;
            }
            .content {
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                font-size: 12px;
                color: #777;
            }
        </style>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <div class="container">
            <div class="header">
                <p class="brand"><b>Crear Construcciones & Mantenimiento S.A.</b></p>
                <p class="brand-sm"><small><i>CONSTRUCCIÓN Y MANTENIMIENTO INTEGRAL</i></small></p>
                <p><b>¡Nueva consulta de un cliente!</b></p>
            </div>
            <div class="content">
                <p><i>El sistema registró que un usuario cargó una consulta por el formulario de Asesoramiento de la web</i></p>
                <p><i>Los datos ingresados son: </i></p>
                <ul>
                    <li><b>NOMBRE: </b>'.$nombre.'</li>
                    <li><b>EMAIL: </b>'.$email.'</li>
                    <li><b>TELEFONO: </b>'.$telefono.'</li>
                    <li><b>CONSULTA: </b>'.$consulta.'</li>
                </ul>
                <br>
                <p>Consideraciones: </p>
                <p>Si el email ingresado por el usuario es incorrecto puede causar problemas</p>
                <p>El email del usuario no esta en copia (CC) en este mensaje</p>
                <p>Use los datos cargados por el cliente e iniciar la conversación, no continuarla por este mail.</p>
            </div>
            <div class="footer">Esto es un mensaje automático. No responder a este mensaje.</div>
        </div>
    </body>
    </html>
    ';
}
