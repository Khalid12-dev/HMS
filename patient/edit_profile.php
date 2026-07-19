<?php
session_start();
require '../config.php';

// Redirect if not logged in as patient
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

$patient_id = $_SESSION['user']['id'];
$error = '';
$success = '';

// Get current patient data
try {
    $stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
    
    if (!$patient) {
        die("Patient not found.");
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);

    // Validate inputs
    if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($dob)) {
        $error = "Please fill all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        try {
            // Check if email is already taken by another patient
            $stmt = $pdo->prepare("SELECT patient_id FROM patient WHERE email = ? AND patient_id != ?");
            $stmt->execute([$email, $patient_id]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Email already in use by another account.";
            } else {
                // Update patient record
                $stmt = $pdo->prepare("
                    UPDATE patient SET 
                    first_name = ?, 
                    last_name = ?, 
                    email = ?, 
                    phone = ?, 
                    dob = ?, 
                    gender = ?, 
                    address = ? 
                    WHERE patient_id = ?
                ");
                $stmt->execute([
                    $first_name, 
                    $last_name, 
                    $email, 
                    $phone, 
                    $dob, 
                    $gender, 
                    $address, 
                    $patient_id
                ]);
                
                $success = "Profile updated successfully!";
                // Refresh patient data
                $stmt = $pdo->prepare("SELECT * FROM patient WHERE patient_id = ?");
                $stmt->execute([$patient_id]);
                $patient = $stmt->fetch();
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
        }
        
        body {
            background-color: #f8f9fa;
        }
        
        .profile-form-container {
            max-width: 800px;
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
    </style>
</head>
<body>
    <div class="profile-form-container">
        <div class="form-header">
            <h2><i class="fas fa-user-edit me-2"></i>Edit Profile</h2>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">First Name *</label>
                    <input type="text" class="form-control" name="first_name" 
                           value="<?= htmlspecialchars($patient['first_name']) ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Last Name *</label>
                    <input type="text" class="form-control" name="last_name" 
                           value="<?= htmlspecialchars($patient['last_name']) ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" name="email" 
                           value="<?= htmlspecialchars($patient['email']) ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone *</label>
                    <input type="tel" class="form-control" name="phone" 
                           value="<?= htmlspecialchars($patient['phone']) ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Date of Birth *</label>
                    <input type="date" class="form-control" name="dob" 
                           value="<?= htmlspecialchars($patient['dob']) ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Gender *</label>
                    <select class="form-select" name="gender" required>
                        <option value="male" <?= $patient['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= $patient['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= $patient['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea class="form-control" name="address" rows="3"><?= htmlspecialchars($patient['address']) ?></textarea>
            </div>
            
            <div class="d-flex justify-content-between mt-4">
                <a href="profile.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>