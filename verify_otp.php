<?php
session_start();
require 'config.php';

// Debugging output
error_log("Session at verify start: " . print_r($_SESSION, true));

if (!isset($_SESSION['otp_sent']) || !isset($_SESSION['reset_phone'])) {
    $_SESSION['error'] = "OTP session expired. Please request a new OTP.";
    header("Location: forget_password.php");
    exit();
}

$error = '';
$phone = $_SESSION['reset_phone'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    
    if (empty($otp)) {
        $error = "Please enter the OTP";
    } else {
        try {
            // Debug before query
            error_log("Verifying OTP: $otp for phone: $phone");
            
            // Get the stored OTP and expiry from database
            $stmt = $pdo->prepare("SELECT reset_token, reset_token_expiry FROM patient WHERE phone = ?");
            $stmt->execute([$phone]);
            $result = $stmt->fetch();
            
            if ($result) {
                $storedOtp = $result['reset_token'];
                $expiry = $result['reset_token_expiry'];
                
                error_log("Stored OTP: $storedOtp, Expiry: $expiry");
                error_log("Current time: " . date('Y-m-d H:i:s'));
                
                // Verify OTP and expiry
                if ($storedOtp == $otp && strtotime($expiry) > time()) {
                    $_SESSION['otp_verified'] = true;
                    error_log("OTP verification successful");
                    header("Location: reset_password.php");
                    exit();
                } else {
                    $error = "Invalid OTP or OTP has expired";
                    error_log("OTP mismatch or expired. Input: $otp, Stored: $storedOtp");
                }
            } else {
                $error = "No OTP found for this number. Please request a new one.";
            }
        } catch (PDOException $e) {
            $error = "System error. Please try again.";
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
    <title>Verify OTP - MediCare Pro HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        .otp-container { max-width: 500px; margin: 5rem auto; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); }
        .otp-header { background-color: #14b8a6; color: white; padding: 1.5rem; text-align: center; }
        .otp-body { background-color: white; padding: 2rem; }
        .btn-verify { background-color: #14b8a6; color: white; padding: 0.75rem; border-radius: 8px; font-weight: 500; width: 100%; border: none; transition: all 0.3s; }
        .btn-verify:hover { background-color: #12a394; color: white; }
    </style>
</head>
<body>
    <div class="otp-container">
        <div class="otp-header">
            <h2><i class="fas fa-shield-alt me-2"></i>Verify OTP</h2>
            <p class="mb-0">Enter the OTP sent to *******<?= substr($phone, -3) ?></p>
        </div>
        <div class="otp-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                    <?php if (isset($_SESSION['debug_otp'])): ?>
                        <div class="mt-2">Debug: Correct OTP is <?= $_SESSION['debug_otp'] ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" autocomplete="off">
                <div class="mb-3">
                    <label for="otp" class="form-label">6-digit OTP</label>
                    <input type="text" class="form-control" id="otp" name="otp" 
                           placeholder="123456" required pattern="\d{6}" maxlength="6"
                           inputmode="numeric">
                </div>
                <button type="submit" class="btn btn-verify mb-3">
                    <i class="fas fa-check-circle me-1"></i> Verify OTP
                </button>
            </form>
        </div>
    </div>
</body>
</html>