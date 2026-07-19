<?php
session_start();
require 'config.php';

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData = [
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'email' => filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL),
        'phone' => trim($_POST['phone']),
        'dob' => $_POST['dob'],
        'gender' => $_POST['gender'],
        'address' => trim($_POST['address']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password']
    ];

    // Validate inputs
    if (empty($formData['first_name']) || empty($formData['last_name'])) {
        $errors[] = "First name and last name are required";
    }

    if (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }

    if (strlen($formData['password']) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }

    if ($formData['password'] !== $formData['confirm_password']) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        try {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT email FROM patient WHERE email = ?");
            $stmt->execute([$formData['email']]);
            
            if ($stmt->fetch()) {
                $errors[] = "Email already registered";
            } else {
                // Store plain text password (NOT RECOMMENDED FOR PRODUCTION)
                $plainPassword = $formData['password'];
                
                // Insert patient with plain text password
                $stmt = $pdo->prepare("INSERT INTO patient 
                    (first_name, last_name, email, phone, dob, gender, address, password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $formData['first_name'],
                    $formData['last_name'],
                    $formData['email'],
                    $formData['phone'],
                    $formData['dob'],
                    $formData['gender'],
                    $formData['address'],
                    $plainPassword  // Storing plain text password
                ]);
                
                // Registration success
                $_SESSION['registration_success'] = true;
                header("Location: login.php");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration - MediCare Pro HMS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* [Previous CSS remains exactly the same] */
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1d4ed8;
            --patient-color: #14b8a6;
        }
        
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .registration-container {
            max-width: 800px;
            margin: 50px auto;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .registration-header {
            background-color: var(--patient-color);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .registration-body {
            background-color: white;
            padding: 30px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .section-title {
            color: var(--patient-color);
            margin-bottom: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .section-title i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        
        .btn-register {
            background-color: var(--patient-color);
            border: none;
            padding: 12px;
            font-weight: 500;
            width: 100%;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-register:hover {
            background-color: #12a394;
            transform: translateY(-2px);
        }
        
        .form-control, .form-select {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--patient-color);
            box-shadow: 0 0 0 0.25rem rgba(20, 184, 166, 0.25);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #64748b;
        }
        
        .login-link {
            color: var(--patient-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-link:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .is-invalid {
            border-color: #dc3545;
        }
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
        
        /* Adjust the login container to accommodate the back button */
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
        @media (max-width: 767.98px) {
            .registration-container {
                margin: 20px auto;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="registration-header">
            <h2><i class="fas fa-user-plus me-2"></i>Patient Registration</h2>
            <p class="mb-0">Create your account to access healthcare services</p>
        </div>
           <button class="back-button" onclick="window.location.href='index.php'">
        <i class="fas fa-arrow-left"></i>
    </button>
        <div class="registration-body">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registrationForm">
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-user-circle"></i> Personal Information
                    </h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control <?php echo isset($errors) && (empty($formData['first_name']) || in_array("First name and last name are required", $errors)) ? 'is-invalid' : ''; ?>" 
                                   id="firstName" name="first_name" 
                                   value="<?php echo htmlspecialchars($formData['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control <?php echo isset($errors) && (empty($formData['last_name']) || in_array("First name and last name are required", $errors)) ? 'is-invalid' : ''; ?>" 
                                   id="lastName" name="last_name" 
                                   value="<?php echo htmlspecialchars($formData['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="dob" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="dob" name="dob" 
                                   value="<?php echo htmlspecialchars($formData['dob'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="" disabled <?php echo empty($formData['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                                <option value="male" <?php echo ($formData['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($formData['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($formData['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Information Section -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-address-book"></i> Contact Information
                    </h4>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control <?php echo isset($errors) && (in_array("Invalid email format", $errors) || in_array("Email already registered", $errors)) ? 'is-invalid' : ''; ?>" 
                               id="email" name="email" 
                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                        <div class="form-text">We'll never share your email with anyone else.</div>
                        <?php if (isset($errors) && in_array("Email already registered", $errors)): ?>
                            <div class="error-message">This email is already registered</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" 
                               value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($formData['address'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Account Security Section -->
                <div class="form-section">
                    <h4 class="section-title">
                        <i class="fas fa-lock"></i> Account Security
                    </h4>
                    <div class="mb-3 position-relative">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control <?php echo isset($errors) && in_array("Password must be at least 8 characters", $errors) ? 'is-invalid' : ''; ?>" 
                               id="password" name="password" required>
                        <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                        <div class="form-text">Minimum 8 characters with at least one number and one letter</div>
                        <?php if (isset($errors) && in_array("Password must be at least 8 characters", $errors)): ?>
                            <div class="error-message">Password must be at least 8 characters</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control <?php echo isset($errors) && in_array("Passwords do not match", $errors) ? 'is-invalid' : ''; ?>" 
                               id="confirmPassword" name="confirm_password" required>
                        <?php if (isset($errors) && in_array("Passwords do not match", $errors)): ?>
                            <div class="error-message">Passwords do not match</div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Terms and Conditions -->
                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="termsCheck" required>
                    <label class="form-check-label" for="termsCheck">
                        I agree to the <a href="#" class="login-link">Terms of Service</a> and <a href="#" class="login-link">Privacy Policy</a>
                    </label>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn-register btn-lg mb-3">
                    <i class="fas fa-user-plus me-2"></i> Register Now
                </button>
                
                <div class="text-center">
                    <p class="mb-0">Already have an account? <a href="login.php" class="login-link">Login here</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Password toggle visibility
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
        
        // Password confirmation validation
        const confirmPassword = document.querySelector('#confirmPassword');
        const passwordMatch = document.querySelector('#passwordMatch');
        
        confirmPassword.addEventListener('input', function() {
            if (this.value !== password.value) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
        
        // Age verification based on date of birth
        const dobInput = document.querySelector('#dob');
        
        dobInput.addEventListener('change', function() {
            const dob = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - dob.getFullYear();
            const monthDiff = today.getMonth() - dob.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                age--;
            }
            
            if (age < 18) {
                alert('You must be at least 18 years old to register');
                this.value = '';
            }
        });
           history.pushState(null, null, document.URL);
        window.addEventListener('popstate', function () {
            history.pushState(null, null, document.URL);
        });
    </script>
</body>
</html>