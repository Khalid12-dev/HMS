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
$service = null;
$error = '';

// Get service data if ID is provided and valid
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $service_id = intval($_GET['id']);
    $sql = "SELECT * FROM services WHERE service_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $service = $result->fetch_assoc();
    } else {
        $error = "Service not found with ID: $service_id";
    }
    $stmt->close();
} else {
    $error = "Invalid service ID";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
    $service_id = intval($_POST['service_id']);
    $name = $conn->real_escape_string($_POST['name']);
    $department = $conn->real_escape_string($_POST['department']);
    $description = $conn->real_escape_string($_POST['description']);
    $cost = floatval($_POST['cost']);
    $status = $conn->real_escape_string($_POST['status']);
    
    $sql = "UPDATE services SET 
            name = ?,
            department = ?,
            description = ?,
            cost = ?,
            status = ?
            WHERE service_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssdsi", $name, $department, $description, $cost, $status, $service_id);
    
    if ($stmt->execute()) {
        header("Location: Hospital Management.php");
        exit();
    } else {
        $error = "Error updating service: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $service ? "Edit ".htmlspecialchars($service['name']) : "Edit Service"; ?> - MediCare Pro HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container py-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4><i class="fas fa-procedures me-2"></i><?php echo $service ? "Edit ".htmlspecialchars($service['name']) : "Edit Service"; ?></h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                            <a href="hospital_management.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Services
                            </a>
                        <?php elseif ($service): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="service_id" value="<?php echo $service['service_id']; ?>">
                            
                            <div class="mb-3">
                                <label for="serviceName" class="form-label">Service Name *</label>
                                <input type="text" class="form-control" id="serviceName" name="name" 
                                       value="<?php echo htmlspecialchars($service['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="serviceDepartment" class="form-label">Department *</label>
                                <select class="form-select" id="serviceDepartment" name="department" required>
                                    <option value="Cardiology" <?= $service['department'] == 'Cardiology' ? 'selected' : '' ?>>Cardiology</option>
                                    <option value="Neurology" <?= $service['department'] == 'Neurology' ? 'selected' : '' ?>>Neurology</option>
                                    <option value="Emergency" <?= $service['department'] == 'Emergency' ? 'selected' : '' ?>>Emergency</option>
                                    <option value="Surgery" <?= $service['department'] == 'Surgery' ? 'selected' : '' ?>>Surgery</option>
                                    <option value="Pediatrics" <?= $service['department'] == 'Pediatrics' ? 'selected' : '' ?>>Pediatrics</option>
                                    <option value="Radiology" <?= $service['department'] == 'Radiology' ? 'selected' : '' ?>>Radiology</option>
                                    <option value="Laboratory" <?= $service['department'] == 'Laboratory' ? 'selected' : '' ?>>Laboratory</option>
                                    <option value="Pharmacy" <?= $service['department'] == 'Pharmacy' ? 'selected' : '' ?>>Pharmacy</option>
                                    <option value="Physiotherapy" <?= $service['department'] == 'Physiotherapy' ? 'selected' : '' ?>>Physiotherapy</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="serviceDescription" class="form-label">Description</label>
                                <textarea class="form-control" id="serviceDescription" name="description" rows="3"><?php echo htmlspecialchars($service['description']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="serviceCost" class="form-label">Cost *</label>
                                <input type="number" class="form-control" id="serviceCost" name="cost" 
                                       value="<?php echo htmlspecialchars($service['cost']); ?>" step="0.01" min="0" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="serviceStatus" class="form-label">Status *</label>
                                <select class="form-select" id="serviceStatus" name="status" required>
                                    <option value="Active" <?= $service['status'] == 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $service['status'] == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="Hospital Management.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </a>
                                <button type="submit" name="update_service" class="btn btn-success">
                                    <i class="fas fa-save me-1"></i> Update Service
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