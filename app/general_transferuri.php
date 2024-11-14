<?php
use PHPMailer\PHPMailer\PHPMailer;

require 'vendor/autoload.php';
require 'encryption_helper.php';

$mysqli = new mysqli("localhost", "username", "password", "database_name");

if ($mysqli->connect_errno) {
    die("Eroare: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
}

$transfer_id = isset($_GET['transfer_id']) ? intval($_GET['transfer_id']) : 0;
$email_type_id = isset($_GET['email_type_id']) ? intval($_GET['email_type_id']) : 0;

if ($transfer_id && $email_type_id) {
    $stmt = $mysqli->prepare("SELECT * FROM rt_transferuri WHERE id = ?");
    $stmt->bind_param("i", $transfer_id);
    $stmt->execute();
    $transfer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($transfer) {
        // all markers from rt_markeri table
        $markerStmt = $mysqli->prepare("SELECT cod FROM rt_markeri");
        $markerStmt->execute();
        $markers = $markerStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $markerStmt->close();

        foreach ($markers as $marker) {
            $markerCode = $marker['cod'];
            if (isset($transfer[$markerCode])) {
                $transfer[$markerCode] = decrypt($transfer[$markerCode]);
            }
        }

        // email template
        $stmt = $mysqli->prepare("SELECT subject, content FROM rt_emails WHERE id = ?");
        $stmt->bind_param("i", $email_type_id);
        $stmt->execute();
        $template = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($template) {
            $subject = $template['subject'];
            $body = $template['content'];

            foreach ($markers as $marker) {
                $markerCode = $marker['cod'];
                $value = isset($transfer[$markerCode]) ? $transfer[$markerCode] : '';
                $subject = str_replace($markerCode, $value, $subject);
                $body = str_replace($markerCode, $value, $body);
            }

            // Send email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.travis.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'sergiu@travis.com';
                $mail->Password = 'your_password';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;

                $mail->setFrom('sergiu@travis.com', 'Sergiu');
                $mail->addAddress($transfer['email']);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $body;

                $mail->send();
                echo json_encode(['status' => 'success', 'message' => 'Email sent.']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Email template not found.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Transfer not found.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid parameters.']);
}

$mysqli->close();
?>
