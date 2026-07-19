<?php
session_start();
require '../config.php';

// Redirect to login if not logged in as admin
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get admin details
$admin_id = $_SESSION['user']['id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE admin_id = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        die("Admin not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | MedCare</title>
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
            padding: 30px;
            text-align: center;
        }
        
        .profile-pic {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            margin-bottom: 15px;
        }
        
        .profile-body {
            padding: 30px;
        }
        
        .detail-card {
            border-left: 4px solid var(--primary);
            padding: 15px;
            margin-bottom: 20px;
            background: var(--gray-100);
            border-radius: 0 8px 8px 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--gray-600);
            margin-bottom: 5px;
        }
        
        .detail-value {
            font-size: 1.1rem;
            color: var(--gray-800);
        }
        
        .edit-btn {
            position: absolute;
            top: 20px;
            right: 20px;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header position-relative">
            <a href="edit.php" class="btn btn-light edit-btn">
                <i class="fas fa-edit"></i> Edit Profile
            </a>
            
            <?php if(!empty($admin['profile_pic'])): ?>
                <img src="../uploads/admin/<?= htmlspecialchars($admin['profile_pic']) ?>" class="profile-pic" alt="Profile Picture">
            <?php else: ?>
                <div class="profile-pic mx-auto bg-secondary d-flex align-items-center justify-content-center">
                    <i class="fas fa-user text-white" style="font-size: 3rem;"></i>
                </div>
            <?php endif; ?>
            
            <h2><?= htmlspecialchars($admin['full_name']) ?></h2>
            <p class="mb-0">Admin since <?= date('F Y', strtotime($admin['created_at'])) ?></p>
        </div>
        
        <div class="profile-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="detail-card">
                        <div class="detail-label">Username</div>
                        <div class="detail-value"><?= htmlspecialchars($admin['username']) ?></div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="detail-card">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value"><?= htmlspecialchars($admin['full_name']) ?></div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="detail-card">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value"><?= htmlspecialchars($admin['email']) ?></div>
                    </div>
                </div>
            </div>
            
            <div class="mt-4 d-flex justify-content-between">
                <a href="admin_dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <a href="password.php" class="btn btn-outline-secondary">
                    <i class="fas fa-lock"></i> Change Password
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>