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
$ip = $_SERVER['REMOTE_ADDR']; // Get client IP
//$nombre, $email, $telefono, $consulta
//$mail->SMTPDebug = 2; // Enable debugging (3 for more details)
//$mail->Debugoutput = 'html'; // Display debug output in the browser
try {
    if(is_null($fn) || is_null($fe) || is_null($ft) || is_null($fc)){
        throw new Exception("Los datos del formulario no pueden venir vacios", 400);
    }
    if($fn == "" || $fe == "" || $ft == "" || $fc == ""){
        throw new Exception("Los datos del formulario no pueden venir vacios", 400);
    }
    if (isIPFromArgentina($ip) == false) {
        throw new Exception("La dirección IP no proviene de ARG, si tiene VPN desactivelo", 400);
    }

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

    // Email Details
    $mail->setFrom('tickets@correoflash.com', 'Mantenor notificaciones');
    $mail->addAddress('<destination@something.com>', 'Destino');
    $mail->Subject = '¡Nueva consulta de un cliente!';
    $mail->Body = formarMail($fn, $fe, $ft, $fc);
    
    // Send email
    $mail->send();

    header('Content-Type: application/json');
    http_response_code(200);
    $json = json_encode(['msg'=>'Consulta generada correctamente']);
    echo $json;
} catch (Exception $e) {
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
                background-color: #E10719;
                color: white;
                padding: 24px;
                text-align: center;
                font-size: 32px;
            }
            .brand{
                font-size: 44px;
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
                <p class="brand"><b>MANTENOR</b></p>
                <p class="brand-sm"><small><i>CONSTRUCCIÓN Y MANTENIMIENTO INTEGRAL</i></small></p>
                <p><b>¡Nueva consulta de un cliente!</b></p>
            </div>
            <div class="content">
                <p><i>El sistema registró que un usuario a cargdo una consulta por el formulario de Asesoramiento de la web</i></p>
                <p><i>Los datos ingresados son: </i></p>
                <br>
                <ul>
                    <li><b>NOMBRE: </b>'.$nombre.'</li>
                    <li><b>EMAIL: </b>'.$email.'</li>
                    <li><b>TELEFONO: </b>'.$telefono.'</li>
                    <li><b>CONSULTA: </b>'.$consulta.'</li>
                </ul>
                <p>Consideraciones: </p>
                <p>Si por algún motivo el mail ingresado por el usuario es incorrecto puede causar problemas</p>
                <p>El mail del usuario no esta en copia (CC) en este mensaje</p>
                <p>Recomendamos iniciar tomar los datos cargados por el cliente e iniciar una nueva conversación, no continuarla por este mail.</p>
            </div>
            <div class="footer">Esto es un mensaje automático. No responder a este mensaje.</div>
        </div>
    </body>
    </html>
    ';
}
