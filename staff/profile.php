<?php
session_start();

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

require '../config.php';

$staff_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// Get staff profile details
$profile_query = "SELECT * FROM staff WHERE staff_id = ?";
$profile_stmt = $pdo->prepare($profile_query);
$profile_stmt->execute([$staff_id]);
$profile = $profile_stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone)) {
        $error = "Please fill in all required fields";
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();

            // Update basic profile info
            $update_query = "UPDATE staff SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE staff_id = ?";
            $update_stmt = $pdo->prepare($update_query);
            $update_stmt->execute([$first_name, $last_name, $email, $phone, $staff_id]);

            // Handle password change if requested
            if (!empty($current_password)) {
                // Verify current password
                $check_password = "SELECT password FROM staff WHERE staff_id = ?";
                $check_stmt = $pdo->prepare($check_password);
                $check_stmt->execute([$staff_id]);
                $db_password = $check_stmt->fetchColumn();

                if ($db_password !== $current_password) {
                    throw new Exception("Current password is incorrect");
                }

                if (empty($new_password) || empty($confirm_password)) {
                    throw new Exception("New password and confirmation are required");
                }

                if ($new_password !== $confirm_password) {
                    throw new Exception("New passwords do not match");
                }

                // Update password
                $password_query = "UPDATE staff SET password = ? WHERE staff_id = ?";
                $password_stmt = $pdo->prepare($password_query);
                $password_stmt->execute([$new_password, $staff_id]);
            }

            // Commit transaction
            $pdo->commit();

            // Update session data
            $_SESSION['user']['full_name'] = $first_name . ' ' . $last_name;
            $_SESSION['user']['email'] = $email;

            $success = "Profile updated successfully!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error updating profile: " . $e->getMessage();
        }
    }

    // Refresh profile data after update
    $profile_stmt->execute([$staff_id]);
    $profile = $profile_stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - MediCare Pro HMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #10b981;
            --primary-dark: #0d9c6e;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .profile-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            margin-bottom: 1rem;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .password-section {
            border-top: 1px solid #eee;
            padding-top: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .dashboard-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Dashboard Button -->
    <a href="staff_dashboard.php" class="btn btn-primary dashboard-btn">
        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
    </a>

    <div class="profile-container">
        <div class="profile-header">
            <img src="<?php echo !empty($profile['photo']) ? '../admin/uploads/' . $profile['photo'] : '../../assets/default-profile.jpg'; ?>" 
                 alt="Profile Image" class="profile-img">
            <h3><?php echo htmlspecialchars($profile['first_name'] . ' ' . htmlspecialchars($profile['last_name'])); ?></h3>
            <p class="mb-0"><?php echo htmlspecialchars($profile['role']); ?> - <?php echo htmlspecialchars($profile['department']); ?></p>
        </div>
        
        <div class="p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="profile.php">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" 
                               value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" 
                               value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($profile['phone']); ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($profile['role']); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($profile['department']); ?>" readonly>
                    </div>
                </div>
                
                <!-- Password Change Section -->
                <div class="password-section">
                    <h5 class="mb-3">Change Password</h5>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        <div class="col-md-4">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                        </div>
                        <div class="col-md-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    <small class="text-muted">Leave password fields blank if you don't want to change it</small>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>