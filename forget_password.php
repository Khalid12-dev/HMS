<?php
session_start();
require 'config.php';
require 'sms_gateway.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = trim($_POST['phone']);
    
    if (empty($phone)) {
        $error = "Please enter your phone number";
    } elseif (!preg_match('/^03\d{9}$/', $phone)) {
        $error = "Please enter a valid 11-digit phone number (e.g. 03001234567)";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT patient_id, first_name FROM patient WHERE phone = ?");
            $stmt->execute([$phone]);
            $patient = $stmt->fetch();
            
            if ($patient) {
                $otp = rand(100000, 999999);
                $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                $stmt = $pdo->prepare("UPDATE patient SET reset_token = ?, reset_token_expiry = ? WHERE phone = ?");
                $stmt->execute([$otp, $expiry, $phone]);
                
                $message = "Your MediCare Pro OTP is: $otp. Valid for 10 minutes.";
                
                if (sendSMS($phone, $message)) {
                    $_SESSION['otp_sent'] = true;
                    $_SESSION['reset_phone'] = $phone;
                    $_SESSION['otp_attempts'] = 0;
                    $_SESSION['debug_otp'] = $otp; // For testing
                    
                    // Debug before redirect
                    error_log("Redirecting to verify_otp.php");
                    header("Location: verify_otp.php");
                    exit();
                } else {
                    $error = "Failed to send OTP. Please try again later.";
                }
            } else {
                $success = "If this number is registered, you'll receive an OTP shortly.";
            }
        } catch (PDOException $e) {
            $error = "System error. Please try again later.";
            error_log("Database error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MediCare Pro HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        .password-container { max-width: 500px; margin: 5rem auto; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .password-header { background-color: #14b8a6; color: white; padding: 1.5rem; text-align: center; }
        .password-body { background-color: white; padding: 2rem; }
        .btn-reset { background-color: #14b8a6; color: white; padding: 0.75rem; border-radius: 8px; font-weight: 500; width: 100%; border: none; transition: all 0.3s; }
        .btn-reset:hover { background-color: #12a394; color: white; }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-header">
            <h2><i class="fas fa-lock me-2"></i>Reset Your Password</h2>
            <p class="mb-0">MediCare Pro HMS Patient Portal</p>
        </div>
        <div class="password-body">
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php else: ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="03001234567" required pattern="03\d{9}">
                        <div class="form-text">Enter your 11-digit phone number (e.g. 03001234567)</div>
                    </div>
                    <button type="submit" class="btn btn-reset mb-3">
                        <i class="fas fa-mobile-alt me-1"></i> Send OTP via SMS
                    </button>
                </form>
            <?php endif; ?>
            <div class="text-center mt-3">
                <a href="login.php" class="text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>