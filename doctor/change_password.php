<?php
session_start();
require '../config.php';

// Redirect if not logged in as patient
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'doctor') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate inputs
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM doctors WHERE id = ?");
            $stmt->execute([$patient_id]);
            $patient = $stmt->fetch();
            
            if (!$patient || $current_password !== $patient['password']) {
                $error = "Current password is incorrect.";
            } else {
                // Update password (storing in plain text - NOT RECOMMENDED for production)
                $stmt = $pdo->prepare("UPDATE doctors SET password = ? WHERE id = ?");
                $stmt->execute([$new_password, $patient_id]);
                
                $success = "Password changed successfully!";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | MedCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --danger: #ef4444;
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .password-form-container {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        .form-header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
    </style>
</head>
<body>
    <div class="password-form-container">
        <div class="form-header">
            <h2><i class="fas fa-lock me-2"></i>Change Password</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Current Password *</label>
                <input type="password" class="form-control" name="current_password" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">New Password *</label>
                <input type="password" class="form-control" name="new_password" id="newPassword" required>
                <div class="password-strength mt-2">
                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                </div>
                <small class="text-muted">Minimum 8 characters</small>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Confirm New Password *</label>
                <input type="password" class="form-control" name="confirm_password" required>
            </div>
            
            <div class="d-flex justify-content-between">
                <a href="doctor_dashboard.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Change Password
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password strength indicator
        document.getElementById('newPassword').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            let strength = 0;
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            // Update strength bar
            const width = (strength / 5) * 100;
            strengthBar.style.width = width + '%';
            
            // Update color
            if (strength <= 2) {
                strengthBar.style.backgroundColor = '#ef4444'; // red
            } else if (strength <= 4) {
                strengthBar.style.backgroundColor = '#f59e0b'; // yellow
            } else {
                strengthBar.style.backgroundColor = '#10b981'; // green
            }
        });
    </script>
</body>
</html>