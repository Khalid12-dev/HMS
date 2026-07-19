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

// Get bed data if ID is provided
$bed = null;
if (isset($_GET['id'])) {
    $bed_id = $_GET['id'];
    $sql = "SELECT * FROM beds WHERE bed_id = $bed_id";
    $result = $conn->query($sql);
    $bed = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bed'])) {
    $bed_id = $_POST['bed_id'];
    $bed_number = $_POST['bed_number'];
    $ward = $_POST['ward'];
    $bed_type = $_POST['bed_type'];
    $status = $_POST['status'];
    
    $sql = "UPDATE beds SET 
            bed_number = '$bed_number',
            ward = '$ward',
            bed_type = '$bed_type',
            status = '$status'
            WHERE bed_id = $bed_id";
    
    if ($conn->query($sql)) {
        header("Location: Hospital Management.php");
        exit();
    } else {
        $error = "Error updating bed: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bed - MediCare Pro HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container py-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4><i class="fas fa-bed me-2"></i>Edit Bed</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($bed): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="bed_id" value="<?php echo $bed['bed_id']; ?>">
                            
                            <div class="mb-3">
                                <label for="bedNumber" class="form-label">Bed Number *</label>
                                <input type="text" class="form-control" id="bedNumber" name="bed_number" 
                                       value="<?php echo htmlspecialchars($bed['bed_number']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ward" class="form-label">Ward/Unit *</label>
                                <select class="form-select" id="ward" name="ward" required>
                                    <option value="General" <?php echo $bed['ward'] == 'General' ? 'selected' : ''; ?>>General Ward</option>
                                    <option value="ICU" <?php echo $bed['ward'] == 'ICU' ? 'selected' : ''; ?>>ICU</option>
                                    <option value="Emergency" <?php echo $bed['ward'] == 'Emergency' ? 'selected' : ''; ?>>Emergency</option>
                                    <option value="Maternity" <?php echo $bed['ward'] == 'Maternity' ? 'selected' : ''; ?>>Maternity</option>
                                    <option value="Pediatric" <?php echo $bed['ward'] == 'Pediatric' ? 'selected' : ''; ?>>Pediatric</option>
                                    <option value="Surgical" <?php echo $bed['ward'] == 'Surgical' ? 'selected' : ''; ?>>Surgical</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bedType" class="form-label">Bed Type *</label>
                                <select class="form-select" id="bedType" name="bed_type" required>
                                    <option value="Regular" <?php echo $bed['bed_type'] == 'Regular' ? 'selected' : ''; ?>>Regular</option>
                                    <option value="ICU" <?php echo $bed['bed_type'] == 'ICU' ? 'selected' : ''; ?>>ICU</option>
                                    <option value="Ventilator" <?php echo $bed['bed_type'] == 'Ventilator' ? 'selected' : ''; ?>>Ventilator</option>
                                    <option value="Pediatric" <?php echo $bed['bed_type'] == 'Pediatric' ? 'selected' : ''; ?>>Pediatric</option>
                                    <option value="Bariatric" <?php echo $bed['bed_type'] == 'Bariatric' ? 'selected' : ''; ?>>Bariatric</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bedStatus" class="form-label">Status *</label>
                                <select class="form-select" id="bedStatus" name="status" required>
                                    <option value="Available" <?php echo $bed['status'] == 'Available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="Occupied" <?php echo $bed['status'] == 'Occupied' ? 'selected' : ''; ?>>Occupied</option>
                                    <option value="Maintenance" <?php echo $bed['status'] == 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                </select>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="Hospital Management.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Back
                                </a>
                                <button type="submit" name="update_bed" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Bed
                                </button>
                            </div>
                        </form>
                        <?php else: ?>
                            <div class="alert alert-danger">Bed not found!</div>
                            <a href="Hospital Management.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Back to Beds
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>