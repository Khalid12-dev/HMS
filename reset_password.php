<?php
session_start();
require 'config.php';

if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
    header("Location: forget_password.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters";
    } else {
        try {
            // Changed to store plain text password (NOT RECOMMENDED FOR PRODUCTION)
            $stmt = $pdo->prepare("UPDATE patient SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE phone = ?");
            $stmt->execute([$password, $_SESSION['reset_phone']]);
            
            session_destroy();
            $success = "Password updated successfully! Redirecting to login...";
            header("Refresh: 2; url=login.php");
        } catch (PDOException $e) {
            $error = "System error. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - MediCare Pro HMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #14b8a6;
            --primary-dark: #12a394;
            --light-bg: #f8fafc;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            color: #333;
        }
        .password-container {
            max-width: 500px;
            margin: 2rem auto;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0,0,0,0.1);
        }
        .password-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }
        .password-header h2 {
            font-weight: 600;
            margin-bottom: 0;
        }
        .password-body {
            background-color: white;
            padding: 2rem;
        }
        .btn-reset {
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 500;
            width: 100%;
            border: none;
            transition: all 0.3s;
            font-size: 1rem;
        }
        .btn-reset:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .form-control {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #ddd;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(20, 184, 166, 0.25);
        }
        .alert {
            border-radius: 8px;
        }
        .password-strength {
            height: 5px;
            background: #eee;
            margin-top: 5px;
            border-radius: 5px;
            overflow: hidden;
        }
        .password-strength span {
            display: block;
            height: 100%;
            transition: all 0.3s;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="password-container">
            <div class="password-header">
                <h2><i class="fas fa-key me-2"></i>Set New Password</h2>
            </div>
            
            <div class="password-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                        <div class="spinner-border spinner-border-sm ms-2"></div>
                    </div>
                <?php else: ?>
                    <form method="POST" id="resetForm">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8" placeholder="At least 8 characters">
                            <div class="password-strength mt-2">
                                <span id="strength-bar"></span>
                            </div>
                            <small class="text-muted">Must contain at least 8 characters</small>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Re-enter your password">
                            <div id="password-match" class="text-danger small mt-1"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-reset">
                            <i class="fas fa-save me-1"></i> Update Password
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Password Strength Checker -->
    <script>
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strength-bar');
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            const colors = ['#ff4d4d', '#ffa64d', '#ffff4d', '#99ff66', '#33cc33'];
            const width = (strength / 5) * 100;
            
            strengthBar.style.width = width + '%';
            strengthBar.style.backgroundColor = colors[strength - 1] || 'transparent';
        });
        
        document.getElementById('confirm_password').addEventListener('input', function() {
            const matchText = document.getElementById('password-match');
            if (this.value !== document.getElementById('password').value) {
                matchText.textContent = 'Passwords do not match';
            } else {
                matchText.textContent = '';
            }
        });
    </script>
</body>
</html>