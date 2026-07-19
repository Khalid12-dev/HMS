<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Redirect if not logged in as patient
if (!isset($_SESSION['user']) || $_SESSION['user']['type'] !== 'patient') {
    header("Location: ../login.php");
    exit();
}

// Database configuration
$db_host = 'localhost';
$db_user = 'root'; // Change to your database username
$db_pass = ''; // Change to your database password
$db_name = 'hms'; // Change to your database name

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize messages
$serviceMessage = '';

// Handle Service Application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_service'])) {
    $servicePatientName = $conn->real_escape_string($_POST['service_patient_name']);
    $servicePatientAge = $conn->real_escape_string($_POST['service_patient_age']);
    $serviceId = $conn->real_escape_string($_POST['service_id']);
    $serviceDate = $conn->real_escape_string($_POST['service_date']);
    $patientId = $_SESSION['user']['id'];
    
    // Get service details
    $serviceQuery = $conn->query("SELECT name, department FROM services WHERE service_id = '$serviceId'");
    if ($serviceQuery->num_rows > 0) {
        $service = $serviceQuery->fetch_assoc();
        $serviceName = $service['name'];
        $serviceDepartment = $service['department'];
        
        // Insert service application using prepared statement
        $stmt = $conn->prepare("INSERT INTO service_requests (patient_id, patient_name, patient_age, service_id, service_name, department, service_date, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("issssss", $patientId, $servicePatientName, $servicePatientAge, $serviceId, $serviceName, $serviceDepartment, $serviceDate);
        
        if ($stmt->execute()) {
            $serviceMessage = "Service application submitted successfully for $servicePatientName (Service: $serviceName)";
        } else {
            $serviceMessage = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $serviceMessage = "Error: Selected service not found";
    }
}

// Function to get appropriate icon for each service
function getServiceIcon($serviceName) {
    $icons = [
        'consultation' => 'fas fa-user-md',
        'x-ray' => 'fas fa-x-ray',
        'blood test' => 'fas fa-tint',
        'ultrasound' => 'fas fa-procedures',
        'surgery' => 'fas fa-scalpel',
        'pharmacy' => 'fas fa-prescription-bottle-alt',
        'dental' => 'fas fa-tooth',
        'physiotherapy' => 'fas fa-spa'
    ];
    
    $serviceLower = strtolower($serviceName);
    foreach ($icons as $key => $icon) {
        if (strpos($serviceLower, $key) !== false) {
            return $icon;
        }
    }
    
    return 'fas fa-medkit'; // Default icon
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Services Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Your existing CSS styles here */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #34495e;
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* Rest of your CSS styles */
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="text-center">
                <h1><i class="fas fa-hospital me-2"></i>Hospital Services Portal</h1>
                <p class="lead">Comprehensive healthcare management system for patients</p>
            </div>
             <a href="patient_dashboard.php" class="btn-dashboard">
                <i class="fas fa-tachometer-alt"></i> Back to Main Dashboard
            </a>
        </div>
    </div>
    
    <div class="container mb-5">
        <?php if (!empty($serviceMessage)): ?>
            <div class="alert alert-<?php echo strpos($serviceMessage, 'Error') === false ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <i class="fas <?php echo strpos($serviceMessage, 'Error') === false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i><?php echo htmlspecialchars($serviceMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0"><i class="fas fa-file-medical me-2"></i>Apply for Service</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="service_patient_name" class="form-label">Patient Name</label>
                                <input type="text" class="form-control" id="service_patient_name" name="service_patient_name" required>
                            </div>
                            <div class="mb-3">
                                <label for="service_patient_age" class="form-label">Patient Age</label>
                                <input type="number" class="form-control" id="service_patient_age" name="service_patient_age" min="0" max="120" required>
                            </div>
                            <div class="mb-3">
                                <label for="service_id" class="form-label">Service</label>
                                <select class="form-select" id="service_id" name="service_id" required>
                                    <option value="">Select a service</option>
                                    <?php
                                    $services = $conn->query("SELECT * FROM services WHERE status = 'Active' ORDER BY name");
                                    if ($services && $services->num_rows > 0) {
                                        while($service = $services->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($service['service_id']) . "' 
                                                    data-department='" . htmlspecialchars($service['department']) . "'>
                                                    " . htmlspecialchars($service['name']) . " (₹" . number_format($service['cost'], 2) . ")
                                                </option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled>No services available</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="service_department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="service_department" name="service_department" readonly required>
                            </div>
                            <div class="mb-3">
                                <label for="service_date" class="form-label">Preferred Date</label>
                                <input type="date" class="form-control" id="service_date" name="service_date" min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <button type="submit" name="apply_service" class="btn btn-success w-100">
                                <i class="fas fa-paper-plane me-2"></i>Apply for Service
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h3 class="mb-0"><i class="fas fa-list-alt me-2"></i>Service Applications</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Service</th>
                                        <th>Department</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $patientId = $_SESSION['user']['id'];
                                    $applications = $conn->query("SELECT * FROM service_requests WHERE patient_id = '$patientId' ORDER BY service_date DESC");
                                    
                                    if ($applications && $applications->num_rows > 0) {
                                        while($app = $applications->fetch_assoc()) {
                                            $statusClass = "status-" . strtolower($app['status']);
                                            echo "<tr>
                                                    <td>" . htmlspecialchars($app['patient_name']) . " (" . htmlspecialchars($app['patient_age']) . ")</td>
                                                    <td>" . htmlspecialchars($app['service_name']) . "</td>
                                                    <td>" . htmlspecialchars($app['department']) . "</td>
                                                    <td>" . date('M d, Y', strtotime($app['service_date'])) . "</td>
                                                    <td class='" . htmlspecialchars($statusClass) . "'><i class='fas fa-circle me-1'></i>" . htmlspecialchars($app['status']) . "</td>
                                                </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='5' class='text-center'>No service applications found</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-medkit me-2"></i>Available Services</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            $services = $conn->query("SELECT * FROM services WHERE status = 'Active' ORDER BY department, name");
                            
                            if ($services && $services->num_rows > 0) {
                                while($service = $services->fetch_assoc()) {
                                    $icon = getServiceIcon($service['name']);
                                    echo '<div class="col-md-6 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-center mb-3">
                                                        <div class="service-icon"><i class="'.$icon.'"></i></div>
                                                        <h5 class="mb-0">' . htmlspecialchars($service['name']) . '</h5>
                                                    </div>
                                                    <p class="mb-2"><strong>Department:</strong> ' . htmlspecialchars($service['department']) . '</p>
                                                    <p class="mb-2"><strong>Cost:</strong> ₹' . number_format($service['cost'], 2) . '</p>
                                                    <p class="text-muted small">' . htmlspecialchars($service['description']) . '</p>
                                                </div>
                                            </div>
                                        </div>';
                                }
                            } else {
                                echo '<div class="col-12"><div class="alert alert-warning">No active services available at this time.</div></div>';
                            }
                            ?>
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
            // Service department auto-fill
            const serviceSelect = document.getElementById('service_id');
            const departmentInput = document.getElementById('service_department');
            
            if (serviceSelect && departmentInput) {
                // Set initial value if an option is already selected
                if (serviceSelect.selectedIndex > 0) {
                    const initialDept = serviceSelect.options[serviceSelect.selectedIndex].getAttribute('data-department');
                    departmentInput.value = initialDept;
                }
                
                // Update on change
                serviceSelect.addEventListener('change', function() {
                    if (this.selectedIndex > 0) {
                        const dept = this.options[this.selectedIndex].getAttribute('data-department');
                        departmentInput.value = dept;
                    } else {
                        departmentInput.value = '';
                    }
                });
            }
            
            // Set minimum date for service date input
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('service_date').min = today;
        });
    </script>
</body>
</html>