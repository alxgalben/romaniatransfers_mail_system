<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

// Database connection
$localhost = "localhost";
$username = "root";
$password = "";
$database_name = "address-book";

$mysqli = new mysqli("localhost", "username", "password", "database_name");

// Check connection
if ($mysqli->connect_errno) {
    echo "Eroare: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}

$interval = isset($_GET['interval']) && $_GET['interval'] == '24' ? 24 : 12;

$stmt = $mysqli->prepare("SELECT id, email, data_preluare, ora_preluare, nume, preluare
                          FROM rt_transferuri 
                          WHERE (status != 'confirmed' OR idUser = 0 OR idAuto = 0) 
                          AND CONCAT(data_preluare, ' ', ora_preluare) 
                          BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? HOUR)");

$stmt->bind_param("i", $interval);

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // tabel HTML
    $email_body = "<h2>Transferuri neprocesate</h2>";
    $email_body .= "<table border='1' cellpadding='5'><tr><th>Data Preluare</th><th>Ora Preluare</th><th>Nume Pasager</th><th>Locație Preluare</th></tr>";

    while ($row = $result->fetch_assoc()) {
        $email_body .= "<tr><td>{$row['data_preluare']}</td><td>{$row['ora_preluare']}</td><td>{$row['nume']}</td><td>{$row['preluare']}</td></tr>";
    }
    
    $email_body .= "</table>";

    // Send email
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.example.com';                     
        $mail->SMTPAuth   = true;                                   
        $mail->Username   = 'test@example.com';               
        $mail->Password   = 'your_password';                        
        $mail->SMTPSecure = 'tls';                                  
        $mail->Port       = 587;                                    

        $mail->setFrom('test@example.com', 'Your Name');
        $mail->addAddress('transfer@travis.ro');  // Email where the alerts will be sent

        $mail->isHTML(true);                                  
        $mail->Subject = 'Alerte Transferuri Neprocesate';
        $mail->Body    = $email_body;

        // Send email
        $mail->send();
        
        // Log emails
        $log_stmt = $mysqli->prepare("INSERT INTO email_alerta_neprocesate_log (idTransfer, data, continut) VALUES (?, NOW(), ?)");
        foreach ($result as $row) {
            $log_stmt->bind_param("is", $row['id'], $email_body);
            $log_stmt->execute();
        }

        echo 'Mesajul a fost trimis și logat cu succes.';
    } catch (Exception $e) {
        echo "Mesajul nu a putut fi trimis. Mailer Error: {$mail->ErrorInfo}";
    }

} else {
    echo "Nu există transferuri neprocesate în intervalul specificat.";
}

$stmt->close();
$mysqli->close();
?>
