<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php'; // Composer autoload

/**
 * Send Disciplinary Action Email
 * 
 * @param string $toEmail   Student email
 * @param string $fullName  Student full name
 * @param string $actionType Action type (Warning, Suspension, etc.)
 * @param string $description Action description
 * @param string $actionDate Action date
 * @param string $operation Operation type: add, edit, delete
 * @return bool
 */
function sendDAEmail($toEmail, $fullName, $actionType, $description, $actionDate, $operation = 'add') {
    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'scsvmvstaffportal@gmail.com';
        $mail->Password   = 'xjyo byvm sdwq yqmj'; // <-- your app password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Sender & recipient
        $mail->setFrom('scsvmvstaffportal@gmail.com', 'SCSVMV Staff Portal');
        $mail->addAddress($toEmail, $fullName);

        // Embed university logo
        $logoPath = __DIR__ . '/../assets/img/logo.png';
        if (file_exists($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'unilogo');
        }

        // Set email subject based on operation
        $subject = match($operation) {
            'add'    => 'New Disciplinary Action Recorded',
            'edit'   => 'Disciplinary Action Updated',
            'delete' => 'Disciplinary Action Removed',
            default  => 'Disciplinary Action Notification'
        };
        $mail->Subject = $subject;

        // Email body text
        $operationText = match($operation) {
            'add'    => 'A new disciplinary action has been recorded against your record.',
            'edit'   => 'Your disciplinary action has been updated. Please see the updated details below.',
            'delete' => 'A disciplinary action has been removed from your record.',
            default  => ''
        };

        // HTML email content
        $mail->isHTML(true);
        $mail->Body = '
        <div style="font-family:Arial, sans-serif; color:#333;">
            <div style="text-align:center; margin-bottom:20px;">
                '. (file_exists($logoPath) ? '<img src="cid:unilogo" alt="University Logo" style="width:120px;">' : '') .'
                <h2 style="margin:10px 0 0 0; color:#0056b3;">Sri Chandrasekharendra Saraswathi Viswa Mahavidyalaya</h2>
            </div>
            <p>Dear <strong>'.htmlspecialchars($fullName).'</strong>,</p>
            <p>'.$operationText.'</p>
            <table style="width:100%; border-collapse: collapse; margin: 20px 0;">
                <tr>
                    <td style="padding:8px; border:1px solid #ccc;"><strong>Action Type</strong></td>
                    <td style="padding:8px; border:1px solid #ccc;">'.htmlspecialchars($actionType).'</td>
                </tr>
                <tr>
                    <td style="padding:8px; border:1px solid #ccc;"><strong>Description</strong></td>
                    <td style="padding:8px; border:1px solid #ccc;">'.nl2br(htmlspecialchars($description)).'</td>
                </tr>
                <tr>
                    <td style="padding:8px; border:1px solid #ccc;"><strong>Action Date</strong></td>
                    <td style="padding:8px; border:1px solid #ccc;">'.htmlspecialchars($actionDate).'</td>
                </tr>
            </table>
            <p>Please contact your HOD if you have any questions.</p>
            <p>Regards,<br><strong>SCSVMV Administration</strong></p>
            <hr style="border:none; border-top:1px solid #ccc; margin:20px 0;">
            <p style="font-size:12px; color:#777; text-align:center;">
                This is an automated message from the University Student Management System.
            </p>
        </div>
        ';

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}
