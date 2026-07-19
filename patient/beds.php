<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'hms';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize messages
$bedMessage = '';

// Handle Bed Request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_bed'])) {
    // Prepare and sanitize inputs
    $patientName = $conn->real_escape_string($_POST['patient_name']);
    $patientAge = (int)$_POST['patient_age'];
    $patientGender = $conn->real_escape_string($_POST['patient_gender']);
    $ward = $conn->real_escape_string($_POST['ward']);
    $contact = $conn->real_escape_string($_POST['contact']);
    
    // Check if there are available beds in the selected ward
    $availableBed = $conn->query("SELECT bed_id FROM beds WHERE ward = '$ward' AND status = 'Available' LIMIT 1");
    
    if ($availableBed && $availableBed->num_rows > 0) {
        $bed = $availableBed->fetch_assoc();
        $bedId = $bed['bed_id'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert into patient_requests
            $insertRequest = $conn->prepare("INSERT INTO patient_requests (patient_name, patient_age, patient_gender, ward, contact) 
                                          VALUES (?, ?, ?, ?, ?)");
            $insertRequest->bind_param("sisss", $patientName, $patientAge, $patientGender, $ward, $contact);
            $insertRequest->execute();
            $requestId = $insertRequest->insert_id;
            
            // Update bed status
            $updateBed = $conn->prepare("UPDATE beds SET status = 'Occupied', patient_id = ? WHERE bed_id = ?");
            $updateBed->bind_param("ii", $requestId, $bedId);
            $updateBed->execute();
            
            // Commit transaction
            $conn->commit();
            
            $bedMessage = "Bed request submitted successfully for $patientName in $ward ward (Status: Approved)";
        } catch (Exception $e) {
            $conn->rollback();
            $bedMessage = "Error processing request: " . $e->getMessage();
        }
    } else {
        // No available beds, just create the request
        $insertRequest = $conn->prepare("INSERT INTO patient_requests (patient_name, patient_age, patient_gender, ward, contact, status) 
                                      VALUES (?, ?, ?, ?, ?, 'Pending')");
        $insertRequest->bind_param("sisss", $patientName, $patientAge, $patientGender, $ward, $contact);
        
        if ($insertRequest->execute()) {
            $bedMessage = "Bed request submitted successfully for $patientName in $ward ward (Status: Pending - no beds currently available)";
        } else {
            $bedMessage = "Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Bed Management</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #495057;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.25rem 1.5rem;
            font-weight: 600;
            border-bottom: none;
        }
        
        .table th {
            font-weight: 600;
            color: var(--dark-color);
            border-top: none;
            background-color: rgba(0,0,0,0.02);
        }
        
        .status-pending {
            color: var(--warning-color);
            font-weight: 600;
        }
        
        .status-approved {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .status-rejected {
            color: var(--danger-color);
            font-weight: 600;
        }
        
        .bed-available {
            background-color: rgba(40, 167, 69, 0.1);
        }
        
        .bed-occupied {
            background-color: rgba(220, 53, 69, 0.1);
        }
        
        .bed-maintenance {
            background-color: rgba(108, 117, 125, 0.1);
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #dee2e6;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem 1.25rem;
        }
        
        .badge {
            font-weight: 500;
            padding: 0.5em 0.75em;
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 1.75rem 0;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="text-center">
                <h1><i class="fas fa-procedures me-2"></i>Hospital Bed Management</h1>
                <p class="lead">Efficient bed allocation and tracking system</p>
            </div>
              <a href="patient_dashboard.php" class="btn-dashboard">
                <i class="fas fa-tachometer-alt"></i> Back to Main Dashboard
            </a>
        </div>
    </div>
    
    <div class="container mb-5">
        <?php if (!empty($bedMessage)): ?>
            <div class="alert alert-<?php echo strpos($bedMessage, 'Error') === false ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="fas <?php echo strpos($bedMessage, 'Error') === false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($bedMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0"><i class="fas fa-bed me-2"></i>Request In-Patient Bed</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="patient_name" class="form-label">Patient Name</label>
                                <input type="text" class="form-control" id="patient_name" name="patient_name" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="patient_age" class="form-label">Age</label>
                                    <input type="number" class="form-control" id="patient_age" name="patient_age" min="0" max="120" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="patient_gender" class="form-label">Gender</label>
                                    <select class="form-select" id="patient_gender" name="patient_gender" required>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="contact" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="contact" name="contact" required>
                            </div>
                            <div class="mb-3">
                                <label for="ward" class="form-label">Preferred Ward</label>
                                <select class="form-select" id="ward" name="ward" required>
                                    <?php
                                    $wards = $conn->query("SELECT ward, COUNT(*) as total, 
                                        SUM(status = 'Available') as available 
                                        FROM beds GROUP BY ward");
                                    if ($wards && $wards->num_rows > 0) {
                                        while($ward = $wards->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($ward['ward']) . "'>
                                                " . htmlspecialchars($ward['ward']) . " (" . htmlspecialchars($ward['available']) . "/" . htmlspecialchars($ward['total']) . " available)
                                            </option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>No wards available</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <button type="submit" name="request_bed" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-paper-plane me-2"></i>Request Bed
                            </button>
                        </form>
                    </div>
                </div>
                 <div class="card">
                    <div class="card-header bg-info text-white">
                        <h3 class="mb-0"><i class="fas fa-list-alt me-2"></i>My Bed Requests Status</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Ward</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get the logged-in patient's ID from session
                                    $patientId = $_SESSION['user']['id']; // Make sure this matches your session structure
                                    
                                    $requests = $conn->query("SELECT * FROM patient_requests 
                                        WHERE id = '$patientId' 
                                        ORDER BY request_date DESC");
                                        
                                    if ($requests && $requests->num_rows > 0) {
                                        while($request = $requests->fetch_assoc()) {
                                            $statusClass = "status-" . strtolower($request['status']);
                                            echo "<tr>
                                                    <td>" . htmlspecialchars($request['patient_name']) . " (" . htmlspecialchars($request['patient_age']) . ")</td>
                                                    <td>" . htmlspecialchars($request['ward']) . "</td>
                                                    <td>" . htmlspecialchars($request['contact']) . "</td>
                                                    <td class='" . htmlspecialchars($statusClass) . "'>
                                                        <i class='fas fa-circle me-1 small'></i>" . htmlspecialchars($request['status']) . "
                                                    </td>
                                                </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center py-4'>No bed requests found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h3 class="mb-0"><i class="fas fa-bed me-2"></i>Current Bed Status</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Ward</th>
                                        <th>Bed No.</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Patient</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $beds = $conn->query("SELECT b.*, p.patient_name 
                                        FROM beds b 
                                        LEFT JOIN patient_requests p ON b.patient_id = p.id 
                                        ORDER BY ward, bed_number");
                                    if ($beds && $beds->num_rows > 0) {
                                        while($bed = $beds->fetch_assoc()) {
                                            $statusClass = "bed-" . strtolower($bed['status']);
                                            $patientName = !empty($bed['patient_name']) ? htmlspecialchars($bed['patient_name']) : 'N/A';
                                            echo "<tr class='" . htmlspecialchars($statusClass) . "'>
                                                    <td>" . htmlspecialchars($bed['ward']) . "</td>
                                                    <td>" . htmlspecialchars($bed['bed_number']) . "</td>
                                                    <td>" . htmlspecialchars($bed['bed_type']) . "</td>
                                                    <td>
                                                        <span class='badge bg-" . 
                                                        ($bed['status'] == 'Available' ? 'success' : 
                                                        ($bed['status'] == 'Occupied' ? 'danger' : 'secondary')) . "'>
                                                            " . htmlspecialchars($bed['status']) . "
                                                        </span>
                                                    </td>
                                                    <td>" . $patientName . "</td>
                                                </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center py-4'>No bed information available</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-focus first input field
            document.getElementById('patient_name')?.focus();
            
            // Form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const inputs = form.querySelectorAll('input[required], select[required]');
                    let isValid = true;
                    
                    inputs.forEach(input => {
                        if (!input.value.trim()) {
                            input.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            input.classList.remove('is-invalid');
                        }
                    });
                    
                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill all required fields');
                    }
                });
            });
        });
    </script>
</body>
</html>