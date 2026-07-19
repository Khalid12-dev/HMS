<?php
include '../config.php';

try {
    // Fetch upcoming appointments (within next 24 hours)
    $stmt = $pdo->prepare("SELECT a.*,  doctor_name,  patient_email 
                          FROM appointments a
                          JOIN doctors d ON a.doctor_id = d.id
                          JOIN patients p ON patient_id = patient_id
                          WHERE a.appointment_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)");
    $stmt->execute();
    $appointments = $stmt->fetchAll();

    if (count($appointments) === 0) {
        die("No upcoming appointments found in the next 24 hours.");
    }

    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: Hospital Reminders <no-reply@hospital.com>',
        'Reply-To: appointments@hospital.com',
        'X-Mailer: PHP/' . phpversion()
    ];

    $count = 0;
    foreach ($appointments as $appointment) {
        $to_email = filter_var($appointment['patient_email'], FILTER_SANITIZE_EMAIL);
        $subject = "Appointment Reminder: Dr. " . htmlspecialchars($appointment['doctor_name']);
        
        // HTML email template
        $message = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #667eea; color: white; padding: 15px; text-align: center; }
                .content { padding: 20px; background-color: #f8f9fa; }
                .footer { margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Appointment Reminder</h2>
                </div>
                <div class="content">
                    <p>Dear Patient,</p>
                    <p>This is a reminder for your appointment with <strong>Dr. ' . htmlspecialchars($appointment['doctor_name']) . '</strong>.</p>
                    <p><strong>Scheduled Time:</strong> ' . date('l, F j, Y \a\t g:i A', strtotime($appointment['appointment_date'])) . '</p>
                    <p>Please arrive 15 minutes before your scheduled time.</p>
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' Hospital Name. All rights reserved.</p>
                    <p>If you need to reschedule, please contact us at appointments@hospital.com</p>
                </div>
            </div>
        </body>
        </html>';

        // Send email
        if (filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
            $mailSent = mail(
                $to_email,
                $subject,
                $message,
                implode("\r\n", $headers)
            );

            if ($mailSent) {
                $count++;
                error_log("Reminder sent to: $to_email");
            } else {
                error_log("Failed to send reminder to: $to_email");
            }
        } else {
            error_log("Invalid email address: $to_email");
        }
    }

    echo "Successfully sent $count appointment reminders.";
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred while processing appointments. Please try again later.");
}