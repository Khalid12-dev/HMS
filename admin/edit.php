<?php
session_start();
require '../config.php';

// Redirect if not logged in as admin
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get admin details
$admin_id = $_SESSION['user']['id'];
$error = '';
$success = '';

try {
    // Fetch current admin data
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();

    if (!$admin) {
        die("Admin not found.");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $username = trim($_POST['username']);

        // Basic validation
        if (empty($full_name) || empty($email) || empty($username)) {
            $error = 'All fields are required!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format!';
        } else {
            // Check if email or username already exists (excluding current admin)
            $stmt = $pdo->prepare("SELECT admin_id FROM admin WHERE (email = ? OR username = ?) AND admin_id != ?");
            $stmt->execute([$email, $username, $admin_id]);
            $existing = $stmt->fetch();

            if ($existing) {
                $error = 'Email or username already exists!';
            } else {
                // Update profile
                $stmt = $pdo->prepare("UPDATE admin SET full_name = ?, email = ?, username = ? WHERE admin_id = ?");
                if ($stmt->execute([$full_name, $email, $username, $admin_id])) {
                    $success = 'Profile updated successfully!';
                    // Update session data
                    $_SESSION['user']['username'] = $username;
                    $_SESSION['user']['email'] = $email;
                    // Refresh admin data
                    $stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_id = ?");
                    $stmt->execute([$admin_id]);
                    $admin = $stmt->fetch();
                } else {
                    $error = 'Failed to update profile!';
                }
            }
        }
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile | MedCare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --secondary: #10b981;
            --danger: #ef4444;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
        }
        
        body {
            background-color: var(--gray-100);
        }
        
        .profile-container {
            max-width: 800px;
            margin: 30px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .profile-body {
            padding: 30px;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25);
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h2>Edit Profile</h2>
        </div>
        
        <div class="profile-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="edit.php">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= htmlspecialchars($admin['username']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?= htmlspecialchars($admin['full_name']) ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($admin['email']) ?>" required>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="profile.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>