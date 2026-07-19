<?php
// Enable error reporting (for debugging)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "hms");
if ($conn->connect_error) die("Connection Failed: " . $conn->connect_error);

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid doctor ID.");
}

$id = intval($_GET['id']);

// Fetch doctor data
$result = $conn->query("SELECT * FROM doctors WHERE id = $id");
if ($result->num_rows === 0) {
    die("Doctor not found.");
}
$doctor = $result->fetch_assoc();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $specialization = $conn->real_escape_string($_POST['specialization']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $status = $conn->real_escape_string($_POST['status']);

    $profile_pic = $doctor['profile_pic']; // Default to existing

    // Handle new image upload
    if (!empty($_FILES['profile_pic']['name'])) {
        $upload_dir = "uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $new_name = uniqid() . "_" . basename($_FILES['profile_pic']['name']);
        $target_file = $upload_dir . $new_name;

        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
            $profile_pic = $new_name;
        }
    }

    // Update database
    $sql = "UPDATE doctors SET 
                name='$name',
                specialization='$specialization',
                email='$email',
                phone='$phone',
                profile_pic='$profile_pic',
                status='$status'
            WHERE id=$id";

    if ($conn->query($sql)) {
        echo "<script>alert('Doctor updated successfully!'); window.location='doctors.php';</script>";
        exit;
    } else {
        echo "<script>alert('Update failed: " . $conn->error . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Doctor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --light-bg: #f8f9fa;
        }
        
        body { 
            background: #f5f7fa; 
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }
        
        .form-container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            padding: 30px;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary-color);
            position: relative;
            padding-bottom: 15px;
        }
        
        .form-header:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 25%;
            width: 50%;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--success-color));
            border-radius: 3px;
        }
        
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .profile-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 10px;
            border: 3px solid #e9ecef;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            display: block;
            margin: 10px 0;
        }
        
        .btn-submit {
            background-color: var(--success-color);
            border: none;
            padding: 10px 20px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            background-color: var(--danger-color);
            border: none;
            padding: 10px 20px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .action-buttons .btn {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h3 class="form-header">
            <i class="bi bi-person-badge me-2"></i>Update Doctor Details
        </h3>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label">Doctor Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($doctor['name']) ?>" required>
                </div>

                <div class="col-md-6 mb-4">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($doctor['specialization']) ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($doctor['email']) ?>" required>
                </div>

                <div class="col-md-6 mb-4">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($doctor['phone']) ?>">
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">Current Profile Picture</label>
                <img src="uploads/<?= htmlspecialchars($doctor['profile_pic']) ?>" class="profile-preview" alt="Doctor">
            </div>

            <div class="mb-4">
                <label class="form-label">Change Profile Picture</label>
                <input type="file" name="profile_pic" class="form-control" accept="image/*">
                <small class="text-muted">Leave blank to keep current image</small>
            </div>

            <div class="mb-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="active" <?= $doctor['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $doctor['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>

            <div class="action-buttons">
                <a href="doctors.php" class="btn btn-cancel">
                    <i class="bi bi-x-circle"></i> Cancel
                </a>
                <button type="submit" class="btn btn-submit">
                    <i class="bi bi-check-circle"></i> Update Doctor
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>