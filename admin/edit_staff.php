<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "hms";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$staff = null;
$error = '';

// Get staff data if ID is provided and valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $staff_id = intval($_GET['id']);
    $sql = "SELECT * FROM staff WHERE staff_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $staff_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $staff = $result->fetch_assoc();
    } else {
        $error = "Staff member not found with ID: $staff_id";
    }
    $stmt->close();
} else {
    $error = "Invalid staff ID";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $staff_id = intval($_POST['staff_id']);
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $role = $conn->real_escape_string($_POST['role']);
    $department = $conn->real_escape_string($_POST['department']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $sql = "UPDATE staff SET 
            first_name = ?,
            last_name = ?,
            role = ?,
            department = ?,
            email = ?,
            phone = ?,
            status = ?
            WHERE staff_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssi", $first_name, $last_name, $role, $department, $email, $phone, $status, $staff_id);
    
    if ($stmt->execute()) {
        header("Location: Hospital Management.php");
        exit();
    } else {
        $error = "Error updating staff: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> - MediCare Pro HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container py-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-user-md me-2"></i><?php echo $staff ? "Edit ".htmlspecialchars($staff['first_name'])." ".htmlspecialchars($staff['last_name']) : "Edit Staff"; ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                            <a href="hospital_management.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Staff
                            </a>
                        <?php elseif ($staff): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="staff_id" value="<?php echo $staff['staff_id']; ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="staffFirstName" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="staffFirstName" name="first_name" 
                                           value="<?php echo htmlspecialchars($staff['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="staffLastName" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="staffLastName" name="last_name" 
                                           value="<?php echo htmlspecialchars($staff['last_name']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="staffRole" class="form-label">Role *</label>
                                    <select class="form-select" id="staffRole" name="role" required>
                                        <option value="Doctor" <?= $staff['role'] == 'Doctor' ? 'selected' : '' ?>>Doctor</option>
                                        <option value="Nurse" <?= $staff['role'] == 'Nurse' ? 'selected' : '' ?>>Nurse</option>
                                        <option value="Technician" <?= $staff['role'] == 'Technician' ? 'selected' : '' ?>>Technician</option>
                                        <option value="Administrator" <?= $staff['role'] == 'Administrator' ? 'selected' : '' ?>>Administrator</option>
                                        <option value="Receptionist" <?= $staff['role'] == 'Receptionist' ? 'selected' : '' ?>>Receptionist</option>
                                        <option value="Pharmacist" <?= $staff['role'] == 'Pharmacist' ? 'selected' : '' ?>>Pharmacist</option>
                                        <option value="Therapist" <?= $staff['role'] == 'Therapist' ? 'selected' : '' ?>>Therapist</option>
                                        <option value="Other" <?= $staff['role'] == 'Other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="staffDepartment" class="form-label">Department *</label>
                                    <select class="form-select" id="staffDepartment" name="department" required>
                                        <option value="Cardiology" <?= $staff['department'] == 'Cardiology' ? 'selected' : '' ?>>Cardiology</option>
                                        <option value="Neurology" <?= $staff['department'] == 'Neurology' ? 'selected' : '' ?>>Neurology</option>
                                        <option value="Emergency" <?= $staff['department'] == 'Emergency' ? 'selected' : '' ?>>Emergency</option>
                                        <option value="Surgery" <?= $staff['department'] == 'Surgery' ? 'selected' : '' ?>>Surgery</option>
                                        <option value="Pediatrics" <?= $staff['department'] == 'Pediatrics' ? 'selected' : '' ?>>Pediatrics</option>
                                        <option value="Radiology" <?= $staff['department'] == 'Radiology' ? 'selected' : '' ?>>Radiology</option>
                                        <option value="Laboratory" <?= $staff['department'] == 'Laboratory' ? 'selected' : '' ?>>Laboratory</option>
                                        <option value="Pharmacy" <?= $staff['department'] == 'Pharmacy' ? 'selected' : '' ?>>Pharmacy</option>
                                        <option value="Physiotherapy" <?= $staff['department'] == 'Physiotherapy' ? 'selected' : '' ?>>Physiotherapy</option>
                                        <option value="Administration" <?= $staff['department'] == 'Administration' ? 'selected' : '' ?>>Administration</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="staffEmail" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="staffEmail" name="email" 
                                           value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="staffPhone" class="form-label">Phone *</label>
                                    <input type="tel" class="form-control" id="staffPhone" name="phone" 
                                           value="<?php echo htmlspecialchars($staff['phone']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="staffStatus" class="form-label">Status *</label>
                                <select class="form-select" id="staffStatus" name="status" required>
                                    <option value="Active" <?= $staff['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="On Leave" <?= $staff['status'] == 'On Leave' ? 'selected' : '' ?>>On Leave</option>
                                    <option value="Inactive" <?= $staff['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="Hospital Management.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </a>
                                <button type="submit" name="update_staff" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Staff
                                </button>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>