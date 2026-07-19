<?php
// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "hms");
    if ($conn->connect_error) die("DB Connection Failed");

    $name = $conn->real_escape_string($_POST['name']);
    $specialization = $conn->real_escape_string($_POST['specialization']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $status = $conn->real_escape_string($_POST['status']);
    $password = $conn->real_escape_string($_POST['password']); // plain password

    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $profile_pic = $_FILES["profile_pic"]["name"];
    $tmp_name = $_FILES["profile_pic"]["tmp_name"];
    $unique_name = uniqid() . "_" . basename($profile_pic);
    $target_file = $upload_dir . $unique_name;

    if (move_uploaded_file($tmp_name, $target_file)) {
        $sql = "INSERT INTO doctors (name, specialization, email, phone, profile_pic, password, status)
                VALUES ('$name', '$specialization', '$email', '$phone', '$unique_name', '$password', '$status')";
        
        if ($conn->query($sql)) {
            echo "<script>alert('Doctor added successfully!'); window.location.href='add_doctor.php';</script>";
        } else {
            echo "<script>alert('Database error: " . $conn->error . "');</script>";
        }
    } else {
        echo "<script>alert('File upload failed. Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Doctor - HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --primary-hover: #2e59d9;
            --secondary-color: #f8f9fc;
            --accent-color: #1cc88a;
            --text-color: #5a5c69;
            --border-radius: 0.35rem;
        }
        
        body {
            background-color: var(--secondary-color);
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-color);
        }
        
        .admin-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 15px;
        }
        
        .admin-card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: all 0.3s;
        }
        
        .admin-card:hover {
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.2);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: var(--border-radius) var(--border-radius) 0 0 !important;
            padding: 1rem 1.35rem;
            border-bottom: none;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        
        .form-control, .form-select {
            padding: 0.75rem 1rem;
            border-radius: var(--border-radius);
            border: 1px solid #d1d3e2;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
            transform: translateY(-1px);
        }
        
        .btn-outline-secondary {
            border-color: #d1d3e2;
            color: var(--text-color);
        }
        
        .btn-outline-secondary:hover {
            background-color: #e2e6ea;
            border-color: #d1d3e2;
        }
        
        .preview-image {
            max-width: 150px;
            max-height: 150px;
            border-radius: var(--border-radius);
            margin-top: 10px;
            display: none;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 1.5rem;
        }
        
        @media (max-width: 768px) {
            .admin-container {
                margin: 1rem auto;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="admin-container">
    <div class="admin-card">
        <div class="card-header">
            <h4 class="mb-0"><i class="fas fa-user-md me-2"></i>Add New Doctor</h4>
        </div>
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Doctor Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label">Specialization</label>
                        <input type="text" name="specialization" class="form-control" placeholder="Cardiology, Neurology, etc." required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="doctor@example.com" required>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" placeholder="+92 300 1234567">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label class="form-label">Password</label>
                        <input type="text" name="password" class="form-control" placeholder="Set a password" required>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Profile Picture</label>
                    <input type="file" name="profile_pic" class="form-control" accept="image/*" id="profilePicInput" required>
                    <img id="imagePreview" class="preview-image" src="#" alt="Profile Preview">
                </div>

                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Save Doctor
                    </button>
                    <a href="admin_dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Image preview functionality
    document.getElementById('profilePicInput').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const preview = document.getElementById('imagePreview');
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            
            reader.readAsDataURL(file);
        } else {
            preview.style.display = 'none';
        }
    });
</script>
</body>
</html>