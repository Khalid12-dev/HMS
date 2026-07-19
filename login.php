<?php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

session_start();
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        try {
            switch ($user_type) {
                case 'admin':
                    $table = 'admin';
                    $redirect = 'admin/admin_dashboard.php';
                    $sql = "SELECT admin_id as id, username, password, full_name, email, created_at 
                            FROM $table 
                            WHERE (username = ? OR email = ?) AND password = ?";
                    break;
                    
                case 'doctor':
                    $table = 'doctors';
                    $redirect = 'doctor/doctor_dashboard.php';
                    $sql = "SELECT id, name as full_name, email, password, specialization, phone, created_at
                            FROM $table 
                            WHERE email = ? AND password = ?";
                    break;
                    
                case 'patient':
                    $table = 'patient';
                    $redirect = 'patient/patient_dashboard.php';
                    $sql = "SELECT patient_id as id, first_name, last_name, email, password, phone, address, gender, created_at
                            FROM $table 
                            WHERE email = ? AND password = ?";
                    break;
                    
                case 'staff':
                    $table = 'staff';
                    $redirect = 'staff/staff_dashboard.php';
                    $sql = "SELECT staff_id as id, first_name, last_name, email, password, phone, role, department, status, created_at
                            FROM $table 
                            WHERE email = ? AND password = ?";
                    break;
            }

            $stmt = $pdo->prepare($sql);
            
            if ($user_type == 'admin') {
                $stmt->execute([$username, $username, $password]);
            } else {
                $stmt->execute([$username, $password]);
            }
            
            $user = $stmt->fetch();

            if ($user) {
                // Store all user data in session
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'type' => $user_type,
                    'username' => $user['username'] ?? ($user['email'] ?? ''),
                    'email' => $user['email'] ?? '',
                    'full_name' => $user_type === 'patient' || $user_type === 'staff'
                        ? $user['first_name'].' '.$user['last_name'] 
                        : $user['full_name'],
                    'created_at' => $user['created_at'] ?? null
                ];
                
                // Store additional user-specific data
                if ($user_type === 'admin') {
                    $_SESSION['user']['is_admin'] = true;
                } 
                elseif ($user_type === 'doctor') {
                    $_SESSION['user']['specialization'] = $user['specialization'] ?? '';
                    $_SESSION['user']['phone'] = $user['phone'] ?? '';
                } 
                elseif ($user_type === 'patient') {
                    $_SESSION['user']['first_name'] = $user['first_name'] ?? '';
                    $_SESSION['user']['last_name'] = $user['last_name'] ?? '';
                    $_SESSION['user']['phone'] = $user['phone'] ?? '';
                    $_SESSION['user']['address'] = $user['address'] ?? '';
                    $_SESSION['user']['gender'] = $user['gender'] ?? '';
                    $_SESSION['user']['blood_group'] = $user['blood_group'] ?? '';
                }
                elseif ($user_type === 'staff') {
                    $_SESSION['user']['first_name'] = $user['first_name'] ?? '';
                    $_SESSION['user']['last_name'] = $user['last_name'] ?? '';
                    $_SESSION['user']['phone'] = $user['phone'] ?? '';
                    $_SESSION['user']['role'] = $user['role'] ?? '';
                    $_SESSION['user']['department'] = $user['department'] ?? '';
                    $_SESSION['user']['status'] = $user['status'] ?? '';
                }
                
                header("Location: $redirect");
                exit();
            } else {
                $error = "Invalid credentials";
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
    <title>Login - MediCare Pro HMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --admin-color: #7c3aed;
            --doctor-color: #f59e0b;
            --patient-color: #14b8a6;
            --staff-color: #10b981;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8fafc;
        }
        .login-container {
            max-width: 500px;
            margin: 5rem auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .login-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        .login-body {
            background-color: white;
            padding: 2rem;
        }
        .user-type-tabs .nav-link {
            color: #64748b;
            font-weight: 500;
            border: none;
            padding: 0.75rem 1rem;
            margin-right: 0.5rem;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        .user-type-tabs .nav-link.active {
            color: var(--primary-color);
            background-color: rgba(37, 99, 235, 0.1);
            font-weight: 600;
        }
        .btn-login {
            padding: 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            width: 100%;
            border: none;
            transition: all 0.3s;
        }
        .btn-admin { background-color: var(--admin-color); color: white; }
        .btn-doctor { background-color: var(--doctor-color); color: white; }
        .btn-patient { background-color: var(--patient-color); color: white; }
        .btn-staff { background-color: var(--staff-color); color: white; }
        .btn-admin:hover { background-color: #6d28d9; }
        .btn-doctor:hover { background-color: #e67e22; }
        .btn-patient:hover { background-color: #12a394; }
        .btn-staff:hover { background-color: #0d9c6e; }
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background-color: #f8f9fa;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .back-button:hover {
            background-color: #e9ecef;
            transform: translateX(-3px);
        }
        .back-button i {
            color: #495057;
            font-size: 1.2rem;
        }
        body {
            position: relative;
        }
        .login-container {
            position: relative;
            max-width: 500px;
            margin: 5rem auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-hospital me-2"></i>MediCare Pro HMS</h2>
            <p class="mb-0">Hospital Management System Login</p>
        </div>
        <button class="back-button" onclick="window.location.href='index.php'">
            <i class="fas fa-arrow-left"></i>
        </button>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <ul class="nav nav-tabs user-type-tabs mb-4" id="userTypeTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#adminLogin">
                        <i class="fas fa-user-shield me-1"></i> Admin
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#doctorLogin">
                        <i class="fas fa-user-md me-1"></i> Doctor
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#patientLogin">
                        <i class="fas fa-user-injured me-1"></i> Patient
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#staffLogin">
                        <i class="fas fa-user-nurse me-1"></i> Staff
                    </a>
                </li>
            </ul>
            
            <div class="tab-content">
                <!-- Admin Login -->
                <div class="tab-pane fade show active" id="adminLogin">
                    <form method="POST" action="login.php">
                        <input type="hidden" name="user_type" value="admin">
                        <div class="mb-3">
                            <label for="adminUsername" class="form-label">Admin Username/Email</label>
                            <input type="text" class="form-control" id="adminUsername" name="username" placeholder="Enter username or email" required>
                        </div>
                        <div class="mb-3">
                            <label for="adminPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="adminPassword" name="password" placeholder="Enter password" required>
                        </div>
                        <button type="submit" class="btn btn-login btn-admin mb-3">
                            <i class="fas fa-lock me-1"></i> Login as Admin
                        </button>
                    </form>
                </div>
                
                <!-- Doctor Login -->
                <div class="tab-pane fade" id="doctorLogin">
                    <form method="POST" action="login.php">
                        <input type="hidden" name="user_type" value="doctor">
                        <div class="mb-3">
                            <label for="doctorUsername" class="form-label">Doctor Email</label>
                            <input type="email" class="form-control" id="doctorUsername" name="username" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                            <label for="doctorPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="doctorPassword" name="password" placeholder="Enter password" required>
                        </div>
                        <button type="submit" class="btn btn-login btn-doctor mb-3">
                            <i class="fas fa-stethoscope me-1"></i> Login as Doctor
                        </button>
                    </form>
                </div>
                
                <!-- Patient Login -->
                <div class="tab-pane fade" id="patientLogin">
                    <form method="POST" action="login.php">
                        <input type="hidden" name="user_type" value="patient">
                        <div class="mb-3">
                            <label for="patientUsername" class="form-label">Patient Email</label>
                            <input type="email" class="form-control" id="patientUsername" name="username" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                            <label for="patientPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="patientPassword" name="password" placeholder="Enter password" required>
                        </div>
                        <div class="mb-3">
                           <a href="forget_password.php">Forget password</a>
                        </div>
                        <button type="submit" class="btn btn-login btn-patient mb-3">
                            <i class="fas fa-user me-1"></i> Login as Patient
                        </button>
                        <div class="text-center">
                            <p class="small mb-0">Don't have an account? <a href="register.php" class="text-decoration-none">Register here</a></p>
                        </div>
                    </form>
                </div>
                
                <!-- Staff Login -->
                <div class="tab-pane fade" id="staffLogin">
                    <form method="POST" action="login.php">
                        <input type="hidden" name="user_type" value="staff">
                        <div class="mb-3">
                            <label for="staffUsername" class="form-label">Staff Email</label>
                            <input type="email" class="form-control" id="staffUsername" name="username" placeholder="Enter your email" required>
                        </div>
                        <div class="mb-3">
                            <label for="staffPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="staffPassword" name="password" placeholder="Enter password" required>
                        </div>
                        <button type="submit" class="btn btn-login btn-staff mb-3">
                            <i class="fas fa-user-nurse me-1"></i> Login as Staff
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            history.pushState(null, null, document.URL);
        });
    </script>
</body>
</html>